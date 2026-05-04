"""
Seed local demo users and support data.

This script is intentionally idempotent for demo records. It resets the three
known demo users and replaces only rows marked with the [Demo] prefix or demo
metadata, leaving normal local data alone.
"""

from __future__ import annotations

import asyncio
import sys
from datetime import datetime, timedelta, timezone
from pathlib import Path

from sqlalchemy import delete, or_, select

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from app.core.security import hash_password
from app.db.models import (
    AuditAction,
    AuditLog,
    ChannelType,
    Conversation,
    ConversationSnippet,
    ConversationStatus,
    DecisionLog,
    Email,
    EmailStatus,
    Message,
    Notification,
    Ticket,
    TicketPriority,
    TicketStatus,
    User,
    UserRole,
    UserStatus,
    VoiceCallLog,
)
from app.db.session import async_session_factory
from app.decision_engine.decision_engine import analyze_ticket


DEMO_USERS = [
    {
        "email": "admin@example.com",
        "password": "Admin123!",
        "full_name": "Admin User",
        "phone_number": "+21600000000",
        "role": UserRole.ADMIN,
        "teams_email": "admin@example.com",
    },
    {
        "email": "agent@example.com",
        "password": "Agent123!",
        "full_name": "Agent User",
        "phone_number": "+21600000001",
        "role": UserRole.AGENT,
        "teams_email": "agent@example.com",
    },
    {
        "email": "client@example.com",
        "password": "Client123!",
        "full_name": "Client User",
        "phone_number": "+21600000002",
        "role": UserRole.CLIENT,
        "teams_email": None,
    },
]


def utc_now() -> datetime:
    return datetime.now(timezone.utc)


def set_timestamps(obj, created_at: datetime) -> None:
    obj.created_at = created_at
    obj.updated_at = created_at


async def upsert_demo_users(session) -> dict[str, User]:
    users: dict[str, User] = {}

    for data in DEMO_USERS:
        user = (
            await session.execute(select(User).where(User.email == data["email"]))
        ).scalar_one_or_none()
        if user is None:
            user = User(email=data["email"])
            session.add(user)

        user.hashed_password = hash_password(data["password"])
        user.full_name = data["full_name"]
        user.phone_number = data["phone_number"]
        user.role = data["role"]
        user.status = UserStatus.ACTIVE
        user.is_deleted = False
        user.deleted_at = None
        user.can_reply_conversations = True
        user.can_reply_whatsapp = True
        user.is_vip = data["role"] == UserRole.CLIENT
        user.teams_email = data["teams_email"]
        user.teams_webhook_url = None
        user.timezone = "Africa/Tunis"
        user.locale = "en"
        user.must_change_password = False
        user.profile_completed = True
        user.profile_picture_url = None
        users[data["email"]] = user

    await session.flush()
    return users


async def clear_existing_demo_data(session) -> None:
    demo_conversation_ids = (
        await session.execute(
            select(Conversation.id).where(Conversation.subject.ilike("[Demo]%"))
        )
    ).scalars().all()
    demo_email_ids = (
        await session.execute(
            select(Email.id).where(
                Email.gmail_message_id.in_(
                    [
                        "demo-inbound-invoice-001",
                        "demo-outbound-invoice-001",
                    ]
                )
            )
        )
    ).scalars().all()
    demo_voice_call_ids = (
        await session.execute(
            select(VoiceCallLog.id).where(VoiceCallLog.room_name.ilike("demo-%"))
        )
    ).scalars().all()

    await session.execute(delete(Notification).where(Notification.meta["demo"].as_boolean() == True))
    await session.execute(delete(AuditLog).where(AuditLog.meta["demo"].as_boolean() == True))
    await session.execute(delete(ConversationSnippet).where(ConversationSnippet.shortcut.in_(["demo_sla", "demo_refund"])))

    ticket_filters = [Ticket.subject.ilike("[Demo]%")]
    if demo_conversation_ids:
        ticket_filters.append(Ticket.conversation_id.in_(demo_conversation_ids))
    if demo_email_ids:
        ticket_filters.append(Ticket.source_email_id.in_(demo_email_ids))
    if demo_voice_call_ids:
        ticket_filters.append(Ticket.source_voice_call_id.in_(demo_voice_call_ids))

    demo_ticket_ids = (
        await session.execute(select(Ticket.id).where(or_(*ticket_filters)))
    ).scalars().all()
    if demo_ticket_ids:
        await session.execute(delete(DecisionLog).where(DecisionLog.ticket_id.in_(demo_ticket_ids)))
        await session.execute(delete(Ticket).where(Ticket.id.in_(demo_ticket_ids)))

    if demo_conversation_ids:
        await session.execute(delete(Message).where(Message.conversation_id.in_(demo_conversation_ids)))
        await session.execute(delete(Conversation).where(Conversation.id.in_(demo_conversation_ids)))

    if demo_email_ids:
        await session.execute(delete(Email).where(Email.id.in_(demo_email_ids)))

    if demo_voice_call_ids:
        await session.execute(delete(VoiceCallLog).where(VoiceCallLog.id.in_(demo_voice_call_ids)))

    await session.flush()


async def seed_conversations(session, users: dict[str, User]) -> dict[str, Conversation]:
    now = utc_now()
    admin = users["admin@example.com"]
    agent = users["agent@example.com"]
    client = users["client@example.com"]

    billing = Conversation(
        user_id=client.id,
        channel=ChannelType.CHAT,
        status=ConversationStatus.OPEN,
        subject="[Demo] Billing portal locked after card update",
        is_pinned=True,
        ai_auto_reply_enabled=True,
    )
    set_timestamps(billing, now - timedelta(hours=5))

    whatsapp = Conversation(
        user_id=client.id,
        channel=ChannelType.WHATSAPP,
        status=ConversationStatus.PENDING,
        subject="[Demo] WhatsApp delivery address confirmation",
        is_pinned=False,
        ai_auto_reply_enabled=False,
        ai_auto_reply_paused_until=now + timedelta(hours=2),
    )
    set_timestamps(whatsapp, now - timedelta(hours=2))

    session.add_all([billing, whatsapp])
    await session.flush()

    messages = [
        (
            billing,
            client,
            "Hi, I updated my card this morning and now the billing portal says my account is locked.",
            False,
            True,
            now - timedelta(hours=5),
        ),
        (
            billing,
            agent,
            "Thanks for the details. I am checking the payment token and will keep this thread updated.",
            False,
            True,
            now - timedelta(hours=4, minutes=45),
        ),
        (
            billing,
            admin,
            "Internal note: treat this as high priority because the customer is VIP and renewal is today.",
            True,
            True,
            now - timedelta(hours=4, minutes=30),
        ),
        (
            whatsapp,
            client,
            "Can you confirm whether my delivery address was updated before dispatch?",
            False,
            False,
            now - timedelta(hours=2),
        ),
        (
            whatsapp,
            agent,
            "We paused auto-replies here while the operations team confirms the latest address.",
            False,
            True,
            now - timedelta(hours=1, minutes=40),
        ),
    ]

    for conversation, sender, content, is_internal, is_read, created_at in messages:
        message = Message(
            conversation_id=conversation.id,
            sender_id=sender.id,
            content=content,
            is_internal=is_internal,
            is_read=is_read,
        )
        set_timestamps(message, created_at)
        session.add(message)

    await session.flush()
    return {"billing": billing, "whatsapp": whatsapp}


async def seed_email_thread(session, users: dict[str, User]) -> dict[str, Email]:
    now = utc_now()
    agent = users["agent@example.com"]

    inbound = Email(
        sender_address="client@example.com",
        recipient_address="support@example.com",
        subject="[Demo] Cannot download March invoice",
        body=(
            "Hello support,\n\n"
            "The billing page loads, but the March invoice download button returns a 500 error. "
            "Could you send the invoice and check the portal issue?\n\n"
            "Thanks."
        ),
        raw_headers="X-Demo: true",
        status=EmailStatus.CONVERTED,
        gmail_message_id="demo-inbound-invoice-001",
        gmail_thread_id="demo-thread-invoice-001",
        is_outbound=False,
        is_read=False,
        is_starred=True,
        labels=["INBOX", "DEMO", "BILLING"],
    )
    set_timestamps(inbound, now - timedelta(hours=8))
    session.add(inbound)
    await session.flush()

    outbound = Email(
        sender_address="support@example.com",
        recipient_address="client@example.com",
        subject="Re: [Demo] Cannot download March invoice",
        body=(
            "Hi,\n\n"
            "We attached the invoice and opened a ticket for the portal error. "
            "An agent is already assigned and will follow up today."
        ),
        raw_headers="X-Demo: true",
        status=EmailStatus.REPLIED,
        gmail_message_id="demo-outbound-invoice-001",
        gmail_thread_id="demo-thread-invoice-001",
        is_outbound=True,
        is_read=True,
        is_starred=False,
        labels=["SENT", "DEMO"],
        in_reply_to_id=inbound.id,
        replied_by_id=agent.id,
    )
    set_timestamps(outbound, now - timedelta(hours=7, minutes=30))
    session.add(outbound)
    await session.flush()

    return {"inbound": inbound, "outbound": outbound}


async def seed_voice_call(session) -> VoiceCallLog:
    now = utc_now()
    call = VoiceCallLog(
        room_name="demo-voice-billing-followup",
        room_sid="demo-room-sid-001",
        transcript=(
            "Client: Bonjour, je veux confirmer le statut du remboursement.\n"
            "Agent: Bonjour, le remboursement est approuve et sera visible sous trois jours ouvrables."
        ),
        audio_file_path="/recordings/demo-voice-billing-followup.wav",
        duration_seconds=184.0,
        started_at=now - timedelta(days=1, minutes=4),
        ended_at=now - timedelta(days=1),
    )
    set_timestamps(call, now - timedelta(days=1, minutes=4))
    session.add(call)
    await session.flush()
    return call


async def seed_tickets(
    session,
    users: dict[str, User],
    conversations: dict[str, Conversation],
    emails: dict[str, Email],
    voice_call: VoiceCallLog,
) -> dict[str, Ticket]:
    now = utc_now()
    admin = users["admin@example.com"]
    agent = users["agent@example.com"]
    client = users["client@example.com"]

    tickets = {
        "billing": Ticket(
            subject="[Demo] Billing portal locked after card update",
            description=(
                "Customer cannot access the billing portal after updating payment details. "
                "Renewal is due today and the account is marked VIP."
            ),
            status=TicketStatus.IN_PROGRESS,
            priority=TicketPriority.HIGH,
            channel_source=ChannelType.CHAT,
            escalation_flag=True,
            creator_id=client.id,
            assigned_agent_id=agent.id,
            conversation_id=conversations["billing"].id,
        ),
        "invoice": Ticket(
            subject="[Demo] Invoice download returns 500",
            description=(
                "Inbound email reports that the March invoice cannot be downloaded from the billing portal."
            ),
            status=TicketStatus.OPEN,
            priority=TicketPriority.MEDIUM,
            channel_source=ChannelType.EMAIL,
            escalation_flag=False,
            creator_id=client.id,
            assigned_agent_id=agent.id,
            source_email_id=emails["inbound"].id,
        ),
        "refund": Ticket(
            subject="[Demo] Follow-up call: refund status",
            description="Voice call summary: customer asked for refund timing and received confirmation.",
            status=TicketStatus.RESOLVED,
            priority=TicketPriority.LOW,
            channel_source=ChannelType.CALL_TRANSCRIPT,
            escalation_flag=False,
            resolution_note="Refund approved and customer was told to expect bank posting within three business days.",
            resolved_at=now - timedelta(hours=20),
            creator_id=client.id,
            assigned_agent_id=agent.id,
            source_voice_call_id=voice_call.id,
            solved_by_id=agent.id,
        ),
        "governance": Ticket(
            subject="[Demo] Review VIP escalation routing",
            description=(
                "Admin follow-up to review whether VIP billing issues should auto-escalate above priority medium."
            ),
            status=TicketStatus.WAITING_ON_CUSTOMER,
            priority=TicketPriority.MEDIUM,
            channel_source=ChannelType.TICKET,
            escalation_flag=False,
            creator_id=admin.id,
            assigned_agent_id=agent.id,
        ),
    }

    created_offsets = {
        "billing": timedelta(hours=5),
        "invoice": timedelta(hours=8),
        "refund": timedelta(days=1),
        "governance": timedelta(hours=3),
    }
    for key, ticket in tickets.items():
        set_timestamps(ticket, now - created_offsets[key])
        session.add(ticket)

    await session.flush()
    return tickets


async def seed_notifications(session, users: dict[str, User], tickets: dict[str, Ticket]) -> None:
    now = utc_now()
    notifications = [
        Notification(
            user_id=users["admin@example.com"].id,
            type="demo.daily_summary",
            title="[Demo] Daily support summary ready",
            body="There are 2 open demo tickets, 1 VIP escalation, and 1 resolved voice follow-up.",
            is_read=False,
            resource_type="dashboard",
            action_url="/dashboard",
            meta={"demo": True},
        ),
        Notification(
            user_id=users["admin@example.com"].id,
            type="demo.escalation",
            title="[Demo] VIP escalation needs review",
            body="Billing portal lockout is high priority because the customer's renewal is due today.",
            is_read=False,
            resource_type="ticket",
            resource_id=str(tickets["billing"].id),
            action_url=f"/tickets/{tickets['billing'].id}",
            meta={"demo": True},
        ),
        Notification(
            user_id=users["agent@example.com"].id,
            type="demo.assignment",
            title="[Demo] Ticket assigned to you",
            body="Invoice download returns 500 has been assigned to your queue.",
            is_read=False,
            resource_type="ticket",
            resource_id=str(tickets["invoice"].id),
            action_url=f"/tickets/{tickets['invoice'].id}",
            meta={"demo": True},
        ),
        Notification(
            user_id=users["agent@example.com"].id,
            type="demo.reply_ready",
            title="[Demo] Assisted reply draft ready",
            body="A concise billing portal response is ready for the VIP conversation.",
            is_read=True,
            resource_type="ticket",
            resource_id=str(tickets["billing"].id),
            action_url=f"/tickets/{tickets['billing'].id}",
            meta={"demo": True},
        ),
        Notification(
            user_id=users["client@example.com"].id,
            type="demo.ticket_update",
            title="[Demo] We are working on your billing issue",
            body="An agent has started investigating your billing portal access issue.",
            is_read=False,
            resource_type="ticket",
            resource_id=str(tickets["billing"].id),
            action_url=f"/tickets/{tickets['billing'].id}",
            meta={"demo": True},
        ),
        Notification(
            user_id=users["client@example.com"].id,
            type="demo.email_reply",
            title="[Demo] Invoice request received",
            body="Your invoice request was received and converted to a support ticket.",
            is_read=True,
            resource_type="ticket",
            resource_id=str(tickets["invoice"].id),
            action_url=f"/tickets/{tickets['invoice'].id}",
            meta={"demo": True},
        ),
    ]

    for index, notification in enumerate(notifications):
        set_timestamps(notification, now - timedelta(minutes=45 - index * 5))
        session.add(notification)

    await session.flush()


async def seed_snippets(session, users: dict[str, User]) -> None:
    now = utc_now()
    admin = users["admin@example.com"]
    snippets = [
        ConversationSnippet(
            title="[Demo] SLA acknowledgement",
            body="Thanks for the details. I am checking this now and will update you before the SLA window closes.",
            description="Quick acknowledgement for high-priority customer issues.",
            shortcut="demo_sla",
            channel=None,
            is_active=True,
            created_by_id=admin.id,
            updated_by_id=admin.id,
        ),
        ConversationSnippet(
            title="[Demo] Refund timeline",
            body="Your refund has been approved. Most banks post it within three business days.",
            description="Refund status response after approval.",
            shortcut="demo_refund",
            channel=ChannelType.CHAT,
            is_active=True,
            created_by_id=admin.id,
            updated_by_id=admin.id,
        ),
    ]
    for index, snippet in enumerate(snippets):
        set_timestamps(snippet, now - timedelta(days=2, minutes=index))
        session.add(snippet)
    await session.flush()


async def seed_audit_logs(session, users: dict[str, User], tickets: dict[str, Ticket]) -> None:
    now = utc_now()
    audit_rows = [
        AuditLog(
            user_id=users["admin@example.com"].id,
            action=AuditAction.CREATE,
            resource_type="demo_seed",
            resource_id="demo-data",
            description="Seeded demo users and support records.",
            meta={"demo": True},
            trace_id="demo-seed-admin",
            ip_address="127.0.0.1",
        ),
        AuditLog(
            user_id=users["agent@example.com"].id,
            action=AuditAction.ASSIGN,
            resource_type="ticket",
            resource_id=str(tickets["invoice"].id),
            description="Demo invoice ticket assigned to agent.",
            meta={"demo": True},
            trace_id="demo-seed-agent",
            ip_address="127.0.0.1",
        ),
        AuditLog(
            user_id=users["client@example.com"].id,
            action=AuditAction.CREATE,
            resource_type="ticket",
            resource_id=str(tickets["billing"].id),
            description="Demo client created billing portal support request.",
            meta={"demo": True},
            trace_id="demo-seed-client",
            ip_address="127.0.0.1",
        ),
    ]

    for index, row in enumerate(audit_rows):
        set_timestamps(row, now - timedelta(minutes=30 - index * 5))
        session.add(row)

    await session.flush()


async def main() -> None:
    async with async_session_factory() as session:
        users = await upsert_demo_users(session)
        await clear_existing_demo_data(session)
        conversations = await seed_conversations(session, users)
        emails = await seed_email_thread(session, users)
        voice_call = await seed_voice_call(session)
        tickets = await seed_tickets(session, users, conversations, emails, voice_call)
        for ticket in tickets.values():
            await analyze_ticket(
                db=session,
                ticket=ticket,
                auto_assign=True,
                auto_update_priority=True,
            )
        await seed_notifications(session, users, tickets)
        await seed_snippets(session, users)
        await seed_audit_logs(session, users, tickets)
        await session.commit()

    print("Seeded demo data:")
    for user in DEMO_USERS:
        print(f"  {user['role'].value}: {user['email']} / {user['password']}")
    print("  2 conversations, 5 messages")
    print("  4 tickets")
    print("  4 decision logs")
    print("  2 emails")
    print("  1 voice call log")
    print("  6 notifications")
    print("  2 snippets")
    print("  3 audit log rows")


if __name__ == "__main__":
    asyncio.run(main())
