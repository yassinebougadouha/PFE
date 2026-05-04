"""
Ticket notification fan-out service.
"""

import asyncio
import logging
import uuid

import httpx
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.enums import TicketStatus, UserRole, UserStatus
from app.db.models.ticket import Ticket
from app.db.models.user import User
from app.services.notification_service import NotificationService
from app.services.runtime_mail_service import RuntimeMailService
from app.services.settings_service import SettingsService

logger = logging.getLogger(__name__)


class TicketNotificationService:
    def __init__(self, db: AsyncSession):
        self.db = db
        self.settings_service = SettingsService(db)
        self.notification_service = NotificationService(db)
        self.runtime_mail_service = RuntimeMailService(db)

    async def _get_active_admin_and_agent_users(self) -> list[User]:
        result = await self.db.execute(
            select(User).where(
                User.role.in_([UserRole.ADMIN, UserRole.AGENT]),
                User.status == UserStatus.ACTIVE,
                User.is_deleted == False,
            )
        )
        return list(result.scalars().all())

    async def _send_email_notifications(
        self,
        *,
        users: list[User],
        subject: str,
        text_body: str,
    ) -> None:
        seen: set[str] = set()
        deliveries = []

        for user in users:
            email = str(getattr(user, "email", "") or "").strip()
            if not email:
                continue
            normalized_email = email.lower()
            if normalized_email in seen:
                continue
            seen.add(normalized_email)
            deliveries.append(
                self.runtime_mail_service.send_email(
                    to_address=email,
                    subject=subject,
                    text_body=text_body,
                )
            )

        if deliveries:
            await asyncio.gather(*deliveries)

    async def notify_new_ticket(self, ticket: Ticket) -> None:
        settings = await self.settings_service.get_all_settings()
        if not settings["notify_new_ticket"]:
            return

        recipients = await self._get_active_admin_and_agent_users()
        if not recipients:
            return

        await self.notification_service.create_many(
            user_ids=[user.id for user in recipients],
            type="ticket_new",
            title=f"New ticket: {ticket.subject[:60]}",
            body=f"Ticket {ticket.id} was created and is ready for triage.",
            resource_type="ticket",
            resource_id=str(ticket.id),
            action_url=f"/tickets/{ticket.id}",
            meta={"ticket_id": str(ticket.id)},
        )

        priority_value = getattr(getattr(ticket, "priority", None), "value", getattr(ticket, "priority", "MEDIUM"))
        status_value = getattr(getattr(ticket, "status", None), "value", getattr(ticket, "status", "OPEN"))
        await self._send_email_notifications(
            users=recipients,
            subject=f"[Support] New ticket created: {ticket.subject[:80]}",
            text_body=(
                f"Hello,\n\n"
                f"A new ticket is ready for triage.\n\n"
                f"Ticket ID: {ticket.id}\n"
                f"Subject: {ticket.subject}\n"
                f"Priority: {priority_value}\n"
                f"Status: {status_value}\n"
            ),
        )

    async def notify_assignment(self, ticket: Ticket, assigned_user_id: uuid.UUID) -> None:
        settings = await self.settings_service.get_all_settings()
        if not settings["notify_assigned"]:
            return

        assigned_user = await self.db.execute(
            select(User).where(User.id == assigned_user_id, User.is_deleted == False)
        )
        user = assigned_user.scalar_one_or_none()
        if not user:
            return

        await self.notification_service.create_notification(
            user_id=user.id,
            type="ticket_assigned",
            title=f"Ticket assigned: {ticket.subject[:60]}",
            body=f"Ticket {ticket.id} is now assigned to you.",
            resource_type="ticket",
            resource_id=str(ticket.id),
            action_url=f"/tickets/{ticket.id}",
            meta={"ticket_id": str(ticket.id)},
        )
        await self._send_email_notifications(
            users=[user],
            subject=f"[Support] Ticket assigned: {ticket.subject[:80]}",
            text_body=(
                f"Hello {user.full_name},\n\n"
                f"Ticket {ticket.id} is now assigned to you.\n\n"
                f"Subject: {ticket.subject}\n"
                f"Open the workspace to review and respond.\n"
            ),
        )

    async def notify_status_change(
        self,
        *,
        ticket: Ticket,
        previous_status: TicketStatus,
        actor: User,
    ) -> None:
        settings = await self.settings_service.get_all_settings()
        status_changed = ticket.status != previous_status
        is_resolved = ticket.status in {TicketStatus.RESOLVED, TicketStatus.CLOSED}
        should_notify = (
            settings["notify_resolved"] if is_resolved else settings["notify_status_change"]
        )

        if not status_changed or not should_notify:
            return

        creator = await self.db.execute(
            select(User).where(User.id == ticket.creator_id, User.is_deleted == False)
        )
        creator_user = creator.scalar_one_or_none()
        status_label = ticket.status.value.replace("_", " ").title()

        if creator_user:
            await self.notification_service.create_notification(
                user_id=creator_user.id,
                type="ticket_status_changed",
                title=f"Ticket {ticket.id} updated",
                body=f"{ticket.subject[:80]} is now {status_label}.",
                resource_type="ticket",
                resource_id=str(ticket.id),
                action_url=f"/tickets/{ticket.id}",
                meta={
                    "ticket_id": str(ticket.id),
                    "status": ticket.status.value,
                    "previous_status": previous_status.value,
                },
            )

            subject = f"[Support] Ticket {ticket.id} is now {status_label}"
            text_body = (
                f"Hello,\n\n"
                f"Your ticket \"{ticket.subject}\" was updated by {actor.full_name}.\n"
                f"Previous status: {previous_status.value}\n"
                f"Current status: {ticket.status.value}\n\n"
                f"Resolution note:\n{ticket.resolution_note or 'No note provided.'}\n"
            )
            await self.runtime_mail_service.send_email(
                to_address=creator_user.email,
                subject=subject,
                text_body=text_body,
            )

        if is_resolved:
            admin_users = await self._get_active_admin_and_agent_users()
            notify_users = [user for user in admin_users if user.id != actor.id]
            if notify_users:
                await self.notification_service.create_many(
                    user_ids=[user.id for user in notify_users],
                    type="ticket_resolved",
                    title=f"Ticket resolved by {actor.full_name}",
                    body=f"Ticket {ticket.id} is now {status_label}.",
                    resource_type="ticket",
                    resource_id=str(ticket.id),
                    action_url=f"/tickets/{ticket.id}",
                    meta={"ticket_id": str(ticket.id), "actor_id": str(actor.id)},
                )

            await self._notify_teams_if_configured(ticket=ticket, actor=actor)

    async def _notify_teams_if_configured(self, *, ticket: Ticket, actor: User) -> None:
        result = await self.db.execute(
            select(User.teams_webhook_url).where(
                User.role == UserRole.ADMIN,
                User.status == UserStatus.ACTIVE,
                User.is_deleted == False,
                User.teams_webhook_url.is_not(None),
            )
        )
        webhook_urls = [url for url in result.scalars().all() if url]
        if not webhook_urls:
            return

        payload = {
            "text": (
                f"Ticket {ticket.id} resolved by {actor.full_name}: {ticket.subject}\n"
                f"Status: {ticket.status.value}\n"
                f"Resolution: {ticket.resolution_note or 'No note provided.'}"
            )
        }

        async with httpx.AsyncClient(timeout=10.0) as client:
            for url in webhook_urls:
                try:
                    await client.post(url, json=payload)
                except Exception:
                    logger.exception("Teams webhook notification failed for %s", url)
