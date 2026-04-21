import asyncio
import os
import uuid
from datetime import datetime, timezone
from types import SimpleNamespace

from fastapi import HTTPException

os.environ["DEBUG"] = "true"

from app.api.routes.conversations import (
    get_agent_reply_suspension,
    set_agent_reply_suspension,
)
from app.db.models.enums import UserRole
from app.schemas.conversation import ConversationAgentReplySuspensionUpdate
from app.services.conversation_service import ConversationService
from app.services.notification_service import NotificationService


class _ScalarResult:
    def __init__(self, value):
        self._value = value

    def scalar_one_or_none(self):
        return self._value


class DummyDb:
    def __init__(self, execute_values=None):
        self._execute_values = list(execute_values or [])
        self.added = []
        self.deleted = []

    async def execute(self, _query):
        value = self._execute_values.pop(0) if self._execute_values else None
        return _ScalarResult(value)

    def add(self, obj):
        self.added.append(obj)

    async def delete(self, obj):
        self.deleted.append(obj)

    async def flush(self):
        return None

    async def refresh(self, obj):
        if getattr(obj, "updated_at", None) is None:
            obj.updated_at = datetime.now(timezone.utc)


def test_agent_can_fetch_own_reply_suspension_state(monkeypatch):
    conversation_id = uuid.uuid4()
    agent_id = uuid.uuid4()
    customer_id = uuid.uuid4()

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return SimpleNamespace(id=conversation_id, user_id=customer_id, subject="Billing issue")

    async def fake_get_user(self, requested_id):
        assert requested_id == agent_id
        return SimpleNamespace(id=agent_id, role=UserRole.AGENT)

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "get_user", fake_get_user)

    result = asyncio.run(
        get_agent_reply_suspension(
            conversation_id=conversation_id,
            agent_id=agent_id,
            db=DummyDb(execute_values=[None]),
            current_user=SimpleNamespace(id=agent_id, role=UserRole.AGENT),
        )
    )

    assert result.conversation_id == conversation_id
    assert result.agent_id == agent_id
    assert result.suspended is False
    assert result.reason is None


def test_agent_cannot_fetch_other_agent_reply_suspension(monkeypatch):
    conversation_id = uuid.uuid4()
    requester_agent_id = uuid.uuid4()
    target_agent_id = uuid.uuid4()

    async def should_not_get_here(*_args, **_kwargs):
        raise AssertionError("ConversationService should not be called for forbidden access")

    monkeypatch.setattr(ConversationService, "get_conversation", should_not_get_here)
    monkeypatch.setattr(ConversationService, "get_user", should_not_get_here)

    try:
        asyncio.run(
            get_agent_reply_suspension(
                conversation_id=conversation_id,
                agent_id=target_agent_id,
                db=DummyDb(),
                current_user=SimpleNamespace(id=requester_agent_id, role=UserRole.AGENT),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 403
        assert exc.detail == "Agents can only view their own conversation reply suspension status"
    else:
        raise AssertionError("Expected non-admin agent access to be rejected")


def test_admin_suspend_notifies_target_agent(monkeypatch):
    conversation_id = uuid.uuid4()
    admin_id = uuid.uuid4()
    agent_id = uuid.uuid4()
    customer_id = uuid.uuid4()

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return SimpleNamespace(id=conversation_id, user_id=customer_id, subject="Payment follow-up")

    async def fake_get_user(self, requested_id):
        assert requested_id == agent_id
        return SimpleNamespace(id=agent_id, role=UserRole.AGENT)

    captured_notification: dict[str, object] = {}

    async def fake_create_notification(self, **kwargs):
        captured_notification.update(kwargs)
        return SimpleNamespace(id=uuid.uuid4())

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "get_user", fake_get_user)
    monkeypatch.setattr(NotificationService, "create_notification", fake_create_notification)

    db = DummyDb(execute_values=[None])
    result = asyncio.run(
        set_agent_reply_suspension(
            conversation_id=conversation_id,
            agent_id=agent_id,
            payload=ConversationAgentReplySuspensionUpdate(suspended=True, reason="Escalation in progress"),
            db=db,
            current_user=SimpleNamespace(id=admin_id, role=UserRole.ADMIN),
        )
    )

    assert result.suspended is True
    assert result.reason == "Escalation in progress"
    assert len(db.added) == 1

    assert captured_notification["user_id"] == agent_id
    assert captured_notification["type"] == "conversation_reply_suspended"
    assert captured_notification["resource_type"] == "conversation"
    assert captured_notification["resource_id"] == str(conversation_id)
    assert captured_notification["action_url"] == (
        f"/conversations?user={customer_id}&conversation={conversation_id}"
    )
    assert captured_notification["meta"] == {
        "conversation_id": str(conversation_id),
        "agent_id": str(agent_id),
        "suspended": True,
        "suspended_by_id": str(admin_id),
        "reason": "Escalation in progress",
    }
