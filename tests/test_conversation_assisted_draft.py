import asyncio
import os
import uuid
from datetime import datetime, timedelta, timezone
from types import SimpleNamespace

from fastapi import BackgroundTasks, HTTPException

os.environ["DEBUG"] = "true"

from app.api.routes.conversations import (  # noqa: E402
    _build_conversation_auto_reply_response,
    generate_assisted_draft,
    send_message,
)
from app.db.models.enums import AuditAction, ChannelType, UserRole  # noqa: E402
from app.rag.response_providers.enums import ResponseChannel, ResponseTone  # noqa: E402
from app.rag.response_providers.schemas import GenerateRequest  # noqa: E402
from app.schemas.conversation import MessageCreate  # noqa: E402
from app.services.conversation_service import ConversationService  # noqa: E402


class DummyScalarResult:
    def __init__(self, value):
        self._value = value

    def scalar_one_or_none(self):
        return self._value


class DummySession:
    def __init__(self, latest_message=None):
        self.latest_message = latest_message
        self.commit_calls = 0

    def add(self, _entry):
        return None

    async def flush(self):
        return None

    async def execute(self, _query):
        return DummyScalarResult(self.latest_message)

    async def commit(self):
        self.commit_calls += 1


def _conversation(conversation_id: uuid.UUID, user_id: uuid.UUID, channel: ChannelType):
    now = datetime.now(timezone.utc)
    return SimpleNamespace(
        id=conversation_id,
        user_id=user_id,
        channel=channel,
        ai_auto_reply_enabled=True,
        ai_auto_reply_paused_until=None,
        updated_at=now,
    )


def _message(message_id: uuid.UUID, conversation_id: uuid.UUID, sender_id: uuid.UUID, content: str):
    now = datetime.now(timezone.utc)
    return SimpleNamespace(
        id=message_id,
        conversation_id=conversation_id,
        sender_id=sender_id,
        content=content,
        is_internal=False,
        created_at=now,
    )


def test_generate_assisted_draft_allows_whatsapp(monkeypatch):
    conversation_id = uuid.uuid4()
    customer_id = uuid.uuid4()
    operator_id = uuid.uuid4()
    audit_calls: list[dict] = []
    latest_customer_message = _message(uuid.uuid4(), conversation_id, customer_id, "Need status update")
    conversation = _conversation(conversation_id, customer_id, ChannelType.WHATSAPP)

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return conversation

    async def fake_get_user(self, requested_id):
        assert requested_id == customer_id
        return SimpleNamespace(id=customer_id)

    async def fake_build_support_reply_request(self, *, conversation, customer, latest_message):
        assert conversation.channel == ChannelType.WHATSAPP
        assert customer.id == customer_id
        assert latest_message.id == latest_customer_message.id
        return (
            GenerateRequest(
                query="Need status update",
                channel=ResponseChannel.WHATSAPP,
                tone=ResponseTone.CONCISE,
                language="en",
                include_sources=False,
            ),
            None,
        )

    async def fake_generate_reply_text(self, request, *, attachment_context=None):
        assert request.channel == ResponseChannel.WHATSAPP
        assert attachment_context is None
        return "Draft reply for WhatsApp"

    async def fake_audit_log(self, action, resource_type, **kwargs):
        audit_calls.append(
            {
                "action": action,
                "resource_type": resource_type,
                **kwargs,
            }
        )

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "get_user", fake_get_user)
    monkeypatch.setattr(ConversationService, "build_support_reply_request", fake_build_support_reply_request)
    monkeypatch.setattr(ConversationService, "_generate_reply_text", fake_generate_reply_text)
    monkeypatch.setattr("app.api.routes.conversations.AuditService.log", fake_audit_log)

    response = asyncio.run(
        generate_assisted_draft(
            conversation_id=conversation_id,
            db=DummySession(latest_customer_message),
            current_user=SimpleNamespace(id=operator_id),
        )
    )

    assert response.conversation_id == conversation_id
    assert response.source_message_id == latest_customer_message.id
    assert response.draft == "Draft reply for WhatsApp"
    assert response.language == "en"
    assert len(audit_calls) == 1
    assert audit_calls[0]["action"] == AuditAction.REPLY
    assert audit_calls[0]["resource_type"] == "assisted_draft"
    assert audit_calls[0]["resource_id"] == str(conversation_id)
    assert audit_calls[0]["user_id"] == operator_id
    assert audit_calls[0]["meta"]["event"] == "generated"
    assert audit_calls[0]["meta"]["channel"] == "whatsapp"
    assert audit_calls[0]["meta"]["source_message_id"] == str(latest_customer_message.id)


def test_generate_assisted_draft_rejects_non_chat_whatsapp(monkeypatch):
    conversation_id = uuid.uuid4()
    customer_id = uuid.uuid4()
    conversation = _conversation(conversation_id, customer_id, ChannelType.EMAIL)

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return conversation

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)

    try:
        asyncio.run(
            generate_assisted_draft(
                conversation_id=conversation_id,
                db=DummySession(),
                current_user=SimpleNamespace(id=uuid.uuid4()),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 400
        assert exc.detail == "Assisted drafts are currently available for chat and WhatsApp conversations only"
    else:
        raise AssertionError("Expected assisted draft request on EMAIL conversation to be rejected")


def test_build_support_reply_request_uses_whatsapp_channel(monkeypatch):
    conversation_id = uuid.uuid4()
    expected_customer_id = uuid.uuid4()
    conversation = _conversation(conversation_id, expected_customer_id, ChannelType.WHATSAPP)
    latest = _message(uuid.uuid4(), conversation_id, expected_customer_id, "Hello, update please")

    async def fake_build_history(self, conversation_id, customer_id, latest_message_id):
        assert conversation_id == conversation.id
        assert customer_id == expected_customer_id
        assert latest_message_id == latest.id
        return []

    async def fake_attachment_context(self, message):
        assert message.id == latest.id
        return None

    monkeypatch.setattr(ConversationService, "_build_conversation_history", fake_build_history)
    monkeypatch.setattr(ConversationService, "_build_attachment_context", fake_attachment_context)

    service = ConversationService(db=DummySession())
    request, attachment_context = asyncio.run(
        service.build_support_reply_request(
            conversation=conversation,
            customer=SimpleNamespace(id=expected_customer_id),
            latest_message=latest,
        )
    )

    assert request.channel == ResponseChannel.WHATSAPP
    assert "Channel instruction: WhatsApp reply" in request.query
    assert request.max_tokens <= 180
    assert request.temperature == 0.12
    assert attachment_context is None


def test_conversation_auto_reply_response_marks_whatsapp_assisted_draft_available(monkeypatch):
    conversation = _conversation(uuid.uuid4(), uuid.uuid4(), ChannelType.WHATSAPP)

    evaluation = SimpleNamespace(
        paused_until=None,
        pause_active=False,
        effective_enabled=True,
        block_reason=None,
    )

    async def fake_evaluate(_db, _conversation):
        return "whatsapp", True, evaluation

    monkeypatch.setattr("app.api.routes.conversations._evaluate_conversation_auto_reply", fake_evaluate)

    response = asyncio.run(_build_conversation_auto_reply_response(db=DummySession(), conversation=conversation))

    assert response.assisted_draft_available is True
    assert response.channel == ChannelType.WHATSAPP


def test_send_message_logs_assisted_draft_acceptance(monkeypatch):
    conversation_id = uuid.uuid4()
    customer_id = uuid.uuid4()
    operator_id = uuid.uuid4()
    sent_message = _message(uuid.uuid4(), conversation_id, operator_id, "Sure, here is the update")
    generated_at = datetime.now(timezone.utc) - timedelta(seconds=40)
    audit_calls: list[dict] = []

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return _conversation(conversation_id, customer_id, ChannelType.CHAT)

    async def fake_add_message(self, requested_id, sender_id, payload):
        assert requested_id == conversation_id
        assert sender_id == operator_id
        assert payload.content == "Sure, here is the update"
        assert payload.used_assisted_draft is True
        return sent_message

    async def fake_audit_log(self, action, resource_type, **kwargs):
        audit_calls.append(
            {
                "action": action,
                "resource_type": resource_type,
                **kwargs,
            }
        )

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "add_message", fake_add_message)
    monkeypatch.setattr("app.api.routes.conversations.AuditService.log", fake_audit_log)

    response = asyncio.run(
        send_message(
            conversation_id=conversation_id,
            payload=MessageCreate(
                content="Sure, here is the update",
                used_assisted_draft=True,
                assisted_draft_edited=True,
                assisted_draft_generated_at=generated_at,
            ),
            background_tasks=BackgroundTasks(),
            db=DummySession(),
            current_user=SimpleNamespace(
                id=operator_id,
                role=UserRole.ADMIN,
                can_reply_conversations=True,
            ),
        )
    )

    assert response.id == sent_message.id
    assert len(audit_calls) == 1
    assert audit_calls[0]["action"] == AuditAction.REPLY
    assert audit_calls[0]["resource_type"] == "assisted_draft"
    assert audit_calls[0]["resource_id"] == str(conversation_id)
    assert audit_calls[0]["user_id"] == operator_id
    assert audit_calls[0]["meta"]["event"] == "accepted"
    assert audit_calls[0]["meta"]["channel"] == "chat"
    assert audit_calls[0]["meta"]["sent_message_id"] == str(sent_message.id)
    assert audit_calls[0]["meta"]["assisted_draft_edited"] is True
    assert audit_calls[0]["meta"]["assisted_draft_generated_at"] == generated_at.isoformat()
    assert audit_calls[0]["meta"]["assisted_draft_seconds_to_send"] >= 0
    assert audit_calls[0]["meta"]["sent_char_count"] == len("Sure, here is the update")
