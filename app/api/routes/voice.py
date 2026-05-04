"""
Voice routes — STT (speech-to-text) and TTS (text-to-speech).

STT: upload audio → Gemini transcribes → text is processed like any other message.
TTS: supply text → edge-tts synthesizes → MP3 stream returned.
"""

import logging
import time
import uuid
from typing import Annotated, Optional

from fastapi import APIRouter, BackgroundTasks, Depends, File, Form, HTTPException, UploadFile, status
from fastapi.responses import FileResponse
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.email import Email
from app.db.models.enums import (
    AuditAction,
    ChannelType,
    EmailStatus,
    TicketPriority,
)
from app.api.deps import get_current_user, require_agent_or_admin
from app.schemas.voice import (
    TranscriptionResult,
    TTSRequest,
    TTSResult,
    VoiceInfo,
    VoiceMessageResult,
)
from app.services.transcription_service import (
    cleanup_file,
    save_upload_and_transcribe,
)
from app.services.tts_service import (
    estimate_audio_duration,
    list_available_voices,
    synthesize_speech,
)
from app.services.audit_service import AuditService
from app.services.ticket_service import TicketService
from app.schemas.ticket import TicketCreate
from app.core.config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()

router = APIRouter(prefix="/voice", tags=["Voice (STT / TTS)"])

# Maximum upload size: 25 MB
MAX_AUDIO_SIZE = 25 * 1024 * 1024
ALLOWED_AUDIO_TYPES = {
    "audio/wav", "audio/wave", "audio/x-wav",
    "audio/mpeg", "audio/mp3",
    "audio/webm", "audio/ogg",
    "audio/flac", "audio/x-flac",
    "audio/mp4", "audio/m4a",
}


# ── STT: Transcribe only ────────────────────────────────

@router.post("/transcribe", response_model=TranscriptionResult)
async def transcribe_audio(
    file: UploadFile = File(..., description="Audio file (wav, mp3, webm, ogg, flac)"),
    _: User = Depends(get_current_user),
):
    """
    Transcribe an audio file to text using Gemini.
    Returns the raw transcription without creating any records.
    Useful for preview before submitting a voice message.
    """
    _validate_audio_file(file)

    content = await file.read()
    if len(content) > MAX_AUDIO_SIZE:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail=f"Audio file too large. Maximum size: {MAX_AUDIO_SIZE // (1024*1024)} MB",
        )

    result = await save_upload_and_transcribe(
        content,
        file.filename or "audio.wav",
        content_type=file.content_type,
    )

    if not result["text"]:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="No speech detected in the audio file",
        )

    return result


# ── STT: Voice message → ticket ─────────────────────────

@router.post("/message", response_model=VoiceMessageResult, status_code=status.HTTP_201_CREATED)
async def submit_voice_message(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    file: UploadFile = File(..., description="Audio file to transcribe and submit"),
    subject: str = Form("Voice Support Request", description="Ticket subject"),
    priority: TicketPriority = Form(TicketPriority.MEDIUM, description="Ticket priority"),
):
    """
    Transcribe a voice message and automatically create a support ticket.
    The transcribed text is treated exactly like an email or chat message:
    → Email record created + Ticket created with the transcription as description.
    """
    _validate_audio_file(file)

    content = await file.read()
    if len(content) > MAX_AUDIO_SIZE:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail=f"Audio file too large. Maximum size: {MAX_AUDIO_SIZE // (1024*1024)} MB",
        )

    # Transcribe
    result = await save_upload_and_transcribe(
        content,
        file.filename or "audio.wav",
        content_type=file.content_type,
    )

    if not result["text"]:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="No speech detected in the audio file",
        )

    transcript = result["text"]

    # Create email record (voice messages are stored as emails for unified history)
    email = Email(
        sender_address=f"voice:{current_user.email}",
        recipient_address="support@platform.local",
        subject=f"[Voice] {subject}"[:500],
        body=transcript,
        raw_headers=f'{{"source": "voice", "language": "{result["language"]}"}}',
        is_outbound=False,
        status=EmailStatus.CONVERTED,
    )
    db.add(email)
    await db.flush()

    # Create ticket from transcription. TicketService runs decision analysis and outcome actions.
    ticket = await TicketService(db).create_ticket(
        current_user.id,
        TicketCreate(
            subject=f"[Voice] {subject}"[:500],
            description=transcript,
            priority=priority,
            channel_source=ChannelType.CHAT,
        ),
    )
    ticket.source_email_id = email.id
    await db.flush()

    # Audit
    audit = AuditService(db)
    await audit.log(
        action=AuditAction.CREATE,
        resource_type="voice_message",
        resource_id=str(ticket.id),
        user_id=current_user.id,
        description=f"Voice message transcribed and ticket created ({len(transcript)} chars, lang={result['language']})",
    )

    await db.commit()

    return {
        "transcription": transcript,
        "language": result["language"],
        "ticket_id": ticket.id,
        "email_id": email.id,
    }


# ── TTS: Text → Audio ──────────────────────────────────

@router.post("/synthesize", response_model=TTSResult)
async def text_to_speech(
    payload: TTSRequest,
    background_tasks: BackgroundTasks,
    _: User = Depends(get_current_user),
):
    """
    Convert text to speech using edge-tts.
    Returns a URL to download the generated MP3 audio file.
    The file is automatically cleaned up after playback time + buffer.
    """
    result = await synthesize_speech(
        text=payload.text,
        voice=payload.voice,
        rate=payload.rate,
        pitch=payload.pitch,
    )

    # Schedule cleanup after estimated playback + buffer
    duration = result["duration_estimate"]
    delay = duration + settings.AUDIO_CLEANUP_DELAY_SECONDS
    background_tasks.add_task(_delayed_cleanup, result["file_path"], delay)

    return {
        "audio_url": f"/api/v1/voice/audio/{result['filename']}",
        "filename": result["filename"],
        "duration_estimate": duration,
        "text_length": len(payload.text),
    }


@router.get("/audio/{filename}")
async def serve_audio(filename: str):
    """
    Serve a generated TTS audio file.
    Files are temporary and deleted after playback.
    """
    # Sanitize filename — prevent path traversal
    if "/" in filename or "\\" in filename or ".." in filename:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Invalid filename")

    from pathlib import Path
    file_path = Path(settings.UPLOADS_DIR) / filename

    if not file_path.exists():
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Audio file not found or already expired",
        )

    return FileResponse(
        path=str(file_path),
        media_type="audio/mpeg",
        filename=filename,
    )


# ── TTS: Available voices ───────────────────────────────

@router.get("/voices", response_model=list[VoiceInfo])
async def get_voices(
    language: str = "en",
    _: User = Depends(get_current_user),
):
    """List available TTS voices, filtered by language prefix."""
    return await list_available_voices(language)


# ── Helpers ──────────────────────────────────────────────

def _validate_audio_file(file: UploadFile) -> None:
    """Validate the uploaded audio file type."""
    raw_content_type = (file.content_type or "").strip().lower()
    normalized_content_type = raw_content_type.split(";", 1)[0].strip()
    if normalized_content_type and normalized_content_type not in ALLOWED_AUDIO_TYPES:
        raise HTTPException(
            status_code=status.HTTP_415_UNSUPPORTED_MEDIA_TYPE,
            detail=f"Unsupported audio format: {file.content_type or normalized_content_type}. "
                   f"Accepted: wav, mp3, webm, ogg, flac, m4a",
        )


def _delayed_cleanup(file_path: str, delay: int) -> None:
    """Sleep then delete a file — runs as a background task."""
    time.sleep(delay)
    cleanup_file(file_path)
