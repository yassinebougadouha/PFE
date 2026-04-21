"""
Audit log routes.
"""

import csv
import io
import uuid
from datetime import date
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, status
from fastapi.responses import StreamingResponse
from sqlalchemy.ext.asyncio import AsyncSession

from app.api.deps import require_admin
from app.db.models.enums import AuditAction
from app.db.models.user import User
from app.db.session import get_db
from app.schemas.audit import AuditClearRequest, AuditLogListResponse
from app.schemas.common import MessageOut
from app.services.audit_service import AuditService

router = APIRouter(prefix="/audit", tags=["Audit Logs"])


@router.get("", response_model=AuditLogListResponse)
async def list_audit_logs(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
    action: Optional[AuditAction] = Query(None),
    resource_type: Optional[str] = Query(None),
    user_id: Optional[uuid.UUID] = Query(None),
    search: Optional[str] = Query(None),
    date_from: Optional[date] = Query(None),
    date_to: Optional[date] = Query(None),
    skip: int = Query(0, ge=0),
    limit: int = Query(100, ge=1, le=500),
):
    svc = AuditService(db)
    logs, total = await svc.list_logs(
        action=action,
        resource_type=resource_type,
        user_id=user_id,
        search=search,
        date_from=date_from,
        date_to=date_to,
        skip=skip,
        limit=limit,
    )
    return {"logs": logs, "total": total}


@router.get("/export")
async def export_audit_logs(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
    action: Optional[AuditAction] = Query(None),
    resource_type: Optional[str] = Query(None),
    user_id: Optional[uuid.UUID] = Query(None),
    search: Optional[str] = Query(None),
    date_from: Optional[date] = Query(None),
    date_to: Optional[date] = Query(None),
):
    svc = AuditService(db)
    logs = await svc.export_logs(
        action=action,
        resource_type=resource_type,
        user_id=user_id,
        search=search,
        date_from=date_from,
        date_to=date_to,
    )

    buffer = io.StringIO()
    writer = csv.writer(buffer)
    writer.writerow([
        "id",
        "created_at",
        "user_id",
        "action",
        "resource_type",
        "resource_id",
        "description",
        "trace_id",
        "ip_address",
    ])
    for log in logs:
        writer.writerow([
            str(log.id),
            log.created_at.isoformat(),
            str(log.user_id) if log.user_id else "",
            log.action.value,
            log.resource_type,
            log.resource_id or "",
            log.description or "",
            log.trace_id or "",
            log.ip_address or "",
        ])

    buffer.seek(0)
    filename = f"audit_logs_{date.today().isoformat()}.csv"
    headers = {"Content-Disposition": f'attachment; filename="{filename}"'}
    return StreamingResponse(iter([buffer.getvalue()]), media_type="text/csv", headers=headers)


@router.delete("/clear", response_model=MessageOut)
async def clear_audit_logs(
    payload: AuditClearRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    if payload.confirmation.strip() != "CLEAR AUDIT LOGS":
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Confirmation text must be exactly: CLEAR AUDIT LOGS",
        )

    svc = AuditService(db)
    cleared = await svc.clear_logs()
    return {"message": f"Cleared {cleared} audit logs"}
