"""
VoiceCallLog model — stores voice call transcripts (in French) and audio recording paths.
"""

import uuid
from datetime import datetime, timezone

from sqlalchemy import String, Text, Float, DateTime
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import Mapped, mapped_column

from app.db.base import Base, TimestampMixin, UUIDPrimaryKeyMixin


class VoiceCallLog(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    __tablename__ = "voice_call_logs"

    # ── LiveKit identifiers ──────────────────────────
    room_name: Mapped[str] = mapped_column(
        String(255), nullable=False, index=True,
    )
    room_sid: Mapped[str | None] = mapped_column(
        String(255), nullable=True, unique=True,
    )

    # ── Transcript (always in French) ────────────────
    transcript: Mapped[str | None] = mapped_column(
        Text, nullable=True,
    )

    # ── Audio recording ──────────────────────────────
    audio_file_path: Mapped[str | None] = mapped_column(
        String(1024), nullable=True,
    )

    # ── Timing ───────────────────────────────────────
    duration_seconds: Mapped[float | None] = mapped_column(
        Float, nullable=True,
    )
    started_at: Mapped[datetime] = mapped_column(
        DateTime(timezone=True),
        default=lambda: datetime.now(timezone.utc),
        nullable=False,
    )
    ended_at: Mapped[datetime | None] = mapped_column(
        DateTime(timezone=True), nullable=True,
    )
