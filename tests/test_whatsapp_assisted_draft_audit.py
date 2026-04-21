import asyncio
import os
import sys
import types
import uuid
from datetime import datetime, timedelta, timezone
from types import SimpleNamespace

os.environ["DEBUG"] = "true"

from app.api.routes.whatsapp import reply_to_conversation
from app.db.models.enums import AuditAction, ChannelType, UserRole
from app.schemas.whatsapp import WhatsAppReplyRequest


class DummyScalarResult:
    def __init__(self, value):
        self._value = value

    def scalar_one_or_none(self):
        return self._value


class QueueSession:
    def __init__(self, values):
        self._values = list(values)
        self.commit_calls = 0

    async def execute(self, _query):
        value = self._values.pop(0) if self._values else None
        return DummyScalarResult(value)

    async def commit(self):
        self.commit_calls += 1


def test_whatsapp_reply_logs_assisted_draft_acceptance(monkeypatch):
    conversation_id = uuid.uuid4()
    customer_id = uuid.uuid4()
    operator_id = uuid.uuid4()

    conversation = SimpleNamespace(
        id=conversation_id,
        user_id=customer_id,
        channel=ChannelType.WHATSAPP,
    )
    customer = SimpleNamespace(phone_number="+21612345678")
    db = QueueSession([conversation, customer])

    outbound_task_calls: list[dict] = []
    provider_calls: list[tuple[str, str]] = []
    audit_calls: list[dict] = []

    fake_tasks_module = types.ModuleType("app.workers.tasks")
    fake_tasks_module.record_whatsapp_outbound_task = SimpleNamespace(
        delay=lambda **kwargs: outbound_task_calls.append(kwargs)
    )
    monkeypatch.setitem(sys.modules, "app.workers.tasks", fake_tasks_module)

    class FakeProvider:
        provider_name = "bridge"

        async def send_message(self, to_number, message):
            provider_calls.append((to_number, message))
            return {
                "success": True,
                "message_id": "wa-message-1",
                "provider": "bridge",
            }

    async def fake_audit_log(self, action, resource_type, **kwargs):
        audit_calls.append(
            {
                "action": action,
                "resource_type": resource_type,
                **kwargs,
            }
        )

    monkeypatch.setattr("app.api.routes.whatsapp.get_whatsapp_provider", lambda: FakeProvider())
    monkeypatch.setattr("app.api.routes.whatsapp.AuditService.log", fake_audit_log)

    response = asyncio.run(
        reply_to_conversation(
            conversation_id=conversation_id,
            data=WhatsAppReplyRequest(
                message="Here is your update",
                used_assisted_draft=True,
                assisted_draft_edited=False,
                assisted_draft_generated_at=datetime.now(timezone.utc) - timedelta(seconds=75),
            ),
            db=db,
            current_user=SimpleNamespace(id=operator_id, role=UserRole.ADMIN),
        )
    )

    assert response["success"] is True
    assert provider_calls == [("+21612345678", "Here is your update")]
    assert len(outbound_task_calls) == 1
    assert outbound_task_calls[0]["conversation_id"] == str(conversation_id)
    assert outbound_task_calls[0]["user_id"] == str(operator_id)

    assert db.commit_calls == 1
    assert len(audit_calls) == 2

    outbound_entry = next(
        call for call in audit_calls if call["action"] == AuditAction.WHATSAPP_OUT
    )
    assert outbound_entry["resource_type"] == "conversation"
    assert outbound_entry["meta"]["channel"] == "whatsapp"
    assert outbound_entry["meta"]["used_assisted_draft"] is True

    assisted_entry = next(
        call
        for call in audit_calls
        if call["action"] == AuditAction.REPLY and call["resource_type"] == "assisted_draft"
    )
    assert assisted_entry["resource_id"] == str(conversation_id)
    assert assisted_entry["user_id"] == operator_id
    assert assisted_entry["meta"]["event"] == "accepted"
    assert assisted_entry["meta"]["channel"] == "whatsapp"
    assert assisted_entry["meta"]["assisted_draft_edited"] is False
    assert assisted_entry["meta"]["assisted_draft_generated_at"] is not None
    assert assisted_entry["meta"]["assisted_draft_seconds_to_send"] >= 0
    assert assisted_entry["meta"]["sent_char_count"] == len("Here is your update")
