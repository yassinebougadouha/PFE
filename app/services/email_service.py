"""
Email service — ingest emails, trigger async conversion to ticket.
"""

import uuid
from typing import Optional

from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.email import Email
from app.db.models.enums import EmailStatus
from app.schemas.email import EmailIngest
from app.utils.mail_content import normalize_email_subject, normalize_mail_like_text


class EmailService:
    LOCAL_THREAD_PREFIX = "local-thread-"

    def __init__(self, db: AsyncSession):
        self.db = db

    async def ingest_email(self, payload: EmailIngest) -> Email:
        """Store an incoming email for async processing."""
        cleaned_subject = normalize_email_subject(payload.subject)
        cleaned_body = normalize_mail_like_text(payload.body) or "(empty)"

        email = Email(
            sender_address=payload.sender_address,
            recipient_address=payload.recipient_address,
            subject=cleaned_subject,
            body=cleaned_body,
            raw_headers=payload.raw_headers,
            status=EmailStatus.RECEIVED,
            is_outbound=False,
            is_read=False,
            is_starred=False,
            labels=["inbox"],
        )
        self.db.add(email)
        await self.db.flush()
        await self.db.refresh(email)
        return email

    async def list_emails(
        self,
        *,
        folder: str = "inbox",
        status: Optional[EmailStatus] = None,
        search: Optional[str] = None,
        unread_only: bool = False,
        starred_only: bool = False,
        label: Optional[str] = None,
        skip: int = 0,
        limit: int = 50,
    ) -> tuple[list[Email], int]:
        filters = []

        if folder == "inbox":
            filters.append(Email.is_outbound.is_(False))
        elif folder == "sent":
            filters.append(Email.is_outbound.is_(True))

        if status is not None:
            filters.append(Email.status == status)

        if unread_only:
            filters.append(Email.is_read.is_(False))

        if starred_only:
            filters.append(Email.is_starred.is_(True))

        normalized_label = self.normalize_labels([label] if label else [])
        if normalized_label:
            filters.append(Email.labels.contains(normalized_label))

        normalized_search = (search or "").strip()
        if normalized_search:
            term = f"%{normalized_search}%"
            filters.append(
                or_(
                    Email.subject.ilike(term),
                    Email.body.ilike(term),
                    Email.sender_address.ilike(term),
                    Email.recipient_address.ilike(term),
                    Email.gmail_thread_id.ilike(term),
                    Email.gmail_message_id.ilike(term),
                )
            )

        list_stmt = select(Email)
        count_stmt = select(func.count()).select_from(Email)

        if filters:
            list_stmt = list_stmt.where(*filters)
            count_stmt = count_stmt.where(*filters)

        list_stmt = list_stmt.order_by(Email.created_at.desc()).offset(skip).limit(limit)

        emails = list((await self.db.execute(list_stmt)).scalars().all())
        total = int((await self.db.execute(count_stmt)).scalar() or 0)
        return emails, total

    @staticmethod
    def normalize_labels(labels: list[str]) -> list[str]:
        unique: list[str] = []
        seen = set()
        for raw in labels:
            label = (raw or "").strip().lower()
            if not label:
                continue
            if len(label) > 64:
                label = label[:64]
            if label in seen:
                continue
            seen.add(label)
            unique.append(label)
        return unique

    async def update_flags(
        self,
        email_id: uuid.UUID,
        *,
        is_read: Optional[bool] = None,
        is_starred: Optional[bool] = None,
        labels: Optional[list[str]] = None,
    ) -> Optional[Email]:
        email = await self.get_email(email_id)
        if not email:
            return None

        if is_read is not None:
            email.is_read = is_read
        if is_starred is not None:
            email.is_starred = is_starred
        if labels is not None:
            email.labels = self.normalize_labels(labels)

        await self.db.flush()
        await self.db.refresh(email)
        return email

    async def apply_bulk_action(
        self,
        *,
        email_ids: list[uuid.UUID],
        action: str,
        label: Optional[str] = None,
    ) -> int:
        if not email_ids:
            return 0

        result = await self.db.execute(select(Email).where(Email.id.in_(email_ids)))
        emails = list(result.scalars().all())
        if not emails:
            return 0

        normalized_label = self.normalize_labels([label] if label else [])
        action_label = normalized_label[0] if normalized_label else None

        for email in emails:
            current_labels = self.normalize_labels(email.labels or [])

            if action == "mark_read":
                email.is_read = True
            elif action == "mark_unread":
                email.is_read = False
            elif action == "star":
                email.is_starred = True
            elif action == "unstar":
                email.is_starred = False
            elif action == "add_label" and action_label:
                email.labels = self.normalize_labels([*current_labels, action_label])
            elif action == "remove_label" and action_label:
                email.labels = [existing for existing in current_labels if existing != action_label]
            elif action == "clear_labels":
                email.labels = []

        await self.db.flush()
        return len(emails)

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

    async def ensure_thread_id(self, email: Email) -> str:
        if email.gmail_thread_id:
            return email.gmail_thread_id

        if not email.id:
            await self.db.flush()

        email.gmail_thread_id = f"{self.LOCAL_THREAD_PREFIX}{email.id}"
        await self.db.flush()
        return email.gmail_thread_id

    async def create_outbound_email(
        self,
        *,
        sender_address: str,
        recipient_address: str,
        subject: str,
        body: str,
        labels: Optional[list[str]] = None,
        gmail_message_id: Optional[str] = None,
        gmail_thread_id: Optional[str] = None,
        in_reply_to_id: Optional[uuid.UUID] = None,
        replied_by_id: Optional[uuid.UUID] = None,
    ) -> Email:
        cleaned_subject = normalize_email_subject(subject)
        cleaned_body = normalize_mail_like_text(body) or "(empty)"
        normalized_labels = self.normalize_labels(["sent", *(labels or [])])

        email = Email(
            sender_address=sender_address[:320],
            recipient_address=recipient_address[:320],
            subject=cleaned_subject[:500],
            body=cleaned_body,
            gmail_message_id=gmail_message_id,
            gmail_thread_id=gmail_thread_id,
            is_outbound=True,
            is_read=True,
            is_starred=False,
            labels=normalized_labels,
            in_reply_to_id=in_reply_to_id,
            replied_by_id=replied_by_id,
            status=EmailStatus.REPLIED,
        )
        self.db.add(email)
        await self.db.flush()
        await self.db.refresh(email)
        return email
