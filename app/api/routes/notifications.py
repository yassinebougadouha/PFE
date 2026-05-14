"""
Notification center routes.
"""

import logging
import uuid
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from app.api.deps import RedisService, get_current_user, get_redis
from app.core.config import get_settings
from app.db.models.user import User
from app.db.session import get_db
from app.schemas.common import MessageOut
from app.schemas.notification import (
    NotificationListResponse,
    NotificationResponse,
    NotificationUnreadCountResponse,
)
from app.services.notification_service import NotificationService

router = APIRouter(prefix="/notifications", tags=["Notifications"])
settings = get_settings()
logger = logging.getLogger(__name__)


def _unread_count_cache_key(user_id: str) -> str:
    return f"notifications:unread-count:{user_id}"


@router.get("", response_model=NotificationListResponse)
async def list_notifications(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    unread_only: bool = Query(False),
    skip: int = Query(0, ge=0),
    limit: int = Query(20, ge=1, le=1000),
):
    service = NotificationService(db)
    items, total, unread_count = await service.list_for_user(
        current_user.id,
        unread_only=unread_only,
        skip=skip,
        limit=limit,
    )
    return NotificationListResponse(
        items=[NotificationResponse.model_validate(item) for item in items],
        total=total,
        unread_count=unread_count,
    )


@router.get("/unread-count", response_model=NotificationUnreadCountResponse)
async def get_unread_count(
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    cache_key = _unread_count_cache_key(str(current_user.id))

    try:
        cached = await redis.get(cache_key)
        if cached is not None:
            try:
                return {"unread_count": int(cached)}
            except (TypeError, ValueError):
                await redis.delete(cache_key)
    except Exception:
        logger.warning("Failed to read unread notification cache", exc_info=True)

    service = NotificationService(db)
    unread_count = await service.get_unread_count(current_user.id)
    try:
        await redis.set(
            cache_key,
            str(unread_count),
            ttl=max(5, int(settings.NOTIFICATIONS_UNREAD_COUNT_CACHE_TTL_SECONDS)),
        )
    except Exception:
        logger.warning("Failed to write unread notification cache", exc_info=True)
    return {"unread_count": unread_count}


@router.post("/{notification_id}/read", response_model=NotificationResponse)
async def mark_notification_read(
    notification_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    service = NotificationService(db)
    notification = await service.mark_read(notification_id, current_user.id)
    if not notification:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Notification not found")
    try:
        await redis.delete(_unread_count_cache_key(str(current_user.id)))
    except Exception:
        logger.warning("Failed to invalidate unread notification cache", exc_info=True)
    return NotificationResponse.model_validate(notification)


@router.post("/read-all", response_model=MessageOut)
async def mark_all_notifications_read(
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    service = NotificationService(db)
    marked = await service.mark_all_read(current_user.id)
    try:
        await redis.delete(_unread_count_cache_key(str(current_user.id)))
    except Exception:
        logger.warning("Failed to invalidate unread notification cache", exc_info=True)
    return {"message": f"Marked {marked} notifications as read"}
