"""
User model — supports CLIENT, AGENT, ADMIN roles.
"""

from sqlalchemy import Boolean, String, Enum, Index, BigInteger
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base, TimestampMixin, SoftDeleteMixin, UUIDPrimaryKeyMixin
from app.db.models.enums import UserRole, UserStatus


class User(Base, TimestampMixin, SoftDeleteMixin):
    __tablename__ = "users"
    id: Mapped[int] = mapped_column(BigInteger, primary_key=True, autoincrement=True)

    email: Mapped[str] = mapped_column(String(255), unique=True, nullable=False, index=True)
    hashed_password: Mapped[str] = mapped_column(String(255), nullable=False)
    full_name: Mapped[str] = mapped_column(String(255), nullable=False)
    phone_number: Mapped[str | None] = mapped_column(
        String(20), unique=True, nullable=True, index=True,
    )
    role: Mapped[UserRole] = mapped_column(
        "role_python", 
        Enum(UserRole, name="user_role", native_enum=False, create_constraint=False),
        default=UserRole.CLIENT,
        nullable=True,
        index=True,
    )
    status: Mapped[UserStatus] = mapped_column(
        Enum(UserStatus, name="user_status", native_enum=False, create_constraint=False),
        default=UserStatus.ACTIVE,
        nullable=False,
    )
    can_reply_conversations: Mapped[bool] = mapped_column(
        default=True,
        nullable=False,
    )
    can_reply_whatsapp: Mapped[bool] = mapped_column(
        default=True,
        nullable=False,
    )
    is_vip: Mapped[bool] = mapped_column(
        Boolean,
        default=False,
        nullable=False,
        index=True,
    )
    teams_email: Mapped[str | None] = mapped_column(
        String(255),
        nullable=True,
        index=True,
    )
    teams_webhook_url: Mapped[str | None] = mapped_column(
        String(1000),
        nullable=True,
    )
    timezone: Mapped[str] = mapped_column(
        String(64),
        default="UTC",
        nullable=False,
    )
    locale: Mapped[str] = mapped_column(
        String(16),
        default="en",
        nullable=False,
    )
    must_change_password: Mapped[bool] = mapped_column(
        default=False,
        nullable=False,
    )
    profile_completed: Mapped[bool] = mapped_column(
        default=False,
        nullable=False,
    )
    profile_picture_url: Mapped[str | None] = mapped_column(
        String(1000),
        nullable=True,
    )
    glpi_user_id: Mapped[int | None] = mapped_column(
        nullable=True,
        index=True,
    )

    laravel_user_id: Mapped[int | None] = mapped_column(
    BigInteger,
    nullable=True,
    unique=True,
    index=True,
    )

    # ── Relationships ──────────────────────────────
    conversations = relationship("Conversation", back_populates="user", lazy="select")
    messages = relationship("Message", back_populates="sender", lazy="select")
    assigned_tickets = relationship(
        "Ticket", back_populates="assigned_agent", foreign_keys="Ticket.assigned_agent_id", lazy="select"
    )
    created_tickets = relationship(
        "Ticket", back_populates="creator", foreign_keys="Ticket.creator_id", lazy="select"
    )
    solved_tickets = relationship(
        "Ticket", back_populates="solved_by", foreign_keys="Ticket.solved_by_id", lazy="select"
    )
    notifications = relationship("Notification", back_populates="user", lazy="select")

    __table_args__ = (
        Index("ix_users_email_active", "email", "is_deleted"),
    )

    def __repr__(self) -> str:
        return f"<User {self.email} role={self.role.value}>"