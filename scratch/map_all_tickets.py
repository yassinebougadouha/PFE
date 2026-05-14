"""Map all GLPI tickets to local FastAPI records."""
import asyncio, sys, os, httpx
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from sqlalchemy import select
from app.db.session import async_session_factory
from app.db.models.ticket import Ticket
from app.db.models.enums import TicketStatus, TicketPriority, ChannelType
from app.db.models.user import User

STATUS_MAP = {1: TicketStatus.OPEN, 2: TicketStatus.IN_PROGRESS, 3: TicketStatus.IN_PROGRESS,
              4: TicketStatus.IN_PROGRESS, 5: TicketStatus.RESOLVED, 6: TicketStatus.CLOSED}
PRIORITY_MAP = {1: TicketPriority.LOW, 2: TicketPriority.LOW, 3: TicketPriority.MEDIUM,
               4: TicketPriority.HIGH, 5: TicketPriority.CRITICAL, 6: TicketPriority.CRITICAL}

async def main():
    async with async_session_factory() as db:
        result = await db.execute(select(Ticket.glpi_ticket_id).where(Ticket.glpi_ticket_id.isnot(None)))
        mapped_ids = {row[0] for row in result.all()}
        print(f"Already mapped: {sorted(mapped_ids)}")

        admin = (await db.execute(select(User).where(User.email == 'open-access-admin@local').limit(1))).scalar_one()
        async with httpx.AsyncClient(timeout=30) as c:
            r = await c.get('http://platform-glpi-main-laravel.test-1/api/v1/glpi/items/Ticket?range=0-9999')
            data = r.json()
            glpi_tickets = data.get('data', []) if data.get('success') else []

        created = 0
        for t in glpi_tickets:
            glpi_id = int(t['id'])
            if glpi_id in mapped_ids:
                continue
            status = STATUS_MAP.get(int(t.get('status', 1)), TicketStatus.OPEN)
            priority = PRIORITY_MAP.get(int(t.get('priority', 3)), TicketPriority.MEDIUM)
            ticket = Ticket(
                subject=str(t.get('name', f'GLPI #{glpi_id}'))[:500],
                description=str(t.get('content', '')) or f'Synced from GLPI ticket #{glpi_id}',
                status=status, priority=priority, channel_source=ChannelType.TICKET,
                creator_id=admin.id, glpi_ticket_id=glpi_id, glpi_sync_status="synced",
            )
            db.add(ticket)
            await db.flush()
            mapped_ids.add(glpi_id)
            created += 1
            print(f"  Mapped GLPI #{glpi_id}: {ticket.subject[:40]}")

        await db.commit()
        print(f"\nCreated {created} new local tickets")
        print(f"Total mapped GLPI IDs: {len(mapped_ids)}")

asyncio.run(main())
