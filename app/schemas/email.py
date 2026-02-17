"""
Email ingestion & reply schemas.
"""

import uuid
from datetime import datetime
from typing import Optional

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
    in_reply_to_id: Optional[uuid.UUID] = None
    replied_by_id: Optional[uuid.UUID] = None
    created_at: datetime

    model_config = {"from_attributes": True}


class EmailReplyRequest(BaseModel):
    """Request body for replying to an ingested email."""
    body: str = Field(..., min_length=1, max_length=50000, description="Reply message body")


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
