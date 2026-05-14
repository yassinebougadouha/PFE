"""
Ticket routes.
"""

import uuid
from typing import Any
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, and_, or_

from app.api.deps import get_current_user, require_admin, require_agent_or_admin
from app.db.models.enums import TicketPriority, TicketStatus, UserRole
from app.db.models.user import User
from app.db.models.ticket import Ticket
from app.db.session import get_db
from app.schemas.common import MessageOut
from app.schemas.ticket import (
    TicketCreate,
    TicketGlpiSyncResponse,
    TicketListResponse,
    TicketResponse,
    TicketStatusUpdate,
    TicketTotalsResponse,
    TicketUpdate,
)
from app.schemas.ticket_ai import (
    TicketClassifyRequest,
    TicketClassifyResponse,
    TicketReformulateRequest,
    TicketReformulateResponse,
    SimilarTicketsResponse,
    SimilarTicketItem,
)
from app.decision_engine.classifier import classify_text
from app.decision_engine.scorer import assess_risk
from app.decision_engine.response_suggester import get_response_suggestions
from app.decision_engine.rules import apply_rules
from app.services.settings_service import SettingsService
from app.services.ticket_service import TicketService
from app.services.glpi_ticket_service import list_glpi_tickets

router = APIRouter(prefix="/tickets", tags=["Tickets"])


_CATEGORY_LABELS = {
    "BILLING": "Facturation",
    "TECHNICAL": "Technique",
    "ACCOUNT": "Compte",
    "GENERAL": "Général",
    "COMPLAINT": "Réclamation",
    "FEATURE_REQUEST": "Demande de fonctionnalité",
    "SECURITY": "Sécurité",
    "URGENT": "Urgent",
}

_PRIORITY_LABELS = {
    1: "Très basse",
    2: "Basse",
    3: "Moyenne",
    4: "Haute",
    5: "Critique",
}


def _map_risk_to_priority_number(risk_level: str) -> int:
    normalized = risk_level.upper()
    if normalized == "CRITICAL":
        return 5
    if normalized == "HIGH":
        return 4
    if normalized == "MEDIUM":
        return 3
    if normalized == "LOW":
        return 2
    return 3


def _shape_ticket_for_similarity(ticket: Ticket) -> SimilarTicketItem:
    return SimilarTicketItem(
        id=ticket.id,
        title=ticket.subject,
        description=(ticket.description or "")[:150] or None,
        solution=None,
        source="local",
    )


@router.post("/classify", response_model=TicketClassifyResponse)
async def classify_ticket_text(
    payload: TicketClassifyRequest,
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Classify ticket input for client-side helper UX."""
    title = (payload.title or "").strip()
    description = (payload.description or "").strip()

    if len(title) < 5:
        return TicketClassifyResponse(available=False)

    classification = classify_text(text=description, subject=title)
    risk = assess_risk(
        text=description,
        subject=title,
        classification=classification,
        existing_priority=TicketPriority.MEDIUM,
        has_escalation_flag=False,
    )
    priority_number = _map_risk_to_priority_number(risk.risk_level.value)

    suggestions = get_response_suggestions(
        category=classification.intent_category,
        confidence_level=classification.confidence_level,
        outcome=apply_rules(
            confidence_level=classification.confidence_level,
            risk_level=risk.risk_level,
            category=classification.intent_category,
        )[0],
        max_suggestions=2,
    )

    confidence_pct = int(round(classification.confidence_score * 100))
    urgency = min(5, max(1, priority_number))

    return TicketClassifyResponse(
        available=True,
        category=classification.intent_category.value.lower(),
        category_label=_CATEGORY_LABELS.get(classification.intent_category.value, "Autre"),
        priority=priority_number,
        priority_label=_PRIORITY_LABELS.get(priority_number, "Moyenne"),
        urgency=urgency,
        confidence=confidence_pct,
        solutions=suggestions,
    )


@router.post("/reformulate", response_model=TicketReformulateResponse)
async def reformulate_ticket_text(
    payload: TicketReformulateRequest,
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Reformulate ticket description with a deterministic clarity helper."""
    title = (payload.title or "").strip()
    description = (payload.description or "").strip()

    if not description:
        return TicketReformulateResponse(available=False, reformulated="")

    compact = " ".join(description.split())
    intro = f"Sujet: {title}. " if title else ""
    if not compact.endswith((".", "!", "?")):
        compact = f"{compact}."

    reformulated = f"{intro}{compact}"
    return TicketReformulateResponse(available=True, reformulated=reformulated)


@router.get("/similar", response_model=SimilarTicketsResponse)
async def find_similar_tickets(
    q: str = Query("", min_length=0),
    db: Annotated[AsyncSession, Depends(get_db)] = None,
    current_user: Annotated[User, Depends(get_current_user)] = None,
):
    """Return similar resolved tickets for client guidance."""
    query = (q or "").strip()
    if len(query) < 4:
        return SimilarTicketsResponse(tickets=[])

    words = [w for w in query.lower().replace("_", " ").split() if len(w) > 2][:5]
    if not words:
        return SimilarTicketsResponse(tickets=[])

    conditions = []
    for word in words:
        like = f"%{word}%"
        conditions.append(Ticket.subject.ilike(like))
        conditions.append(Ticket.description.ilike(like))

    visibility_filter = []
    if current_user.role == UserRole.CLIENT:
        visibility_filter.append(Ticket.creator_id == current_user.id)
    elif current_user.role == UserRole.AGENT:
        visibility_filter.append(
            or_(
                Ticket.assigned_agent_id == current_user.id,
                Ticket.assigned_agent_id == None,
            )
        )

    query_stmt = (
        select(Ticket)
        .where(
            and_(
                Ticket.is_deleted == False,
                Ticket.status.in_([TicketStatus.RESOLVED, TicketStatus.CLOSED]),
                or_(*conditions),
                or_(*visibility_filter) if visibility_filter else True,
            )
        )
        .order_by(Ticket.updated_at.desc())
        .limit(5)
    )

    result = await db.execute(query_stmt)
    tickets = list(result.scalars().all())
    return SimilarTicketsResponse(tickets=[_shape_ticket_for_similarity(t) for t in tickets])


@router.post("", response_model=TicketResponse, status_code=status.HTTP_201_CREATED)
async def create_ticket(
    payload: TicketCreate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Create a new ticket."""
    svc = TicketService(db)
    return await svc.create_ticket(current_user.id, payload)


@router.get("", response_model=TicketListResponse)
async def list_tickets(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    status_filter: Optional[TicketStatus] = Query(None, alias="status"),
    priority: Optional[TicketPriority] = Query(None),
    include_total: bool = Query(True),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=1000),
):
    """List tickets. Clients see own. Agents see assigned + unassigned. Admins see all."""
    svc = TicketService(db)

    creator_id = None
    assigned_agent_id = None
    include_unassigned = False
    if current_user.role == UserRole.CLIENT:
        creator_id = current_user.id
    elif current_user.role == UserRole.AGENT:
        assigned_agent_id = current_user.id
        include_unassigned = True

    tickets, total = await svc.list_tickets(
        creator_id=creator_id,
        assigned_agent_id=assigned_agent_id,
        include_unassigned=include_unassigned,
        status=status_filter,
        priority=priority,
        include_total=include_total,
        skip=skip,
        limit=limit,
    )
    return {"tickets": tickets, "total": total}


@router.get("/totals", response_model=TicketTotalsResponse)
async def get_ticket_totals(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    priority: Optional[TicketPriority] = Query(None),
):
    """Return role-scoped ticket totals grouped by status in one query."""
    svc = TicketService(db)

    creator_id = None
    assigned_agent_id = None
    include_unassigned = False
    if current_user.role == UserRole.CLIENT:
        creator_id = current_user.id
    elif current_user.role == UserRole.AGENT:
        assigned_agent_id = current_user.id
        include_unassigned = True

    counts = await svc.count_by_status(
        creator_id=creator_id,
        assigned_agent_id=assigned_agent_id,
        include_unassigned=include_unassigned,
        priority=priority,
    )
    open_count = counts.get(TicketStatus.OPEN, 0)
    in_progress_count = (
        counts.get(TicketStatus.IN_PROGRESS, 0)
        + counts.get(TicketStatus.WAITING_ON_CUSTOMER, 0)
    )
    escalated_count = counts.get(TicketStatus.ESCALATED, 0)
    resolved_count = counts.get(TicketStatus.RESOLVED, 0)
    closed_count = counts.get(TicketStatus.CLOSED, 0)

    return TicketTotalsResponse(
        total=int(sum(counts.values())),
        open=int(open_count),
        in_progress=int(in_progress_count),
        escalated=int(escalated_count),
        resolved=int(resolved_count),
        closed=int(closed_count),
    )


@router.get("/glpi-list", response_model=dict)
async def list_glpi_tickets_endpoint(
    range: str = Query("0-999"),
    db: Annotated[AsyncSession, Depends(get_db)] = None,
    _: Annotated[User, Depends(require_agent_or_admin)] = None,
):
    """List all tickets from GLPI via the Laravel proxy, with local UUID mapping."""
    from sqlalchemy import select
    result = await db.execute(select(Ticket.glpi_ticket_id, Ticket.id).where(Ticket.glpi_ticket_id.isnot(None)))
    uuid_map = {int(row[0]): str(row[1]) for row in result.all() if row[0]}
    tickets = await list_glpi_tickets(range_str=range, glpi_to_uuid_map=uuid_map)
    return {"tickets": tickets, "total": len(tickets)}


@router.get("/{ticket_id}", response_model=TicketResponse)
async def get_ticket(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    svc = TicketService(db)
    ticket = await svc.get_ticket(ticket_id)
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")
    if current_user.role == UserRole.CLIENT and ticket.creator_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    return ticket


@router.patch("/{ticket_id}", response_model=TicketResponse)
async def update_ticket(
    ticket_id: uuid.UUID,
    payload: TicketUpdate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Update a ticket. Clients can update their own tickets."""
    svc = TicketService(db)
    existing = await svc.get_ticket(ticket_id)
    if not existing:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")

    if current_user.role == UserRole.CLIENT:
        if existing.creator_id != current_user.id:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

        payload_data = payload.model_dump(exclude_unset=True)
        forbidden_fields = {
            "assigned_agent_id",
            "escalation_flag",
            "resolution_note",
            "source_voice_call_id",
        }
        if any(field in payload_data for field in forbidden_fields):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Clients cannot update assignment or escalation settings",
            )

        if "status" in payload_data:
            next_status = payload_data["status"]
            if next_status != TicketStatus.CLOSED:
                raise HTTPException(
                    status_code=status.HTTP_403_FORBIDDEN,
                    detail="Clients can only close their own tickets",
                )

            settings = await SettingsService(db).get_all_settings()
            if not settings["allow_client_close"]:
                raise HTTPException(
                    status_code=status.HTTP_403_FORBIDDEN,
                    detail="Client ticket closing is disabled by admin policy",
                )
        payload = TicketUpdate(**payload_data)
    elif current_user.role not in {UserRole.AGENT, UserRole.ADMIN}:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Insufficient permissions")
    elif payload.status is not None:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Use the dedicated /tickets/{ticket_id}/status endpoint for operator status transitions",
        )

    ticket = await svc.update_ticket(ticket_id, payload)
    return ticket


@router.post("/{ticket_id}/status", response_model=TicketResponse)
async def update_ticket_status(
    ticket_id: uuid.UUID,
    payload: TicketStatusUpdate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = TicketService(db)
    existing = await svc.get_ticket(ticket_id)
    if not existing:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")

    if current_user.role == UserRole.AGENT and existing.assigned_agent_id not in {None, current_user.id}:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

    try:
        ticket = await svc.update_ticket_status(ticket_id, payload, actor=current_user)
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(exc))

    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")
    return ticket


@router.post("/{ticket_id}/assign/{agent_id}", response_model=TicketResponse)
async def assign_ticket(
    ticket_id: uuid.UUID,
    agent_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    """Assign ticket to agent. Admin only."""
    svc = TicketService(db)
    try:
        ticket = await svc.assign_agent(ticket_id, agent_id)
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(exc))
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")
    return ticket


@router.delete("/{ticket_id}", response_model=MessageOut)
async def delete_ticket(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Soft-delete a ticket. Clients can delete their own tickets."""
    svc = TicketService(db)
    existing = await svc.get_ticket(ticket_id)
    if not existing:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")

    if current_user.role == UserRole.CLIENT:
        if existing.creator_id != current_user.id:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    elif current_user.role != UserRole.ADMIN:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Insufficient permissions")

    await svc.soft_delete(ticket_id)
    return {"message": "Ticket deleted"}


# ══════════════════════════════════════════════════════════════════════════════
# GLPI SYNCHRONIZATION ENDPOINTS
# ══════════════════════════════════════════════════════════════════════════════

@router.post("/{ticket_id}/glpi/sync", response_model=TicketGlpiSyncResponse)
async def sync_ticket_to_glpi(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    """
    Manually sync ticket to GLPI.
    Admin only.
    """
    svc = TicketService(db)
    ticket = await svc.get_ticket(ticket_id)
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")

    success = await svc.sync_to_glpi(ticket)
    
    return TicketGlpiSyncResponse(
        success=success,
        ticket_id=ticket.id,
        glpi_ticket_id=ticket.glpi_ticket_id,
        sync_status=ticket.glpi_sync_status,
        message="Ticket synced to GLPI" if success else "Failed to sync ticket to GLPI",
        error=ticket.glpi_sync_error,
    )


@router.post("/glpi/{glpi_ticket_id}/sync", response_model=TicketResponse)
async def sync_ticket_from_glpi(
    glpi_ticket_id: int,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    """
    Fetch ticket from GLPI and update local database.
    Admin only.
    """
    svc = TicketService(db)
    ticket = await svc.sync_from_glpi(glpi_ticket_id)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found in GLPI or local database"
        )
    return ticket


@router.get("/{ticket_id}/glpi/status", response_model=dict)
async def get_glpi_sync_status(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Get GLPI sync status for a ticket."""
    svc = TicketService(db)
    ticket = await svc.get_ticket(ticket_id)
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")

    # Clients can only view their own tickets
    if current_user.role == UserRole.CLIENT and ticket.creator_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

    return {
        "ticket_id": str(ticket.id),
        "glpi_ticket_id": ticket.glpi_ticket_id,
        "sync_status": ticket.glpi_sync_status,
        "sync_error": ticket.glpi_sync_error,
        "synced_at": ticket.updated_at.isoformat() if ticket.updated_at else None,
    }
