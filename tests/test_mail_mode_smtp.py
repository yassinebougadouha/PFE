import asyncio
import os
import sys
import types
import uuid
from types import SimpleNamespace

os.environ["DEBUG"] = "true"

from app.api.routes.emails import compose_email
from app.schemas.email import EmailComposeRequest
from app.services.runtime_mail_service import RuntimeMailService


def test_compose_email_allows_smtp_mode_without_gmail(monkeypatch):
    task_calls: list[dict] = []
    audit_calls: list[dict] = []
    fake_tasks_module = types.ModuleType("app.workers.tasks")
    fake_tasks_module.send_new_email_task = SimpleNamespace(
        delay=lambda **kwargs: task_calls.append(kwargs)
    )
    monkeypatch.setitem(sys.modules, "app.workers.tasks", fake_tasks_module)

    async def fake_get_all_settings(self):
        return {
            "mail_mode": "smtp",
            "support_email": "support@example.com",
            "smtp_from_name": "Support",
            "smtp_from_email": "support@example.com",
            "smtp_host": "smtp.example.com",
            "smtp_port": 587,
            "smtp_encryption": "tls",
            "smtp_username": "",
            "smtp_password": "",
        }

    async def fake_audit_log(self, action, resource_type, **kwargs):
        audit_calls.append(
            {
                "action": action,
                "resource_type": resource_type,
                **kwargs,
            }
        )

    async def fail_if_gmail_checked(self, _user_id):
        raise AssertionError("Gmail should not be checked when SMTP mode is active")

    monkeypatch.setattr("app.api.routes.emails.SettingsService.get_all_settings", fake_get_all_settings)
    monkeypatch.setattr("app.api.routes.emails.AuditService.log", fake_audit_log)
    monkeypatch.setattr("app.api.routes.emails.GmailService.get_credential", fail_if_gmail_checked)

    current_user = SimpleNamespace(id=uuid.uuid4())
    response = asyncio.run(
        compose_email(
            payload=EmailComposeRequest(
                recipient="customer@example.com",
                subject="Welcome",
                body="Hello from SMTP mode",
                labels=["manual"],
            ),
            request=SimpleNamespace(state=SimpleNamespace(trace_id="trace-smtp-compose"), client=None),
            db=SimpleNamespace(),
            current_user=current_user,
        )
    )

    assert response["recipient"] == "customer@example.com"
    assert response["subject"] == "Welcome"
    assert len(task_calls) == 1
    assert task_calls[0] == {
        "user_id": str(current_user.id),
        "recipient": "customer@example.com",
        "subject": "Welcome",
        "body": "Hello from SMTP mode",
        "labels": ["manual"],
    }
    assert len(audit_calls) == 1
    assert audit_calls[0]["resource_type"] == "email"
    assert audit_calls[0]["description"] == "Outbound email queued to customer@example.com"


def test_runtime_mail_service_smtp_adds_reply_headers(monkeypatch):
    captured: dict[str, object] = {}

    class FakeSMTP:
        def __init__(self, host, port, timeout):
            captured["host"] = host
            captured["port"] = port
            captured["timeout"] = timeout

        def __enter__(self):
            return self

        def __exit__(self, exc_type, exc, tb):
            return False

        def starttls(self):
            captured["starttls"] = True

        def login(self, username, password):
            captured["login"] = (username, password)

        def send_message(self, message):
            captured["message"] = message

    monkeypatch.setattr("app.services.runtime_mail_service.smtplib.SMTP", FakeSMTP)

    result = RuntimeMailService.send_via_smtp_with_settings(
        {
            "smtp_from_name": "Support",
            "smtp_from_email": "support@example.com",
            "smtp_host": "smtp.example.com",
            "smtp_port": 587,
            "smtp_encryption": "tls",
            "smtp_username": "",
            "smtp_password": "",
        },
        to_address="customer@example.com",
        subject="Re: Ticket update",
        text_body="We are on it.",
        in_reply_to="<original@example.com>",
        references=["<original@example.com>"],
    )

    assert result.ok is True
    assert result.sender_email == "support@example.com"
    assert result.message_id
    assert captured["host"] == "smtp.example.com"
    assert captured["port"] == 587
    assert captured["starttls"] is True
    assert captured["message"]["From"] == "Support <support@example.com>"
    assert captured["message"]["To"] == "customer@example.com"
    assert captured["message"]["Subject"] == "Re: Ticket update"
    assert captured["message"]["In-Reply-To"] == "<original@example.com>"
    assert captured["message"]["References"] == "<original@example.com>"
    assert captured["message"]["Message-ID"] == result.message_id
