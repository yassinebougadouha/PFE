"""
Email model — email ingestion channel.
Emails are auto-converted to tickets via Celery tasks.
Supports Gmail threading via gmail_message_id and thread_id.
"""

import uuid
from datetime import datetime

from sqlalchemy import Boolean, String, Text, Enum, ForeignKey, Index
from sqlalchemy.dialects.postgresql import UUID, JSONB
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base, TimestampMixin, UUIDPrimaryKeyMixin
from app.db.models.enums import EmailStatus


class Email(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    __tablename__ = "emails"

    sender_address: Mapped[str] = mapped_column(String(320), nullable=False, index=True)
    recipient_address: Mapped[str] = mapped_column(String(320), nullable=False)
    subject: Mapped[str] = mapped_column(String(500), nullable=False)
    body: Mapped[str] = mapped_column(Text, nullable=False)
    raw_headers: Mapped[str | None] = mapped_column(Text, nullable=True)
    status: Mapped[EmailStatus] = mapped_column(
        Enum(EmailStatus, name="email_status", native_enum=False, create_constraint=True),
        default=EmailStatus.RECEIVED,
        nullable=False,
    )

    # Gmail-specific fields for threading & reply
    gmail_message_id: Mapped[str | None] = mapped_column(String(255), nullable=True, unique=True)
    gmail_thread_id: Mapped[str | None] = mapped_column(String(255), nullable=True, index=True)
    is_outbound: Mapped[bool] = mapped_column(Boolean, default=False, nullable=False)
    is_read: Mapped[bool] = mapped_column(Boolean, default=False, nullable=False, index=True)
    is_starred: Mapped[bool] = mapped_column(Boolean, default=False, nullable=False, index=True)
    labels: Mapped[list[str]] = mapped_column(JSONB, default=list, nullable=False)

    # Self-referential: reply points to the original email
    in_reply_to_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("emails.id"), nullable=True,
    )
    replied_by_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=True,
    )

    # Relationships
    ticket = relationship("Ticket", back_populates="source_email", uselist=False)
    original_email = relationship("Email", remote_side="Email.id", foreign_keys=[in_reply_to_id])
    replied_by = relationship("User", foreign_keys=[replied_by_id])

    __table_args__ = (
        Index("ix_emails_sender_status", "sender_address", "status"),
        Index("ix_emails_gmail_thread", "gmail_thread_id"),
        Index("ix_emails_read_starred", "is_read", "is_starred"),
        Index("ix_emails_outbound_created_replied_by", "is_outbound", "created_at", "replied_by_id"),
    )
