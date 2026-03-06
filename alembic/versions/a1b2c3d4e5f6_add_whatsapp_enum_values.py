"""add_whatsapp_enum_values

Revision ID: a1b2c3d4e5f6
Revises: 32cdb2e446fe
Create Date: 2026-02-19

Adds WHATSAPP to channel_type and WHATSAPP_IN/WHATSAPP_OUT to audit_action enums.
"""

from typing import Sequence, Union
from alembic import op

# revision identifiers
revision: str = "a1b2c3d4e5f6"
down_revision: Union[str, None] = "32cdb2e446fe"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    # Add WHATSAPP to channel_type enum
    op.execute("ALTER TYPE channel_type ADD VALUE IF NOT EXISTS 'WHATSAPP'")
    # Add audit actions for WhatsApp
    op.execute("ALTER TYPE audit_action ADD VALUE IF NOT EXISTS 'WHATSAPP_IN'")
    op.execute("ALTER TYPE audit_action ADD VALUE IF NOT EXISTS 'WHATSAPP_OUT'")


def downgrade() -> None:
    # PostgreSQL does not support removing enum values.
    # These values will remain but be unused.
    pass
