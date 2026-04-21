"""add_conversation_playbook_and_snippets

Revision ID: p6q7r8s9t0u
Revises: o5p6q7r8s9t
Create Date: 2026-04-18 10:40:00.000000
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


# revision identifiers, used by Alembic.
revision: str = "p6q7r8s9t0u"
down_revision: Union[str, None] = "o5p6q7r8s9t"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


_USER_COLUMN = "is_vip"
_USER_INDEX = "ix_users_is_vip"
_CONVERSATION_COLUMN = "sla_snoozed_until"
_CONVERSATION_INDEX = "ix_conversations_sla_snoozed_until"
_SNIPPET_TABLE = "conversation_snippets"


def _ensure_index(table_name: str, index_name: str, columns: list[str]) -> None:
    inspector = sa.inspect(op.get_bind())
    existing = {index["name"] for index in inspector.get_indexes(table_name)}
    if index_name not in existing:
        op.create_index(index_name, table_name, columns, unique=False)


def upgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    tables = set(inspector.get_table_names())

    if "users" in tables:
        user_columns = {column["name"] for column in inspector.get_columns("users")}
        if _USER_COLUMN not in user_columns:
            op.add_column(
                "users",
                sa.Column(_USER_COLUMN, sa.Boolean(), nullable=False, server_default=sa.text("false")),
            )
        _ensure_index("users", _USER_INDEX, [_USER_COLUMN])

    if "conversations" in tables:
        conversation_columns = {column["name"] for column in inspector.get_columns("conversations")}
        if _CONVERSATION_COLUMN not in conversation_columns:
            op.add_column(
                "conversations",
                sa.Column(_CONVERSATION_COLUMN, sa.DateTime(timezone=True), nullable=True),
            )
        _ensure_index("conversations", _CONVERSATION_INDEX, [_CONVERSATION_COLUMN])

    if _SNIPPET_TABLE not in tables:
        op.create_table(
            _SNIPPET_TABLE,
            sa.Column("title", sa.String(length=120), nullable=False),
            sa.Column("body", sa.Text(), nullable=False),
            sa.Column("description", sa.String(length=300), nullable=True),
            sa.Column("shortcut", sa.String(length=32), nullable=True),
            sa.Column(
                "channel",
                postgresql.ENUM(
                    "CHAT",
                    "EMAIL",
                    "TICKET",
                    "CALL_TRANSCRIPT",
                    "WHATSAPP",
                    name="channel_type",
                    create_type=False,
                ),
                nullable=True,
            ),
            sa.Column("is_active", sa.Boolean(), nullable=False, server_default=sa.text("true")),
            sa.Column("created_by_id", sa.UUID(), nullable=True),
            sa.Column("updated_by_id", sa.UUID(), nullable=True),
            sa.Column(
                "created_at",
                sa.DateTime(timezone=True),
                nullable=False,
                server_default=sa.text("CURRENT_TIMESTAMP"),
            ),
            sa.Column(
                "updated_at",
                sa.DateTime(timezone=True),
                nullable=False,
                server_default=sa.text("CURRENT_TIMESTAMP"),
            ),
            sa.Column("id", sa.UUID(), nullable=False),
            sa.ForeignKeyConstraint(["created_by_id"], ["users.id"], name="fk_conversation_snippets_created_by_id_users"),
            sa.ForeignKeyConstraint(["updated_by_id"], ["users.id"], name="fk_conversation_snippets_updated_by_id_users"),
            sa.PrimaryKeyConstraint("id"),
        )

        op.create_index("ix_conversation_snippets_shortcut", _SNIPPET_TABLE, ["shortcut"], unique=False)
        op.create_index("ix_conversation_snippets_channel", _SNIPPET_TABLE, ["channel"], unique=False)
        op.create_index("ix_conversation_snippets_is_active", _SNIPPET_TABLE, ["is_active"], unique=False)
        op.create_index("ix_conversation_snippets_created_by_id", _SNIPPET_TABLE, ["created_by_id"], unique=False)
        op.create_index("ix_conversation_snippets_updated_by_id", _SNIPPET_TABLE, ["updated_by_id"], unique=False)
        op.create_index(
            "ix_conversation_snippets_channel_active",
            _SNIPPET_TABLE,
            ["channel", "is_active"],
            unique=False,
        )
        op.create_index("ix_conversation_snippets_title", _SNIPPET_TABLE, ["title"], unique=False)

        op.execute(
            sa.text(
                """
                INSERT INTO conversation_snippets (
                    id,
                    title,
                    body,
                    description,
                    shortcut,
                    channel,
                    is_active,
                    created_by_id,
                    updated_by_id
                )
                VALUES
                    (
                        '11111111-1111-4111-8111-111111111111',
                        'Handoff acknowledgement',
                        'Hi {{customer_name}}, thanks for your patience. I am escalating this to a specialist now and we will keep you posted shortly.',
                        'Use when escalating a case to a specialist.',
                        'handoff',
                        'CHAT',
                        true,
                        NULL,
                        NULL
                    ),
                    (
                        '22222222-2222-4222-8222-222222222222',
                        'Missing details request',
                        'Thanks for the update, {{customer_name}}. To move faster, could you share: 1) exact steps, 2) screenshot or error text, 3) when this started?',
                        'Collect missing troubleshooting details in chat.',
                        'need-details',
                        'CHAT',
                        true,
                        NULL,
                        NULL
                    ),
                    (
                        '33333333-3333-4333-8333-333333333333',
                        'Investigation in progress',
                        'Hello {{customer_name}}, we are actively investigating your request and will follow up as soon as we complete the next verification step.',
                        'General customer update across channels.',
                        'investigating',
                        NULL,
                        true,
                        NULL,
                        NULL
                    )
                ON CONFLICT (id) DO NOTHING
                """
            )
        )


def downgrade() -> None:
    bind = op.get_bind()
    inspector = sa.inspect(bind)
    tables = set(inspector.get_table_names())

    if _SNIPPET_TABLE in tables:
        snippet_indexes = {index["name"] for index in inspector.get_indexes(_SNIPPET_TABLE)}
        for index_name in (
            "ix_conversation_snippets_title",
            "ix_conversation_snippets_channel_active",
            "ix_conversation_snippets_updated_by_id",
            "ix_conversation_snippets_created_by_id",
            "ix_conversation_snippets_is_active",
            "ix_conversation_snippets_channel",
            "ix_conversation_snippets_shortcut",
        ):
            if index_name in snippet_indexes:
                op.drop_index(index_name, table_name=_SNIPPET_TABLE)
        op.drop_table(_SNIPPET_TABLE)

    if "conversations" in tables:
        conversation_indexes = {index["name"] for index in inspector.get_indexes("conversations")}
        if _CONVERSATION_INDEX in conversation_indexes:
            op.drop_index(_CONVERSATION_INDEX, table_name="conversations")

        conversation_columns = {column["name"] for column in inspector.get_columns("conversations")}
        if _CONVERSATION_COLUMN in conversation_columns:
            op.drop_column("conversations", _CONVERSATION_COLUMN)

    if "users" in tables:
        user_indexes = {index["name"] for index in inspector.get_indexes("users")}
        if _USER_INDEX in user_indexes:
            op.drop_index(_USER_INDEX, table_name="users")

        user_columns = {column["name"] for column in inspector.get_columns("users")}
        if _USER_COLUMN in user_columns:
            op.drop_column("users", _USER_COLUMN)
