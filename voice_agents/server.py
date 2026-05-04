"""
Voice Agent Server — entry point for the LiveKit agents process.

Supports multiple LLM providers (Gemini, OpenAI) via AI_RESPONSE_PROVIDER env var,
and connects to the backend's RAG knowledge base for dynamic knowledge retrieval.

Features:
  - Multi-agent voice sessions (StarterAgent → SupportAgent / BookingAgent / FAQAgent)
  - Full call transcript capture → translated to French → stored in DB
  - Full call audio recording → saved as single WAV file

Run with:
    python -m voice_agents.server dev          # development (console mode)
    python -m voice_agents.server start        # production (connects to LiveKit)

Or via the convenience script:
    python run_voice_agents.py dev
"""

import logging
from datetime import datetime, timezone
import os

from livekit import agents, rtc
from livekit.agents import AgentSession
from livekit.plugins import silero

from voice_agents.config import get_voice_settings
from voice_agents.llm_factory import make_stt
from voice_agents.agents import StarterAgent
from voice_agents import rag_bridge
from voice_agents.call_transcript import CallTranscriptCollector
from voice_agents.call_recorder import CallAudioRecorder
from voice_agents import shared_state

logger = logging.getLogger(__name__)


async def entrypoint(ctx: agents.JobContext):
    """
    Called for every new LiveKit room / voice session.
    Starts the StarterAgent (Tom) who greets the user
    and routes to specialist agents as needed.

    Also sets up:
      - Transcript collection (all turns → French translation)
      - Audio recording (all tracks → single WAV file)
      - Shutdown hook to persist everything to the database
    """
    settings = get_voice_settings()
    provider = settings.ai_provider
    room_name = ctx.room.name if ctx.room else "unknown"
    room_sid = ctx.room.sid if ctx.room else None
    started_at = datetime.now(timezone.utc)

    logger.info(
        "Starting voice session — provider=%s, realtime=%s, backend=%s, room=%s",
        provider, settings.use_realtime, settings.backend_api_url, room_name,
    )

    if settings.use_realtime and provider.lower() == "gemini":
        logger.warning(
            "Gemini Realtime may fail with 1008 unless enabled for your key/project. "
            "Set USE_REALTIME=false to use stable pipeline mode."
        )

    # ── Initialize transcript collector & audio recorder ──
    transcript_collector = CallTranscriptCollector()
    transcript_collector.set_client_id(_infer_client_id_from_room_name(room_name))
    audio_recorder = CallAudioRecorder()

    # ── Build the agent session ───────────────────────────
    if settings.use_realtime:
        session = AgentSession()
    else:
        session = AgentSession(
            stt=make_stt(),
            vad=silero.VAD.load(),
        )

    # ── Register transcript event handlers ────────────────
    # All three cover different provider/event paths — all are required.
    session.on("user_input_transcribed",  transcript_collector.on_user_input_transcribed)
    session.on("agent_speech_committed",  transcript_collector.on_agent_speech_committed)
    session.on("conversation_item_added", transcript_collector.on_conversation_item_added)

    # ── Register audio recording on track subscription ────
    @ctx.room.on("track_subscribed")
    def _on_track_subscribed(
        track: rtc.Track,
        publication: rtc.RemoteTrackPublication,
        participant: rtc.RemoteParticipant,
    ):
        audio_recorder.on_track_subscribed(track, publication, participant)

    @ctx.room.on("local_track_published")
    def _on_local_track_published(publication, track: rtc.Track | None = None):
        audio_track = track or getattr(publication, "track", None)
        if audio_track is not None:
            audio_recorder.on_local_track_published(audio_track)

    @ctx.room.on("local_track_subscribed")
    def _on_local_track_subscribed(track: rtc.Track):
        audio_recorder.on_local_track_subscribed(track)

    # Capture any tracks already present before event hooks were registered.
    audio_recorder.capture_existing_tracks(ctx.room)

    # ── Start the session ─────────────────────────────────
    await session.start(
        room=ctx.room,
        agent=StarterAgent(),
    )

    # Capture tracks that may have been published during session.start().
    audio_recorder.capture_existing_tracks(ctx.room)

    # ── Shutdown hook: persist transcript & audio ─────────
    async def _on_shutdown():
        ended_at = datetime.now(timezone.utc)
        duration = (ended_at - started_at).total_seconds()

        logger.info(
            "Voice session ending — finalizing transcript & audio for room=%s", room_name
        )

        # 1. Finalize audio recording (save WAV)
        audio_path = None
        try:
            audio_path = await audio_recorder.finalize(room_name)
            if audio_path:
                logger.info("Audio saved: %s, room=%s", audio_path, room_name)
        except Exception as exc:
            logger.error("Failed to finalize audio for room=%s: %s", room_name, exc)

        # 2. Build transcript from recorded audio, then translate to French
        french_transcript = ""
        try:
            if audio_path:
                french_transcript = await transcript_collector.finalize_from_audio(audio_path)

            if not french_transcript:
                if audio_path:
                    logger.warning(
                        "Audio transcription returned empty transcript for room=%s, "
                        "falling back to live turns.",
                        room_name,
                    )
                else:
                    logger.warning(
                        "No recording available for room=%s, falling back to live "
                        "transcript events.",
                        room_name,
                    )
                french_transcript = await transcript_collector.finalize()

            if french_transcript:
                logger.info(
                    "French transcript ready: %d chars, room=%s",
                    len(french_transcript), room_name,
                )
        except Exception as exc:
            logger.error("Failed to finalize transcript for room=%s: %s", room_name, exc)

        # 3. Retrieve escalation state — guard against pop_session_state returning None
        state = shared_state.pop_session_state(room_name) or {}
        is_escalated = state.get("escalated", False)
        escalation_reason = state.get("escalation_reason", "No reason provided")
        escalation_ticket_id = state.get("escalation_ticket_id")

        # 3b. Clear any cached live screen-analysis context for this room
        try:
            await rag_bridge.clear_support_call_screen_context(room_name)
        except Exception as exc:
            logger.debug(
                "Failed to clear support-call screen context for room=%s: %s",
                room_name,
                exc,
            )

        # 4. Always persist a row so operators can see the call happened,
        #    even when transcription/audio capture produced nothing.
        try:
            await _save_call_log(
                room_name=room_name,
                room_sid=room_sid,
                transcript=french_transcript,
                audio_file_path=audio_path,
                duration_seconds=duration,
                started_at=started_at,
                ended_at=ended_at,
                is_escalated=is_escalated,
                escalation_reason=escalation_reason,
                escalation_ticket_id=escalation_ticket_id,
            )
            logger.info(
                "VoiceCallLog saved to DB for room=%s (escalated=%s)",
                room_name, is_escalated,
            )
        except Exception as exc:
            logger.error(
                "Failed to save VoiceCallLog for room=%s: %s", room_name, exc
            )

    ctx.add_shutdown_callback(_on_shutdown)


async def _save_call_log(
    room_name: str,
    room_sid: str | None,
    transcript: str,
    audio_file_path: str | None,
    duration_seconds: float,
    started_at: datetime,
    ended_at: datetime,
    is_escalated: bool = False,
    escalation_reason: str = "",
    escalation_ticket_id: str | None = None,
) -> None:
    """Persist a VoiceCallLog, and if escalated, automatically create a support Ticket."""
    from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession, async_sessionmaker
    from sqlalchemy import select
    import sys

    # Add project root to path so we can import app.db models
    project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    if project_root not in sys.path:
        sys.path.insert(0, project_root)

    try:
        from app.db.models.voice_call_log import VoiceCallLog
        from app.db.models.ticket import Ticket
        from app.db.models.user import User
        from app.db.models.enums import (
            UserRole, UserStatus, TicketPriority, ChannelType, TicketStatus,
        )
    except ModuleNotFoundError as exc:
        logger.error(
            "Cannot persist VoiceCallLog due to missing dependency: %s. "
            "Install voice-agent dependencies with `pip install -r voice_agents/requirements.txt`.",
            exc,
        )
        if transcript:
            logger.info(
                "=== TRANSCRIPT (room=%s) ===\n%s\n=== END ===", room_name, transcript
            )
        return

    settings = get_voice_settings()
    db_url = settings.database_url
    if not db_url:
        logger.warning(
            "DATABASE_URL not set — cannot save VoiceCallLog. Logging transcript to console."
        )
        if transcript:
            logger.info(
                "=== TRANSCRIPT (room=%s) ===\n%s\n=== END ===", room_name, transcript
            )
        return

    engine = create_async_engine(db_url, echo=False)
    async_session = async_sessionmaker(engine, class_=AsyncSession, expire_on_commit=False)

    async with async_session() as session:
        # Save Voice Call Log
        log_entry = VoiceCallLog(
            room_name=room_name,
            room_sid=room_sid,
            transcript=transcript or None,
            audio_file_path=audio_file_path,
            duration_seconds=duration_seconds,
            started_at=started_at,
            ended_at=ended_at,
        )
        session.add(log_entry)

        # Handle Escalation Ticket
        if is_escalated and not escalation_ticket_id:
            # 1. Ensure system user exists
            system_email = "voice_agent_system@local"
            user_res = await session.execute(
                select(User).where(User.email == system_email)
            )
            system_user = user_res.scalar_one_or_none()

            if not system_user:
                system_user = User(
                    email=system_email,
                    full_name="Voice Agent System",
                    hashed_password="!no_login",
                    role=UserRole.CLIENT,
                    status=UserStatus.ACTIVE,
                )
                session.add(system_user)
                await session.flush()

            # 2. Build ticket description
            desc_parts = [
                f"**Escalation Reason:** {escalation_reason}",
                f"**Room Name:** {room_name}",
            ]
            if audio_file_path:
                desc_parts.append(f"**Audio Recording:** `{audio_file_path}`")
            if transcript:
                desc_parts.append("\n**--- Transcript (Translated and Summarized) ---**")
                desc_parts.append(transcript)

            ticket_desc = "\n".join(desc_parts)

            # 3. Create Ticket
            ticket = Ticket(
                subject=f"Voice Call Escalation: {room_name}",
                description=ticket_desc,
                status=TicketStatus.OPEN,
                priority=TicketPriority.HIGH,
                channel_source=ChannelType.VOICE,
                creator_id=system_user.id,
                escalation_flag=True,
            )
            session.add(ticket)

        await session.commit()

    await engine.dispose()


async def entrypoint_error_handler(ctx: agents.JobContext, exc: Exception):
    """Detect common errors like 1008 and log them clearly."""
    if "1008" in str(exc):
        logger.error(
            "CRITICAL: Gemini Realtime API permission denied (Error 1008). "
            "This usually means the API key is not enabled for Realtime sessions. "
            "Please set USE_REALTIME=false in .env to use stable pipeline mode."
        )
    else:
        logger.error("Job error in room %s: %s", ctx.room.name, exc)


def _infer_client_id_from_room_name(room_name: str) -> str | None:
    prefix = "support-call-"
    if room_name.startswith(prefix):
        inferred = room_name[len(prefix):].strip()
        return inferred or None
    return None


def main():
    """CLI entry point."""
    settings = get_voice_settings()
    logger.info(
        "Voice Agent Server — provider=%s, backend=%s, recordings=%s",
        settings.ai_provider, settings.backend_api_url, settings.voice_recordings_dir,
    )
    from livekit.agents import WorkerOptions

    agents.cli.run_app(
        WorkerOptions(
            entrypoint_fnc=entrypoint,
            port=0,
        )
    )


if __name__ == "__main__":
    main()