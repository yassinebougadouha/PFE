"""
Conversation & Message schemas.
"""

import uuid
from datetime import datetime
from typing import Literal, Optional

from pydantic import BaseModel, Field

from app.db.models.enums import ChannelType, ConversationStatus, TicketPriority


# ── Conversation ──────────────────────────────────

class ConversationCreate(BaseModel):
    channel: ChannelType = ChannelType.CHAT
    subject: Optional[str] = Field(None, max_length=500)


class ConversationUpdate(BaseModel):
    status: Optional[ConversationStatus] = None
    subject: Optional[str] = Field(None, max_length=500)
    is_pinned: Optional[bool] = None


class ConversationResponse(BaseModel):
    id: uuid.UUID
    user_id: int
    channel: ChannelType
    status: ConversationStatus
    subject: Optional[str]
    is_pinned: bool
    ai_auto_reply_enabled: bool
    ai_auto_reply_paused_until: Optional[datetime] = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class ConversationListResponse(BaseModel):
    conversations: list[ConversationResponse]
    total: int


class ConversationSummaryResponse(BaseModel):
    """AI-generated summary for a conversation."""
    conversation_id: uuid.UUID
    message_count: int
    provider: str
    model: str
    problem_summary: str
    resolution_state: Literal[
        "unresolved",
        "in_progress",
        "partially_resolved",
        "resolved",
        "unknown",
    ] = "unknown"
    resolution_description: str
    next_action: str
    customer_sentiment: Literal[
        "calm",
        "frustrated",
        "urgent",
        "neutral",
        "unknown",
    ] = "unknown"
    language: Optional[str] = None
    generated_at: datetime


class ConversationAgentReplySuspensionUpdate(BaseModel):
    suspended: bool
    reason: Optional[str] = Field(None, max_length=2000)


class ConversationAgentReplySuspensionResponse(BaseModel):
    conversation_id: uuid.UUID
    agent_id: uuid.UUID
    suspended: bool
    reason: Optional[str] = None
    suspended_by_id: Optional[uuid.UUID] = None
    updated_at: Optional[datetime] = None


class ConversationAutoReplyUpdate(BaseModel):
    ai_auto_reply_enabled: bool


class ConversationAutoReplyResponse(BaseModel):
    conversation_id: uuid.UUID
    channel: ChannelType
    channel_auto_reply_enabled: bool
    ai_auto_reply_enabled: bool
    ai_auto_reply_paused_until: Optional[datetime] = None
    pause_active: bool = False
    effective_ai_auto_reply_enabled: bool
    block_reason: Optional[
        Literal["channel_disabled", "conversation_disabled", "pause_active"]
    ] = None
    assisted_draft_available: bool = True
    updated_at: datetime


class ConversationAutoReplyPauseUpdate(BaseModel):
    minutes: Optional[int] = Field(None, ge=1, le=7 * 24 * 60)
    pause_until: Optional[datetime] = None
    clear: bool = False


class ConversationAssistedDraftResponse(BaseModel):
    conversation_id: uuid.UUID
    source_message_id: uuid.UUID
    draft: str
    language: Optional[str] = None
    generated_at: datetime


class ConversationAiJobQueuedResponse(BaseModel):
    job_id: str
    job_type: Literal["summary", "assisted_draft"]
    status: Literal["queued"] = "queued"


class ConversationSummaryJobStatusResponse(BaseModel):
    job_id: str
    job_type: Literal["summary"] = "summary"
    status: Literal["queued", "started", "succeeded", "failed"]
    summary: Optional[ConversationSummaryResponse] = None
    error: Optional[str] = None


class ConversationAssistedDraftJobStatusResponse(BaseModel):
    job_id: str
    job_type: Literal["assisted_draft"] = "assisted_draft"
    status: Literal["queued", "started", "succeeded", "failed"]
    assisted_draft: Optional[ConversationAssistedDraftResponse] = None
    error: Optional[str] = None


ConversationPlaybookTriggerKey = Literal[
    "pause_active_too_long",
    "conversation_toggle_off",
    "high_risk_intent",
    "no_agent_reply_within_sla",
    "vip_customer",
]


class ConversationPlaybookTrigger(BaseModel):
    key: ConversationPlaybookTriggerKey
    reason: str
    meta: dict[str, object] = Field(default_factory=dict)


class ConversationSlaPredictorResponse(BaseModel):
    conversation_id: uuid.UUID
    channel: ChannelType
    pending_customer_message_id: Optional[uuid.UUID] = None
    pending_customer_message_at: Optional[datetime] = None
    latest_agent_reply_at: Optional[datetime] = None
    reply_due_at: Optional[datetime] = None
    seconds_remaining: Optional[int] = None
    risk_level: Literal["low", "medium", "high", "critical"] = "low"
    at_risk: bool = False
    breached: bool = False
    snoozed: bool = False
    snoozed_until: Optional[datetime] = None
    triggers: list[ConversationPlaybookTrigger] = Field(default_factory=list)
    recommended_actions: list[Literal["escalate", "assign", "snooze"]] = Field(default_factory=list)
    escalation_ticket_id: Optional[uuid.UUID] = None
    escalation_ticket_priority: Optional[TicketPriority] = None
    generated_at: datetime


class ConversationSlaEscalateRequest(BaseModel):
    note: Optional[str] = Field(None, max_length=2000)


class ConversationSlaAssignRequest(BaseModel):
    agent_id: Optional[uuid.UUID] = None


class ConversationSlaSnoozeRequest(BaseModel):
    minutes: int = Field(60, ge=5, le=7 * 24 * 60)


class ConversationSlaActionResponse(BaseModel):
    conversation_id: uuid.UUID
    action: Literal["escalate", "assign", "snooze"]
    success: bool
    ticket_id: Optional[uuid.UUID] = None
    assigned_agent_id: Optional[uuid.UUID] = None
    snoozed_until: Optional[datetime] = None
    predictor: ConversationSlaPredictorResponse


class ConversationSnippetCreate(BaseModel):
    title: str = Field(..., min_length=1, max_length=120)
    body: str = Field(..., min_length=1, max_length=8000)
    description: Optional[str] = Field(None, max_length=300)
    shortcut: Optional[str] = Field(None, max_length=32)
    channel: Optional[ChannelType] = None
    is_active: bool = True


class ConversationSnippetUpdate(BaseModel):
    title: Optional[str] = Field(None, min_length=1, max_length=120)
    body: Optional[str] = Field(None, min_length=1, max_length=8000)
    description: Optional[str] = Field(None, max_length=300)
    shortcut: Optional[str] = Field(None, max_length=32)
    channel: Optional[ChannelType] = None
    is_active: Optional[bool] = None


class ConversationSnippetResponse(BaseModel):
    id: uuid.UUID
    title: str
    body: str
    description: Optional[str] = None
    shortcut: Optional[str] = None
    channel: Optional[ChannelType] = None
    is_active: bool
    created_by_id: Optional[uuid.UUID] = None
    updated_by_id: Optional[uuid.UUID] = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class ConversationSnippetListResponse(BaseModel):
    snippets: list[ConversationSnippetResponse]
    total: int


# ── Message ───────────────────────────────────────

class MessageCreate(BaseModel):
    content: str = Field(..., min_length=1)
    is_internal: bool = False
    used_assisted_draft: bool = False
    assisted_draft_edited: bool | None = None
    assisted_draft_generated_at: Optional[datetime] = None


class ConversationStreamRequest(BaseModel):
    conversation_id: Optional[uuid.UUID] = None
    content: str = Field(..., min_length=1)
    subject: Optional[str] = Field(None, max_length=500)


class MessageResponse(BaseModel):
    id: uuid.UUID
    conversation_id: uuid.UUID
    sender_id: int
    content: str
    is_internal: bool
    is_read: bool
    attachment_filename: Optional[str] = None
    attachment_content_type: Optional[str] = None
    attachment_size: Optional[int] = None
    created_at: datetime

    model_config = {"from_attributes": True}
