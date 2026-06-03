"""
Conversation & Message models — Chat channel.
"""

from datetime import datetime
import uuid

from sqlalchemy import BigInteger, Boolean, String, Text, Enum, ForeignKey, Index, Integer, UniqueConstraint, DateTime
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base, TimestampMixin, SoftDeleteMixin, UUIDPrimaryKeyMixin
from app.db.models.enums import ConversationStatus, ChannelType


class Conversation(Base, UUIDPrimaryKeyMixin, TimestampMixin, SoftDeleteMixin):
    __tablename__ = "conversations"

    user_id: Mapped[int] = mapped_column(
        BigInteger, ForeignKey("users.id"), nullable=False, index=True,
    )
    channel: Mapped[ChannelType] = mapped_column(
        Enum(ChannelType, name="channel_type", native_enum=False, create_constraint=True),
        default=ChannelType.CHAT,
        nullable=False,
    )
    status: Mapped[ConversationStatus] = mapped_column(
        Enum(ConversationStatus, name="conversation_status", native_enum=False, create_constraint=True),
        default=ConversationStatus.OPEN,
        nullable=False,
    )
    subject: Mapped[str | None] = mapped_column(String(500), nullable=True)
    is_pinned: Mapped[bool] = mapped_column(Boolean, default=False, nullable=False, index=True)
    ai_auto_reply_enabled: Mapped[bool] = mapped_column(
        Boolean,
        default=True,
        nullable=False,
        index=True,
    )
    ai_auto_reply_paused_until: Mapped[datetime | None] = mapped_column(
        DateTime(timezone=True),
        nullable=True,
        index=True,
    )
    sla_snoozed_until: Mapped[datetime | None] = mapped_column(
        DateTime(timezone=True),
        nullable=True,
        index=True,
    )

    # Future-ready: placeholder fields for decision engine (Sprint 2)
    # confidence_score, risk_score, decision_outcome will be added

    # ── Relationships ──────────────────────────────
    user = relationship("User", back_populates="conversations")
    messages = relationship("Message", back_populates="conversation", lazy="selectin", order_by="Message.created_at")

    __table_args__ = (
        Index("ix_conversations_user_status", "user_id", "status"),
        Index("ix_conversations_user_pinned_updated", "user_id", "is_pinned", "updated_at"),
        Index(
            "ix_conversations_deleted_channel_pinned_updated",
            "is_deleted",
            "channel",
            "is_pinned",
            "updated_at",
        ),
    )


class Message(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    __tablename__ = "messages"

    conversation_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("conversations.id"), nullable=False, index=True,
    )
    sender_id: Mapped[int] = mapped_column(
        BigInteger, ForeignKey("users.id"), nullable=False, index=True,
    )
    content: Mapped[str] = mapped_column(Text, nullable=False)
    is_internal: Mapped[bool] = mapped_column(default=False, nullable=False)
    is_read: Mapped[bool] = mapped_column(default=False, nullable=False, index=True)
    attachment_path: Mapped[str | None] = mapped_column(String(1000), nullable=True)
    attachment_filename: Mapped[str | None] = mapped_column(String(500), nullable=True)
    attachment_content_type: Mapped[str | None] = mapped_column(String(255), nullable=True)
    attachment_size: Mapped[int | None] = mapped_column(Integer, nullable=True)

    # ── Relationships ──────────────────────────────
    conversation = relationship("Conversation", back_populates="messages")
    sender = relationship("User", back_populates="messages")

    __table_args__ = (
        Index("ix_messages_conversation_created", "conversation_id", "created_at"),
    )


class ConversationAgentReplySuspension(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    __tablename__ = "conversation_agent_reply_suspensions"

    conversation_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("conversations.id"), nullable=False, index=True,
    )
    agent_id: Mapped[int] = mapped_column(
        BigInteger, ForeignKey("users.id"), nullable=False, index=True,
    )
    suspended_by_id: Mapped[int] = mapped_column(
        BigInteger, ForeignKey("users.id"), nullable=False, index=True,
    )
    reason: Mapped[str | None] = mapped_column(Text, nullable=True)

    __table_args__ = (
        UniqueConstraint(
            "conversation_id",
            "agent_id",
            name="uq_conversation_agent_reply_suspensions",
        ),
        Index(
            "ix_conversation_agent_reply_suspensions_conversation_agent",
            "conversation_id",
            "agent_id",
        ),
    )
