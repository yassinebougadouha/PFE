"""
PDF ingestion service for the RAG knowledge base.

Bridges PDF extraction (pdf_loader) with the knowledge base service:
  1. Extract text from PDF
  2. Create a KnowledgeArticle from the extracted content
  3. Optionally index (chunk + embed) and publish
"""

from __future__ import annotations

import datetime
import logging
import uuid
from pathlib import Path
from typing import Optional

from sqlalchemy import select, and_
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.rag.models import KnowledgeArticle
from app.rag.enums import ArticleStatus
from app.rag.schemas import ArticleCreate
from app.rag.service import KnowledgeBaseService
from app.rag.pdf_loader import extract_pdf, list_pdf_files, PDFDocument
from app.rag.pdf_schemas import (
    IngestPDFRequest,
    IngestPDFResponse,
    BulkIngestRequest,
    BulkIngestResponse,
    PDFFileInfo,
    PDFListResponse,
)

logger = logging.getLogger(__name__)
settings = get_settings()


def _get_documents_dir() -> Path:
    """Resolve the documents directory path."""
    return Path(settings.RAG_DOCUMENTS_DIR)


def _human_size(size_bytes: int) -> str:
    """Convert bytes to a human-readable string."""
    for unit in ("B", "KB", "MB", "GB"):
        if size_bytes < 1024:
            return f"{size_bytes:.1f} {unit}"
        size_bytes /= 1024
    return f"{size_bytes:.1f} TB"


class PDFIngestionService:
    """Handles ingesting PDF documents into the knowledge base."""

    def __init__(self, db: AsyncSession):
        self.db = db
        self.kb_service = KnowledgeBaseService(db)
        self.documents_dir = _get_documents_dir()

    # ── List available PDFs ───────────────────────────────

    async def list_pdfs(self) -> PDFListResponse:
        """List all PDF files in the documents directory with ingestion status."""
        pdf_paths = list_pdf_files(self.documents_dir)
        
        # Get all articles that have a PDF source to avoid N+1 queries
        stmt = select(KnowledgeArticle).where(
            and_(
                KnowledgeArticle.source.like("pdf:%"),
                KnowledgeArticle.is_deleted == False
            )
        )
        result = await self.db.execute(stmt)
        articles = result.scalars().all()
        
        # Map source to article info
        source_map = {a.source: a for a in articles}
        
        files = []
        for p in pdf_paths:
            stat = p.stat()
            source_tag = f"pdf:{p.name}"
            article = source_map.get(source_tag)
            
            files.append(PDFFileInfo(
                filename=p.name,
                size_bytes=stat.st_size,
                size_human=_human_size(stat.st_size),
                modified_at=datetime.datetime.fromtimestamp(stat.st_mtime),
                is_ingested=article is not None,
                article_id=article.id if article else None,
                chunks_count=article.chunk_count if article else 0,
            ))
            
        return PDFListResponse(
            directory=str(self.documents_dir),
            files=files,
            total_files=len(files),
        )

    # ── Check if PDF already ingested ─────────────────────

    async def _is_already_ingested(self, filename: str) -> bool:
        """Check if an article with this PDF source already exists."""
        source_tag = f"pdf:{filename}"
        stmt = (
            select(KnowledgeArticle.id)
            .where(
                and_(
                    KnowledgeArticle.source == source_tag,
                    KnowledgeArticle.is_deleted == False,
                )
            )
            .limit(1)
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none() is not None

    # ── Ingest a single PDF ──────────────────────────────

    async def ingest_pdf(
        self,
        request: IngestPDFRequest,
        user_id: uuid.UUID | None = None,
    ) -> IngestPDFResponse:
        """
        Ingest a single PDF file into the knowledge base.

        Steps:
        1. Extract text from PDF
        2. Create a KnowledgeArticle with source='pdf:<filename>'
        3. Optionally index (chunk + embed)
        4. Optionally publish
        """
        pdf_path = self.documents_dir / request.filename
        if not pdf_path.exists():
            raise FileNotFoundError(
                f"PDF not found in documents folder: {request.filename}"
            )

        # Extract text
        pdf_doc = extract_pdf(pdf_path)

        if not pdf_doc.text_only.strip():
            raise ValueError(
                f"PDF '{request.filename}' contains no extractable text"
            )

        # Build article title from PDF metadata or filename
        title = pdf_doc.title or pdf_path.stem.replace("_", " ").replace("-", " ").title()

        # Create article
        article_payload = ArticleCreate(
            title=title,
            content=pdf_doc.text_only,
            summary=(
                f"Extracted from PDF: {pdf_doc.filename} "
                f"({pdf_doc.page_count} pages, {pdf_doc.total_words} words)"
            ),
            category=request.category,
            tags=list(set(["pdf", pdf_doc.filename] + request.tags)),
            source=f"pdf:{pdf_doc.filename}",
            language=request.language,
            metadata_extra={
                "pdf_filename": pdf_doc.filename,
                "pdf_pages": pdf_doc.page_count,
                "pdf_words": pdf_doc.total_words,
                "pdf_chars": pdf_doc.total_chars,
                "pdf_author": pdf_doc.author,
                "pdf_subject": pdf_doc.subject,
            },
            auto_index=False,  # We handle indexing ourselves
        )

        article = await self.kb_service.create_article(article_payload, user_id=user_id)

        # Index if requested
        chunks_created = 0
        total_tokens = 0
        if request.auto_index:
            try:
                idx_result = await self.kb_service.index_article(article.id)
                chunks_created = idx_result.chunks_created
                total_tokens = idx_result.total_tokens
            except Exception as e:
                logger.error("Failed to index PDF article %s: %s", article.id, e)

        # Publish if requested
        is_published = False
        if request.auto_publish:
            await self.kb_service.publish_article(article.id)
            is_published = True

        # Refresh article to get latest state
        article = await self.kb_service.get_article(article.id)

        logger.info(
            "Ingested PDF '%s' → article %s (%d chunks)",
            pdf_doc.filename, article.id, chunks_created,
        )

        return IngestPDFResponse(
            filename=pdf_doc.filename,
            article_id=article.id,
            title=article.title,
            page_count=pdf_doc.page_count,
            total_words=pdf_doc.total_words,
            chunks_created=chunks_created,
            total_tokens=total_tokens,
            status="published" if is_published else "indexed" if chunks_created > 0 else "created",
            is_published=is_published,
        )

    # ── Bulk ingest all PDFs ─────────────────────────────

    async def bulk_ingest(
        self,
        request: BulkIngestRequest,
        user_id: uuid.UUID | None = None,
    ) -> BulkIngestResponse:
        """
        Ingest all PDFs from the documents directory.

        Optionally skips PDFs that already have matching articles.
        """
        pdf_paths = list_pdf_files(self.documents_dir)
        results: list[IngestPDFResponse] = []
        errors: list[dict] = []
        skipped = 0

        for pdf_path in pdf_paths:
            filename = pdf_path.name

            # Check if already ingested
            if request.skip_existing and await self._is_already_ingested(filename):
                logger.info("Skipping already-ingested PDF: %s", filename)
                skipped += 1
                continue

            try:
                ingest_req = IngestPDFRequest(
                    filename=filename,
                    category=request.category,
                    language=request.language,
                    tags=request.tags,
                    auto_publish=request.auto_publish,
                    auto_index=request.auto_index,
                )
                result = await self.ingest_pdf(ingest_req, user_id=user_id)
                results.append(result)
            except Exception as e:
                logger.error("Failed to ingest PDF '%s': %s", filename, e)
                errors.append({"filename": filename, "error": str(e)})

        return BulkIngestResponse(
            total_files=len(pdf_paths),
            ingested=len(results),
            skipped=skipped,
            failed=len(errors),
            results=results,
            errors=errors,
        )