"""add_ticket_voice_call_link

Revision ID: r8s9t0u1v2w
Revises: q7r8s9t0u1v
Create Date: 2026-04-21 10:10:00.000000
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


# revision identifiers, used by Alembic.
revision: str = "r8s9t0u1v2w"
down_revision: Union[str, None] = "q7r8s9t0u1v"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.add_column(
        "tickets",
        sa.Column("source_voice_call_id", postgresql.UUID(as_uuid=True), nullable=True),
    )
    op.create_index(
        "ix_tickets_source_voice_call_id",
        "tickets",
        ["source_voice_call_id"],
        unique=False,
    )
    op.create_foreign_key(
        "fk_tickets_source_voice_call_id",
        "tickets",
        "voice_call_logs",
        ["source_voice_call_id"],
        ["id"],
    )


def downgrade() -> None:
    op.drop_constraint("fk_tickets_source_voice_call_id", "tickets", type_="foreignkey")
    op.drop_index("ix_tickets_source_voice_call_id", table_name="tickets")
    op.drop_column("tickets", "source_voice_call_id")
