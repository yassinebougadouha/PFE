import asyncio
from types import SimpleNamespace
import uuid

from app.services.ticket_notification_service import TicketNotificationService


class DummyScalarResult:
    def __init__(self, value):
        self.value = value

    def scalar_one_or_none(self):
        return self.value


class DummyDB:
    def __init__(self, result=None):
        self.result = result

    async def execute(self, _statement):
        return DummyScalarResult(self.result)


def test_notify_new_ticket_sends_in_app_and_email(monkeypatch):
    service = TicketNotificationService(db=DummyDB())
    recipients = [
        SimpleNamespace(id=uuid.uuid4(), email="agent1@example.com", full_name="Agent One"),
        SimpleNamespace(id=uuid.uuid4(), email="admin@example.com", full_name="Admin User"),
    ]
    calls: dict[str, object] = {}
    sent: list[dict[str, str]] = []

    async def fake_get_all_settings():
        return {"notify_new_ticket": True}

    async def fake_get_recipients():
        return recipients

    async def fake_create_many(**kwargs):
        calls["create_many"] = kwargs
        return []

    async def fake_send_email(*, to_address, subject, text_body, html_body=None):
        sent.append(
            {
                "to_address": to_address,
                "subject": subject,
                "text_body": text_body,
                "html_body": html_body,
            }
        )
        return True

    monkeypatch.setattr(service.settings_service, "get_all_settings", fake_get_all_settings)
    monkeypatch.setattr(service, "_get_active_admin_and_agent_users", fake_get_recipients)
    monkeypatch.setattr(service.notification_service, "create_many", fake_create_many)
    monkeypatch.setattr(service.runtime_mail_service, "send_email", fake_send_email)

    ticket = SimpleNamespace(
        id=uuid.uuid4(),
        subject="Login issue",
        priority=SimpleNamespace(value="HIGH"),
        status=SimpleNamespace(value="OPEN"),
    )

    asyncio.run(service.notify_new_ticket(ticket))

    assert calls["create_many"]["user_ids"] == [user.id for user in recipients]
    assert {item["to_address"] for item in sent} == {user.email for user in recipients}
    assert all("New ticket created" in item["subject"] for item in sent)
    assert all(str(ticket.id) in item["text_body"] for item in sent)


def test_notify_assignment_sends_in_app_and_email(monkeypatch):
    assignee = SimpleNamespace(
        id=uuid.uuid4(),
        email="agent@example.com",
        full_name="Assigned Agent",
    )
    service = TicketNotificationService(db=DummyDB(result=assignee))
    calls: dict[str, object] = {}
    sent: list[dict[str, str]] = []

    async def fake_get_all_settings():
        return {"notify_assigned": True}

    async def fake_create_notification(**kwargs):
        calls["create_notification"] = kwargs
        return None

    async def fake_send_email(*, to_address, subject, text_body, html_body=None):
        sent.append(
            {
                "to_address": to_address,
                "subject": subject,
                "text_body": text_body,
                "html_body": html_body,
            }
        )
        return True

    monkeypatch.setattr(service.settings_service, "get_all_settings", fake_get_all_settings)
    monkeypatch.setattr(service.notification_service, "create_notification", fake_create_notification)
    monkeypatch.setattr(service.runtime_mail_service, "send_email", fake_send_email)

    ticket = SimpleNamespace(id=uuid.uuid4(), subject="Password reset")

    asyncio.run(service.notify_assignment(ticket, assignee.id))

    assert calls["create_notification"]["user_id"] == assignee.id
    assert sent == [
        {
            "to_address": assignee.email,
            "subject": f"[Support] Ticket assigned: {ticket.subject[:80]}",
            "text_body": (
                f"Hello {assignee.full_name},\n\n"
                f"Ticket {ticket.id} is now assigned to you.\n\n"
                f"Subject: {ticket.subject}\n"
                f"Open the workspace to review and respond.\n"
            ),
            "html_body": None,
        }
    ]


def test_notify_new_ticket_respects_toggle(monkeypatch):
    service = TicketNotificationService(db=DummyDB())
    called = {"in_app": False, "email": False}

    async def fake_get_all_settings():
        return {"notify_new_ticket": False}

    async def fail_create_many(**_kwargs):
        called["in_app"] = True
        return []

    async def fail_send_email(**_kwargs):
        called["email"] = True
        return True

    monkeypatch.setattr(service.settings_service, "get_all_settings", fake_get_all_settings)
    monkeypatch.setattr(service.notification_service, "create_many", fail_create_many)
    monkeypatch.setattr(service.runtime_mail_service, "send_email", fail_send_email)

    ticket = SimpleNamespace(id=uuid.uuid4(), subject="Ignored ticket")

    asyncio.run(service.notify_new_ticket(ticket))

    assert called == {"in_app": False, "email": False}
