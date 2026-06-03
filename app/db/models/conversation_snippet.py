"""
Shared conversation snippets/macros for assisted drafting.
"""

import uuid

from sqlalchemy import Boolean, Enum, ForeignKey, Index, String, Text
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import Mapped, mapped_column

from app.db.base import Base, TimestampMixin, UUIDPrimaryKeyMixin
from app.db.models.enums import ChannelType


class ConversationSnippet(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    __tablename__ = "conversation_snippets"

    title: Mapped[str] = mapped_column(String(120), nullable=False)
    body: Mapped[str] = mapped_column(Text, nullable=False)
    description: Mapped[str | None] = mapped_column(String(300), nullable=True)
    shortcut: Mapped[str | None] = mapped_column(String(32), nullable=True, index=True)
    channel: Mapped[ChannelType | None] = mapped_column(
        Enum(ChannelType, name="channel_type", native_enum=False, create_constraint=True, create_type=False),
        nullable=True,
        index=True,
        doc="Null means snippet is available across all channels.",
    )
    is_active: Mapped[bool] = mapped_column(Boolean, default=True, nullable=False, index=True)
    created_by_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True),
        ForeignKey("users.id"),
        nullable=True,
        index=True,
    )
    updated_by_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True),
        ForeignKey("users.id"),
        nullable=True,
        index=True,
    )

    __table_args__ = (
        Index("ix_conversation_snippets_channel_active", "channel", "is_active"),
        Index("ix_conversation_snippets_title", "title"),
    )
