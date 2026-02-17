"""
Ticket routes.
"""

import uuid
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import TicketStatus, TicketPriority, UserRole
from app.api.deps import get_current_user, require_agent_or_admin, require_admin
from app.schemas.ticket import TicketCreate, TicketResponse, TicketUpdate, TicketListResponse
from app.schemas.common import MessageOut
from app.services.ticket_service import TicketService

router = APIRouter(prefix="/tickets", tags=["Tickets"])


@router.post("", response_model=TicketResponse, status_code=status.HTTP_201_CREATED)
async def create_ticket(
    payload: TicketCreate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Create a new ticket (any authenticated user)."""
    svc = TicketService(db)
    return await svc.create_ticket(current_user.id, payload)


@router.get("", response_model=TicketListResponse)
async def list_tickets(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    status_filter: Optional[TicketStatus] = Query(None, alias="status"),
    priority: Optional[TicketPriority] = Query(None),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=200),
):
    """List tickets. Clients see own. Agents see assigned. Admins see all."""
    svc = TicketService(db)

    creator_id = None
    assigned_agent_id = None
    if current_user.role == UserRole.CLIENT:
        creator_id = current_user.id
    elif current_user.role == UserRole.AGENT:
        assigned_agent_id = current_user.id

    tickets, total = await svc.list_tickets(
        creator_id=creator_id,
        assigned_agent_id=assigned_agent_id,
        status=status_filter,
        priority=priority,
        skip=skip,
        limit=limit,
    )
    return {"tickets": tickets, "total": total}


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
    # Access control
    if current_user.role == UserRole.CLIENT and ticket.creator_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    return ticket


@router.patch("/{ticket_id}", response_model=TicketResponse)
async def update_ticket(
    ticket_id: uuid.UUID,
    payload: TicketUpdate,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """Update ticket — agents/admins."""
    svc = TicketService(db)
    ticket = await svc.update_ticket(ticket_id, payload)
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
    """Assign ticket to agent — admin only."""
    svc = TicketService(db)
    ticket = await svc.assign_agent(ticket_id, agent_id)
    if not ticket:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")
    return ticket


@router.delete("/{ticket_id}", response_model=MessageOut)
async def delete_ticket(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    """Soft-delete ticket — admin only."""
    svc = TicketService(db)
    deleted = await svc.soft_delete(ticket_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ticket not found")
    return {"message": "Ticket deleted"}
