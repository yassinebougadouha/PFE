"""
Email ingestion & reply schemas.
"""

import uuid
from datetime import datetime
from typing import Literal, Optional

from pydantic import BaseModel, EmailStr, Field

from app.db.models.enums import EmailStatus


class EmailIngest(BaseModel):
    sender_address: EmailStr
    recipient_address: EmailStr
    subject: str = Field(..., max_length=500)
    body: str
    raw_headers: Optional[str] = None


class EmailResponse(BaseModel):
    id: uuid.UUID
    sender_address: str
    recipient_address: str
    subject: str
    body: str
    status: EmailStatus
    gmail_message_id: Optional[str] = None
    gmail_thread_id: Optional[str] = None
    is_outbound: bool = False
    is_read: bool = False
    is_starred: bool = False
    labels: list[str] = Field(default_factory=list)
    in_reply_to_id: Optional[uuid.UUID] = None
    replied_by_id: Optional[uuid.UUID] = None
    created_at: datetime

    model_config = {"from_attributes": True}


class EmailReplyRequest(BaseModel):
    """Request body for replying to an ingested email."""
    body: str = Field(..., min_length=1, max_length=50000, description="Reply message body")
    used_assisted_draft: bool = Field(
        default=False,
        description="Whether the reply was based on an assisted draft suggestion",
    )
    assisted_draft_edited: Optional[bool] = Field(
        default=None,
        description="Whether the assisted draft text was edited before sending",
    )
    assisted_draft_generated_at: Optional[datetime] = Field(
        default=None,
        description="Timestamp when the assisted draft suggestion was generated",
    )


class EmailComposeRequest(BaseModel):
    recipient: EmailStr
    subject: str = Field(..., min_length=1, max_length=500)
    body: str = Field(..., min_length=1, max_length=50000)
    labels: list[str] = Field(default_factory=list)


class EmailComposeResponse(BaseModel):
    id: uuid.UUID
    recipient: str
    subject: str
    body: str
    gmail_message_id: Optional[str] = None
    gmail_thread_id: Optional[str] = None
    sent_at: datetime


class EmailDeliveryStatusResponse(BaseModel):
    mail_mode: Literal["gmail", "smtp"]
    ready: bool
    gmail_connected: bool = False
    gmail_address: Optional[str] = None
    gmail_last_synced: Optional[datetime] = None
    smtp_ready: bool = False
    smtp_sender_email: Optional[str] = None
    smtp_missing_fields: list[str] = Field(default_factory=list)


class EmailFlagUpdateRequest(BaseModel):
    is_read: Optional[bool] = None
    is_starred: Optional[bool] = None
    labels: Optional[list[str]] = None


EmailBulkAction = Literal[
    "mark_read",
    "mark_unread",
    "star",
    "unstar",
    "add_label",
    "remove_label",
    "clear_labels",
]


class EmailBulkActionRequest(BaseModel):
    email_ids: list[uuid.UUID] = Field(..., min_length=1)
    action: EmailBulkAction
    label: Optional[str] = None


class EmailBulkActionResponse(BaseModel):
    action: EmailBulkAction
    updated: int


class EmailReplyResponse(BaseModel):
    """Response after sending a reply."""
    id: uuid.UUID
    original_email_id: uuid.UUID
    recipient: str
    subject: str
    body: str
    gmail_message_id: Optional[str] = None
    gmail_thread_id: Optional[str] = None
    sent_at: datetime

    model_config = {"from_attributes": True}


class EmailAssistedDraftResponse(BaseModel):
    """Generated assisted draft for an email reply."""

    original_email_id: uuid.UUID
    draft: str
    language: Optional[str] = None
    generated_at: datetime


class EmailListResponse(BaseModel):
    emails: list[EmailResponse]
    total: int
