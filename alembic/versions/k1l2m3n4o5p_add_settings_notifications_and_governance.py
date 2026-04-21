"""add_settings_notifications_and_governance

Revision ID: k1l2m3n4o5p
Revises: j0k1l2m3n4o
Create Date: 2026-04-14 00:30:00.000000

"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


# revision identifiers, used by Alembic.
revision: str = "k1l2m3n4o5p"
down_revision: Union[str, None] = "j0k1l2m3n4o"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    tables = set(inspector.get_table_names())

    if "settings" not in tables:
        op.create_table(
            "settings",
            sa.Column("section", sa.String(length=64), nullable=False),
            sa.Column("key", sa.String(length=100), nullable=False),
            sa.Column("value", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
            sa.Column("is_secret", sa.Boolean(), nullable=False, server_default=sa.text("false")),
            sa.Column("created_at", sa.DateTime(timezone=True), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
            sa.Column("updated_at", sa.DateTime(timezone=True), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
            sa.Column("id", sa.UUID(), nullable=False),
            sa.PrimaryKeyConstraint("id"),
            sa.UniqueConstraint("key", name="uq_settings_key"),
        )
        op.create_index("ix_settings_section", "settings", ["section"], unique=False)
        op.create_index("ix_settings_section_key", "settings", ["section", "key"], unique=False)

    if "notifications" not in tables:
        op.create_table(
            "notifications",
            sa.Column("user_id", sa.UUID(), nullable=False),
            sa.Column("type", sa.String(length=100), nullable=False),
            sa.Column("title", sa.String(length=255), nullable=False),
            sa.Column("body", sa.Text(), nullable=False),
            sa.Column("is_read", sa.Boolean(), nullable=False, server_default=sa.text("false")),
            sa.Column("read_at", sa.DateTime(timezone=True), nullable=True),
            sa.Column("resource_type", sa.String(length=100), nullable=True),
            sa.Column("resource_id", sa.String(length=255), nullable=True),
            sa.Column("action_url", sa.String(length=1000), nullable=True),
            sa.Column("meta", postgresql.JSONB(astext_type=sa.Text()), nullable=True),
            sa.Column("created_at", sa.DateTime(timezone=True), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
            sa.Column("updated_at", sa.DateTime(timezone=True), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
            sa.Column("id", sa.UUID(), nullable=False),
            sa.ForeignKeyConstraint(["user_id"], ["users.id"], name="fk_notifications_user_id_users"),
            sa.PrimaryKeyConstraint("id"),
        )
        op.create_index("ix_notifications_type", "notifications", ["type"], unique=False)
        op.create_index("ix_notifications_is_read", "notifications", ["is_read"], unique=False)
        op.create_index("ix_notifications_user_id", "notifications", ["user_id"], unique=False)
        op.create_index(
            "ix_notifications_user_read_created",
            "notifications",
            ["user_id", "is_read", "created_at"],
            unique=False,
        )

    user_columns = {col["name"] for col in inspector.get_columns("users")}

    if "teams_email" not in user_columns:
        op.add_column("users", sa.Column("teams_email", sa.String(length=255), nullable=True))
        op.create_index("ix_users_teams_email", "users", ["teams_email"], unique=False)

    if "teams_webhook_url" not in user_columns:
        op.add_column("users", sa.Column("teams_webhook_url", sa.String(length=1000), nullable=True))

    if "timezone" not in user_columns:
        op.add_column(
            "users",
            sa.Column("timezone", sa.String(length=64), nullable=False, server_default=sa.text("'UTC'")),
        )

    if "locale" not in user_columns:
        op.add_column(
            "users",
            sa.Column("locale", sa.String(length=16), nullable=False, server_default=sa.text("'en'")),
        )

    if "must_change_password" not in user_columns:
        op.add_column(
            "users",
            sa.Column("must_change_password", sa.Boolean(), nullable=False, server_default=sa.text("false")),
        )

    if "profile_completed" not in user_columns:
        op.add_column(
            "users",
            sa.Column("profile_completed", sa.Boolean(), nullable=False, server_default=sa.text("false")),
        )

    ticket_columns = {col["name"] for col in inspector.get_columns("tickets")}
    ticket_fks = {fk["name"] for fk in inspector.get_foreign_keys("tickets")}

    if "resolution_note" not in ticket_columns:
        op.add_column("tickets", sa.Column("resolution_note", sa.Text(), nullable=True))

    if "resolved_at" not in ticket_columns:
        op.add_column("tickets", sa.Column("resolved_at", sa.DateTime(timezone=True), nullable=True))

    if "solved_by_id" not in ticket_columns:
        op.add_column("tickets", sa.Column("solved_by_id", sa.UUID(), nullable=True))
        op.create_index("ix_tickets_solved_by_id", "tickets", ["solved_by_id"], unique=False)

    if "fk_tickets_solved_by_id_users" not in ticket_fks and "solved_by_id" in {
        col["name"] for col in sa.inspect(bind).get_columns("tickets")
    }:
        op.create_foreign_key(
            "fk_tickets_solved_by_id_users",
            "tickets",
            "users",
            ["solved_by_id"],
            ["id"],
        )

    op.execute(
        sa.text(
            """
            UPDATE users
            SET profile_completed = CASE
                WHEN role = 'ADMIN'
                    THEN COALESCE(NULLIF(trim(phone_number), ''), NULL) IS NOT NULL
                     AND COALESCE(NULLIF(trim(teams_email), ''), NULL) IS NOT NULL
                ELSE true
            END
            """
        )
    )

    op.execute(
        sa.text(
            """
            UPDATE tickets
            SET resolved_at = COALESCE(resolved_at, updated_at)
            WHERE status IN ('RESOLVED', 'CLOSED') AND resolved_at IS NULL
            """
        )
    )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    tables = set(inspector.get_table_names())

    if "tickets" in tables:
        ticket_columns = {col["name"] for col in inspector.get_columns("tickets")}
        ticket_fks = {fk["name"] for fk in inspector.get_foreign_keys("tickets")}
        ticket_indexes = {idx["name"] for idx in inspector.get_indexes("tickets")}

        if "fk_tickets_solved_by_id_users" in ticket_fks:
            op.drop_constraint("fk_tickets_solved_by_id_users", "tickets", type_="foreignkey")
        if "ix_tickets_solved_by_id" in ticket_indexes:
            op.drop_index("ix_tickets_solved_by_id", table_name="tickets")
        if "solved_by_id" in ticket_columns:
            op.drop_column("tickets", "solved_by_id")
        if "resolved_at" in ticket_columns:
            op.drop_column("tickets", "resolved_at")
        if "resolution_note" in ticket_columns:
            op.drop_column("tickets", "resolution_note")

    if "users" in tables:
        user_columns = {col["name"] for col in inspector.get_columns("users")}
        user_indexes = {idx["name"] for idx in inspector.get_indexes("users")}

        if "profile_completed" in user_columns:
            op.drop_column("users", "profile_completed")
        if "must_change_password" in user_columns:
            op.drop_column("users", "must_change_password")
        if "locale" in user_columns:
            op.drop_column("users", "locale")
        if "timezone" in user_columns:
            op.drop_column("users", "timezone")
        if "teams_webhook_url" in user_columns:
            op.drop_column("users", "teams_webhook_url")
        if "ix_users_teams_email" in user_indexes:
            op.drop_index("ix_users_teams_email", table_name="users")
        if "teams_email" in user_columns:
            op.drop_column("users", "teams_email")

    if "notifications" in tables:
        notification_indexes = {idx["name"] for idx in inspector.get_indexes("notifications")}
        for index_name in (
            "ix_notifications_user_read_created",
            "ix_notifications_user_id",
            "ix_notifications_is_read",
            "ix_notifications_type",
        ):
            if index_name in notification_indexes:
                op.drop_index(index_name, table_name="notifications")
        op.drop_table("notifications")

    if "settings" in tables:
        settings_indexes = {idx["name"] for idx in inspector.get_indexes("settings")}
        for index_name in (
            "ix_settings_section_key",
            "ix_settings_section",
        ):
            if index_name in settings_indexes:
                op.drop_index(index_name, table_name="settings")
        op.drop_table("settings")
