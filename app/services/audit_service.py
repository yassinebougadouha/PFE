"""
Audit service — write audit log entries for traceability.
"""

import uuid
from typing import Optional

from sqlalchemy import select, func
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
        skip: int = 0,
        limit: int = 100,
    ) -> tuple[list[AuditLog], int]:
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

        total = (await self.db.execute(count_q)).scalar() or 0
        result = await self.db.execute(
            query.offset(skip).limit(limit).order_by(AuditLog.created_at.desc())
        )
        return list(result.scalars().all()), total
