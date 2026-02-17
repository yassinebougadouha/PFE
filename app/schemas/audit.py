"""
Audit log schemas (read-only, admins only).
"""

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel

from app.db.models.enums import AuditAction


class AuditLogResponse(BaseModel):
    id: uuid.UUID
    user_id: Optional[uuid.UUID]
    action: AuditAction
    resource_type: str
    resource_id: Optional[str]
    description: Optional[str]
    meta: Optional[dict]
    trace_id: Optional[str]
    ip_address: Optional[str]
    created_at: datetime

    model_config = {"from_attributes": True}


class AuditLogListResponse(BaseModel):
    logs: list[AuditLogResponse]
    total: int
