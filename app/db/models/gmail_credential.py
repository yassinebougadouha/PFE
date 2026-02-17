"""
GmailCredential model — stores OAuth2 tokens per user.
Tokens are encrypted at rest for security.
"""

import uuid

from sqlalchemy import String, Text, ForeignKey, Index, Boolean
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base, TimestampMixin, UUIDPrimaryKeyMixin


class GmailCredential(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    __tablename__ = "gmail_credentials"

    user_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=False, unique=True, index=True,
    )
    gmail_address: Mapped[str] = mapped_column(String(320), nullable=False)
    access_token: Mapped[str] = mapped_column(Text, nullable=False)
    refresh_token: Mapped[str] = mapped_column(Text, nullable=False)
    token_uri: Mapped[str] = mapped_column(String(500), default="https://oauth2.googleapis.com/token")
    scopes: Mapped[str] = mapped_column(Text, nullable=False)  # JSON-serialized list
    is_active: Mapped[bool] = mapped_column(Boolean, default=True, nullable=False)

    # Track last synced message to avoid re-processing
    last_history_id: Mapped[str | None] = mapped_column(String(50), nullable=True)

    # ── Relationships ──────────────────────────────
    user = relationship("User", backref="gmail_credential", uselist=False)

    __table_args__ = (
        Index("ix_gmail_credentials_active", "user_id", "is_active"),
    )

    def __repr__(self) -> str:
        return f"<GmailCredential user={self.user_id} gmail={self.gmail_address}>"
