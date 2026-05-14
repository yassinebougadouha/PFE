"""Simple test: create 1 ticket, verify Laravel admin GLPI ID is used."""
import asyncio, os, sys
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))
from sqlalchemy import select
from app.db.session import async_session_factory
from app.db.models.enums import TicketPriority, ChannelType, UserRole
from app.db.models.user import User
from app.services.ticket_service import TicketService
from app.schemas.ticket import TicketCreate

async def main():
    async with async_session_factory() as db:
        admin = (await db.execute(select(User).where(User.role == UserRole.ADMIN).limit(1))).scalar_one()
        svc = TicketService(db)
        ticket = await svc.create_ticket(
            admin.id,
            TicketCreate(subject="Laravel Admin Assignment Test", description="Verify GLPI assignee is a Laravel admin", priority=TicketPriority.CRITICAL, channel_source=ChannelType.TICKET),
        )
        print(f"Created ticket: glpi_id={ticket.glpi_ticket_id}, status={ticket.status.value}, sync={ticket.glpi_sync_status}")
    print("Done")

asyncio.run(main())
