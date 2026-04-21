"""
Schemas for live support-call screen-sharing context.

This data is ingested from the authenticated frontend call page and consumed
by internal services (voice agents) to answer questions like "what do you see?".
"""

from __future__ import annotations

from datetime import datetime
from typing import Literal

from pydantic import BaseModel, Field


class SupportCallScreenContextIngestRequest(BaseModel):
    analysis_text: str = Field(..., min_length=1, max_length=4000)
    caption: str | None = Field(default=None, max_length=2000)
    assistance_hints: list[str] = Field(default_factory=list)
    frame_number: int | None = Field(default=None, ge=1)
    capture_mode: Literal["chunk", "frame"] | None = None
    recorded_at: datetime | None = None
    session_id: str | None = Field(default=None, max_length=128)
    chunk_index: int | None = Field(default=None, ge=1)


class SupportCallScreenContextEvent(BaseModel):
    analysis_text: str
    caption: str | None = None
    assistance_hints: list[str] = Field(default_factory=list)
    frame_number: int | None = None
    capture_mode: Literal["chunk", "frame"] | None = None
    recorded_at: datetime
    session_id: str | None = None
    chunk_index: int | None = None


class SupportCallScreenContextIngestResponse(BaseModel):
    room_name: str
    updated_at: datetime
    events_stored: int


class SupportCallScreenContextSnapshotResponse(BaseModel):
    room_name: str
    has_context: bool
    updated_at: datetime | None = None
    age_seconds: float | None = None

    latest_analysis_text: str | None = None
    latest_caption: str | None = None
    latest_hints: list[str] = Field(default_factory=list)
    latest_frame_number: int | None = None
    latest_capture_mode: Literal["chunk", "frame"] | None = None
    latest_recorded_at: datetime | None = None

    recent_events: list[SupportCallScreenContextEvent] = Field(default_factory=list)


class SupportCallScreenContextClearResponse(BaseModel):
    room_name: str
    cleared: bool
