"""
Ticket service — CRUD + assignment + escalation flag.
"""

import uuid
from datetime import datetime, timezone
from typing import Optional

from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.ticket import Ticket
from app.db.models.enums import TicketStatus, TicketPriority
from app.schemas.ticket import TicketCreate, TicketUpdate


class TicketService:

    def __init__(self, db: AsyncSession):
        self.db = db

    async def create_ticket(self, creator_id: uuid.UUID, payload: TicketCreate) -> Ticket:
        ticket = Ticket(
            subject=payload.subject,
            description=payload.description,
            priority=payload.priority,
            channel_source=payload.channel_source,
            creator_id=creator_id,
        )
        self.db.add(ticket)
        await self.db.flush()
        await self.db.refresh(ticket)
        return ticket

    async def get_ticket(self, ticket_id: uuid.UUID) -> Optional[Ticket]:
        result = await self.db.execute(
            select(Ticket).where(Ticket.id == ticket_id, Ticket.is_deleted == False)
        )
        return result.scalar_one_or_none()

    async def list_tickets(
        self,
        creator_id: Optional[uuid.UUID] = None,
        assigned_agent_id: Optional[uuid.UUID] = None,
        status: Optional[TicketStatus] = None,
        priority: Optional[TicketPriority] = None,
        skip: int = 0,
        limit: int = 50,
    ) -> tuple[list[Ticket], int]:
        query = select(Ticket).where(Ticket.is_deleted == False)
        count_q = select(func.count(Ticket.id)).where(Ticket.is_deleted == False)

        if creator_id:
            query = query.where(Ticket.creator_id == creator_id)
            count_q = count_q.where(Ticket.creator_id == creator_id)
        if assigned_agent_id:
            query = query.where(Ticket.assigned_agent_id == assigned_agent_id)
            count_q = count_q.where(Ticket.assigned_agent_id == assigned_agent_id)
        if status:
            query = query.where(Ticket.status == status)
            count_q = count_q.where(Ticket.status == status)
        if priority:
            query = query.where(Ticket.priority == priority)
            count_q = count_q.where(Ticket.priority == priority)

        total = (await self.db.execute(count_q)).scalar() or 0
        result = await self.db.execute(
            query.offset(skip).limit(limit).order_by(Ticket.created_at.desc())
        )
        return list(result.scalars().all()), total

    async def update_ticket(self, ticket_id: uuid.UUID, payload: TicketUpdate) -> Optional[Ticket]:
        ticket = await self.get_ticket(ticket_id)
        if not ticket:
            return None
        for field, value in payload.model_dump(exclude_unset=True).items():
            setattr(ticket, field, value)
        await self.db.flush()
        await self.db.refresh(ticket)
        return ticket

    async def assign_agent(self, ticket_id: uuid.UUID, agent_id: uuid.UUID) -> Optional[Ticket]:
        ticket = await self.get_ticket(ticket_id)
        if not ticket:
            return None
        ticket.assigned_agent_id = agent_id
        ticket.status = TicketStatus.IN_PROGRESS
        await self.db.flush()
        await self.db.refresh(ticket)
        return ticket

    async def soft_delete(self, ticket_id: uuid.UUID) -> bool:
        ticket = await self.get_ticket(ticket_id)
        if not ticket:
            return False
        ticket.is_deleted = True
        ticket.deleted_at = datetime.now(timezone.utc)
        await self.db.flush()
        return True
