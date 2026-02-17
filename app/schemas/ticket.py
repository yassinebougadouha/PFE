"""
Ticket schemas.
"""

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field

from app.db.models.enums import TicketStatus, TicketPriority, ChannelType


class TicketCreate(BaseModel):
    subject: str = Field(..., min_length=1, max_length=500)
    description: str = Field(..., min_length=1)
    priority: TicketPriority = TicketPriority.MEDIUM
    channel_source: ChannelType = ChannelType.TICKET


class TicketUpdate(BaseModel):
    subject: Optional[str] = None
    description: Optional[str] = None
    status: Optional[TicketStatus] = None
    priority: Optional[TicketPriority] = None
    assigned_agent_id: Optional[uuid.UUID] = None
    escalation_flag: Optional[bool] = None


class TicketResponse(BaseModel):
    id: uuid.UUID
    subject: str
    description: str
    status: TicketStatus
    priority: TicketPriority
    channel_source: ChannelType
    escalation_flag: bool
    creator_id: uuid.UUID
    assigned_agent_id: Optional[uuid.UUID]
    source_email_id: Optional[uuid.UUID]
    conversation_id: Optional[uuid.UUID]
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class TicketListResponse(BaseModel):
    tickets: list[TicketResponse]
    total: int
