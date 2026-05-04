"""add_user_profile_picture_url

Revision ID: s9t0u1v2w3x
Revises: r8s9t0u1v2w
Create Date: 2026-05-02 07:25:00.000000

"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "s9t0u1v2w3x"
down_revision: Union[str, None] = "r8s9t0u1v2w"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    user_columns = {column["name"] for column in inspector.get_columns("users")}

    if "profile_picture_url" not in user_columns:
        op.add_column(
            "users",
            sa.Column("profile_picture_url", sa.String(length=1000), nullable=True),
        )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    user_columns = {column["name"] for column in inspector.get_columns("users")}

    if "profile_picture_url" in user_columns:
        op.drop_column("users", "profile_picture_url")
