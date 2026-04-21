import base64
import os
import uuid
from datetime import datetime, timezone
from types import SimpleNamespace

os.environ.setdefault("DEBUG", "true")

from app.db.models.email import Email
from app.db.models.enums import EmailStatus
from app.services.auto_reply_guardrails import get_email_auto_reply_skip_reason
from app.services.gmail_service import GmailSyncService


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


def test_guardrails_skip_mailing_list_header():
    raw_headers = {
        "From": "News <updates@example.com>",
        "List-Unsubscribe": "<mailto:unsubscribe@example.com>",
    }

    reason = get_email_auto_reply_skip_reason(
        "News <updates@example.com>",
        "Weekly Digest",
        raw_headers=raw_headers,
        recipient="support@example.com",
        body="Click here to unsubscribe",
    )

    assert reason == "mailing_list_header"


def test_guardrails_skip_support_system_notification():
    reason = get_email_auto_reply_skip_reason(
        "L2T Support <l2t.glpi2026@gmail.com>",
        "Votre ticket #157 a bien ete recu - L2T Support",
        recipient="aminbouhlel041@gmail.com",
        body="Votre ticket a ete cree automatiquement.",
    )

    assert reason == "support_system_notification"


def test_guardrails_skip_marketing_json_headers():
    raw_headers = """{
      "From": "Coursera <Coursera@m.learn.coursera.org>",
      "To": "aminbouhlel041@gmail.com",
      "Subject": "Now 40% off: Enjoy all that Coursera Plus offers",
      "List-Unsubscribe": "<mailto:unsubscribe@coursera.org>",
      "List-Unsubscribe-Post": "List-Unsubscribe=One-Click"
    }"""

    reason = get_email_auto_reply_skip_reason(
        "Coursera <Coursera@m.learn.coursera.org>",
        "Now 40% off: Enjoy all that Coursera Plus offers",
        raw_headers=raw_headers,
        recipient="aminbouhlel041@gmail.com",
        body="Secure your access to career-changing programs and manage preferences anytime.",
    )

    assert reason == "mailing_list_header"


def test_guardrails_skip_marketing_subject_and_body_without_headers():
    reason = get_email_auto_reply_skip_reason(
        "Updates <team@example.com>",
        "Ends tomorrow: Save 40% on your annual subscription",
        recipient="support@example.com",
        body="Limited time offer. Update your preferences or unsubscribe anytime.",
    )

    assert reason == "marketing_email"


def test_guardrails_allow_real_customer_email():
    reason = get_email_auto_reply_skip_reason(
        "Customer <customer@example.com>",
        "Need help with SMS pricing",
        recipient="support@example.com",
        body="Hello, can you explain your SMS pricing tiers?",
    )

    assert reason is None


def test_gmail_sync_skips_auto_reply_for_mailing_list(monkeypatch):
    fake_db = FakeSyncSession()
    sync_service = GmailSyncService(fake_db)
    cred = SimpleNamespace(
        user_id=uuid.uuid4(),
        gmail_address="support@example.com",
    )

    inbound_body = "Read our latest release notes and unsubscribe anytime."
    gmail_payload = {
        "id": "gmail-list-1",
        "threadId": "thread-list-1",
        "payload": {
            "mimeType": "text/plain",
            "headers": [
                {"name": "Subject", "value": "Weekly Digest"},
                {"name": "From", "value": "Updates <updates@example.com>"},
                {"name": "To", "value": "support@example.com"},
                {"name": "List-Unsubscribe", "value": "<mailto:unsubscribe@example.com>"},
            ],
            "body": {
                "data": base64.urlsafe_b64encode(inbound_body.encode("utf-8")).decode("utf-8")
            },
        },
    }
    gmail_api = DummyGmailAPI(gmail_payload)

    def fail_send_reply(*args, **kwargs):
        raise AssertionError("send_reply should not be called for mailing-list traffic")

    monkeypatch.setattr(GmailSyncService, "send_reply", fail_send_reply)

    sync_service._process_message(gmail_api, cred, "gmail-list-1")

    emails = [obj for obj in fake_db.added if isinstance(obj, Email)]
    assert len(emails) == 1
    assert emails[0].status == EmailStatus.CONVERTED
    assert emails[0].is_outbound is False
    assert gmail_api.users().messages().modified == [
        ("me", "gmail-list-1", {"removeLabelIds": ["UNREAD"]})
    ]


def test_gmail_sync_skips_auto_reply_for_coursera_promo(monkeypatch):
    fake_db = FakeSyncSession()
    sync_service = GmailSyncService(fake_db)
    cred = SimpleNamespace(
        user_id=uuid.uuid4(),
        gmail_address="aminbouhlel041@gmail.com",
    )

    inbound_body = (
        "Secure your access to career-changing programs and AI-powered learning. "
        "Manage preferences or unsubscribe anytime."
    )
    gmail_payload = {
        "id": "gmail-coursera-1",
        "threadId": "thread-coursera-1",
        "payload": {
            "mimeType": "text/plain",
            "headers": [
                {"name": "Subject", "value": "Now 40% off: Enjoy all that Coursera Plus offers"},
                {"name": "From", "value": "Coursera <Coursera@m.learn.coursera.org>"},
                {"name": "To", "value": "aminbouhlel041@gmail.com"},
                {"name": "List-Unsubscribe", "value": "<mailto:unsubscribe@coursera.org>"},
                {"name": "List-Unsubscribe-Post", "value": "List-Unsubscribe=One-Click"},
            ],
            "body": {
                "data": base64.urlsafe_b64encode(inbound_body.encode("utf-8")).decode("utf-8")
            },
        },
    }
    gmail_api = DummyGmailAPI(gmail_payload)

    def fail_send_reply(*args, **kwargs):
        raise AssertionError("send_reply should not be called for marketing mail")

    monkeypatch.setattr(GmailSyncService, "send_reply", fail_send_reply)

    sync_service._process_message(gmail_api, cred, "gmail-coursera-1")

    emails = [obj for obj in fake_db.added if isinstance(obj, Email)]
    assert len(emails) == 1
    assert emails[0].status == EmailStatus.CONVERTED
    assert emails[0].is_outbound is False
