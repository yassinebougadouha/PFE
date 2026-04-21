"""add_conversation_agent_reply_suspensions

Revision ID: m3n4o5p6q7r
Revises: l2m3n4o5p6q
Create Date: 2026-04-16 19:20:00.000000
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


# revision identifiers, used by Alembic.
revision: str = "m3n4o5p6q7r"
down_revision: Union[str, None] = "l2m3n4o5p6q"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)

    tables = set(inspector.get_table_names())
    if "conversation_agent_reply_suspensions" not in tables:
        op.create_table(
            "conversation_agent_reply_suspensions",
            sa.Column("conversation_id", postgresql.UUID(as_uuid=True), nullable=False),
            sa.Column("agent_id", postgresql.UUID(as_uuid=True), nullable=False),
            sa.Column("suspended_by_id", postgresql.UUID(as_uuid=True), nullable=False),
            sa.Column("reason", sa.Text(), nullable=True),
            sa.Column("id", postgresql.UUID(as_uuid=True), nullable=False),
            sa.Column("created_at", sa.DateTime(timezone=True), nullable=False),
            sa.Column("updated_at", sa.DateTime(timezone=True), nullable=False),
            sa.ForeignKeyConstraint(["agent_id"], ["users.id"]),
            sa.ForeignKeyConstraint(["conversation_id"], ["conversations.id"]),
            sa.ForeignKeyConstraint(["suspended_by_id"], ["users.id"]),
            sa.PrimaryKeyConstraint("id"),
            sa.UniqueConstraint(
                "conversation_id",
                "agent_id",
                name="uq_conversation_agent_reply_suspensions",
            ),
        )

    indexes = {index["name"] for index in inspector.get_indexes("conversation_agent_reply_suspensions")}

    if "ix_conversation_agent_reply_suspensions_agent_id" not in indexes:
        op.create_index(
            "ix_conversation_agent_reply_suspensions_agent_id",
            "conversation_agent_reply_suspensions",
            ["agent_id"],
            unique=False,
        )

    if "ix_conversation_agent_reply_suspensions_conversation_id" not in indexes:
        op.create_index(
            "ix_conversation_agent_reply_suspensions_conversation_id",
            "conversation_agent_reply_suspensions",
            ["conversation_id"],
            unique=False,
        )

    if "ix_conversation_agent_reply_suspensions_suspended_by_id" not in indexes:
        op.create_index(
            "ix_conversation_agent_reply_suspensions_suspended_by_id",
            "conversation_agent_reply_suspensions",
            ["suspended_by_id"],
            unique=False,
        )

    if "ix_conversation_agent_reply_suspensions_conversation_agent" not in indexes:
        op.create_index(
            "ix_conversation_agent_reply_suspensions_conversation_agent",
            "conversation_agent_reply_suspensions",
            ["conversation_id", "agent_id"],
            unique=False,
        )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)

    tables = set(inspector.get_table_names())
    if "conversation_agent_reply_suspensions" not in tables:
        return

    indexes = {index["name"] for index in inspector.get_indexes("conversation_agent_reply_suspensions")}

    if "ix_conversation_agent_reply_suspensions_conversation_agent" in indexes:
        op.drop_index(
            "ix_conversation_agent_reply_suspensions_conversation_agent",
            table_name="conversation_agent_reply_suspensions",
        )

    if "ix_conversation_agent_reply_suspensions_suspended_by_id" in indexes:
        op.drop_index(
            "ix_conversation_agent_reply_suspensions_suspended_by_id",
            table_name="conversation_agent_reply_suspensions",
        )

    if "ix_conversation_agent_reply_suspensions_conversation_id" in indexes:
        op.drop_index(
            "ix_conversation_agent_reply_suspensions_conversation_id",
            table_name="conversation_agent_reply_suspensions",
        )

    if "ix_conversation_agent_reply_suspensions_agent_id" in indexes:
        op.drop_index(
            "ix_conversation_agent_reply_suspensions_agent_id",
            table_name="conversation_agent_reply_suspensions",
        )

    op.drop_table("conversation_agent_reply_suspensions")
