"""add_laravel_user_id_to_users

Revision ID: 227af21ed124
Revises: u1v2w3x4y5z
Create Date: 2026-05-16 14:37:47.503098
"""

from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql

# revision identifiers, used by Alembic.
revision: str = '227af21ed124'
down_revision: Union[str, None] = 'u1v2w3x4y5z'
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None

def upgrade() -> None:
    op.add_column('users',
        sa.Column(
            'laravel_user_id',
            sa.BigInteger(),
            nullable=True
        )
    )
    op.create_unique_constraint(
        'uq_users_laravel_user_id',
        'users',
        ['laravel_user_id']
    )
    op.create_index(
        'ix_users_laravel_user_id',
        'users',
        ['laravel_user_id']
    )


def downgrade() -> None:
    op.drop_index('ix_users_laravel_user_id', table_name='users')
    op.drop_constraint('uq_users_laravel_user_id', 'users')
    op.drop_column('users', 'laravel_user_id') 
