"""
Conversation & Message models — Chat channel.
"""

import uuid

from sqlalchemy import String, Text, Enum, ForeignKey, Index
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base, TimestampMixin, SoftDeleteMixin, UUIDPrimaryKeyMixin
from app.db.models.enums import ConversationStatus, ChannelType


class Conversation(Base, UUIDPrimaryKeyMixin, TimestampMixin, SoftDeleteMixin):
    __tablename__ = "conversations"

    user_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=False, index=True,
    )
    channel: Mapped[ChannelType] = mapped_column(
        Enum(ChannelType, name="channel_type", create_constraint=True),
        default=ChannelType.CHAT,
        nullable=False,
    )
    status: Mapped[ConversationStatus] = mapped_column(
        Enum(ConversationStatus, name="conversation_status", create_constraint=True),
        default=ConversationStatus.OPEN,
        nullable=False,
    )
    subject: Mapped[str | None] = mapped_column(String(500), nullable=True)

    # Future-ready: placeholder fields for decision engine (Sprint 2)
    # confidence_score, risk_score, decision_outcome will be added

    # ── Relationships ──────────────────────────────
    user = relationship("User", back_populates="conversations")
    messages = relationship("Message", back_populates="conversation", lazy="selectin", order_by="Message.created_at")

    __table_args__ = (
        Index("ix_conversations_user_status", "user_id", "status"),
    )


class Message(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    __tablename__ = "messages"

    conversation_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("conversations.id"), nullable=False, index=True,
    )
    sender_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=False, index=True,
    )
    content: Mapped[str] = mapped_column(Text, nullable=False)
    is_internal: Mapped[bool] = mapped_column(default=False, nullable=False)

    # ── Relationships ──────────────────────────────
    conversation = relationship("Conversation", back_populates="messages")
    sender = relationship("User", back_populates="messages")

    __table_args__ = (
        Index("ix_messages_conversation_created", "conversation_id", "created_at"),
    )
