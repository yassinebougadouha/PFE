import asyncio
import base64
import os
import uuid
from datetime import datetime, timezone
from types import SimpleNamespace

os.environ.setdefault("DEBUG", "true")

from app.api.routes.whatsapp import bridge_webhook
from app.db.models.email import Email
from app.db.models.enums import EmailStatus
from app.services.gmail_service import GmailSyncService


class DummyRequest:
    def __init__(self, payload: dict):
        self._payload = payload

    async def json(self) -> dict:
        return self._payload


class DummyScalarResult:
    def scalar_one_or_none(self):
        return None


class FakeSyncSession:
    def __init__(self):
        self.added: list[object] = []
        self.flush_count = 0

    def execute(self, _query):
        return DummyScalarResult()

    def add(self, obj):
        if getattr(obj, "id", None) is None:
            obj.id = uuid.uuid4()
        if getattr(obj, "created_at", None) is None:
            now = datetime.now(timezone.utc)
            obj.created_at = now
            obj.updated_at = now
        self.added.append(obj)

    def flush(self):
        self.flush_count += 1


class DummyExec:
    def __init__(self, payload):
        self._payload = payload

    def execute(self):
        return self._payload


class DummyMessagesAPI:
    def __init__(self, message_payload: dict):
        self._message_payload = message_payload
        self.modified: list[tuple[str, str, dict]] = []

    def get(self, userId: str, id: str, format: str):
        return DummyExec(self._message_payload)

    def modify(self, userId: str, id: str, body: dict):
        self.modified.append((userId, id, body))
        return DummyExec({})


class DummyUsersAPI:
    def __init__(self, message_payload: dict):
        self._messages = DummyMessagesAPI(message_payload)

    def messages(self):
        return self._messages


class DummyGmailAPI:
    def __init__(self, message_payload: dict):
        self._users = DummyUsersAPI(message_payload)

    def users(self):
        return self._users


def test_bridge_webhook_queues_reply_target(monkeypatch):
    captured: dict = {}

    class DummyTask:
        def delay(self, **kwargs):
            captured.update(kwargs)

    monkeypatch.setattr("app.workers.tasks.process_whatsapp_incoming_task", DummyTask())

    payload = {
        "id": "bridge-msg-1",
        "from": "171185199403136@lid",
        "body": "hello from bridge",
        "sender_name": "Bridge Tester",
    }

    result = asyncio.run(bridge_webhook(request=DummyRequest(payload), db=None))

    assert result == {"status": "ok", "from": "171185199403136", "queued": True}
    assert captured["from_number"] == "171185199403136"
    assert captured["body"] == "hello from bridge"
    assert captured["sender_name"] == "Bridge Tester"
    assert captured["message_id"] == "bridge-msg-1"
    assert captured["reply_target"] == "171185199403136@lid"


def test_gmail_sync_auto_reply_marks_original_replied_and_records_outbound(monkeypatch):
    fake_db = FakeSyncSession()
    sync_service = GmailSyncService(fake_db)
    cred = SimpleNamespace(
        user_id=uuid.uuid4(),
        gmail_address="support@example.com",
    )

    inbound_body = "Bonjour, j'ai besoin d'aide pour mon ticket."
    gmail_payload = {
        "id": "gmail-msg-123",
        "threadId": "thread-123",
        "payload": {
            "mimeType": "text/plain",
            "headers": [
                {"name": "Subject", "value": "Need help"},
                {"name": "From", "value": "Customer <customer@example.com>"},
                {"name": "To", "value": "support@example.com"},
            ],
            "body": {
                "data": base64.urlsafe_b64encode(inbound_body.encode("utf-8")).decode("utf-8")
            },
        },
    }
    gmail_api = DummyGmailAPI(gmail_payload)

    monkeypatch.setattr(
        GmailSyncService,
        "_generate_auto_reply",
        lambda self, subject, body, sender=None: "Auto reply body",
    )

    def fake_send_reply(self, user_id, original_email_id, reply_body):
        original = next(
            obj
            for obj in fake_db.added
            if isinstance(obj, Email) and obj.id == original_email_id and not obj.is_outbound
        )
        reply = Email(
            sender_address=cred.gmail_address,
            recipient_address=original.sender_address,
            subject=f"Re: {original.subject}",
            body=reply_body,
            gmail_message_id="gmail-outbound-1",
            gmail_thread_id=original.gmail_thread_id,
            is_outbound=True,
            in_reply_to_id=original.id,
            replied_by_id=user_id,
            status=EmailStatus.REPLIED,
        )
        fake_db.add(reply)
        return reply

    monkeypatch.setattr(GmailSyncService, "send_reply", fake_send_reply)

    sync_service._process_message(gmail_api, cred, "gmail-msg-123")

    emails = [obj for obj in fake_db.added if isinstance(obj, Email)]
    inbound = next(email for email in emails if not email.is_outbound)
    outbound = next(email for email in emails if email.is_outbound)

    assert inbound.sender_address == "Customer <customer@example.com>"
    assert inbound.status == EmailStatus.REPLIED
    assert outbound.body == "Auto reply body"
    assert outbound.in_reply_to_id == inbound.id
    assert outbound.status == EmailStatus.REPLIED
    assert gmail_api.users().messages().modified == [
        ("me", "gmail-msg-123", {"removeLabelIds": ["UNREAD"]})
    ]
