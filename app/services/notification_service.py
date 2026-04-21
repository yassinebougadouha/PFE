"""
In-app notification service.
"""

import uuid
from datetime import datetime, timezone
from typing import Optional

from sqlalchemy import func, select, update
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.notification import Notification


class NotificationService:
    def __init__(self, db: AsyncSession):
        self.db = db

    async def create_notification(
        self,
        *,
        user_id: uuid.UUID,
        type: str,
        title: str,
        body: str,
        resource_type: Optional[str] = None,
        resource_id: Optional[str] = None,
        action_url: Optional[str] = None,
        meta: Optional[dict] = None,
    ) -> Notification:
        notification = Notification(
            user_id=user_id,
            type=type,
            title=title,
            body=body,
            resource_type=resource_type,
            resource_id=resource_id,
            action_url=action_url,
            meta=meta,
        )
        self.db.add(notification)
        await self.db.flush()
        await self.db.refresh(notification)
        return notification

    async def create_many(
        self,
        *,
        user_ids: list[uuid.UUID],
        type: str,
        title: str,
        body: str,
        resource_type: Optional[str] = None,
        resource_id: Optional[str] = None,
        action_url: Optional[str] = None,
        meta: Optional[dict] = None,
    ) -> list[Notification]:
        items: list[Notification] = []
        for user_id in user_ids:
            items.append(
                await self.create_notification(
                    user_id=user_id,
                    type=type,
                    title=title,
                    body=body,
                    resource_type=resource_type,
                    resource_id=resource_id,
                    action_url=action_url,
                    meta=meta,
                )
            )
        return items

    async def list_for_user(
        self,
        user_id: uuid.UUID,
        *,
        unread_only: bool = False,
        skip: int = 0,
        limit: int = 20,
    ) -> tuple[list[Notification], int, int]:
        query = select(Notification).where(Notification.user_id == user_id)
        count_query = select(func.count(Notification.id)).where(Notification.user_id == user_id)

        if unread_only:
            query = query.where(Notification.is_read == False)
            count_query = count_query.where(Notification.is_read == False)

        unread_count_query = select(func.count(Notification.id)).where(
            Notification.user_id == user_id,
            Notification.is_read == False,
        )

        total = (await self.db.execute(count_query)).scalar() or 0
        unread_count = (await self.db.execute(unread_count_query)).scalar() or 0
        result = await self.db.execute(
            query.order_by(Notification.created_at.desc()).offset(skip).limit(limit)
        )
        return list(result.scalars().all()), total, unread_count

    async def get_unread_count(self, user_id: uuid.UUID) -> int:
        query = select(func.count(Notification.id)).where(
            Notification.user_id == user_id,
            Notification.is_read == False,
        )
        return (await self.db.execute(query)).scalar() or 0

    async def mark_read(self, notification_id: uuid.UUID, user_id: uuid.UUID) -> Notification | None:
        result = await self.db.execute(
            select(Notification).where(
                Notification.id == notification_id,
                Notification.user_id == user_id,
            )
        )
        notification = result.scalar_one_or_none()
        if not notification:
            return None

        notification.is_read = True
        notification.read_at = datetime.now(timezone.utc)
        await self.db.flush()
        await self.db.refresh(notification)
        return notification

    async def mark_all_read(self, user_id: uuid.UUID) -> int:
        stmt = (
            update(Notification)
            .where(Notification.user_id == user_id, Notification.is_read == False)
            .values(is_read=True, read_at=datetime.now(timezone.utc))
        )
        result = await self.db.execute(stmt)
        await self.db.flush()
        return int(result.rowcount or 0)
