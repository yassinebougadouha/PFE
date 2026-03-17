"""
Response Generation Service — orchestrates RAG retrieval → LLM generation → channel formatting.
This is the main entry point for the response_providers module.
"""

from __future__ import annotations

import logging
from typing import AsyncIterator, Optional

from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.rag.retriever import VectorRetriever
from app.rag.response_providers.base import BaseProvider
from app.rag.response_providers.enums import AIProvider, ResponseChannel, ResponseTone
from app.rag.response_providers.channel_formatter import format_response
from app.rag.response_providers.openai_provider import OpenAIProvider
from app.rag.response_providers.claude_provider import ClaudeProvider
from app.rag.response_providers.gemini_provider import GeminiProvider
from app.rag.response_providers.local_provider import LocalProvider
from app.rag.response_providers.schemas import (
    GenerateRequest,
    GenerateResponse,
    MultiChannelPreviewResponse,
    ChannelPreview,
    ProviderStatus,
    ProvidersStatusResponse,
    SourceReference,
)
from app.rag.enums import ArticleCategory

logger = logging.getLogger(__name__)


# ═══════════════════════════════════════════════════════════
#  Provider registry
# ═══════════════════════════════════════════════════════════

_PROVIDERS: dict[AIProvider, type[BaseProvider]] = {
    AIProvider.OPENAI: OpenAIProvider,
    AIProvider.CLAUDE: ClaudeProvider,
    AIProvider.GEMINI: GeminiProvider,
    AIProvider.LOCAL: LocalProvider,
}


def _get_default_provider() -> AIProvider:
    """Read the default provider from config, fallback to openai."""
    settings = get_settings()
    raw = getattr(settings, "AI_RESPONSE_PROVIDER", "openai")
    try:
        return AIProvider(raw)
    except ValueError:
        return AIProvider.OPENAI


def get_provider(provider: Optional[AIProvider] = None) -> BaseProvider:
    """Instantiate and return a provider by enum (or the default)."""
    provider = provider or _get_default_provider()
    cls = _PROVIDERS.get(provider)
    if cls is None:
        raise ValueError(f"Unknown provider: {provider}")
    return cls()


# ═══════════════════════════════════════════════════════════
#  Service class
# ═══════════════════════════════════════════════════════════

class ResponseGenerationService:
    """
    Orchestrates the full pipeline:
      1. Retrieve RAG context from knowledge base
      2. Call the selected LLM provider
      3. Format the output for the target channel
    """

    def __init__(self, db: AsyncSession) -> None:
        self.db = db
        self.retriever = VectorRetriever(db)

    # ── RAG context retrieval ───────────────────────────

    async def _fetch_rag_context(
        self,
        query: str,
        top_k: int = 5,
        category: Optional[ArticleCategory] = None,
    ) -> tuple[list[dict], list[SourceReference]]:
        """
        Retrieve relevant chunks from the knowledge base.
        Returns (raw_chunks, source_references).
        """
        try:
            chunks = await self.retriever.get_context_for_query(
                query=query,
                top_k=top_k,
                category=category,
            )
        except Exception as exc:
            logger.warning("RAG context retrieval failed: %s", exc)
            chunks = []

        sources = [
            SourceReference(
                article_id=c.get("article_id", ""),
                article_title=c.get("article_title", "Unknown"),
                similarity=c.get("similarity", 0.0),
                chunk_preview=c.get("chunk_content", "")[:120] + "..." if c.get("chunk_content") else None,
            )
            for c in chunks
        ]
        return chunks, sources

    # ── main generate ───────────────────────────────────

    async def generate(self, request: GenerateRequest) -> GenerateResponse:
        """
        Full pipeline: RAG → LLM → channel format → response.
        """
        # 1. Fetch RAG context
        chunks, sources = await self._fetch_rag_context(
            query=request.query,
            top_k=request.top_k,
            category=request.category,
        )

        # 2. Get the provider
        provider = get_provider(request.provider)

        # 3. Call LLM
        result = await provider.generate_response(
            query=request.query,
            channel=request.channel,
            tone=request.tone,
            rag_context=chunks,
            conversation_history=request.conversation_history,
            model=None,  # use provider default
            temperature=request.temperature,
            max_tokens=request.max_tokens,
            language=request.language,
            customer_name=request.customer_name,
            agent_name=request.agent_name,
        )

        raw_response = result["content"]

        # 4. Format for channel
        formatted = format_response(
            text=raw_response,
            channel=request.channel,
            customer_name=request.customer_name,
            agent_name=request.agent_name,
            sources=[s.model_dump() for s in sources] if request.include_sources else None,
        )

        return GenerateResponse(
            response=formatted,
            raw_response=raw_response,
            provider=result["provider"],
            model_used=result["model"],
            channel=request.channel,
            tone=request.tone,
            sources=sources if request.include_sources else [],
            rag_chunks_used=len(chunks),
            tokens_used=result.get("tokens_used"),
            latency_ms=result.get("latency_ms"),
        )

    # ── streaming ───────────────────────────────────────

    async def generate_stream(
        self, request: GenerateRequest
    ) -> AsyncIterator[str]:
        """
        Stream response tokens. Yields raw text chunks — channel formatting
        is not applied during streaming (apply on the frontend or after completion).
        """
        chunks, _sources = await self._fetch_rag_context(
            query=request.query,
            top_k=request.top_k,
            category=request.category,
        )

        provider = get_provider(request.provider)
        system_prompt = provider.build_system_prompt(
            channel=request.channel,
            tone=request.tone,
            rag_context=chunks,
            language=request.language,
            customer_name=request.customer_name,
            agent_name=request.agent_name,
        )
        messages = provider._build_messages(
            system_prompt=system_prompt,
            query=request.query,
            conversation_history=request.conversation_history,
        )

        async for token in provider.stream(
            messages=messages,
            temperature=request.temperature,
            max_tokens=request.max_tokens,
        ):
            yield token

    # ── multi-channel preview ───────────────────────────

    async def generate_multi_channel_preview(
        self, request: GenerateRequest
    ) -> MultiChannelPreviewResponse:
        """
        Generate once, then format for ALL channels to preview differences.
        """
        # Generate via the primary channel
        chunks, sources = await self._fetch_rag_context(
            query=request.query,
            top_k=request.top_k,
            category=request.category,
        )

        provider = get_provider(request.provider)
        result = await provider.generate_response(
            query=request.query,
            channel=request.channel,
            tone=request.tone,
            rag_context=chunks,
            conversation_history=request.conversation_history,
            temperature=request.temperature,
            max_tokens=request.max_tokens,
            language=request.language,
            customer_name=request.customer_name,
            agent_name=request.agent_name,
        )

        raw_response = result["content"]

        # Format for every channel
        previews = []
        for ch in ResponseChannel:
            formatted = format_response(
                text=raw_response,
                channel=ch,
                customer_name=request.customer_name,
                agent_name=request.agent_name,
                sources=[s.model_dump() for s in sources],
            )
            previews.append(ChannelPreview(channel=ch, formatted_response=formatted))

        return MultiChannelPreviewResponse(
            query=request.query,
            raw_response=raw_response,
            provider=result["provider"],
            model_used=result["model"],
            previews=previews,
            sources=sources,
        )

    # ── provider status ─────────────────────────────────

    @staticmethod
    async def get_providers_status() -> ProvidersStatusResponse:
        """Return configuration status of all providers."""
        default = _get_default_provider()
        statuses = []
        for provider_enum, cls in _PROVIDERS.items():
            instance = cls()
            statuses.append(
                ProviderStatus(
                    provider=provider_enum,
                    is_configured=instance._is_configured,
                    is_default=(provider_enum == default),
                    default_model=instance.default_model,
                    available_models=instance.available_models,
                )
            )
        return ProvidersStatusResponse(
            default_provider=default,
            providers=statuses,
        )

    @staticmethod
    async def check_provider_health(provider: AIProvider) -> bool:
        """Run a health check against a specific provider."""
        instance = get_provider(provider)
        return await instance.health_check()
