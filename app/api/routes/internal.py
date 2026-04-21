"""
Internal API routes for service-to-service communication.

These endpoints are protected by a shared service key (INTERNAL_SERVICE_KEY)
instead of JWT auth, allowing internal services (e.g. voice agents) to
access the RAG knowledge base and response providers without user tokens.

Prefix: /internal
Tag: Internal Services
"""

from __future__ import annotations

import logging
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, Header, HTTPException, status
from httpx import HTTPStatusError
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.db.session import get_db
from app.rag.retriever import VectorRetriever
from app.rag.schemas import SearchRequest, SearchResponse
from app.rag.response_providers.service import ResponseGenerationService
from app.rag.response_providers.schemas import GenerateRequest, GenerateResponse
from app.schemas.support_call_screen_context import (
    SupportCallScreenContextClearResponse,
    SupportCallScreenContextSnapshotResponse,
)
from app.services.support_call_screen_context import support_call_screen_context_store

logger = logging.getLogger(__name__)
settings = get_settings()

router = APIRouter(prefix="/internal", tags=["Internal Services"])

# ── Type aliases ──────────────────────────────────────────
DB = Annotated[AsyncSession, Depends(get_db)]


# ── Service key dependency ────────────────────────────────

async def verify_service_key(
    x_service_key: str = Header(..., alias="X-Service-Key"),
) -> str:
    """Verify the internal service key from the request header."""
    if x_service_key != settings.INTERNAL_SERVICE_KEY:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Invalid service key",
        )
    return x_service_key


ServiceKey = Annotated[str, Depends(verify_service_key)]


# ═══════════════════════════════════════════════════════════
#  RAG Semantic Search (for voice agents)
# ═══════════════════════════════════════════════════════════

@router.post(
    "/rag/search",
    response_model=SearchResponse,
    summary="Internal RAG semantic search",
    description="Search the knowledge base using vector similarity. For internal services only.",
)
async def internal_rag_search(
    payload: SearchRequest,
    db: DB,
    _key: ServiceKey,
) -> SearchResponse:
    """Search the knowledge base — internal service endpoint."""
    try:
        retriever = VectorRetriever(db)
        return await retriever.semantic_search(payload)
    except Exception as exc:
        logger.error("Internal RAG search failed: %s", exc, exc_info=True)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Search failed: {exc}",
        )


# ═══════════════════════════════════════════════════════════
#  RAG + LLM Generation (for voice agents)
# ═══════════════════════════════════════════════════════════

@router.post(
    "/rag/generate",
    response_model=GenerateResponse,
    summary="Internal RAG-augmented response generation",
    description="Generate an AI response with RAG context. For internal services only.",
)
async def internal_rag_generate(
    body: GenerateRequest,
    db: DB,
    _key: ServiceKey,
) -> GenerateResponse:
    """Generate a RAG-augmented response — internal service endpoint."""
    try:
        service = ResponseGenerationService(db)
        return await service.generate(body)
    except HTTPStatusError as exc:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail=f"Upstream provider error: {exc.response.status_code}",
        )
    except RuntimeError as exc:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail=str(exc),
        )
    except Exception as exc:
        logger.error("Internal RAG generation failed: %s", exc, exc_info=True)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Generation failed: {exc}",
        )


# ═══════════════════════════════════════════════════════════
#  Support-call live screen context (for voice agents)
# ═══════════════════════════════════════════════════════════

@router.get(
    "/support-call-screen-context/{room_name}",
    response_model=SupportCallScreenContextSnapshotResponse,
    summary="Get latest support-call screen-sharing context",
    description="Returns the latest live screen-analysis context for a support-call room.",
)
async def internal_get_support_call_screen_context(
    room_name: str,
    _key: ServiceKey,
) -> SupportCallScreenContextSnapshotResponse:
    snapshot = support_call_screen_context_store.get_snapshot(room_name)
    return SupportCallScreenContextSnapshotResponse(**snapshot)


@router.delete(
    "/support-call-screen-context/{room_name}",
    response_model=SupportCallScreenContextClearResponse,
    summary="Clear support-call screen-sharing context",
    description="Clears cached live screen-analysis context for a support-call room.",
)
async def internal_clear_support_call_screen_context(
    room_name: str,
    _key: ServiceKey,
) -> SupportCallScreenContextClearResponse:
    cleared = support_call_screen_context_store.clear(room_name)
    return SupportCallScreenContextClearResponse(room_name=room_name, cleared=cleared)
