"""
Pydantic schemas for PDF document operations.
"""

from __future__ import annotations

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field

from app.rag.enums import ArticleCategory


# ═══════════════════════════════════════════════════════════
#  PDF file info
# ═══════════════════════════════════════════════════════════

class PDFFileInfo(BaseModel):
    """Information about a PDF file on disk."""
    filename: str
    size_bytes: int
    size_human: str
    modified_at: datetime
    is_ingested: bool = False
    article_id: Optional[uuid.UUID] = None
    chunks_count: int = 0


class PDFListResponse(BaseModel):
    """List of PDF files in the documents directory."""
    directory: str
    files: list[PDFFileInfo]
    total_files: int


# ═══════════════════════════════════════════════════════════
#  PDF ingestion request / response
# ═══════════════════════════════════════════════════════════

class IngestPDFRequest(BaseModel):
    """Request to ingest a specific PDF file from the documents folder."""
    filename: str = Field(..., description="PDF filename in the documents folder")
    category: ArticleCategory = Field(
        ArticleCategory.GENERAL,
        description="Category to assign to the created article",
    )
    language: str = Field("en", max_length=10)
    tags: list[str] = Field(default_factory=list)
    auto_publish: bool = Field(
        False,
        description="Automatically publish the article after ingestion",
    )
    auto_index: bool = Field(
        True,
        description="Automatically chunk and embed after creation",
    )


class IngestPDFResponse(BaseModel):
    """Result of ingesting a single PDF."""
    filename: str
    article_id: uuid.UUID
    title: str
    page_count: int
    total_words: int
    chunks_created: int
    total_tokens: int
    status: str
    is_published: bool


class BulkIngestRequest(BaseModel):
    """Request to ingest all PDFs from the documents folder."""
    category: ArticleCategory = Field(
        ArticleCategory.GENERAL,
        description="Default category for all ingested articles",
    )
    language: str = Field("en", max_length=10)
    tags: list[str] = Field(default_factory=list)
    auto_publish: bool = Field(False)
    auto_index: bool = Field(True)
    skip_existing: bool = Field(
        True,
        description="Skip PDFs that already have a matching article (by source filename)",
    )


class BulkIngestResponse(BaseModel):
    """Result of bulk PDF ingestion."""
    total_files: int
    ingested: int
    skipped: int
    failed: int
    results: list[IngestPDFResponse]
    errors: list[dict] = Field(default_factory=list)


# ═══════════════════════════════════════════════════════════
#  PDF upload response
# ═══════════════════════════════════════════════════════════

class PDFUploadResponse(BaseModel):
    """Response after uploading a PDF to the documents folder."""
    filename: str
    size_bytes: int
    message: str
