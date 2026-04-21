import asyncio
import os
import sys
import types
import uuid
from datetime import datetime, timedelta, timezone
from types import SimpleNamespace

os.environ["DEBUG"] = "true"

from app.api.routes.emails import generate_email_assisted_draft, reply_to_email
from app.db.models.enums import AuditAction, UserRole
from app.rag.response_providers.enums import ResponseChannel
from app.schemas.email import EmailReplyRequest


class DummyScalarResult:
    def __init__(self, rows):
        self._rows = rows

    def all(self):
        return self._rows


class DummyResult:
    def __init__(self, *, scalar_rows=None):
        self._scalar_rows = scalar_rows or []

    def scalars(self):
        return DummyScalarResult(self._scalar_rows)


class QueueSession:
    def __init__(self, results):
        self._results = list(results)

    async def execute(self, _query):
        if not self._results:
            raise AssertionError("No queued DB result available")
        return self._results.pop(0)


def _email(
    *,
    email_id: uuid.UUID,
    subject: str,
    body: str,
    is_outbound: bool,
    created_at: datetime,
    sender_address: str = "customer@example.com",
    recipient_address: str = "support@example.com",
    gmail_thread_id: str | None = "thread-1",
):
    return SimpleNamespace(
        id=email_id,
        subject=subject,
        body=body,
        is_outbound=is_outbound,
        created_at=created_at,
        sender_address=sender_address,
        recipient_address=recipient_address,
        gmail_thread_id=gmail_thread_id,
    )


def test_generate_email_assisted_draft_logs_generated_event(monkeypatch):
    now = datetime.now(timezone.utc)
    anchor_id = uuid.uuid4()
    latest_inbound_id = uuid.uuid4()
    operator_id = uuid.uuid4()

    anchor = _email(
        email_id=anchor_id,
        subject="Re: Order status",
        body="Following up from our side",
        is_outbound=True,
        created_at=now - timedelta(minutes=3),
        sender_address="support@example.com",
        recipient_address="customer@example.com",
    )
    latest_inbound = _email(
        email_id=latest_inbound_id,
        subject="Order status",
        body="Can you confirm shipment date?",
        is_outbound=False,
        created_at=now - timedelta(minutes=1),
    )

    db = QueueSession([
        DummyResult(scalar_rows=[anchor, latest_inbound]),
    ])

    async def fake_get_email(self, requested_id):
        assert requested_id == anchor_id
        return anchor

    captured_generate_requests = []

    async def fake_generate(self, request):
        captured_generate_requests.append(request)
        return SimpleNamespace(
            response="Hello, your shipment is scheduled for tomorrow.",
            generated_at=now,
        )

    audit_calls: list[dict] = []

    async def fake_audit_log(self, action, resource_type, **kwargs):
        audit_calls.append(
            {
                "action": action,
                "resource_type": resource_type,
                **kwargs,
            }
        )

    monkeypatch.setattr("app.api.routes.emails.EmailService.get_email", fake_get_email)
    monkeypatch.setattr("app.api.routes.emails.ResponseGenerationService.generate", fake_generate)
    monkeypatch.setattr("app.api.routes.emails.AuditService.log", fake_audit_log)

    response = asyncio.run(
        generate_email_assisted_draft(
            email_id=anchor_id,
            request=SimpleNamespace(state=SimpleNamespace(trace_id="trace-email-generated")),
            db=db,
            current_user=SimpleNamespace(id=operator_id, role=UserRole.AGENT),
        )
    )

    assert response["original_email_id"] == latest_inbound_id
    assert response["draft"] == "Hello, your shipment is scheduled for tomorrow."
    assert len(captured_generate_requests) == 1
    assert captured_generate_requests[0].channel == ResponseChannel.EMAIL

    assert len(audit_calls) == 1
    assert audit_calls[0]["action"] == AuditAction.REPLY
    assert audit_calls[0]["resource_type"] == "assisted_draft"
    assert audit_calls[0]["resource_id"] == str(latest_inbound_id)
    assert audit_calls[0]["user_id"] == operator_id
    assert audit_calls[0]["meta"]["event"] == "generated"
    assert audit_calls[0]["meta"]["channel"] == "email"
    assert audit_calls[0]["meta"]["source_email_id"] == str(latest_inbound_id)
    assert audit_calls[0]["trace_id"] == "trace-email-generated"


def test_reply_to_email_logs_assisted_draft_acceptance(monkeypatch):
    now = datetime.now(timezone.utc)
    email_id = uuid.uuid4()
    operator_id = uuid.uuid4()

    original = _email(
        email_id=email_id,
        subject="Need invoice copy",
        body="Please resend invoice",
        is_outbound=False,
        created_at=now - timedelta(minutes=4),
    )

    async def fake_get_email(self, requested_id):
        assert requested_id == email_id
        return original

    task_calls: list[dict] = []
    fake_tasks_module = types.ModuleType("app.workers.tasks")
    fake_tasks_module.send_email_reply_task = SimpleNamespace(
        delay=lambda **kwargs: task_calls.append(kwargs)
    )
    monkeypatch.setitem(sys.modules, "app.workers.tasks", fake_tasks_module)

    audit_calls: list[dict] = []

    async def fake_audit_log(self, action, resource_type, **kwargs):
        audit_calls.append(
            {
                "action": action,
                "resource_type": resource_type,
                **kwargs,
            }
        )

    monkeypatch.setattr("app.api.routes.emails.EmailService.get_email", fake_get_email)
    monkeypatch.setattr("app.api.routes.emails.AuditService.log", fake_audit_log)

    generated_at = now - timedelta(seconds=30)
    response = asyncio.run(
        reply_to_email(
            email_id=email_id,
            payload=EmailReplyRequest(
                body="Happy to help. I attached the invoice copy.",
                used_assisted_draft=True,
                assisted_draft_edited=False,
                assisted_draft_generated_at=generated_at,
            ),
            request=SimpleNamespace(state=SimpleNamespace(trace_id="trace-email-accepted")),
            db=QueueSession([]),
            current_user=SimpleNamespace(id=operator_id, role=UserRole.AGENT),
        )
    )

    assert response["original_email_id"] == email_id
    assert response["recipient"] == "customer@example.com"

    assert len(task_calls) == 1
    assert task_calls[0]["user_id"] == str(operator_id)
    assert task_calls[0]["original_email_id"] == str(email_id)
    assert task_calls[0]["reply_body"] == "Happy to help. I attached the invoice copy."

    assert len(audit_calls) == 2

    email_reply_entry = next(
        call for call in audit_calls if call["resource_type"] == "email"
    )
    assert email_reply_entry["action"] == AuditAction.REPLY
    assert email_reply_entry["resource_id"] == str(email_id)
    assert email_reply_entry["meta"]["channel"] == "email"
    assert email_reply_entry["meta"]["used_assisted_draft"] is True
    assert email_reply_entry["trace_id"] == "trace-email-accepted"

    assisted_entry = next(
        call for call in audit_calls if call["resource_type"] == "assisted_draft"
    )
    assert assisted_entry["action"] == AuditAction.REPLY
    assert assisted_entry["resource_id"] == str(email_id)
    assert assisted_entry["meta"]["event"] == "accepted"
    assert assisted_entry["meta"]["channel"] == "email"
    assert assisted_entry["meta"]["assisted_draft_edited"] is False
    assert assisted_entry["meta"]["assisted_draft_generated_at"] == generated_at.isoformat()
    assert assisted_entry["meta"]["assisted_draft_seconds_to_send"] >= 0
    assert assisted_entry["meta"]["sent_char_count"] == len("Happy to help. I attached the invoice copy.")
