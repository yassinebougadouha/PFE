"""
API routes for the RAG knowledge base module.

Prefix: /rag
Tag: Knowledge Base (RAG)
"""

from __future__ import annotations

import uuid
import shutil
from pathlib import Path
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, UploadFile, File, status
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.api.deps import require_agent_or_admin, require_admin

from app.rag.enums import ArticleStatus, ArticleCategory
from app.rag.schemas import (
    ArticleCreate,
    ArticleUpdate,
    ArticleResponse,
    ArticleListResponse,
    IndexArticleResponse,
    ReindexAllResponse,
    SearchRequest,
    SearchResponse,
    HybridSearchRequest,
    KnowledgeBaseStats,
)
from app.rag.pdf_schemas import (
    PDFListResponse,
    PDFUploadResponse,
    IngestPDFRequest,
    IngestPDFResponse,
    BulkIngestRequest,
    BulkIngestResponse,
)
from app.rag.service import KnowledgeBaseService
from app.rag.pdf_service import PDFIngestionService
from app.rag.retriever import VectorRetriever
from app.rag.response_providers.service import ResponseGenerationService
from app.core.config import get_settings

router = APIRouter(prefix="/rag", tags=["Knowledge Base (RAG)"])

# ── Type aliases ──────────────────────────────────────────
DB = Annotated[AsyncSession, Depends(get_db)]
AgentOrAdmin = Annotated[User, Depends(require_agent_or_admin)]
Admin = Annotated[User, Depends(require_admin)]


@router.get(
    "/status",
    summary="Compatibility RAG provider status",
)
async def rag_status_compat(
    user: AgentOrAdmin,
):
    """
    Compatibility endpoint for older frontend clients that call /rag/status.
    New clients should use /rag/generate/providers.
    """
    statuses = await ResponseGenerationService.get_providers_status()
    default_provider = statuses.default_provider.value
    default_entry = next(
        (p for p in statuses.providers if p.provider == statuses.default_provider),
        None,
    )
    return {
        "provider": default_provider,
        "status": "active" if (default_entry and default_entry.is_configured) else "not-configured",
        "model": default_entry.default_model if default_entry else None,
    }


# ═══════════════════════════════════════════════════════════
#  Article CRUD
# ═══════════════════════════════════════════════════════════

@router.post(
    "/articles",
    response_model=ArticleResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Create knowledge article",
)
async def create_article(
    payload: ArticleCreate,
    db: DB,
    user: AgentOrAdmin,
):
    """Create a new knowledge base article. Optionally auto-indexes it."""
    svc = KnowledgeBaseService(db)
    article = await svc.create_article(payload, user_id=user.id)

    if payload.auto_index:
        try:
            await svc.index_article(article.id)
            # Refresh to get updated indexing metadata
            article = await svc.get_article(article.id)
        except Exception:
            pass  # Indexing failure shouldn't block article creation

    return article


@router.get(
    "/articles",
    response_model=ArticleListResponse,
    summary="List knowledge articles",
)
async def list_articles(
    db: DB,
    user: AgentOrAdmin,
    skip: int = Query(0, ge=0),
    limit: int = Query(20, ge=1, le=1000),
    category: Optional[ArticleCategory] = None,
    article_status: Optional[ArticleStatus] = Query(None, alias="status"),
    language: Optional[str] = None,
    search: Optional[str] = Query(None, description="Search in title"),
):
    """List knowledge articles with optional filters and pagination."""
    svc = KnowledgeBaseService(db)
    return await svc.list_articles(
        skip=skip,
        limit=limit,
        category=category,
        status=article_status,
        language=language,
        search=search,
    )


@router.get(
    "/articles/{article_id}",
    response_model=ArticleResponse,
    summary="Get knowledge article",
)
async def get_article(
    article_id: uuid.UUID,
    db: DB,
    user: AgentOrAdmin,
):
    """Get a single knowledge article with its chunks."""
    svc = KnowledgeBaseService(db)
    article = await svc.get_article(article_id)
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")
    return article


@router.patch(
    "/articles/{article_id}",
    response_model=ArticleResponse,
    summary="Update knowledge article",
)
async def update_article(
    article_id: uuid.UUID,
    payload: ArticleUpdate,
    db: DB,
    user: Admin,
):
    """Update an existing knowledge article. Re-indexes if content changed."""
    svc = KnowledgeBaseService(db)
    article = await svc.update_article(article_id, payload, user_id=user.id)
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")

    # Re-index if content was updated and re_index is requested
    if payload.re_index and payload.content is not None:
        try:
            await svc.index_article(article_id)
            article = await svc.get_article(article_id)
        except Exception:
            pass

    return article


@router.delete(
    "/articles/{article_id}",
    status_code=status.HTTP_204_NO_CONTENT,
    summary="Delete knowledge article",
)
async def delete_article(
    article_id: uuid.UUID,
    db: DB,
    user: Admin,
):
    """Soft-delete a knowledge article."""
    svc = KnowledgeBaseService(db)
    deleted = await svc.delete_article(article_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="Article not found")


# ═══════════════════════════════════════════════════════════
#  Article Lifecycle
# ═══════════════════════════════════════════════════════════

@router.post(
    "/articles/{article_id}/publish",
    response_model=ArticleResponse,
    summary="Publish article",
)
async def publish_article(
    article_id: uuid.UUID,
    db: DB,
    user: Admin,
):
    """Set article status to PUBLISHED, making it searchable."""
    svc = KnowledgeBaseService(db)
    article = await svc.publish_article(article_id)
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")
    return article


@router.post(
    "/articles/{article_id}/archive",
    response_model=ArticleResponse,
    summary="Archive article",
)
async def archive_article(
    article_id: uuid.UUID,
    db: DB,
    user: Admin,
):
    """Set article status to ARCHIVED, removing it from search results."""
    svc = KnowledgeBaseService(db)
    article = await svc.archive_article(article_id)
    if not article:
        raise HTTPException(status_code=404, detail="Article not found")
    return article


# ═══════════════════════════════════════════════════════════
#  Indexing
# ═══════════════════════════════════════════════════════════

@router.post(
    "/articles/{article_id}/index",
    response_model=IndexArticleResponse,
    summary="Index article (chunk + embed)",
)
async def index_article(
    article_id: uuid.UUID,
    db: DB,
    user: AgentOrAdmin,
    chunk_size: int = Query(512, ge=64, le=2048),
    chunk_overlap: int = Query(64, ge=0, le=256),
):
    """
    Split article into chunks and generate embeddings.
    Replaces any existing chunks for this article.
    """
    svc = KnowledgeBaseService(db)
    try:
        result = await svc.index_article(
            article_id,
            chunk_size=chunk_size,
            chunk_overlap=chunk_overlap,
        )
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    return result


@router.post(
    "/reindex-all",
    response_model=ReindexAllResponse,
    summary="Reindex all published articles",
)
async def reindex_all(
    db: DB,
    user: Admin,
    use_celery: bool = Query(
        True, description="Run asynchronously via Celery",
    ),
):
    """Reindex all published articles. Can run sync or async via Celery."""
    svc = KnowledgeBaseService(db)
    article_ids = await svc.get_all_published_article_ids()

    if use_celery:
        from app.rag.tasks import reindex_all_articles_task
        task = reindex_all_articles_task.delay([str(aid) for aid in article_ids])
        return ReindexAllResponse(
            total_articles=len(article_ids),
            task_id=task.id,
            status="queued",
        )

    # Sync reindex
    for aid in article_ids:
        await svc.index_article(aid)

    return ReindexAllResponse(
        total_articles=len(article_ids),
        task_id=None,
        status="completed",
    )


# ═══════════════════════════════════════════════════════════
#  Search
# ═══════════════════════════════════════════════════════════

@router.post(
    "/search",
    response_model=SearchResponse,
    summary="Semantic search",
)
async def semantic_search(
    payload: SearchRequest,
    db: DB,
    user: AgentOrAdmin,
):
    """Search the knowledge base using semantic similarity (vector search)."""
    retriever = VectorRetriever(db)
    return await retriever.semantic_search(payload)


@router.post(
    "/search/hybrid",
    response_model=SearchResponse,
    summary="Hybrid search (keyword + semantic)",
)
async def hybrid_search(
    payload: HybridSearchRequest,
    db: DB,
    user: AgentOrAdmin,
):
    """
    Search using a combination of keyword matching and semantic similarity.
    Adjustable weighting between the two methods.
    """
    retriever = VectorRetriever(db)
    return await retriever.hybrid_search(payload)


# ═══════════════════════════════════════════════════════════
#  Statistics
# ═══════════════════════════════════════════════════════════

@router.get(
    "/stats",
    response_model=KnowledgeBaseStats,
    summary="Knowledge base statistics",
)
async def get_stats(
    db: DB,
    user: AgentOrAdmin,
):
    """Get knowledge base dashboard statistics."""
    svc = KnowledgeBaseService(db)
    return await svc.get_stats()


# ═══════════════════════════════════════════════════════════
#  PDF Documents
# ═══════════════════════════════════════════════════════════

@router.get(
    "/documents",
    response_model=PDFListResponse,
    summary="List PDF documents",
)
async def list_documents(
    db: DB,
    user: AgentOrAdmin,
):
    """List all PDF files available in the documents folder."""
    svc = PDFIngestionService(db)
    return await svc.list_pdfs()


@router.post(
    "/documents/upload",
    response_model=PDFUploadResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Upload a PDF document",
)
async def upload_document(
    db: DB,
    user: Admin,
    file: UploadFile = File(..., description="PDF file to upload"),
):
    """
    Upload a PDF file to the documents folder.
    The file can then be ingested into the knowledge base.
    """
    if not file.filename or not file.filename.lower().endswith(".pdf"):
        raise HTTPException(
            status_code=400,
            detail="Only PDF files are accepted",
        )

    settings = get_settings()
    docs_dir = Path(settings.RAG_DOCUMENTS_DIR)
    docs_dir.mkdir(parents=True, exist_ok=True)

    dest = docs_dir / file.filename
    try:
        with open(dest, "wb") as f:
            shutil.copyfileobj(file.file, f)
    finally:
        await file.close()

    size = dest.stat().st_size
    return PDFUploadResponse(
        filename=file.filename,
        size_bytes=size,
        message=f"Uploaded successfully ({size:,} bytes)",
    )


@router.post(
    "/documents/ingest",
    response_model=IngestPDFResponse,
    summary="Ingest a single PDF into the knowledge base",
)
async def ingest_document(
    payload: IngestPDFRequest,
    db: DB,
    user: Admin,
):
    """
    Extract text from a PDF in the documents folder and create
    a knowledge article. Optionally indexes and publishes it.
    """
    svc = PDFIngestionService(db)
    try:
        return await svc.ingest_pdf(payload, user_id=user.id)
    except FileNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except ValueError as e:
        raise HTTPException(status_code=422, detail=str(e))


@router.post(
    "/documents/ingest-all",
    response_model=BulkIngestResponse,
    summary="Ingest all PDFs from documents folder",
)
async def ingest_all_documents(
    payload: BulkIngestRequest,
    db: DB,
    user: Admin,
):
    """
    Scan the documents folder and ingest all PDF files.
    Optionally skips files that have already been ingested.
    """
    svc = PDFIngestionService(db)
    return await svc.bulk_ingest(payload, user_id=user.id)
