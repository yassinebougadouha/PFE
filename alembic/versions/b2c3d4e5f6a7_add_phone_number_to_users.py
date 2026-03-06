"""add phone_number to users

Revision ID: b2c3d4e5f6a7
Revises: a1b2c3d4e5f6
Create Date: 2026-02-21
"""

from alembic import op
import sqlalchemy as sa

revision = "b2c3d4e5f6a7"
down_revision = "a1b2c3d4e5f6"
branch_labels = None
depends_on = None


def upgrade() -> None:
    op.add_column(
        "users",
        sa.Column("phone_number", sa.String(20), nullable=True),
    )
    op.create_index("ix_users_phone_number", "users", ["phone_number"], unique=True)


def downgrade() -> None:
    op.drop_index("ix_users_phone_number", table_name="users")
    op.drop_column("users", "phone_number")
