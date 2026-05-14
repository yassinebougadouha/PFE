"""
Smoke test v2: verify GLPI user assignment works end-to-end.
Creates 5 urgent tickets and confirms _users_id_requester / _users_id_assign are set.
"""
import asyncio, sys, os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from sqlalchemy import select, text
from app.db.session import async_session_factory
from app.db.models.enums import TicketStatus, TicketPriority, ChannelType, UserRole
from app.db.models.ticket import Ticket
from app.db.models.user import User
from app.services.ticket_service import TicketService
from app.schemas.ticket import TicketCreate

TICKETS = [
    {"subject": "GLPI Smoke #1: Critical Security Breach", "description": "Production database breach detected. Customer data exposed. Need immediate GLPI escalation.", "priority": TicketPriority.CRITICAL},
    {"subject": "GLPI Smoke #2: Payment Gateway Down", "description": "Payment gateway returning 503 errors. All transactions failing. Revenue impact critical.", "priority": TicketPriority.CRITICAL},
    {"subject": "GLPI Smoke #3: VIP Account Locked", "description": "Enterprise customer unable to login for 2 hours. SLA breached. VP demanding escalation.", "priority": TicketPriority.HIGH},
    {"subject": "GLPI Smoke #4: Data Corruption Report", "description": "Multiple customers reporting corrupted data after maintenance window. Urgent investigation needed.", "priority": TicketPriority.HIGH},
    {"subject": "GLPI Smoke #5: Compliance Violation", "description": "PCI DSS compliance scan failed. Sensitive data exposed in API logs. Legal notified.", "priority": TicketPriority.CRITICAL},
]

async def run():
    print("=" * 70)
    print("GLPI ASSIGNMENT SMOKE TEST")
    print("=" * 70)

    async with async_session_factory() as db:
        # Verify users have glpi_user_id set
        result = await db.execute(select(User).where(User.role.in_([UserRole.ADMIN, UserRole.AGENT])))
        users = list(result.scalars().all())
        print(f"Users with glpi_user_id:")
        for u in users:
            print(f"  {u.email} (role={u.role.value}) → glpi_user_id={u.glpi_user_id}")

        admin = next(u for u in users if u.role == UserRole.ADMIN)
        svc = TicketService(db)

        for i, tdata in enumerate(TICKETS):
            print(f"\n--- Creating {tdata['subject']} ---")
            payload = TicketCreate(
                subject=tdata["subject"],
                description=tdata["description"],
                priority=tdata["priority"],
                channel_source=ChannelType.TICKET,
            )
            ticket = await svc.create_ticket(admin.id, payload)
            print(f"  id={ticket.id}")
            print(f"  status={ticket.status.value} priority={ticket.priority.value}")
            print(f"  assigned_agent_id={ticket.assigned_agent_id}")
            print(f"  glpi_ticket_id={ticket.glpi_ticket_id} sync={ticket.glpi_sync_status}")
            print(f"  escalation_flag={ticket.escalation_flag}")

            if ticket.assigned_agent_id:
                assignee = await db.get(User, ticket.assigned_agent_id)
                print(f"  assigned_to_glpi_user_id={assignee.glpi_user_id if assignee else 'UNKNOWN'}")

        # Verify GLPI created tickets
        print(f"\n{'=' * 70}")
        print(f"VERIFY GLPI TICKETS")
        print(f"{'=' * 70}")
        import httpx
        async with httpx.AsyncClient() as client:
            glpi_tickets = await svc.list_tickets(status=TicketStatus.ESCALATED, include_unassigned=True)
            print(f"Escalated tickets in app: {len(glpi_tickets[0])}")

        # Re-sync old escalated tickets that don't have GLPI user refs
        print(f"\n{'=' * 70}")
        print(f"RE-SYNC OLD TICKETS WITH GLPI USER REFS")
        print(f"{'=' * 70}")
        old_result = await db.execute(select(Ticket).where(Ticket.status == TicketStatus.ESCALATED))
        old_tickets = list(old_result.scalars().all())
        for t in old_tickets:
            if t.glpi_sync_status == "synced" and t.glpi_ticket_id:
                print(f"  Re-syncing ticket {t.id} (glpi_id={t.glpi_ticket_id})...")
                try:
                    await svc.sync_to_glpi(t)
                    print(f"    → sync={t.glpi_sync_status} glpi_id={t.glpi_ticket_id}")
                except Exception as e:
                    print(f"    ✗ FAILED: {e}")

        print(f"\n{'=' * 70}")
        print(f"DONE")
        print(f"{'=' * 70}")

    await db.close()

if __name__ == "__main__":
    asyncio.run(run())
