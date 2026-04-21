"""
In-memory store for support-call live screen-analysis context.

The frontend pushes each fresh analysis packet while screen sharing is active.
Voice agents read this context through internal API endpoints.
"""

from __future__ import annotations

from collections import deque
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from threading import Lock
from typing import Any

from app.schemas.support_call_screen_context import (
    SupportCallScreenContextEvent,
    SupportCallScreenContextIngestRequest,
)

_MAX_EVENTS_PER_ROOM = 24
_DEFAULT_TTL_SECONDS = 60 * 30


@dataclass(slots=True)
class _RoomContext:
    updated_at: datetime
    events: deque[SupportCallScreenContextEvent] = field(
        default_factory=lambda: deque(maxlen=_MAX_EVENTS_PER_ROOM)
    )


class SupportCallScreenContextStore:
    def __init__(self, ttl_seconds: int = _DEFAULT_TTL_SECONDS) -> None:
        self._ttl_seconds = max(60, int(ttl_seconds))
        self._rooms: dict[str, _RoomContext] = {}
        self._lock = Lock()

    def upsert(self, room_name: str, payload: SupportCallScreenContextIngestRequest) -> dict[str, Any]:
        normalized_room = room_name.strip()
        if not normalized_room:
            raise ValueError("room_name is required")

        analysis_text = payload.analysis_text.strip()
        if not analysis_text:
            raise ValueError("analysis_text cannot be empty")

        now = datetime.now(timezone.utc)
        recorded_at = payload.recorded_at or now
        if recorded_at.tzinfo is None:
            recorded_at = recorded_at.replace(tzinfo=timezone.utc)
        else:
            recorded_at = recorded_at.astimezone(timezone.utc)

        hints = [hint.strip() for hint in payload.assistance_hints if hint and hint.strip()]

        event = SupportCallScreenContextEvent(
            analysis_text=analysis_text,
            caption=payload.caption.strip() if payload.caption else None,
            assistance_hints=hints,
            frame_number=payload.frame_number,
            capture_mode=payload.capture_mode,
            recorded_at=recorded_at,
            session_id=payload.session_id.strip() if payload.session_id else None,
            chunk_index=payload.chunk_index,
        )

        with self._lock:
            self._cleanup_expired_locked(now)
            context = self._rooms.get(normalized_room)
            if context is None:
                context = _RoomContext(updated_at=now)
                self._rooms[normalized_room] = context

            context.updated_at = now
            context.events.appendleft(event)

            return {
                "room_name": normalized_room,
                "updated_at": context.updated_at,
                "events_stored": len(context.events),
            }

    def get_snapshot(self, room_name: str) -> dict[str, Any]:
        normalized_room = room_name.strip()
        now = datetime.now(timezone.utc)

        with self._lock:
            self._cleanup_expired_locked(now)
            context = self._rooms.get(normalized_room)
            if context is None or not context.events:
                return {
                    "room_name": normalized_room,
                    "has_context": False,
                    "updated_at": None,
                    "age_seconds": None,
                    "latest_analysis_text": None,
                    "latest_caption": None,
                    "latest_hints": [],
                    "latest_frame_number": None,
                    "latest_capture_mode": None,
                    "latest_recorded_at": None,
                    "recent_events": [],
                }

            latest = context.events[0]
            age_seconds = max(0.0, (now - context.updated_at).total_seconds())
            return {
                "room_name": normalized_room,
                "has_context": True,
                "updated_at": context.updated_at,
                "age_seconds": age_seconds,
                "latest_analysis_text": latest.analysis_text,
                "latest_caption": latest.caption,
                "latest_hints": list(latest.assistance_hints),
                "latest_frame_number": latest.frame_number,
                "latest_capture_mode": latest.capture_mode,
                "latest_recorded_at": latest.recorded_at,
                "recent_events": list(context.events),
            }

    def clear(self, room_name: str) -> bool:
        normalized_room = room_name.strip()
        if not normalized_room:
            return False

        with self._lock:
            return self._rooms.pop(normalized_room, None) is not None

    def _cleanup_expired_locked(self, now: datetime) -> None:
        if not self._rooms:
            return

        threshold = now - timedelta(seconds=self._ttl_seconds)
        stale_rooms = [
            room_name
            for room_name, context in self._rooms.items()
            if context.updated_at < threshold
        ]
        for room_name in stale_rooms:
            self._rooms.pop(room_name, None)


support_call_screen_context_store = SupportCallScreenContextStore()
