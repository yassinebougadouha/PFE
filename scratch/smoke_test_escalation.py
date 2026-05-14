"""
Smoke test: creates 5 urgent/risky tickets and verifies the escalation flow end-to-end.
"""
import asyncio
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from sqlalchemy import select, func, or_
from sqlalchemy.ext.asyncio import AsyncSession
from app.db.session import async_session_factory
from app.db.models.enums import TicketStatus, TicketPriority, ChannelType, UserRole
from app.db.models.ticket import Ticket
from app.db.models.user import User
from app.services.ticket_service import TicketService
from app.schemas.ticket import TicketCreate
from app.core.config import get_settings

settings = get_settings()

TICKETS = [
    {"subject": "Smoke Test #1: Critical Security Breach", "description": "Unauthorized access detected in the production database. Multiple user accounts compromised. Need immediate investigation and mitigation. This is a security emergency requiring escalation.", "priority": TicketPriority.CRITICAL},
    {"subject": "Smoke Test #2: Payment System Down", "description": "All payment transactions are failing with error code 500. Customers cannot complete purchases. Revenue loss estimated at $10k/hour. Urgent fix required.", "priority": TicketPriority.CRITICAL},
    {"subject": "Smoke Test #3: Data Loss Incident", "description": "Customer reported all their project files disappeared after the latest update. CEO is CC'd on this ticket. Potential legal liability if data cannot be recovered.", "priority": TicketPriority.HIGH},
    {"subject": "Smoke Test #4: VIP Customer Outage", "description": "Our largest enterprise customer (Fortune 500) is completely locked out of their account. SLA breached. VP of Customer Success requesting immediate escalation.", "priority": TicketPriority.CRITICAL},
    {"subject": "Smoke Test #5: Compliance Violation Alert", "description": "GDPR compliance scan found PII data exposed in public API responses. Must patch within 24 hours or face regulatory fines. Legal team notified.", "priority": TicketPriority.HIGH},
]


async def run_smoke_test():
    print("=" * 70)
    print("ESCALATION SMOKE TEST")
    print("=" * 70)

    async with async_session_factory() as db:
        # Get admin user as creator
        result = await db.execute(select(User).where(User.role == UserRole.ADMIN).limit(1))
        admin = result.scalar_one_or_none()
        if not admin:
            print("ERROR: No admin user found!")
            return
        print(f"Admin: {admin.email} (id={admin.id})")

        svc = TicketService(db)
        created_tickets = []

        for i, tdata in enumerate(TICKETS):
            print(f"\n--- Creating {tdata['subject']} ---")
            payload = TicketCreate(
                subject=tdata["subject"],
                description=tdata["description"],
                priority=tdata["priority"],
                channel_source=ChannelType.TICKET,
            )
            try:
                ticket = await svc.create_ticket(admin.id, payload)
                created_tickets.append(ticket)
                print(f"  Created: id={ticket.id}")
                print(f"  Status: {ticket.status.value}")
                print(f"  Priority: {ticket.priority.value}")
                print(f"  Assigned to: {ticket.assigned_agent_id}")
                print(f"  GLPI sync: {ticket.glpi_sync_status} (glpi_id={ticket.glpi_ticket_id})")
                print(f"  Escalation flag: {ticket.escalation_flag}")

                if ticket.status == TicketStatus.ESCALATED:
                    print(f"  ✓ TICKET ESCALATED AUTOMATICALLY")
                elif ticket.status == TicketStatus.RESOLVED:
                    print(f"  ⚠ Auto-resolved by decision engine")
                else:
                    print(f"  Status: {ticket.status.value} (not escalated)")
            except Exception as e:
                print(f"  ✗ FAILED: {e}")

        # List all tickets to verify visibility
        print(f"\n{'=' * 70}")
        print(f"VERIFYING TICKET LISTING (admin view)")
        print(f"{'=' * 70}")
        tickets, total = await svc.list_tickets(creator_id=None, assigned_agent_id=None, include_unassigned=False)
        print(f"Total tickets (admin): {total}")
        for t in tickets[:5]:
            print(f"  [{t.status.value}] {t.subject[:60]}")

        # List as agent to verify the new include_unassigned behavior
        print(f"\n{'=' * 70}")
        print(f"VERIFYING AGENT VIEW (with include_unassigned=True)")
        print(f"{'=' * 70}")
        agent_result = await db.execute(select(User).where(User.role == UserRole.AGENT).limit(1))
        agent = agent_result.scalar_one_or_none()
        if agent:
            agent_tickets, agent_total = await svc.list_tickets(
                assigned_agent_id=agent.id,
                include_unassigned=True,
            )
            print(f"Agent ({agent.email}) sees {agent_total} tickets (assigned + unassigned)")
            for t in agent_tickets[:5]:
                note = "assigned to me" if t.assigned_agent_id == agent.id else "unassigned"
                print(f"  [{t.status.value}] {t.subject[:50]} ({note})")

            # Old behavior (without include_unassigned) for comparison
            agent_tickets_old, agent_total_old = await svc.list_tickets(
                assigned_agent_id=agent.id,
                include_unassigned=False,
            )
            print(f"OLD behavior: Agent would see only {agent_total_old} tickets (assigned only)")

        # Verify escalation page query
        print(f"\n{'=' * 70}")
        print(f"VERIFYING ESCALATION QUEUE")
        print(f"{'=' * 70}")
        escalated, esc_total = await svc.list_tickets(
            status=TicketStatus.ESCALATED,
            assigned_agent_id=None,
            include_unassigned=False,
        )
        print(f"Escalated tickets (admin): {esc_total}")
        for t in escalated:
            print(f"  [{t.priority.value}] {t.subject[:55]} | glpi_id={t.glpi_ticket_id} | sync={t.glpi_sync_status}")

        # Check agent sees escalated tickets with new behavior
        if agent:
            agent_esc, agent_esc_total = await svc.list_tickets(
                status=TicketStatus.ESCALATED,
                assigned_agent_id=agent.id,
                include_unassigned=True,
            )
            print(f"Agent sees escalated: {agent_esc_total} (with include_unassigned=True)")

        # Count by status
        print(f"\n{'=' * 70}")
        print(f"TOTALS BY STATUS")
        print(f"{'=' * 70}")
        counts = await svc.count_by_status()
        for status, count in sorted(counts.items(), key=lambda x: str(x[0])):
            print(f"  {status.value}: {count}")

        # Summary
        print(f"\n{'=' * 70}")
        print(f"RESULTS SUMMARY")
        print(f"{'=' * 70}")
        auto_escalated = sum(1 for t in created_tickets if t.status == TicketStatus.ESCALATED)
        auto_resolved = sum(1 for t in created_tickets if t.status == TicketStatus.RESOLVED)
        synced = sum(1 for t in created_tickets if t.glpi_sync_status == "synced")
        print(f"  Created: {len(created_tickets)}")
        print(f"  Auto-escalated: {auto_escalated}")
        print(f"  Auto-resolved: {auto_resolved}")
        print(f"  Synced to GLPI: {synced}")
        print(f"  In escalation queue: {esc_total}")

        if auto_escalated > 0:
            print(f"\n  ✓ ESCALATION FLOW WORKING")
        else:
            print(f"\n  ⚠ No auto-escalation triggered (decision engine may need tuning)")

    await db.close()


if __name__ == "__main__":
    asyncio.run(run_smoke_test())
