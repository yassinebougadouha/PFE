"""
WhatsApp schemas — request / response models for WhatsApp endpoints.
"""

import uuid
from datetime import datetime
from typing import Optional, Literal

from pydantic import BaseModel, Field


# ── Incoming webhook (Meta Cloud API format) ─────────────

class WhatsAppWebhookVerify(BaseModel):
    """Query params for Meta webhook verification."""
    hub_mode: str = Field(alias="hub.mode")
    hub_verify_token: str = Field(alias="hub.verify_token")
    hub_challenge: str = Field(alias="hub.challenge")


class WhatsAppProfile(BaseModel):
    name: str


class WhatsAppContact(BaseModel):
    profile: WhatsAppProfile
    wa_id: str


class WhatsAppText(BaseModel):
    body: str


class WhatsAppIncomingMessage(BaseModel):
    from_: str = Field(alias="from")
    id: str
    timestamp: str
    type: str
    text: Optional[WhatsAppText] = None


class WhatsAppMetadataPayload(BaseModel):
    display_phone_number: str
    phone_number_id: str


class WhatsAppValue(BaseModel):
    messaging_product: str
    metadata: WhatsAppMetadataPayload
    contacts: Optional[list[WhatsAppContact]] = None
    messages: Optional[list[WhatsAppIncomingMessage]] = None


class WhatsAppChange(BaseModel):
    value: WhatsAppValue
    field: str


class WhatsAppEntry(BaseModel):
    id: str
    changes: list[WhatsAppChange]


class WhatsAppWebhookPayload(BaseModel):
    """Full webhook POST body from Meta Cloud API."""
    object: str
    entry: list[WhatsAppEntry]


# ── Bridge incoming format ───────────────────────────────

class BridgeIncomingMessage(BaseModel):
    """Message format from the WhatsApp Web bridge."""
    from_number: str = Field(..., description="Sender phone number with country code")
    to_number: str = Field(..., description="Recipient (your number)")
    body: str = Field(..., description="Message text")
    message_id: Optional[str] = None
    timestamp: Optional[str] = None
    sender_name: Optional[str] = None


# ── Send / Reply ─────────────────────────────────────────

class WhatsAppSendRequest(BaseModel):
    """Send a message to a WhatsApp number."""
    to_number: str = Field(..., description="Recipient phone number (e.g. '216XXXXXXXX')", min_length=8, max_length=20)
    message: str = Field(..., min_length=1, max_length=4096)


class WhatsAppReplyRequest(BaseModel):
    """Reply in a WhatsApp conversation."""
    message: str = Field(..., min_length=1, max_length=4096)


class WhatsAppSendResult(BaseModel):
    """Response after sending a WhatsApp message."""
    success: bool
    message_id: Optional[str] = None
    provider: str  # "meta" or "bridge"
    error: Optional[str] = None


class WhatsAppConversationResult(BaseModel):
    """Response when an incoming message creates/updates a conversation."""
    message: str = "WhatsApp message received"
    conversation_id: uuid.UUID
    message_id: uuid.UUID
    sender: str
    text: str


# ── Status ───────────────────────────────────────────────

class WhatsAppStatusResponse(BaseModel):
    provider: str
    configured: bool
    details: dict = {}


# ── Inbox / unread ───────────────────────────────────────

class WhatsAppMessageItem(BaseModel):
    """Single message in inbox view."""
    id: uuid.UUID
    conversation_id: uuid.UUID
    sender_id: uuid.UUID
    sender_name: Optional[str] = None
    sender_phone: Optional[str] = None
    content: str
    is_read: bool
    created_at: datetime

    model_config = {"from_attributes": True}


class WhatsAppConversationInbox(BaseModel):
    """Conversation summary for inbox list."""
    id: uuid.UUID
    user_id: uuid.UUID
    contact_name: Optional[str] = None
    contact_phone: Optional[str] = None
    subject: Optional[str] = None
    status: str
    unread_count: int = 0
    last_message: Optional[str] = None
    last_message_at: Optional[datetime] = None
    created_at: datetime
    updated_at: datetime


class WhatsAppInboxResponse(BaseModel):
    """Paginated inbox response."""
    conversations: list[WhatsAppConversationInbox]
    total: int


class WhatsAppConversationDetail(BaseModel):
    """Full conversation with messages."""
    id: uuid.UUID
    user_id: uuid.UUID
    contact_name: Optional[str] = None
    contact_phone: Optional[str] = None
    subject: Optional[str] = None
    status: str
    messages: list[WhatsAppMessageItem]
    total_messages: int


class MarkReadRequest(BaseModel):
    """Mark messages as read."""
    message_ids: Optional[list[uuid.UUID]] = None  # if None, mark all in conversation
