"""add_reply_enum_values

Revision ID: 32cdb2e446fe
Revises: 43637c752d26
Create Date: 2026-02-17
"""
from typing import Sequence, Union
from alembic import op

revision: str = "32cdb2e446fe"
down_revision: str = "43637c752d26"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.execute("ALTER TYPE email_status ADD VALUE IF NOT EXISTS 'REPLIED'")
    op.execute("ALTER TYPE audit_action ADD VALUE IF NOT EXISTS 'REPLY'")


def downgrade() -> None:
    pass  # PostgreSQL does not support removing enum values
