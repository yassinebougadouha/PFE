"""
Service layer for VoiceCallLog table reads.
"""

import uuid
import re
import wave
import logging
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Optional

from sqlalchemy import select, func, or_
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.db.models.voice_call_log import VoiceCallLog


settings = get_settings()
_RECORDING_EXTENSIONS = {".wav", ".mp3", ".m4a", ".ogg", ".webm"}
_FILENAME_TIMESTAMP_PATTERN = re.compile(r"^(?P<room>.+)_(?P<date>\d{8})_(?P<time>\d{6})$")
logger = logging.getLogger(__name__)


class VoiceCallService:

    def __init__(self, db: AsyncSession):
        self.db = db

    async def list_calls(self, skip: int = 0, limit: int = 50) -> tuple[list[VoiceCallLog], int]:
        """List and count voice call logs (newest first)."""
        query = select(VoiceCallLog)
        count_q = select(func.count(VoiceCallLog.id))

        await self._backfill_from_recordings()
        total = (await self.db.execute(count_q)).scalar() or 0

        await self._enrich_missing_durations()

        result = await self.db.execute(
            query.offset(skip).limit(limit).order_by(VoiceCallLog.created_at.desc())
        )
        return list(result.scalars().all()), total

    async def get_call(
        self,
        call_id: uuid.UUID,
        enrich_transcript: bool = True,
    ) -> Optional[VoiceCallLog]:
        """Fetch a single voice call log by ID."""
        result = await self.db.execute(
            select(VoiceCallLog).where(VoiceCallLog.id == call_id)
        )
        call = result.scalar_one_or_none()
        if not call:
            return None

        updated = await self._enrich_call(call, enrich_transcript=enrich_transcript)
        if updated:
            await self.db.commit()
            await self.db.refresh(call)

        return call

    async def _backfill_from_recordings(self) -> int:
        recordings_dir = self._recordings_dir()
        if not recordings_dir.exists() or not recordings_dir.is_dir():
            return 0

        existing_result = await self.db.execute(
            select(VoiceCallLog.audio_file_path).where(VoiceCallLog.audio_file_path.is_not(None))
        )
        existing_paths: set[str] = set()
        existing_filenames: set[str] = set()
        for value in existing_result.scalars().all():
            if not value:
                continue
            existing_paths.add(str(self._resolve_recording_path(value)))
            existing_filenames.add(Path(value).name)

        recording_files = sorted(
            (
                path
                for path in recordings_dir.iterdir()
                if path.is_file() and path.suffix.lower() in _RECORDING_EXTENSIONS
            ),
            key=lambda path: path.stat().st_mtime,
            reverse=True,
        )

        created = 0
        for recording_path in recording_files:
            absolute_path = str(recording_path.resolve())
            if absolute_path in existing_paths or recording_path.name in existing_filenames:
                continue

            started_at = self._infer_started_at(recording_path)
            duration_seconds = self._infer_duration_seconds(recording_path)
            ended_at = (
                started_at + timedelta(seconds=duration_seconds)
                if duration_seconds is not None
                else None
            )

            self.db.add(
                VoiceCallLog(
                    room_name=self._infer_room_name(recording_path),
                    room_sid=None,
                    transcript=None,
                    audio_file_path=absolute_path,
                    duration_seconds=duration_seconds,
                    started_at=started_at,
                    ended_at=ended_at,
                )
            )
            existing_paths.add(absolute_path)
            existing_filenames.add(recording_path.name)
            created += 1

        if created:
            await self.db.commit()

        return created

    async def _enrich_missing_durations(self) -> None:
        result = await self.db.execute(
            select(VoiceCallLog).where(
                VoiceCallLog.audio_file_path.is_not(None),
                or_(
                    VoiceCallLog.duration_seconds.is_(None),
                    VoiceCallLog.ended_at.is_(None),
                ),
            ).limit(100)
        )

        updated = False
        for call in result.scalars().all():
            updated = self._hydrate_duration_fields(call) or updated

        if updated:
            await self.db.commit()

    async def _enrich_call(self, call: VoiceCallLog, enrich_transcript: bool) -> bool:
        updated = self._hydrate_duration_fields(call)

        if (
            enrich_transcript
            and call.audio_file_path
            and (not call.transcript or not call.transcript.strip())
        ):
            transcript = await self._generate_transcript(
                call.audio_file_path,
                room_name=call.room_name,
            )
            if transcript:
                call.transcript = transcript
                updated = True

        return updated

    def _hydrate_duration_fields(self, call: VoiceCallLog) -> bool:
        if not call.audio_file_path:
            return False

        recording_path = self._resolve_recording_path(call.audio_file_path)
        if not recording_path.exists() or not recording_path.is_file():
            return False

        updated = False
        duration_seconds = call.duration_seconds
        if duration_seconds is None:
            duration_seconds = self._infer_duration_seconds(recording_path)
            if duration_seconds is not None:
                call.duration_seconds = duration_seconds
                updated = True

        if call.ended_at is None and duration_seconds is not None:
            call.ended_at = call.started_at + timedelta(seconds=duration_seconds)
            updated = True

        return updated

    async def _generate_transcript(self, audio_file_path: str, room_name: str | None = None) -> str | None:
        recording_path = self._resolve_recording_path(audio_file_path)
        if not recording_path.exists() or not recording_path.is_file():
            return None

        try:
            from voice_agents.call_transcript import CallTranscriptCollector

            collector = CallTranscriptCollector()
            collector.set_client_id(self._infer_client_id_from_room_name(room_name))
            transcript = await collector.finalize_from_audio(str(recording_path))
            cleaned = transcript.strip()
            return cleaned or None
        except Exception as exc:
            logger.warning(
                "Failed to enrich transcript from recording %s: %s",
                recording_path,
                exc,
            )
            return None

    @staticmethod
    def _resolve_recording_path(audio_file_path: str) -> Path:
        candidate = Path(audio_file_path)
        if candidate.exists():
            return candidate

        configured_candidate = VoiceCallService._recordings_dir() / candidate.name
        if candidate.name and configured_candidate.exists():
            return configured_candidate

        if candidate.is_absolute():
            return candidate

        return (Path.cwd() / candidate).resolve()

    @staticmethod
    def _recordings_dir() -> Path:
        recordings_dir = Path(settings.VOICE_RECORDINGS_DIR)
        if recordings_dir.is_absolute():
            return recordings_dir

        return (Path.cwd() / recordings_dir).resolve()

    @staticmethod
    def _infer_client_id_from_room_name(room_name: str | None) -> str | None:
        if not room_name:
            return None

        prefix = "support-call-"
        if room_name.startswith(prefix):
            inferred = room_name[len(prefix):].strip()
            return inferred or None

        return None

    @staticmethod
    def _infer_room_name(recording_path: Path) -> str:
        stem = recording_path.stem
        match = _FILENAME_TIMESTAMP_PATTERN.match(stem)
        if not match:
            return stem
        return match.group("room")

    @staticmethod
    def _infer_started_at(recording_path: Path) -> datetime:
        match = _FILENAME_TIMESTAMP_PATTERN.match(recording_path.stem)
        if match:
            try:
                parsed = datetime.strptime(
                    f"{match.group('date')}{match.group('time')}",
                    "%Y%m%d%H%M%S",
                )
                return parsed.replace(tzinfo=timezone.utc)
            except ValueError:
                pass

        return datetime.fromtimestamp(recording_path.stat().st_mtime, tz=timezone.utc)

    @staticmethod
    def _infer_duration_seconds(recording_path: Path) -> float | None:
        if recording_path.suffix.lower() != ".wav":
            return None

        try:
            with wave.open(str(recording_path), "rb") as wav_file:
                frame_rate = wav_file.getframerate()
                total_frames = wav_file.getnframes()
                if frame_rate <= 0:
                    return None
                return total_frames / float(frame_rate)
        except Exception:
            return None
