"""
Audit log routes — admin only, read-only.
"""

import uuid
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, Query
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import AuditAction
from app.api.deps import require_admin
from app.schemas.audit import AuditLogListResponse
from app.services.audit_service import AuditService

router = APIRouter(prefix="/audit", tags=["Audit Logs"])


@router.get("", response_model=AuditLogListResponse)
async def list_audit_logs(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
    action: Optional[AuditAction] = Query(None),
    resource_type: Optional[str] = Query(None),
    user_id: Optional[uuid.UUID] = Query(None),
    skip: int = Query(0, ge=0),
    limit: int = Query(100, ge=1, le=500),
):
    """List audit logs — admin only."""
    svc = AuditService(db)
    logs, total = await svc.list_logs(
        action=action,
        resource_type=resource_type,
        user_id=user_id,
        skip=skip,
        limit=limit,
    )
    return {"logs": logs, "total": total}
