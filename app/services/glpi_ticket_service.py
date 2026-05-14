"""
Service for fetching GLPI tickets via the Laravel proxy and transforming
them to the frontend-expected format.
"""

import httpx
import logging
from typing import Any, Optional
from uuid import uuid4

from sqlalchemy import text
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.db.models.enums import TicketStatus, TicketPriority

logger = logging.getLogger(__name__)
settings = get_settings()

GLPI_STATUS_MAP = {
    1: "open",
    2: "in_progress",
    3: "in_progress",
    4: "in_progress",
    5: "resolved",
    6: "closed",
}

GLPI_PRIORITY_MAP = {
    1: "low",
    2: "low",
    3: "medium",
    4: "high",
    5: "critical",
    6: "critical",
}

FASTAPI_STATUS_TO_GLPI = {
    TicketStatus.OPEN: 1,
    TicketStatus.IN_PROGRESS: 2,
    TicketStatus.WAITING_ON_CUSTOMER: 4,
    TicketStatus.ESCALATED: 2,
    TicketStatus.RESOLVED: 5,
    TicketStatus.CLOSED: 6,
}

FASTAPI_PRIORITY_TO_GLPI = {
    TicketPriority.LOW: 1,
    TicketPriority.MEDIUM: 3,
    TicketPriority.HIGH: 4,
    TicketPriority.CRITICAL: 5,
}


def _map_glpi_status(glpi_status: int) -> str:
    return GLPI_STATUS_MAP.get(glpi_status, "open")


def _map_glpi_priority(glpi_priority: int) -> str:
    return GLPI_PRIORITY_MAP.get(glpi_priority, "medium")


def _transform_glpi_ticket(t: dict[str, Any], uuid_map: Optional[dict[int, str]] = None) -> dict[str, Any]:
    glpi_id = t.get("id")
    mapped = uuid_map.get(int(glpi_id)) if uuid_map and glpi_id else None
    fastapi_id = str(mapped) if mapped else None
    return {
        "id": fastapi_id or str(uuid4()),
        "fastapi_ticket_id": fastapi_id,
        "subject": t.get("name", ""),
        "description": t.get("content", ""),
        "status": _map_glpi_status(int(t.get("status", 1))),
        "priority": _map_glpi_priority(int(t.get("priority", 3))),
        "channel_source": "ticket",
        "escalation_flag": False,
        "creator_id": str(t.get("users_id_recipient", "")),
        "assigned_agent_id": str(t.get("users_id_lastupdater", "")) if t.get("users_id_lastupdater") else None,
        "conversation_id": None,
        "resolution_note": t.get("solution", None),
        "created_at": t.get("date_creation", t.get("date", "")),
        "updated_at": t.get("date_mod", ""),
        "glpi_ticket_id": glpi_id,
        "glpi_sync_status": "synced",
    }


async def get_laravel_admin_glpi_id(laravel_db: AsyncSession) -> Optional[int]:
    """Query the Laravel DB for an admin/super_admin user with glpi_user_id."""
    result = await laravel_db.execute(
        text(
            "SELECT glpi_user_id FROM users "
            "WHERE glpi_user_id IS NOT NULL "
            "AND role IN ('admin', 'super_admin') "
            "ORDER BY glpi_user_id LIMIT 1"
        )
    )
    row = result.fetchone()
    if row:
        return int(row[0])
    return None


async def list_glpi_tickets(
    laravel_db: Optional[AsyncSession] = None,
    range_str: str = "0-999",
    glpi_to_uuid_map: Optional[dict[int, str]] = None,
) -> list[dict[str, Any]]:
    """Fetch tickets from GLPI via the Laravel proxy."""
    url = f"{settings.GLPI_LIST_URL}?range={range_str}"
    try:
        async with httpx.AsyncClient(timeout=30.0) as client:
            resp = await client.get(url)
            resp.raise_for_status()
            data = resp.json()
            if not data.get("success"):
                logger.warning("GLPI list returned success=false: %s", data)
                return []
            items = data.get("data", [])
            return [_transform_glpi_ticket(t, uuid_map=glpi_to_uuid_map) for t in items]
    except Exception as e:
        logger.error("Failed to fetch GLPI tickets: %s", e)
        return []
