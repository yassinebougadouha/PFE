"""add_glpi_ticket_fields

Revision ID: t0u1v2w3x4y
Revises: s9t0u1v2w3x
Create Date: 2026-05-11 10:00:00.000000

"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "t0u1v2w3x4y"
down_revision: Union[str, None] = "s9t0u1v2w3x"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    ticket_columns = {column["name"] for column in inspector.get_columns("tickets")}

    # Add GLPI integration fields
    if "glpi_ticket_id" not in ticket_columns:
        op.add_column(
            "tickets",
            sa.Column("glpi_ticket_id", sa.Integer(), nullable=True),
        )
        op.create_index(
            "ix_tickets_glpi_id",
            "tickets",
            ["glpi_ticket_id"],
        )

    if "glpi_sync_status" not in ticket_columns:
        op.add_column(
            "tickets",
            sa.Column(
                "glpi_sync_status",
                sa.String(length=20),
                nullable=False,
                server_default="pending",
            ),
        )
        op.create_index(
            "ix_tickets_glpi_sync_status",
            "tickets",
            ["glpi_sync_status"],
        )

    if "glpi_sync_error" not in ticket_columns:
        op.add_column(
            "tickets",
            sa.Column("glpi_sync_error", sa.Text(), nullable=True),
        )

    # Create combined index for sync operations
    op.create_index(
        "ix_tickets_glpi_sync",
        "tickets",
        ["glpi_ticket_id", "glpi_sync_status"],
    )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    ticket_columns = {column["name"] for column in inspector.get_columns("tickets")}

    # Drop indexes
    try:
        op.drop_index("ix_tickets_glpi_sync")
    except Exception:
        pass

    try:
        op.drop_index("ix_tickets_glpi_sync_status")
    except Exception:
        pass

    try:
        op.drop_index("ix_tickets_glpi_id")
    except Exception:
        pass

    # Drop columns
    if "glpi_sync_error" in ticket_columns:
        op.drop_column("tickets", "glpi_sync_error")

    if "glpi_sync_status" in ticket_columns:
        op.drop_column("tickets", "glpi_sync_status")

    if "glpi_ticket_id" in ticket_columns:
        op.drop_column("tickets", "glpi_ticket_id")
