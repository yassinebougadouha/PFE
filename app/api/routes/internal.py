"""
Internal API routes for service-to-service communication.

These endpoints are open while backend auth is disabled, allowing internal
services (e.g. voice agents) to access the RAG knowledge base and response
providers without user tokens.

Prefix: /internal
Tag: Internal Services
"""

from __future__ import annotations

import logging
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, Header, HTTPException, status
from httpx import HTTPStatusError
from sqlalchemy import select
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
from app.schemas.voice_agent import VoiceEscalationRequest, VoiceEscalationResponse
from app.schemas.ticket import GlpiTicketIngestRequest, TicketCreate, TicketResponse, TicketUpdate
from app.db.models.enums import ChannelType, TicketPriority, TicketStatus, UserRole, UserStatus
from app.db.models.user import User
from app.services.ticket_service import TicketService
from app.services.support_call_screen_context import support_call_screen_context_store

logger = logging.getLogger(__name__)
settings = get_settings()

router = APIRouter(prefix="/internal", tags=["Internal Services"])

# ── Type aliases ──────────────────────────────────────────
DB = Annotated[AsyncSession, Depends(get_db)]


# ── Service key dependency ────────────────────────────────

async def verify_service_key(
    x_service_key: Optional[str] = Header(None, alias="X-Service-Key"),
) -> str:
    """Accept internal requests without requiring a service key."""
    return x_service_key or ""


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


async def _resolve_internal_user(
    db: AsyncSession,
    *,
    email: str,
    full_name: str,
    role: UserRole = UserRole.CLIENT,
) -> User:
    result = await db.execute(
        select(User).where(
            User.email == email,
            User.is_deleted == False,
        )
    )
    system_user = result.scalar_one_or_none()
    if not system_user:
        system_user = User(
            email=email,
            full_name=full_name,
            hashed_password="!no_login",
            role=role,
            status=UserStatus.ACTIVE,
        )
        db.add(system_user)
        await db.flush()
    return system_user


@router.post(
    "/tickets/glpi-ingest",
    response_model=TicketResponse,
    summary="Ingest a GLPI ticket into the backend",
    description="Creates or updates a local backend ticket from a GLPI-created ticket and runs the decision engine.",
)
async def internal_ingest_glpi_ticket(
    payload: GlpiTicketIngestRequest,
    db: DB,
    _key: ServiceKey,
) -> TicketResponse:
    creator_email = (payload.creator_email or "glpi_ingest_system@local").strip().lower()
    creator_name = (payload.creator_name or "GLPI Ingest System").strip() or "GLPI Ingest System"
    creator = await _resolve_internal_user(db, email=creator_email, full_name=creator_name)

    ticket_service = TicketService(db)
    ticket, _decision = await ticket_service.ingest_glpi_ticket(creator.id, payload)
    return ticket


@router.post(
    "/voice/escalations",
    response_model=VoiceEscalationResponse,
    summary="Create an immediate voice escalation ticket",
    description="Creates a high-priority human handoff ticket for a voice call. For internal services only.",
)
async def internal_create_voice_escalation(
    payload: VoiceEscalationRequest,
    db: DB,
    _key: ServiceKey,
) -> VoiceEscalationResponse:
    room_name = payload.room_name.strip()
    reason = payload.reason.strip()
    if not room_name or not reason:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="room_name and reason are required")

    system_user = await _resolve_internal_user(
        db,
        email="voice_agent_system@local",
        full_name="Voice Agent System",
        role=UserRole.CLIENT,
    )

    description_parts = [
        f"**Escalation Reason:** {reason}",
        f"**Room Name:** {room_name}",
    ]
    if payload.audio_file_path:
        description_parts.append(f"**Audio Recording:** `{payload.audio_file_path}`")
    if payload.transcript:
        description_parts.append("\n**--- Transcript ---**")
        description_parts.append(payload.transcript)

    ticket_service = TicketService(db)
    ticket = await ticket_service.create_ticket(
        system_user.id,
        TicketCreate(
            subject=f"Voice Call Escalation: {room_name}",
            description="\n".join(description_parts),
            priority=TicketPriority.HIGH,
            channel_source=ChannelType.CALL_TRANSCRIPT,
        ),
    )

    escalated_ticket = await ticket_service.update_ticket(
        ticket.id,
        TicketUpdate(
            status=TicketStatus.ESCALATED,
            escalation_flag=True,
        ),
    )
    if not escalated_ticket:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail="Escalation ticket update failed")

    return VoiceEscalationResponse(
        room_name=room_name,
        ticket_id=str(escalated_ticket.id),
        ticket_subject=escalated_ticket.subject,
        status=escalated_ticket.status.value,
        escalation_flag=bool(escalated_ticket.escalation_flag),
    )


# ═══════════════════════════════════════════════════════════
#  Laravel User Sync
# ═══════════════════════════════════════════════════════════

from pydantic import BaseModel

class LaravelUserSyncRequest(BaseModel):
    laravel_user_id: int
    email: str
    role: str = "CLIENT"

@router.post(
    "/sync-laravel-user",
    summary="Sync Laravel user ID to support_db",
)
async def sync_laravel_user(
    payload: LaravelUserSyncRequest,
    db: DB,
    _key: ServiceKey,
) -> dict:
    # Map role string to UserRole enum safely
    try:
        python_role = UserRole(payload.role)
    except ValueError:
        python_role = UserRole.CLIENT

    result = await db.execute(
        select(User).where(
            User.email == payload.email,
            User.is_deleted == False,
        )
    )
    user = result.scalar_one_or_none()

    if not user:
        user = User(
            email=payload.email,
            full_name=payload.email,
            hashed_password="!laravel_auth",
            role=python_role,
            status=UserStatus.ACTIVE,
            laravel_user_id=payload.laravel_user_id,
        )
        db.add(user)
        await db.commit()
        logger.info(f"Created user laravel_user_id={payload.laravel_user_id} → {payload.email} role={python_role}")
        return {"status": "created", "email": payload.email}

    user.laravel_user_id = payload.laravel_user_id
    user.role = python_role
    await db.commit()
    logger.info(f"Synced laravel_user_id={payload.laravel_user_id} → {payload.email} role={python_role}")
    return {"status": "synced", "email": payload.email}

# ═══════════════════════════════════════════════════════════
#  Laravel Token — generate JWT for a synced Laravel user
# ═══════════════════════════════════════════════════════════

from app.core.security import create_access_token

class LaravelTokenRequest(BaseModel):
    laravel_user_id: int

@router.post(
    "/laravel-token",
    summary="Get JWT token for a Laravel user by laravel_user_id",
)
async def get_laravel_token(
    payload: LaravelTokenRequest,
    db: DB,
    _key: ServiceKey,
) -> dict:
    result = await db.execute(
        select(User).where(
            User.laravel_user_id == payload.laravel_user_id,
            User.is_deleted == False,
        )
    )
    user = result.scalar_one_or_none()

    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"No user found with laravel_user_id={payload.laravel_user_id}. Call /sync-laravel-user first.",
        )

    access_token = create_access_token(
        subject=str(user.id),
        extra_claims={"role": user.role.value},
    )

    logger.info(f"Laravel token issued for laravel_user_id={payload.laravel_user_id} → {user.email}")
    return {"access_token": access_token, "token_type": "bearer"}