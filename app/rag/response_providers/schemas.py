"""
Pydantic schemas for response generation requests and responses.
"""

from __future__ import annotations

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field

from app.rag.response_providers.enums import AIProvider, ResponseChannel, ResponseTone
from app.rag.enums import ArticleCategory


# ═══════════════════════════════════════════════════════════
#  Generation request
# ═══════════════════════════════════════════════════════════

class GenerateRequest(BaseModel):
    """Request to generate an AI response using RAG context."""
    query: str = Field(..., min_length=2, max_length=5000, description="User question / message")
    channel: ResponseChannel = Field(
        ResponseChannel.CHAT,
        description="Output channel — determines formatting style",
    )
    provider: Optional[AIProvider] = Field(
        None,
        description="LLM provider to use. Falls back to default from config.",
    )
    tone: ResponseTone = Field(
        ResponseTone.PROFESSIONAL,
        description="Tone preset for the response",
    )
    category: Optional[ArticleCategory] = Field(
        None,
        description="Optional RAG category filter for context retrieval",
    )
    language: Optional[str] = Field(
        None,
        description="ISO language code for response (e.g. 'en', 'fr')",
    )
    top_k: int = Field(5, ge=1, le=20, description="Number of RAG context chunks")
    conversation_history: list[dict] = Field(
        default_factory=list,
        description="Previous messages [{role: 'user'|'assistant', content: '...'}]",
    )
    customer_name: Optional[str] = Field(None, description="Customer name for personalization")
    agent_name: Optional[str] = Field(None, description="Agent/bot name for sign-off")
    max_tokens: int = Field(1024, ge=64, le=4096, description="Max response tokens")
    temperature: float = Field(0.3, ge=0.0, le=1.0, description="Sampling temperature")
    include_sources: bool = Field(True, description="Include source references in response")


# ═══════════════════════════════════════════════════════════
#  RAG context (internal)
# ═══════════════════════════════════════════════════════════

class RAGContext(BaseModel):
    """Knowledge base context retrieved for a query."""
    chunks: list[dict] = Field(default_factory=list)
    total_chunks: int = 0
    query: str = ""


# ═══════════════════════════════════════════════════════════
#  Generation response
# ═══════════════════════════════════════════════════════════

class SourceReference(BaseModel):
    """A knowledge base source cited in the response."""
    article_id: str
    article_title: str
    similarity: float
    chunk_preview: Optional[str] = None


class GenerateResponse(BaseModel):
    """Full response from the generation pipeline."""
    model_config = {"protected_namespaces": ()}

    response: str = Field(..., description="Generated response text, formatted for the channel")
    raw_response: str = Field(..., description="Unformatted LLM output")
    provider: AIProvider
    model_used: str
    channel: ResponseChannel
    tone: ResponseTone
    sources: list[SourceReference] = Field(default_factory=list)
    rag_chunks_used: int = 0
    tokens_used: Optional[int] = None
    latency_ms: Optional[int] = None
    generated_at: datetime = Field(default_factory=datetime.utcnow)


# ═══════════════════════════════════════════════════════════
#  Multi-channel preview
# ═══════════════════════════════════════════════════════════

class ChannelPreview(BaseModel):
    """A response formatted for a specific channel."""
    channel: ResponseChannel
    formatted_response: str


class MultiChannelPreviewResponse(BaseModel):
    """Preview the same response formatted for all channels."""
    model_config = {"protected_namespaces": ()}

    query: str
    raw_response: str
    provider: AIProvider
    model_used: str
    previews: list[ChannelPreview]
    sources: list[SourceReference] = Field(default_factory=list)


# ═══════════════════════════════════════════════════════════
#  Provider status
# ═══════════════════════════════════════════════════════════

class ProviderStatus(BaseModel):
    """Health / config status for a single provider."""
    provider: AIProvider
    is_configured: bool
    is_default: bool = False
    default_model: str
    available_models: list[str]


class ProvidersStatusResponse(BaseModel):
    """Status of all configured providers."""
    default_provider: AIProvider
    providers: list[ProviderStatus]
