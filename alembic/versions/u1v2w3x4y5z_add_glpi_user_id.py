"""add_glpi_user_id

Revision ID: u1v2w3x4y5z
Revises: t0u1v2w3x4y
Create Date: 2026-05-11 11:00:00.000000

"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "u1v2w3x4y5z"
down_revision: Union[str, None] = "t0u1v2w3x4y"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    user_columns = {column["name"] for column in inspector.get_columns("users")}

    if "glpi_user_id" not in user_columns:
        op.add_column(
            "users",
            sa.Column("glpi_user_id", sa.Integer(), nullable=True),
        )
        op.create_index(
            "ix_users_glpi_user_id",
            "users",
            ["glpi_user_id"],
        )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    user_columns = {column["name"] for column in inspector.get_columns("users")}

    if "glpi_user_id" in user_columns:
        op.drop_index("ix_users_glpi_user_id", table_name="users")
        op.drop_column("users", "glpi_user_id")
