"""
API endpoints for viewing Voice Call Logs (transcriptions and audio).
"""

import uuid
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status
from fastapi.responses import FileResponse
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import ChannelType, UserRole
from app.api.deps import get_current_user, require_agent_or_admin
from app.schemas.ticket import TicketCreate, TicketUpdate
from app.schemas.voice_call import (
    VoiceCallLogListResponse,
    VoiceCallLogResponse,
    VoiceCallPostCallSummaryRequest,
    VoiceCallPostCallSummaryResponse,
    VoiceCallTicketLinkRequest,
    VoiceCallTicketLinkResponse,
)
from app.services.ticket_service import TicketService
from app.services.voice_call_post_call_service import VoiceCallPostCallService
from app.services.voice_call_service import VoiceCallService
from app.core.config import get_settings

settings = get_settings()

router = APIRouter(prefix="/voice-calls", tags=["Voice Call Logs"])


@router.get(
    "/",
    response_model=VoiceCallLogListResponse,
    dependencies=[Depends(require_agent_or_admin)],
)
async def list_voice_calls(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(get_current_user)],
    skip: int = Query(0, ge=0),
    limit: int = Query(20, ge=1, le=1000),
):
    """
    List all recorded voice calls and transcripts.
    Restricted to agents and admins.
    """
    service = VoiceCallService(db)
    items, total = await service.list_calls(skip=skip, limit=limit)
    return {
        "items": items,
        "total": total,
        "skip": skip,
        "limit": limit,
    }


@router.get(
    "/{call_id}",
    response_model=VoiceCallLogResponse,
    dependencies=[Depends(require_agent_or_admin)],
)
async def get_voice_call(
    call_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(get_current_user)],
):
    """
    Get the details of a specific voice call transcript.
    """
    service = VoiceCallService(db)
    call = await service.get_call(call_id)
    if not call:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Voice call log not found")
    return call


@router.post(
    "/{call_id}/post-call-summary",
    response_model=VoiceCallPostCallSummaryResponse,
    dependencies=[Depends(require_agent_or_admin)],
)
async def generate_post_call_summary(
    call_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(get_current_user)],
    payload: VoiceCallPostCallSummaryRequest | None = None,
):
    """Generate a post-call summary with action items and ticket suggestions."""
    call_service = VoiceCallService(db)
    call = await call_service.get_call(call_id, enrich_transcript=True)
    if not call:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Voice call log not found")

    summary_service = VoiceCallPostCallService()
    request_payload = payload or VoiceCallPostCallSummaryRequest()
    return await summary_service.summarize_call(
        call,
        max_transcript_chars=request_payload.max_transcript_chars,
    )


@router.post(
    "/{call_id}/link-ticket",
    response_model=VoiceCallTicketLinkResponse,
    dependencies=[Depends(require_agent_or_admin)],
)
async def link_voice_call_ticket(
    call_id: uuid.UUID,
    payload: VoiceCallTicketLinkRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Link a voice call to an existing ticket or create a new linked ticket."""
    call_service = VoiceCallService(db)
    call = await call_service.get_call(call_id, enrich_transcript=False)
    if not call:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Voice call log not found")

    ticket_service = TicketService(db)

    if payload.ticket_id:
        existing_ticket = await ticket_service.get_ticket(payload.ticket_id)
        if not existing_ticket:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")

        if (
            current_user.role == UserRole.AGENT
            and existing_ticket.assigned_agent_id not in {None, current_user.id}
        ):
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

        updated_ticket = await ticket_service.update_ticket(
            existing_ticket.id,
            TicketUpdate(source_voice_call_id=call_id),
        )
        if not updated_ticket:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")

        return VoiceCallTicketLinkResponse(
            call_id=call_id,
            ticket_id=updated_ticket.id,
            ticket_subject=updated_ticket.subject,
            link_type="attached",
            source_voice_call_id=call_id,
        )

    subject = (payload.subject or "").strip()
    description = (payload.description or "").strip()

    if not subject or not description:
        summary_service = VoiceCallPostCallService()
        summary = await summary_service.summarize_call(call)
        if not subject:
            subject = summary.ticket_subject_suggestion
        if not description:
            description = summary.ticket_description_suggestion

    created_ticket = await ticket_service.create_ticket(
        current_user.id,
        TicketCreate(
            subject=subject,
            description=description,
            priority=payload.priority,
            channel_source=ChannelType.CALL_TRANSCRIPT,
            source_voice_call_id=call_id,
        ),
    )

    return VoiceCallTicketLinkResponse(
        call_id=call_id,
        ticket_id=created_ticket.id,
        ticket_subject=created_ticket.subject,
        link_type="created",
        source_voice_call_id=call_id,
    )


@router.get(
    "/{call_id}/audio",
    dependencies=[Depends(require_agent_or_admin)],
)
async def stream_audio_file(
    call_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(get_current_user)],
):
    """
    Stream the WAV audio recording for a specific call.
    """
    service = VoiceCallService(db)
    call = await service.get_call(call_id, enrich_transcript=False)
    
    if not call:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Voice call log not found")
        
    if not call.audio_file_path:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="No audio file associated with this call")

    file_path = service._resolve_recording_path(call.audio_file_path)
    
    if not file_path.exists():
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Audio file not found on disk")

    return FileResponse(
        path=str(file_path),
        media_type="audio/wav",
        filename=f"call_{call.room_name}_{call.id.hex[:8]}.wav",
    )
