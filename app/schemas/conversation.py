"""
Conversation & Message schemas.
"""

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field

from app.db.models.enums import ConversationStatus, ChannelType


# ── Conversation ──────────────────────────────────

class ConversationCreate(BaseModel):
    channel: ChannelType = ChannelType.CHAT
    subject: Optional[str] = Field(None, max_length=500)


class ConversationUpdate(BaseModel):
    status: Optional[ConversationStatus] = None
    subject: Optional[str] = None


class ConversationResponse(BaseModel):
    id: uuid.UUID
    user_id: uuid.UUID
    channel: ChannelType
    status: ConversationStatus
    subject: Optional[str]
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ── Message ───────────────────────────────────────

class MessageCreate(BaseModel):
    content: str = Field(..., min_length=1)
    is_internal: bool = False


class MessageResponse(BaseModel):
    id: uuid.UUID
    conversation_id: uuid.UUID
    sender_id: uuid.UUID
    content: str
    is_internal: bool
    is_read: bool
    created_at: datetime

    model_config = {"from_attributes": True}
