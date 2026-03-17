"""
SQLAlchemy models for the RAG knowledge base.

KnowledgeArticle — full article with metadata
ArticleChunk     — chunked text with pgvector embeddings for similarity search
"""

import uuid

from sqlalchemy import String, Text, Float, Integer, ForeignKey, Index, JSON, Boolean
from sqlalchemy.dialects.postgresql import UUID, ENUM, ARRAY
from sqlalchemy.orm import Mapped, mapped_column, relationship
from pgvector.sqlalchemy import Vector

from app.db.base import Base, TimestampMixin, UUIDPrimaryKeyMixin, SoftDeleteMixin
from app.rag.enums import ArticleStatus, ArticleCategory, ChunkStatus

# ── PostgreSQL-native ENUMs (migration creates them) ──────
_article_status = ENUM(ArticleStatus, name="article_status", create_type=False)
_article_category = ENUM(ArticleCategory, name="article_category", create_type=False)
_chunk_status = ENUM(ChunkStatus, name="chunk_status", create_type=False)

# Embedding dimension for all-MiniLM-L6-v2
EMBEDDING_DIM = 384


class KnowledgeArticle(Base, UUIDPrimaryKeyMixin, TimestampMixin, SoftDeleteMixin):
    """A knowledge base article (FAQ, guide, policy, etc.)."""

    __tablename__ = "knowledge_articles"

    title: Mapped[str] = mapped_column(String(500), nullable=False, index=True)
    content: Mapped[str] = mapped_column(Text, nullable=False)
    summary: Mapped[str | None] = mapped_column(Text, nullable=True)

    category: Mapped[ArticleCategory] = mapped_column(
        _article_category, nullable=False, index=True,
    )
    status: Mapped[ArticleStatus] = mapped_column(
        _article_status, nullable=False, default=ArticleStatus.DRAFT, index=True,
    )

    tags: Mapped[list | None] = mapped_column(JSON, nullable=True, default=list)
    source: Mapped[str | None] = mapped_column(
        String(255), nullable=True, comment="Origin: manual, import, crawl, etc.",
    )
    language: Mapped[str] = mapped_column(
        String(10), nullable=False, default="en",
    )
    metadata_extra: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, default=dict,
        comment="Arbitrary key-value metadata",
    )

    # ── Author ────────────────────────────────────────────
    created_by: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=True,
    )
    updated_by: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=True,
    )

    # ── Indexing state ────────────────────────────────────
    is_indexed: Mapped[bool] = mapped_column(Boolean, default=False, nullable=False)
    chunk_count: Mapped[int] = mapped_column(Integer, default=0, nullable=False)
    total_tokens: Mapped[int] = mapped_column(Integer, default=0, nullable=False)

    # ── Relationships ─────────────────────────────────────
    chunks: Mapped[list["ArticleChunk"]] = relationship(
        "ArticleChunk",
        back_populates="article",
        cascade="all, delete-orphan",
        order_by="ArticleChunk.chunk_index",
    )
    author = relationship("User", foreign_keys=[created_by])

    __table_args__ = (
        Index("ix_articles_category_status", "category", "status"),
        Index("ix_articles_language", "language"),
    )

    def __repr__(self) -> str:
        return f"<KnowledgeArticle {self.id} title={self.title!r}>"


class ArticleChunk(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    """An indexed text chunk with its embedding vector."""

    __tablename__ = "article_chunks"

    article_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("knowledge_articles.id", ondelete="CASCADE"),
        nullable=False, index=True,
    )
    chunk_index: Mapped[int] = mapped_column(Integer, nullable=False)
    content: Mapped[str] = mapped_column(Text, nullable=False)
    token_count: Mapped[int] = mapped_column(Integer, nullable=False, default=0)

    # ── pgvector embedding ────────────────────────────────
    embedding: Mapped[list | None] = mapped_column(
        Vector(EMBEDDING_DIM), nullable=True,
    )

    status: Mapped[ChunkStatus] = mapped_column(
        _chunk_status, nullable=False, default=ChunkStatus.PENDING,
    )

    # ── Relationship ──────────────────────────────────────
    article: Mapped["KnowledgeArticle"] = relationship(
        "KnowledgeArticle", back_populates="chunks",
    )

    __table_args__ = (
        Index("ix_chunks_article_index", "article_id", "chunk_index", unique=True),
    )

    def __repr__(self) -> str:
        return f"<ArticleChunk {self.id} article={self.article_id} idx={self.chunk_index}>"
