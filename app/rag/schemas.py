"""
Pydantic schemas for the RAG knowledge base module.
"""

from __future__ import annotations

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field

from app.rag.enums import ArticleStatus, ArticleCategory


# ═══════════════════════════════════════════════════════════
#  Article — Create / Update
# ═══════════════════════════════════════════════════════════

class ArticleCreate(BaseModel):
    """Payload for creating a new knowledge article."""
    title: str = Field(..., min_length=3, max_length=500)
    content: str = Field(..., min_length=10)
    summary: Optional[str] = None
    category: ArticleCategory
    tags: list[str] = Field(default_factory=list)
    source: Optional[str] = Field(None, max_length=255)
    language: str = Field("en", max_length=10)
    metadata_extra: Optional[dict] = None
    auto_index: bool = Field(
        True,
        description="Automatically chunk and index the article after creation",
    )


class ArticleUpdate(BaseModel):
    """Payload for updating an existing article."""
    title: Optional[str] = Field(None, min_length=3, max_length=500)
    content: Optional[str] = Field(None, min_length=10)
    summary: Optional[str] = None
    category: Optional[ArticleCategory] = None
    tags: Optional[list[str]] = None
    source: Optional[str] = None
    language: Optional[str] = None
    metadata_extra: Optional[dict] = None
    re_index: bool = Field(
        True,
        description="Re-chunk and re-index if content changed",
    )


# ═══════════════════════════════════════════════════════════
#  Article — Response
# ═══════════════════════════════════════════════════════════

class ArticleChunkResponse(BaseModel):
    """A single chunk returned in article detail."""
    id: uuid.UUID
    chunk_index: int
    content: str
    token_count: int
    status: str
    created_at: datetime

    model_config = {"from_attributes": True}


class ArticleResponse(BaseModel):
    """Full article detail."""
    id: uuid.UUID
    title: str
    content: str
    summary: Optional[str]
    category: ArticleCategory
    status: ArticleStatus
    tags: list[str]
    source: Optional[str]
    language: str
    metadata_extra: Optional[dict]
    is_indexed: bool
    chunk_count: int
    total_tokens: int
    chunks: list[ArticleChunkResponse] = Field(default_factory=list)
    created_by: Optional[uuid.UUID]
    updated_by: Optional[uuid.UUID]
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class ArticleSummaryResponse(BaseModel):
    """Lightweight article listing item."""
    id: uuid.UUID
    title: str
    category: ArticleCategory
    status: ArticleStatus
    language: str
    is_indexed: bool
    chunk_count: int
    tags: list[str]
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class ArticleListResponse(BaseModel):
    """Paginated article list."""
    items: list[ArticleSummaryResponse]
    total: int
    skip: int
    limit: int


# ═══════════════════════════════════════════════════════════
#  Search
# ═══════════════════════════════════════════════════════════

class SearchRequest(BaseModel):
    """Semantic search request."""
    query: str = Field(..., min_length=2, max_length=2000)
    top_k: int = Field(5, ge=1, le=50, description="Number of results to return")
    category: Optional[ArticleCategory] = Field(
        None, description="Filter by category",
    )
    language: Optional[str] = Field(None, description="Filter by language")
    min_similarity: float = Field(
        0.3, ge=0.0, le=1.0,
        description="Minimum cosine similarity threshold",
    )
    include_content: bool = Field(
        True, description="Include chunk content in results",
    )


class SearchHit(BaseModel):
    """A single search result."""
    article_id: uuid.UUID
    article_title: str
    article_category: ArticleCategory
    chunk_id: uuid.UUID
    chunk_index: int
    chunk_content: Optional[str] = None
    similarity: float = Field(..., description="Cosine similarity score (0-1)")
    token_count: int


class SearchResponse(BaseModel):
    """Semantic search response."""
    model_config = {"protected_namespaces": ()}

    query: str
    hits: list[SearchHit]
    total_hits: int
    model_used: str


class HybridSearchRequest(BaseModel):
    """Hybrid keyword + semantic search request."""
    query: str = Field(..., min_length=2, max_length=2000)
    top_k: int = Field(5, ge=1, le=50)
    category: Optional[ArticleCategory] = None
    language: Optional[str] = None
    keyword_weight: float = Field(
        0.3, ge=0.0, le=1.0,
        description="Weight for keyword matching (1 - this = semantic weight)",
    )
    min_similarity: float = Field(0.2, ge=0.0, le=1.0)


# ═══════════════════════════════════════════════════════════
#  Indexing
# ═══════════════════════════════════════════════════════════

class IndexArticleResponse(BaseModel):
    """Response after indexing an article."""
    article_id: uuid.UUID
    chunks_created: int
    total_tokens: int
    status: str


class ReindexAllResponse(BaseModel):
    """Response for bulk reindex."""
    total_articles: int
    task_id: Optional[str] = Field(
        None, description="Celery task ID if async",
    )
    status: str


# ═══════════════════════════════════════════════════════════
#  Stats
# ═══════════════════════════════════════════════════════════

class KnowledgeBaseStats(BaseModel):
    """Dashboard statistics for the knowledge base."""
    total_articles: int
    published_articles: int
    draft_articles: int
    archived_articles: int
    total_chunks: int
    indexed_chunks: int
    total_tokens: int
    categories: dict[str, int]
    languages: dict[str, int]
    avg_chunks_per_article: float
