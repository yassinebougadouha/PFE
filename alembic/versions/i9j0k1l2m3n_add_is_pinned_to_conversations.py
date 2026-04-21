"""add_is_pinned_to_conversations

Revision ID: i9j0k1l2m3n
Revises: h8i9j0k1l2m
Create Date: 2026-04-13 00:00:00.000000

"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "i9j0k1l2m3n"
down_revision: Union[str, None] = "h8i9j0k1l2m"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)

    columns = {col["name"] for col in inspector.get_columns("conversations")}
    if "is_pinned" not in columns:
        op.add_column(
            "conversations",
            sa.Column("is_pinned", sa.Boolean(), nullable=False, server_default=sa.text("false")),
        )
        op.alter_column("conversations", "is_pinned", server_default=None)

    indexes = {idx["name"] for idx in inspector.get_indexes("conversations")}
    if "ix_conversations_is_pinned" not in indexes:
        op.create_index("ix_conversations_is_pinned", "conversations", ["is_pinned"], unique=False)
    if "ix_conversations_user_pinned_updated" not in indexes:
        op.create_index(
            "ix_conversations_user_pinned_updated",
            "conversations",
            ["user_id", "is_pinned", "updated_at"],
            unique=False,
        )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)

    indexes = {idx["name"] for idx in inspector.get_indexes("conversations")}
    if "ix_conversations_user_pinned_updated" in indexes:
        op.drop_index("ix_conversations_user_pinned_updated", table_name="conversations")
    if "ix_conversations_is_pinned" in indexes:
        op.drop_index("ix_conversations_is_pinned", table_name="conversations")

    columns = {col["name"] for col in inspector.get_columns("conversations")}
    if "is_pinned" in columns:
        op.drop_column("conversations", "is_pinned")
