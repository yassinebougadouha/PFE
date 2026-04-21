"""add_message_attachments

Revision ID: h8i9j0k1l2m
Revises: g7h8i9j0k1l2
Create Date: 2026-04-06 15:45:00.000000

"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = "h8i9j0k1l2m"
down_revision: Union[str, None] = "g7h8i9j0k1l2"
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    op.add_column("messages", sa.Column("attachment_path", sa.String(length=1000), nullable=True))
    op.add_column("messages", sa.Column("attachment_filename", sa.String(length=500), nullable=True))
    op.add_column("messages", sa.Column("attachment_content_type", sa.String(length=255), nullable=True))
    op.add_column("messages", sa.Column("attachment_size", sa.Integer(), nullable=True))


def downgrade() -> None:
    op.drop_column("messages", "attachment_size")
    op.drop_column("messages", "attachment_content_type")
    op.drop_column("messages", "attachment_filename")
    op.drop_column("messages", "attachment_path")
