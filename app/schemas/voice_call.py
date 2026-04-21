"""
Schemas for Voice Call Logs API.
"""

import uuid
from datetime import datetime
from typing import Literal, Optional

from pydantic import BaseModel, ConfigDict, Field

from app.db.models.enums import TicketPriority


class VoiceCallLogResponse(BaseModel):
    """Serialization schema for VoiceCallLog."""
    id: uuid.UUID
    room_name: str
    room_sid: Optional[str] = None
    transcript: Optional[str] = None
    audio_file_path: Optional[str] = None
    duration_seconds: Optional[float] = None
    started_at: datetime
    ended_at: Optional[datetime] = None
    created_at: datetime
    updated_at: datetime

    model_config = ConfigDict(from_attributes=True)


class VoiceCallLogListResponse(BaseModel):
    """Pagination envelope."""
    items: list[VoiceCallLogResponse]
    total: int
    skip: int
    limit: int


class VoiceCallActionItem(BaseModel):
    title: str = Field(..., min_length=3, max_length=220)
    owner: Literal["agent", "client", "system"] = "agent"
    priority: Literal["low", "medium", "high"] = "medium"


class VoiceCallPostCallSummaryRequest(BaseModel):
    max_transcript_chars: int = Field(12_000, ge=1_000, le=40_000)


class VoiceCallPostCallSummaryResponse(BaseModel):
    call_id: uuid.UUID
    room_name: str
    provider: str
    model: str
    summary: str
    customer_issue: str
    resolution_status: Literal["unresolved", "in_progress", "resolved", "unknown"]
    follow_up_recommendation: str
    action_items: list[VoiceCallActionItem] = Field(default_factory=list)
    ticket_subject_suggestion: str
    ticket_description_suggestion: str
    generated_at: datetime


class VoiceCallTicketLinkRequest(BaseModel):
    ticket_id: Optional[uuid.UUID] = None
    subject: Optional[str] = Field(None, min_length=3, max_length=500)
    description: Optional[str] = Field(None, min_length=5)
    priority: TicketPriority = TicketPriority.MEDIUM


class VoiceCallTicketLinkResponse(BaseModel):
    call_id: uuid.UUID
    ticket_id: uuid.UUID
    ticket_subject: str
    link_type: Literal["created", "attached"]
    source_voice_call_id: uuid.UUID
