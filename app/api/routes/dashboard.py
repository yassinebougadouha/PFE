"""
Dashboard summary routes.
"""

import json
import logging
from typing import Annotated

from fastapi import APIRouter, Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession

from app.api.deps import RedisService, get_current_user, get_redis
from app.core.config import get_settings
from app.db.models.user import User
from app.db.session import get_db
from app.schemas.dashboard import DashboardSummaryResponse
from app.services.dashboard_service import DashboardService

router = APIRouter(prefix="/dashboard", tags=["Dashboard"])
settings = get_settings()
logger = logging.getLogger(__name__)


def _dashboard_summary_cache_key(user_id: str, assisted_draft_days: int) -> str:
    return f"dashboard:summary:{user_id}:days:{assisted_draft_days}"


@router.get("/summary", response_model=DashboardSummaryResponse)
async def get_dashboard_summary(
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
    current_user: Annotated[User, Depends(get_current_user)],
    assisted_draft_days: Annotated[
        int,
        Query(description="Assisted draft lookback window in days"),
    ] = 30,
):
    cache_key = _dashboard_summary_cache_key(str(current_user.id), assisted_draft_days)

    try:
        cached = await redis.get(cache_key)
        if cached:
            return DashboardSummaryResponse.model_validate(json.loads(cached))
    except Exception:
        logger.warning("Failed to read dashboard summary cache", exc_info=True)

    service = DashboardService(db)
    summary = await service.get_summary(current_user, assisted_draft_days=assisted_draft_days)

    try:
        await redis.set(
            cache_key,
            json.dumps(summary.model_dump(mode="json")),
            ttl=max(5, int(settings.DASHBOARD_SUMMARY_CACHE_TTL_SECONDS)),
        )
    except Exception:
        logger.warning("Failed to store dashboard summary cache", exc_info=True)

    return summary
