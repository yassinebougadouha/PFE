"""
Audit service for writing, filtering, exporting, and clearing audit logs.
"""

import uuid
from datetime import date, datetime, time, timezone
from typing import Optional

from sqlalchemy import delete, func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.audit_log import AuditLog
from app.db.models.enums import AuditAction


class AuditService:

    def __init__(self, db: AsyncSession):
        self.db = db

    async def log(
        self,
        action: AuditAction,
        resource_type: str,
        resource_id: Optional[str] = None,
        user_id: Optional[uuid.UUID] = None,
        description: Optional[str] = None,
        meta: Optional[dict] = None,
        trace_id: Optional[str] = None,
        ip_address: Optional[str] = None,
    ) -> AuditLog:
        entry = AuditLog(
            user_id=user_id,
            action=action,
            resource_type=resource_type,
            resource_id=resource_id,
            description=description,
            meta=meta,
            trace_id=trace_id,
            ip_address=ip_address,
        )
        self.db.add(entry)
        await self.db.flush()
        return entry

    async def list_logs(
        self,
        action: Optional[AuditAction] = None,
        resource_type: Optional[str] = None,
        user_id: Optional[uuid.UUID] = None,
        search: Optional[str] = None,
        date_from: Optional[date] = None,
        date_to: Optional[date] = None,
        skip: int = 0,
        limit: int = 100,
    ) -> tuple[list[AuditLog], int]:
        query, count_q = self._build_filtered_queries(
            action=action,
            resource_type=resource_type,
            user_id=user_id,
            search=search,
            date_from=date_from,
            date_to=date_to,
        )

        total = (await self.db.execute(count_q)).scalar() or 0
        result = await self.db.execute(
            query.offset(skip).limit(limit).order_by(AuditLog.created_at.desc())
        )
        return list(result.scalars().all()), total

    async def export_logs(
        self,
        action: Optional[AuditAction] = None,
        resource_type: Optional[str] = None,
        user_id: Optional[uuid.UUID] = None,
        search: Optional[str] = None,
        date_from: Optional[date] = None,
        date_to: Optional[date] = None,
    ) -> list[AuditLog]:
        query, _ = self._build_filtered_queries(
            action=action,
            resource_type=resource_type,
            user_id=user_id,
            search=search,
            date_from=date_from,
            date_to=date_to,
        )
        result = await self.db.execute(query.order_by(AuditLog.created_at.desc()))
        return list(result.scalars().all())

    async def clear_logs(self) -> int:
        count = (await self.db.execute(select(func.count(AuditLog.id)))).scalar() or 0
        await self.db.execute(delete(AuditLog))
        await self.db.flush()
        return int(count)

    def _build_filtered_queries(
        self,
        *,
        action: Optional[AuditAction] = None,
        resource_type: Optional[str] = None,
        user_id: Optional[uuid.UUID] = None,
        search: Optional[str] = None,
        date_from: Optional[date] = None,
        date_to: Optional[date] = None,
    ):
        query = select(AuditLog)
        count_q = select(func.count(AuditLog.id))

        if action:
            query = query.where(AuditLog.action == action)
            count_q = count_q.where(AuditLog.action == action)
        if resource_type:
            query = query.where(AuditLog.resource_type == resource_type)
            count_q = count_q.where(AuditLog.resource_type == resource_type)
        if user_id:
            query = query.where(AuditLog.user_id == user_id)
            count_q = count_q.where(AuditLog.user_id == user_id)
        if search:
            pattern = f"%{search.strip()}%"
            filters = or_(
                AuditLog.description.ilike(pattern),
                AuditLog.resource_type.ilike(pattern),
                AuditLog.resource_id.ilike(pattern),
            )
            query = query.where(filters)
            count_q = count_q.where(filters)
        if date_from:
            start_dt = datetime.combine(date_from, time.min, tzinfo=timezone.utc)
            query = query.where(AuditLog.created_at >= start_dt)
            count_q = count_q.where(AuditLog.created_at >= start_dt)
        if date_to:
            end_dt = datetime.combine(date_to, time.max, tzinfo=timezone.utc)
            query = query.where(AuditLog.created_at <= end_dt)
            count_q = count_q.where(AuditLog.created_at <= end_dt)

        return query, count_q
