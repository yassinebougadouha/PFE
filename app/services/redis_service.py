"""
Redis service — token blacklist, session cache, rate-limiting helpers.
"""

from typing import Any, Optional

import redis.asyncio as aioredis

from app.core.config import get_settings

settings = get_settings()


class RedisService:
    """Thin async wrapper around redis for application-level caching."""

    def __init__(self, redis_client: aioredis.Redis):
        self.redis = redis_client

    # ── Token blacklist ──────────────────────────────────
    async def blacklist_token(self, token: str, exp_timestamp: int):
        """Add a JWT to the blacklist until its natural expiry."""
        import time
        ttl = max(int(exp_timestamp - time.time()), 1)
        await self.redis.setex(f"bl:{token}", ttl, "1")

    async def is_token_blacklisted(self, token: str) -> bool:
        return await self.redis.exists(f"bl:{token}") > 0

    # ── Generic cache helpers ────────────────────────────
    async def set(self, key: str, value: str, ttl: int = 300):
        await self.redis.setex(key, ttl, value)

    async def get(self, key: str) -> Optional[str]:
        val = await self.redis.get(key)
        return val.decode() if val else None

    async def delete(self, key: str):
        await self.redis.delete(key)

    # ── Rate limiting placeholder ────────────────────────
    async def increment_rate(self, key: str, window: int = 60) -> int:
        """Increment a counter and set expiry for a sliding window."""
        pipe = self.redis.pipeline()
        pipe.incr(key)
        pipe.expire(key, window)
        results = await pipe.execute()
        return results[0]

    async def get_rate(self, key: str) -> int:
        val = await self.redis.get(key)
        return int(val) if val else 0


# ── Factory ─────────────────────────────────────────────
async def get_redis_client() -> aioredis.Redis:
    return aioredis.from_url(
        settings.REDIS_URL,
        decode_responses=False,
    )
