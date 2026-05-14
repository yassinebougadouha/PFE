"""Test GLPI list endpoint and ticket creation with Laravel admin assignment."""
import asyncio
import httpx
import sys
import os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from app.db.session import async_session_factory
from app.db.models.enums import TicketStatus, TicketPriority, ChannelType, UserRole
from app.db.models.user import User
from sqlalchemy import select
from app.services.ticket_service import TicketService
from app.schemas.ticket import TicketCreate
from app.services.glpi_ticket_service import list_glpi_tickets, get_laravel_admin_glpi_id
from app.db.session import laravel_session_factory


async def main():
    print("=" * 70)
    print("GLPI LIST & LARAVEL ADMIN ASSIGNMENT TEST")
    print("=" * 70)

    # 1. Test GLPI list endpoint
    print("\n1. Fetching GLPI tickets via Laravel proxy...")
    tickets = await list_glpi_tickets(range_str="0-49")
    print(f"   Total GLPI tickets: {len(tickets)}")
    for t in tickets[:10]:
        print(f"   [{t['status']}] {t['subject'][:55]} (glpi_id={t['glpi_ticket_id']})")

    # 2. Test Laravel admin GLPI ID lookup
    print("\n2. Looking up Laravel admin GLPI user IDs...")
    async with laravel_session_factory() as laravel_db:
        glpi_id = await get_laravel_admin_glpi_id(laravel_db)
        print(f"   First Laravel admin/super_admin GLPI user ID: {glpi_id}")

        # List all admin/super_admin GLPI IDs
        result = await laravel_db.execute(
            "SELECT email, role, glpi_user_id FROM users "
            "WHERE glpi_user_id IS NOT NULL "
            "AND role IN ('admin', 'super_admin') "
            "ORDER BY role, glpi_user_id"
        )
        print("   All admin/super_admin GLPI IDs:")
        for row in result.fetchall():
            print(f"     {row[2]} | {row[1]:12} | {row[0]}")

    # 3. Create a new ticket and verify Laravel admin assignment
    print("\n3. Creating a ticket (Laravel admin should be used for GLPI)...")
    async with async_session_factory() as db:
        admin = (await db.execute(select(User).where(User.role == UserRole.ADMIN).limit(1))).scalar_one()
        svc = TicketService(db)

        ticket = await svc.create_ticket(
            admin.id,
            TicketCreate(
                subject="GLPI Assignment Test",
                description="Testing that GLPI ticket gets assigned to Laravel admin user",
                priority=TicketPriority.HIGH,
                channel_source=ChannelType.TICKET,
            ),
        )
        print(f"   Created: {ticket.id}")
        print(f"   Status: {ticket.status.value}  Priority: {ticket.priority.value}")
        print(f"   GLPI ID: {ticket.glpi_ticket_id}")
        print(f"   Sync: {ticket.glpi_sync_status}")

    # 4. Verify GLPI ticket_users
    print("\n4. Verifying GLPI ticket_users from the DB...")
    import asyncpg
    glpi_conn = await asyncpg.connect(
        host='glpi_db', port=3306, user='glpi',
        password='glpipass', database='glpidb',
    )
    rows = await glpi_conn.fetch(
        "SELECT tu.tickets_id, tu.users_id, u.name, tu.type "
        "FROM glpi_tickets_users tu "
        "JOIN glpi_users u ON tu.users_id = u.id "
        "WHERE tu.tickets_id = $1 ORDER BY tu.type",
        ticket.glpi_ticket_id,
    )
    for r in rows:
        role = "requester" if r[3] == 1 else "assignee"
        print(f"   {role}: {r[2]} (GLPI user_id={r[1]})")
    await glpi_conn.close()

    print("\n" + "=" * 70)
    print("TEST COMPLETE")
    print("=" * 70)


if __name__ == "__main__":
    asyncio.run(main())
