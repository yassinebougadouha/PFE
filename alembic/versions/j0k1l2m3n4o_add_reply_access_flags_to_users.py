"""add_reply_access_flags_to_users

Revision ID: j0k1l2m3n4o
Revises: i9j0k1l2m3n
Create Date: 2026-04-14 00:00:00.000000

"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "j0k1l2m3n4o"
down_revision: Union[str, None] = "i9j0k1l2m3n"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    columns = {col["name"] for col in inspector.get_columns("users")}

    if "can_reply_conversations" not in columns:
        op.add_column(
            "users",
            sa.Column("can_reply_conversations", sa.Boolean(), nullable=False, server_default=sa.text("true")),
        )

    if "can_reply_whatsapp" not in columns:
        op.add_column(
            "users",
            sa.Column("can_reply_whatsapp", sa.Boolean(), nullable=False, server_default=sa.text("true")),
        )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    columns = {col["name"] for col in inspector.get_columns("users")}

    if "can_reply_whatsapp" in columns:
        op.drop_column("users", "can_reply_whatsapp")
    if "can_reply_conversations" in columns:
        op.drop_column("users", "can_reply_conversations")
