"""add_email_mailbox_state

Revision ID: l2m3n4o5p6q
Revises: k1l2m3n4o5p
Create Date: 2026-04-16 12:00:00.000000
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


# revision identifiers, used by Alembic.
revision: str = "l2m3n4o5p6q"
down_revision: Union[str, None] = "k1l2m3n4o5p"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.add_column(
        "emails",
        sa.Column("is_read", sa.Boolean(), nullable=False, server_default=sa.text("false")),
    )
    op.add_column(
        "emails",
        sa.Column("is_starred", sa.Boolean(), nullable=False, server_default=sa.text("false")),
    )
    op.add_column(
        "emails",
        sa.Column(
            "labels",
            postgresql.JSONB(astext_type=sa.Text()),
            nullable=False,
            server_default=sa.text("'[]'::jsonb"),
        ),
    )

    # NOTE:
    # We intentionally skip a bulk backfill UPDATE here to avoid full-table writes
    # during startup migrations. Existing rows will use server defaults and can be
    # updated lazily by mailbox actions/sync.

    op.create_index(op.f("ix_emails_is_read"), "emails", ["is_read"], unique=False)
    op.create_index(op.f("ix_emails_is_starred"), "emails", ["is_starred"], unique=False)
    op.create_index("ix_emails_read_starred", "emails", ["is_read", "is_starred"], unique=False)


def downgrade() -> None:
    op.drop_index("ix_emails_read_starred", table_name="emails")
    op.drop_index(op.f("ix_emails_is_starred"), table_name="emails")
    op.drop_index(op.f("ix_emails_is_read"), table_name="emails")

    op.drop_column("emails", "labels")
    op.drop_column("emails", "is_starred")
    op.drop_column("emails", "is_read")
