"""add_performance_indexes

Revision ID: q7r8s9t0u1v
Revises: p6q7r8s9t0u
Create Date: 2026-04-20 18:05:00.000000
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "q7r8s9t0u1v"
down_revision: Union[str, None] = "p6q7r8s9t0u"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.execute(
        sa.text(
            """
            CREATE INDEX IF NOT EXISTS ix_conversations_deleted_channel_pinned_updated
            ON conversations (is_deleted, channel, is_pinned, updated_at DESC)
            """
        )
    )
    op.execute(
        sa.text(
            """
            CREATE INDEX IF NOT EXISTS ix_tickets_deleted_created
            ON tickets (is_deleted, created_at DESC)
            """
        )
    )
    op.execute(
        sa.text(
            """
            CREATE INDEX IF NOT EXISTS ix_tickets_deleted_status_created
            ON tickets (is_deleted, status, created_at DESC)
            """
        )
    )
    op.execute(
        sa.text(
            """
            CREATE INDEX IF NOT EXISTS ix_audit_logs_action_resource_created_user
            ON audit_logs (action, resource_type, created_at, user_id)
            """
        )
    )
    op.execute(
        sa.text(
            """
            CREATE INDEX IF NOT EXISTS ix_emails_outbound_created_replied_by
            ON emails (is_outbound, created_at, replied_by_id)
            """
        )
    )


def downgrade() -> None:
    op.execute(sa.text("DROP INDEX IF EXISTS ix_emails_outbound_created_replied_by"))
    op.execute(sa.text("DROP INDEX IF EXISTS ix_audit_logs_action_resource_created_user"))
    op.execute(sa.text("DROP INDEX IF EXISTS ix_tickets_deleted_status_created"))
    op.execute(sa.text("DROP INDEX IF EXISTS ix_tickets_deleted_created"))
    op.execute(sa.text("DROP INDEX IF EXISTS ix_conversations_deleted_channel_pinned_updated"))
