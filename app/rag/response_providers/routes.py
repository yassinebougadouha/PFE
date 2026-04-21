"""
API routes for the AI response generation module.

Prefix: /rag/generate
Tag: AI Response Generation
"""

from __future__ import annotations

import json
import logging
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.responses import StreamingResponse
from httpx import HTTPStatusError
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.api.deps import require_agent_or_admin

from app.rag.response_providers.enums import AIProvider, ResponseChannel, ResponseTone
from app.rag.response_providers.schemas import (
    GenerateRequest,
    GenerateResponse,
    MultiChannelPreviewResponse,
    ProvidersStatusResponse,
)
from app.rag.response_providers.service import ResponseGenerationService

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/rag/generate", tags=["AI Response Generation"])

# ── Type aliases ──────────────────────────────────────────
DB = Annotated[AsyncSession, Depends(get_db)]
Agent = Annotated[User, Depends(require_agent_or_admin)]


# ═══════════════════════════════════════════════════════════
#  Generate response
# ═══════════════════════════════════════════════════════════

@router.post(
    "",
    response_model=GenerateResponse,
    status_code=status.HTTP_200_OK,
    summary="Generate an AI response",
    description=(
        "Retrieve relevant knowledge base context, generate an LLM response, "
        "and format it for the specified channel (chat, email, whatsapp, voice, ticket)."
    ),
)
async def generate_response(
    body: GenerateRequest,
    db: DB,
    _user: Agent,
) -> GenerateResponse:
    try:
        service = ResponseGenerationService(db)
        return await service.generate(body)
    except HTTPStatusError as exc:
        # Provider quota / transient upstream failures should not surface as 500.
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
        logger.error("Response generation failed: %s", exc, exc_info=True)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Generation failed: {exc}",
        )


# ═══════════════════════════════════════════════════════════
#  Stream response (Server-Sent Events)
# ═══════════════════════════════════════════════════════════

@router.post(
    "/stream",
    status_code=status.HTTP_200_OK,
    summary="Stream an AI response (SSE)",
    description=(
        "Same as /generate but streams tokens as Server-Sent Events. "
        "Channel formatting is NOT applied during streaming."
    ),
)
async def stream_response(
    body: GenerateRequest,
    db: DB,
    _user: Agent,
):
    service = ResponseGenerationService(db)

    async def event_stream():
        try:
            async for token in service.generate_stream(body):
                yield f"data: {json.dumps({'token': token})}\n\n"
            yield "data: [DONE]\n\n"
        except RuntimeError as exc:
            yield f"data: {json.dumps({'error': str(exc)})}\n\n"
        except Exception as exc:
            logger.error("Stream generation failed: %s", exc, exc_info=True)
            yield f"data: {json.dumps({'error': 'Stream generation failed'})}\n\n"

    return StreamingResponse(
        event_stream(),
        media_type="text/event-stream",
        headers={
            "Cache-Control": "no-cache",
            "Connection": "keep-alive",
            "X-Accel-Buffering": "no",
        },
    )


# ═══════════════════════════════════════════════════════════
#  Multi-channel preview
# ═══════════════════════════════════════════════════════════

@router.post(
    "/preview",
    response_model=MultiChannelPreviewResponse,
    status_code=status.HTTP_200_OK,
    summary="Preview response across all channels",
    description=(
        "Generate a response once, then format it for every channel "
        "(CHAT, EMAIL, WHATSAPP, VOICE, TICKET) to preview differences."
    ),
)
async def multi_channel_preview(
    body: GenerateRequest,
    db: DB,
    _user: Agent,
) -> MultiChannelPreviewResponse:
    try:
        service = ResponseGenerationService(db)
        return await service.generate_multi_channel_preview(body)
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
        logger.error("Multi-channel preview failed: %s", exc, exc_info=True)
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Preview failed: {exc}",
        )


# ═══════════════════════════════════════════════════════════
#  Provider management
# ═══════════════════════════════════════════════════════════

@router.get(
    "/providers",
    response_model=ProvidersStatusResponse,
    status_code=status.HTTP_200_OK,
    summary="List configured LLM providers",
)
async def list_providers(_user: Agent) -> ProvidersStatusResponse:
    return await ResponseGenerationService.get_providers_status()


@router.get(
    "/providers/{provider}/health",
    status_code=status.HTTP_200_OK,
    summary="Health check a specific LLM provider",
)
async def provider_health_check(
    provider: AIProvider,
    _user: Agent,
) -> dict:
    healthy = await ResponseGenerationService.check_provider_health(provider)
    return {
        "provider": provider.value,
        "healthy": healthy,
    }
