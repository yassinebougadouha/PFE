"""
Auth service — login, token generation, token refresh, blacklist.
"""

import uuid

from sqlalchemy.ext.asyncio import AsyncSession

from app.core.security import verify_password, create_access_token, create_refresh_token, decode_token
from app.services.user_service import UserService
from app.services.redis_service import RedisService
from app.db.models.user import User
from app.db.models.enums import UserStatus


class AuthService:

    def __init__(self, db: AsyncSession, redis: RedisService):
        self.db = db
        self.user_service = UserService(db)
        self.redis = redis

    async def authenticate(self, email: str, password: str) -> User | None:
        """Validate credentials. Returns user or None."""
        user = await self.user_service.get_by_email(email)
        if not user:
            return None
        if user.status != UserStatus.ACTIVE:
            return None
        if not verify_password(password, user.hashed_password):
            return None
        return user

    def generate_tokens(self, user: User, *, access_token_minutes: int | None = None) -> dict:
        """Create access + refresh token pair."""
        access = create_access_token(
            subject=str(user.id),
            extra_claims={"role": user.role.value},
            expires_minutes=access_token_minutes,
        )
        refresh = create_refresh_token(subject=str(user.id))
        return {
            "access_token": access,
            "refresh_token": refresh,
            "token_type": "bearer",
        }

    async def refresh_tokens(
        self,
        refresh_token: str,
        *,
        access_token_minutes: int | None = None,
    ) -> dict | None:
        """Validate refresh token and issue a new pair."""
        try:
            payload = decode_token(refresh_token)
        except Exception:
            return None

        if payload.get("type") != "refresh":
            return None

        # Check blacklist
        if await self.redis.is_token_blacklisted(refresh_token):
            return None

        user_id = payload.get("sub")
        user = await self.user_service.get_by_id(uuid.UUID(user_id))
        if not user or user.status != UserStatus.ACTIVE:
            return None

        # Blacklist the old refresh token
        ttl = payload.get("exp", 0)
        await self.redis.blacklist_token(refresh_token, ttl)

        return self.generate_tokens(user, access_token_minutes=access_token_minutes)

    async def logout(self, access_token: str, refresh_token: str | None = None):
        """Blacklist current tokens."""
        try:
            payload = decode_token(access_token)
            await self.redis.blacklist_token(access_token, payload.get("exp", 0))
        except Exception:
            pass

        if refresh_token:
            try:
                payload = decode_token(refresh_token)
                await self.redis.blacklist_token(refresh_token, payload.get("exp", 0))
            except Exception:
                pass
