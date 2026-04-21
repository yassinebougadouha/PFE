"""
Shared API dependencies: current user, role enforcement, Redis.
"""

import uuid
from typing import Annotated, List

from fastapi import Depends, HTTPException, Request, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
import redis.asyncio as aioredis
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.security import decode_token
from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import UserRole, UserStatus
from app.services.settings_service import SettingsService
from app.services.user_service import UserService
from app.services.redis_service import RedisService, get_redis_client

security_scheme = HTTPBearer()

# ── Redis dependency ─────────────────────────────────────

async def get_redis() -> RedisService:
    client = await get_redis_client()
    return RedisService(client)


# ── Current user dependency ──────────────────────────────

async def get_current_user(
    request: Request,
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

    user.profile_completed = UserService._compute_profile_completed(
        user.role,
        user.phone_number,
        user.teams_email,
    )

    exempt_prefixes = (
        "/api/v1/users/me",
        "/api/v1/auth/logout",
        "/api/v1/notifications",
    )
    is_exempt_path = request.url.path.startswith(exempt_prefixes)

    if user.must_change_password and not is_exempt_path:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail={
                "code": "password_change_required",
                "message": "You must change your password before continuing.",
            },
        )

    if user.role == UserRole.ADMIN and not is_exempt_path:
        settings_service = SettingsService(db)
        if await settings_service.get_bool("require_admin_profile_completion") and not user.profile_completed:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail={
                    "code": "profile_completion_required",
                    "message": "Complete your admin profile before accessing the workspace.",
                },
            )
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


def require_conversation_reply_access(
    user: Annotated[User, Depends(require_agent_or_admin)],
) -> User:
    if not user.can_reply_conversations:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Read-only mode: conversation replies are disabled for this account",
        )
    return user


def require_whatsapp_reply_access(
    user: Annotated[User, Depends(require_agent_or_admin)],
) -> User:
    if not user.can_reply_whatsapp:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Read-only mode: WhatsApp replies are disabled for this account",
        )
    return user
