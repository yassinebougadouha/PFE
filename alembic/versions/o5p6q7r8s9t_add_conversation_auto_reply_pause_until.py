"""add_conversation_auto_reply_pause_until

Revision ID: o5p6q7r8s9t
Revises: n4o5p6q7r8s
Create Date: 2026-04-17 13:45:00.000000
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "o5p6q7r8s9t"
down_revision: Union[str, None] = "n4o5p6q7r8s"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


_COLUMN_NAME = "ai_auto_reply_paused_until"
_INDEX_NAME = "ix_conversations_ai_auto_reply_paused_until"


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)

    columns = {column["name"] for column in inspector.get_columns("conversations")}
    if _COLUMN_NAME not in columns:
        op.add_column(
            "conversations",
            sa.Column(_COLUMN_NAME, sa.DateTime(timezone=True), nullable=True),
        )

    indexes = {index["name"] for index in inspector.get_indexes("conversations")}
    if _INDEX_NAME not in indexes:
        op.create_index(_INDEX_NAME, "conversations", [_COLUMN_NAME], unique=False)


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)

    indexes = {index["name"] for index in inspector.get_indexes("conversations")}
    if _INDEX_NAME in indexes:
        op.drop_index(_INDEX_NAME, table_name="conversations")

    columns = {column["name"] for column in inspector.get_columns("conversations")}
    if _COLUMN_NAME in columns:
        op.drop_column("conversations", _COLUMN_NAME)
