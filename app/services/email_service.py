"""
Email service — ingest emails, trigger async conversion to ticket.
"""

import uuid
from typing import Optional

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.email import Email
from app.db.models.enums import EmailStatus
from app.schemas.email import EmailIngest


class EmailService:

    def __init__(self, db: AsyncSession):
        self.db = db

    async def ingest_email(self, payload: EmailIngest) -> Email:
        """Store an incoming email for async processing."""
        email = Email(
            sender_address=payload.sender_address,
            recipient_address=payload.recipient_address,
            subject=payload.subject,
            body=payload.body,
            raw_headers=payload.raw_headers,
            status=EmailStatus.RECEIVED,
        )
        self.db.add(email)
        await self.db.flush()
        await self.db.refresh(email)
        return email

    async def get_email(self, email_id: uuid.UUID) -> Optional[Email]:
        result = await self.db.execute(select(Email).where(Email.id == email_id))
        return result.scalar_one_or_none()

    async def update_status(self, email_id: uuid.UUID, status: EmailStatus) -> Optional[Email]:
        email = await self.get_email(email_id)
        if not email:
            return None
        email.status = status
        await self.db.flush()
        await self.db.refresh(email)
        return email
