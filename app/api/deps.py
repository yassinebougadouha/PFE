"""
Shared API dependencies: current user, role enforcement, Redis.
"""

import uuid
from typing import Annotated, List

from fastapi import Depends, HTTPException, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
import redis.asyncio as aioredis
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.security import decode_token
from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import UserRole, UserStatus
from app.services.user_service import UserService
from app.services.redis_service import RedisService, get_redis_client

security_scheme = HTTPBearer()

# ── Redis dependency ─────────────────────────────────────

async def get_redis() -> RedisService:
    client = await get_redis_client()
    return RedisService(client)


# ── Current user dependency ──────────────────────────────

async def get_current_user(
    credentials: Annotated[HTTPAuthorizationCredentials, Depends(security_scheme)],
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
) -> User:
    """Extract user from JWT access token. Raises 401 on failure."""
    token = credentials.credentials
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )

    # Check blacklist
    if await redis.is_token_blacklisted(token):
        raise credentials_exception

    try:
        payload = decode_token(token)
    except Exception:
        raise credentials_exception

    if payload.get("type") != "access":
        raise credentials_exception

    user_id = payload.get("sub")
    if not user_id:
        raise credentials_exception

    user_service = UserService(db)
    user = await user_service.get_by_id(uuid.UUID(user_id))
    if not user:
        raise credentials_exception
    if user.status != UserStatus.ACTIVE:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Account suspended")
    return user


# ── Role-based access control ────────────────────────────

class RoleChecker:
    """Reusable FastAPI dependency that enforces role-based access."""

    def __init__(self, allowed_roles: List[UserRole]):
        self.allowed_roles = allowed_roles

    def __call__(self, user: Annotated[User, Depends(get_current_user)]) -> User:
        if user.role not in self.allowed_roles:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Insufficient permissions",
            )
        return user


# Pre-built role dependencies
require_admin = RoleChecker([UserRole.ADMIN])
require_agent_or_admin = RoleChecker([UserRole.AGENT, UserRole.ADMIN])
require_any_authenticated = RoleChecker([UserRole.CLIENT, UserRole.AGENT, UserRole.ADMIN])
