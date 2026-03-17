"""
Service layer for the RAG knowledge base.

Handles CRUD operations, article indexing (chunk + embed), and statistics.
"""

from __future__ import annotations

import logging
import uuid
from typing import Optional

from sqlalchemy import select, func, and_, delete
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.rag.models import KnowledgeArticle, ArticleChunk
from app.rag.enums import ArticleStatus, ArticleCategory, ChunkStatus
from app.rag.schemas import (
    ArticleCreate,
    ArticleUpdate,
    ArticleResponse,
    ArticleSummaryResponse,
    ArticleListResponse,
    IndexArticleResponse,
    KnowledgeBaseStats,
)
from app.rag.chunker import chunk_text
from app.rag.embeddings import embed_texts

logger = logging.getLogger(__name__)


class KnowledgeBaseService:
    """Knowledge base CRUD + indexing operations."""

    def __init__(self, db: AsyncSession):
        self.db = db

    # ═══════════════════════════════════════════════════════
    #  Article CRUD
    # ═══════════════════════════════════════════════════════

    async def create_article(
        self,
        payload: ArticleCreate,
        user_id: uuid.UUID | None = None,
    ) -> KnowledgeArticle:
        """Create a new knowledge article."""
        article = KnowledgeArticle(
            title=payload.title,
            content=payload.content,
            summary=payload.summary,
            category=payload.category,
            tags=payload.tags,
            source=payload.source,
            language=payload.language,
            metadata_extra=payload.metadata_extra,
            created_by=user_id,
            updated_by=user_id,
            status=ArticleStatus.DRAFT,
        )
        self.db.add(article)
        await self.db.flush()
        await self.db.refresh(article)

        logger.info("Created article %s: %r", article.id, article.title)
        return article

    async def get_article(self, article_id: uuid.UUID) -> Optional[KnowledgeArticle]:
        """Get a single article with its chunks."""
        stmt = (
            select(KnowledgeArticle)
            .options(selectinload(KnowledgeArticle.chunks))
            .where(
                and_(
                    KnowledgeArticle.id == article_id,
                    KnowledgeArticle.is_deleted == False,
                )
            )
        )
        result = await self.db.execute(stmt)
        return result.scalar_one_or_none()

    async def list_articles(
        self,
        skip: int = 0,
        limit: int = 20,
        category: Optional[ArticleCategory] = None,
        status: Optional[ArticleStatus] = None,
        language: Optional[str] = None,
        search: Optional[str] = None,
    ) -> ArticleListResponse:
        """List articles with optional filters."""
        base_filter = KnowledgeArticle.is_deleted == False

        # Count query
        count_stmt = select(func.count(KnowledgeArticle.id)).where(base_filter)
        if category:
            count_stmt = count_stmt.where(KnowledgeArticle.category == category)
        if status:
            count_stmt = count_stmt.where(KnowledgeArticle.status == status)
        if language:
            count_stmt = count_stmt.where(KnowledgeArticle.language == language)
        if search:
            count_stmt = count_stmt.where(
                KnowledgeArticle.title.ilike(f"%{search}%")
            )

        total = (await self.db.execute(count_stmt)).scalar() or 0

        # Data query
        data_stmt = (
            select(KnowledgeArticle)
            .where(base_filter)
            .order_by(KnowledgeArticle.updated_at.desc())
            .offset(skip)
            .limit(limit)
        )
        if category:
            data_stmt = data_stmt.where(KnowledgeArticle.category == category)
        if status:
            data_stmt = data_stmt.where(KnowledgeArticle.status == status)
        if language:
            data_stmt = data_stmt.where(KnowledgeArticle.language == language)
        if search:
            data_stmt = data_stmt.where(
                KnowledgeArticle.title.ilike(f"%{search}%")
            )

        result = await self.db.execute(data_stmt)
        articles = result.scalars().all()

        return ArticleListResponse(
            items=[ArticleSummaryResponse.model_validate(a) for a in articles],
            total=total,
            skip=skip,
            limit=limit,
        )

    async def update_article(
        self,
        article_id: uuid.UUID,
        payload: ArticleUpdate,
        user_id: uuid.UUID | None = None,
    ) -> Optional[KnowledgeArticle]:
        """Update an existing article."""
        article = await self.get_article(article_id)
        if not article:
            return None

        content_changed = False
        update_data = payload.model_dump(exclude_unset=True, exclude={"re_index"})

        for field, value in update_data.items():
            if field == "content" and value != article.content:
                content_changed = True
            setattr(article, field, value)

        article.updated_by = user_id
        await self.db.flush()
        await self.db.refresh(article)

        logger.info("Updated article %s", article_id)
        return article

    async def delete_article(self, article_id: uuid.UUID) -> bool:
        """Soft-delete an article."""
        article = await self.get_article(article_id)
        if not article:
            return False

        from datetime import datetime, timezone
        article.is_deleted = True
        article.deleted_at = datetime.now(timezone.utc)
        await self.db.flush()

        logger.info("Soft-deleted article %s", article_id)
        return True

    async def publish_article(self, article_id: uuid.UUID) -> Optional[KnowledgeArticle]:
        """Set article status to PUBLISHED."""
        article = await self.get_article(article_id)
        if not article:
            return None
        article.status = ArticleStatus.PUBLISHED
        await self.db.flush()
        await self.db.refresh(article)
        logger.info("Published article %s", article_id)
        return article

    async def archive_article(self, article_id: uuid.UUID) -> Optional[KnowledgeArticle]:
        """Set article status to ARCHIVED."""
        article = await self.get_article(article_id)
        if not article:
            return None
        article.status = ArticleStatus.ARCHIVED
        await self.db.flush()
        await self.db.refresh(article)
        logger.info("Archived article %s", article_id)
        return article

    # ═══════════════════════════════════════════════════════
    #  Indexing (chunk + embed)
    # ═══════════════════════════════════════════════════════

    async def index_article(
        self,
        article_id: uuid.UUID,
        chunk_size: int = 512,
        chunk_overlap: int = 64,
    ) -> IndexArticleResponse:
        """
        Index an article: split into chunks and generate embeddings.

        Steps:
        1. Delete existing chunks for this article
        2. Split content into overlapping chunks
        3. Generate embeddings for all chunks in batch
        4. Store chunks with embeddings in DB
        5. Update article indexing metadata
        """
        article = await self.get_article(article_id)
        if not article:
            raise ValueError(f"Article {article_id} not found")

        # Step 1: Delete existing chunks
        await self.db.execute(
            delete(ArticleChunk).where(ArticleChunk.article_id == article_id)
        )
        await self.db.flush()

        # Step 2: Chunk the content
        chunks = chunk_text(
            article.content,
            chunk_size=chunk_size,
            chunk_overlap=chunk_overlap,
        )

        if not chunks:
            article.is_indexed = False
            article.chunk_count = 0
            article.total_tokens = 0
            await self.db.flush()
            return IndexArticleResponse(
                article_id=article_id,
                chunks_created=0,
                total_tokens=0,
                status="empty",
            )

        # Step 3: Generate embeddings in batch
        chunk_texts = [c.content for c in chunks]
        embeddings = embed_texts(chunk_texts)

        # Step 4: Create ArticleChunk records
        total_tokens = 0
        for chunk, embedding in zip(chunks, embeddings):
            db_chunk = ArticleChunk(
                article_id=article_id,
                chunk_index=chunk.index,
                content=chunk.content,
                token_count=chunk.token_count,
                embedding=embedding,
                status=ChunkStatus.INDEXED,
            )
            self.db.add(db_chunk)
            total_tokens += chunk.token_count

        # Step 5: Update article metadata
        article.is_indexed = True
        article.chunk_count = len(chunks)
        article.total_tokens = total_tokens

        await self.db.flush()

        logger.info(
            "Indexed article %s: %d chunks, %d tokens",
            article_id, len(chunks), total_tokens,
        )

        return IndexArticleResponse(
            article_id=article_id,
            chunks_created=len(chunks),
            total_tokens=total_tokens,
            status="indexed",
        )

    async def get_all_published_article_ids(self) -> list[uuid.UUID]:
        """Get IDs of all published, non-deleted articles."""
        stmt = (
            select(KnowledgeArticle.id)
            .where(
                and_(
                    KnowledgeArticle.status == ArticleStatus.PUBLISHED,
                    KnowledgeArticle.is_deleted == False,
                )
            )
        )
        result = await self.db.execute(stmt)
        return [row[0] for row in result.all()]

    # ═══════════════════════════════════════════════════════
    #  Statistics
    # ═══════════════════════════════════════════════════════

    async def get_stats(self) -> KnowledgeBaseStats:
        """Get knowledge base statistics."""
        base_filter = KnowledgeArticle.is_deleted == False

        # Article counts by status
        total_q = select(func.count(KnowledgeArticle.id)).where(base_filter)
        total = (await self.db.execute(total_q)).scalar() or 0

        published_q = total_q.where(KnowledgeArticle.status == ArticleStatus.PUBLISHED)
        published = (await self.db.execute(published_q)).scalar() or 0

        draft_q = select(func.count(KnowledgeArticle.id)).where(
            and_(base_filter, KnowledgeArticle.status == ArticleStatus.DRAFT)
        )
        draft = (await self.db.execute(draft_q)).scalar() or 0

        archived_q = select(func.count(KnowledgeArticle.id)).where(
            and_(base_filter, KnowledgeArticle.status == ArticleStatus.ARCHIVED)
        )
        archived = (await self.db.execute(archived_q)).scalar() or 0

        # Chunk counts
        total_chunks_q = select(func.count(ArticleChunk.id))
        total_chunks = (await self.db.execute(total_chunks_q)).scalar() or 0

        indexed_chunks_q = total_chunks_q.where(
            ArticleChunk.status == ChunkStatus.INDEXED
        )
        indexed_chunks = (await self.db.execute(indexed_chunks_q)).scalar() or 0

        # Total tokens
        tokens_q = select(func.coalesce(func.sum(KnowledgeArticle.total_tokens), 0)).where(base_filter)
        total_tokens = (await self.db.execute(tokens_q)).scalar() or 0

        # Category breakdown
        cat_q = (
            select(KnowledgeArticle.category, func.count(KnowledgeArticle.id))
            .where(base_filter)
            .group_by(KnowledgeArticle.category)
        )
        cat_result = await self.db.execute(cat_q)
        categories = {str(row[0].value): row[1] for row in cat_result.all()}

        # Language breakdown
        lang_q = (
            select(KnowledgeArticle.language, func.count(KnowledgeArticle.id))
            .where(base_filter)
            .group_by(KnowledgeArticle.language)
        )
        lang_result = await self.db.execute(lang_q)
        languages = {row[0]: row[1] for row in lang_result.all()}

        avg_chunks = total_chunks / max(total, 1)

        return KnowledgeBaseStats(
            total_articles=total,
            published_articles=published,
            draft_articles=draft,
            archived_articles=archived,
            total_chunks=total_chunks,
            indexed_chunks=indexed_chunks,
            total_tokens=total_tokens,
            categories=categories,
            languages=languages,
            avg_chunks_per_article=round(avg_chunks, 2),
        )
