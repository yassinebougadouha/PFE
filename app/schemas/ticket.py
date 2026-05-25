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
    conversation_id: Optional[uuid.UUID] = None
    source_voice_call_id: Optional[uuid.UUID] = None
    # GLPI sync options
    glpi_category_id: Optional[int] = None
    glpi_requester_id: Optional[int] = None


class GlpiTicketIngestRequest(BaseModel):
    glpi_ticket_id: int = Field(..., ge=1)
    subject: str = Field(..., min_length=1, max_length=500)
    description: str = Field(..., min_length=1)
    priority: TicketPriority = TicketPriority.MEDIUM
    channel_source: ChannelType = ChannelType.TICKET
    creator_email: Optional[str] = None
    creator_name: Optional[str] = None


class TicketUpdate(BaseModel):
    subject: Optional[str] = None
    description: Optional[str] = None
    status: Optional[TicketStatus] = None
    priority: Optional[TicketPriority] = None
    assigned_agent_id: Optional[int] = None
    escalation_flag: Optional[bool] = None
    resolution_note: Optional[str] = None
    source_voice_call_id: Optional[uuid.UUID] = None


class TicketStatusUpdate(BaseModel):
    status: TicketStatus
    resolution_note: Optional[str] = Field(None, min_length=5)


class TicketResponse(BaseModel):
    id: uuid.UUID
    subject: str
    description: str
    status: TicketStatus
    priority: TicketPriority
    channel_source: ChannelType
    escalation_flag: bool
    resolution_note: Optional[str] = None
    creator_id: int
    assigned_agent_id: Optional[int]
    source_email_id: Optional[uuid.UUID]
    conversation_id: Optional[uuid.UUID]
    source_voice_call_id: Optional[uuid.UUID]
    solved_by_id: Optional[int]
    resolved_at: Optional[datetime]
    # GLPI sync info
    glpi_ticket_id: Optional[int] = None
    glpi_sync_status: str = "pending"
    glpi_sync_error: Optional[str] = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class TicketListResponse(BaseModel):
    tickets: list[TicketResponse]
    total: int


class TicketTotalsResponse(BaseModel):
    total: int
    open: int
    in_progress: int
    escalated: int
    resolved: int
    closed: int


class TicketGlpiSyncResponse(BaseModel):
    """Response for manual GLPI sync operations."""
    success: bool
    ticket_id: uuid.UUID
    glpi_ticket_id: Optional[int] = None
    sync_status: str
    message: str
    error: Optional[str] = None

    model_config = {"from_attributes": True}
