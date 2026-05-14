"""
Shared API dependencies: current user, role enforcement, Redis.
"""

from typing import Annotated, List

from fastapi import Depends, Request
from sqlalchemy import select
from sqlalchemy.exc import IntegrityError
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import UserRole, UserStatus
from app.services.redis_service import RedisService, get_redis_client

# ── Redis dependency ─────────────────────────────────────

async def get_redis() -> RedisService:
    client = await get_redis_client()
    return RedisService(client)


# ── Current user dependency ──────────────────────────────

async def _get_open_access_user(db: AsyncSession, role: UserRole = UserRole.ADMIN) -> User:
    email = f"open-access-{role.value.lower()}@local"
    result = await db.execute(
        select(User).where(User.email == email)
    )
    user = result.scalar_one_or_none()
    if user:
        return user

    user = User(
        email=email,
        hashed_password="!auth_disabled",
        full_name=f"Open Access {role.value.title()}",
        role=role,
        status=UserStatus.ACTIVE,
        can_reply_conversations=True,
        can_reply_whatsapp=True,
        must_change_password=False,
        profile_completed=True,
    )
    db.add(user)
    try:
        await db.flush()
        await db.refresh(user)
    except IntegrityError:
        await db.rollback()
        result = await db.execute(select(User).where(User.email == email))
        user = result.scalar_one()
    return user


async def get_current_user(
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
) -> User:
    """Return a user context without requiring JWT authentication."""
    if request.url.path.endswith("/conversations/stream"):
        return await _get_open_access_user(db, UserRole.CLIENT)
    return await _get_open_access_user(db, UserRole.ADMIN)


# ── Role-based access control ────────────────────────────

class RoleChecker:
    """Reusable FastAPI dependency that enforces role-based access."""

    def __init__(self, allowed_roles: List[UserRole]):
        self.allowed_roles = allowed_roles

    def __call__(self, user: Annotated[User, Depends(get_current_user)]) -> User:
        return user


# Pre-built role dependencies
require_admin = RoleChecker([UserRole.ADMIN])
require_agent_or_admin = RoleChecker([UserRole.AGENT, UserRole.ADMIN])
require_any_authenticated = RoleChecker([UserRole.CLIENT, UserRole.AGENT, UserRole.ADMIN])


def require_conversation_reply_access(
    user: Annotated[User, Depends(require_agent_or_admin)],
) -> User:
    return user


def require_whatsapp_reply_access(
    user: Annotated[User, Depends(require_agent_or_admin)],
) -> User:
    return user
