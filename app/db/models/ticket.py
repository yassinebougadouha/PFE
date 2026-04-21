"""
Ticket model — support ticketing channel.
"""

import uuid

from sqlalchemy import String, Text, Boolean, Enum, ForeignKey, Index, DateTime
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base, TimestampMixin, SoftDeleteMixin, UUIDPrimaryKeyMixin
from app.db.models.enums import TicketStatus, TicketPriority, ChannelType


class Ticket(Base, UUIDPrimaryKeyMixin, TimestampMixin, SoftDeleteMixin):
    __tablename__ = "tickets"

    subject: Mapped[str] = mapped_column(String(500), nullable=False)
    description: Mapped[str] = mapped_column(Text, nullable=False)
    status: Mapped[TicketStatus] = mapped_column(
        Enum(TicketStatus, name="ticket_status", create_constraint=True),
        default=TicketStatus.OPEN,
        nullable=False,
        index=True,
    )
    priority: Mapped[TicketPriority] = mapped_column(
        Enum(TicketPriority, name="ticket_priority", create_constraint=True),
        default=TicketPriority.MEDIUM,
        nullable=False,
    )
    channel_source: Mapped[ChannelType] = mapped_column(
        Enum(ChannelType, name="channel_type", create_constraint=True, create_type=False),
        default=ChannelType.TICKET,
        nullable=False,
    )

    # Sprint 2 placeholder
    escalation_flag: Mapped[bool] = mapped_column(Boolean, default=False, nullable=False)
    resolution_note: Mapped[str | None] = mapped_column(Text, nullable=True)
    resolved_at: Mapped[object | None] = mapped_column(DateTime(timezone=True), nullable=True)

    # ── Foreign keys ──────────────────────────────
    creator_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=False, index=True,
    )
    assigned_agent_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=True, index=True,
    )
    source_email_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("emails.id"), nullable=True,
    )
    conversation_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("conversations.id"), nullable=True,
    )
    source_voice_call_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("voice_call_logs.id"), nullable=True, index=True,
    )
    solved_by_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=True, index=True,
    )

    # ── Relationships ──────────────────────────────
    creator = relationship("User", back_populates="created_tickets", foreign_keys=[creator_id])
    assigned_agent = relationship("User", back_populates="assigned_tickets", foreign_keys=[assigned_agent_id])
    solved_by = relationship("User", back_populates="solved_tickets", foreign_keys=[solved_by_id])
    source_email = relationship("Email", back_populates="ticket", uselist=False)
    conversation = relationship("Conversation")
    source_voice_call = relationship("VoiceCallLog")

    __table_args__ = (
        Index("ix_tickets_status_priority", "status", "priority"),
        Index("ix_tickets_agent_status", "assigned_agent_id", "status"),
        Index("ix_tickets_deleted_created", "is_deleted", "created_at"),
        Index("ix_tickets_deleted_status_created", "is_deleted", "status", "created_at"),
    )
