"""
Gmail integration schemas.
"""

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel


class GmailAuthURL(BaseModel):
    authorization_url: str


class GmailStatusResponse(BaseModel):
    connected: bool
    gmail_address: Optional[str] = None
    is_active: bool = False
    last_synced: Optional[datetime] = None

    model_config = {"from_attributes": True}


class GmailSyncResult(BaseModel):
    emails_fetched: int
    emails_ingested: int
    errors: int


class GmailCredentialResponse(BaseModel):
    id: uuid.UUID
    user_id: uuid.UUID
    gmail_address: str
    is_active: bool
    last_history_id: Optional[str]
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}
