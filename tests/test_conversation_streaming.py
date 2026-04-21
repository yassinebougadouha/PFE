import asyncio
import json
import os
import uuid
from datetime import datetime, timezone
from types import SimpleNamespace

from fastapi import HTTPException

os.environ["DEBUG"] = "true"

from app.api.routes.conversations import stream_client_message
from app.db.models.enums import ChannelType, ConversationStatus, UserRole
from app.rag.response_providers.enums import ResponseChannel, ResponseTone
from app.rag.response_providers.schemas import GenerateRequest
from app.rag.response_providers.service import ResponseGenerationService
from app.schemas.conversation import ConversationStreamRequest
from app.services.conversation_service import ConversationService


class DummySession:
    class _ScalarResult:
        @staticmethod
        def scalar_one_or_none():
            return None

    async def commit(self):
        return None

    async def refresh(self, _obj):
        return None

    async def rollback(self):
        return None

    async def execute(self, _query):
        return self._ScalarResult()


class DummySessionContext:
    def __init__(self, session):
        self.session = session

    async def __aenter__(self):
        return self.session

    async def __aexit__(self, exc_type, exc, tb):
        return False


def _conversation(
    conversation_id: uuid.UUID,
    user_id: uuid.UUID,
    subject: str,
    *,
    ai_auto_reply_enabled: bool = True,
):
    now = datetime.now(timezone.utc)
    return SimpleNamespace(
        id=conversation_id,
        user_id=user_id,
        channel=ChannelType.CHAT,
        status=ConversationStatus.OPEN,
        subject=subject,
        is_pinned=False,
        ai_auto_reply_enabled=ai_auto_reply_enabled,
        created_at=now,
        updated_at=now,
        is_deleted=False,
    )


def _message(message_id: uuid.UUID, conversation_id: uuid.UUID, sender_id: uuid.UUID, content: str):
    now = datetime.now(timezone.utc)
    return SimpleNamespace(
        id=message_id,
        conversation_id=conversation_id,
        sender_id=sender_id,
        content=content,
        is_internal=False,
        is_read=False,
        attachment_filename=None,
        attachment_content_type=None,
        attachment_size=None,
        created_at=now,
    )


async def _collect_chunks(body_iterator) -> list[str]:
    chunks: list[str] = []
    async for chunk in body_iterator:
        chunks.append(chunk.decode() if isinstance(chunk, bytes) else chunk)
    return chunks


def test_stream_client_message_emits_meta_token_and_done_events(monkeypatch):
    client_id = uuid.uuid4()
    conversation_id = uuid.uuid4()
    user_message_id = uuid.uuid4()
    assistant_message_id = uuid.uuid4()

    conversation = _conversation(conversation_id, client_id, "Need help")
    user_message = _message(user_message_id, conversation_id, client_id, "Need help")
    assistant_message = _message(
        assistant_message_id,
        conversation_id,
        uuid.uuid4(),
        "Here is a streamed reply.",
    )

    async def fake_create_conversation(self, user_id, payload):
        assert user_id == client_id
        assert payload.subject == "Need help"
        return conversation

    async def fake_add_message(self, requested_conversation_id, sender_id, payload, **_kwargs):
        assert requested_conversation_id == conversation_id
        assert sender_id == client_id
        assert payload.content == "Need help"
        return user_message

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return conversation

    async def fake_get_user(self, requested_id):
        assert requested_id == client_id
        return SimpleNamespace(id=client_id)

    async def fake_get_message(self, requested_id):
        assert requested_id == user_message_id
        return user_message

    async def fake_build_request(self, conversation, customer, latest_message):
        assert conversation.id == conversation_id
        assert customer.id == client_id
        assert latest_message.id == user_message_id
        return (
            GenerateRequest(
                query="Need help",
                channel=ResponseChannel.CHAT,
                tone=ResponseTone.CONCISE,
                language="en",
                include_sources=False,
            ),
            None,
        )

    async def fake_save_support_reply(self, *, conversation, reply_text):
        assert conversation.id == conversation_id
        assert reply_text == "Here is a streamed reply."
        return assistant_message

    async def fake_generate_stream(self, _request):
        yield "Here is "
        yield "a streamed reply."

    monkeypatch.setattr(ConversationService, "create_conversation", fake_create_conversation)
    monkeypatch.setattr(ConversationService, "add_message", fake_add_message)
    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "get_user", fake_get_user)
    monkeypatch.setattr(ConversationService, "get_message", fake_get_message)
    monkeypatch.setattr(ConversationService, "build_support_reply_request", fake_build_request)
    monkeypatch.setattr(ConversationService, "save_support_reply", fake_save_support_reply)
    monkeypatch.setattr(ResponseGenerationService, "generate_stream", fake_generate_stream)
    monkeypatch.setattr(
        "app.api.routes.conversations.async_session_factory",
        lambda: DummySessionContext(DummySession()),
    )

    response = asyncio.run(
        stream_client_message(
            payload=ConversationStreamRequest(content="Need help", subject="Need help"),
            db=DummySession(),
            current_user=SimpleNamespace(id=client_id, role=UserRole.CLIENT),
        )
    )

    chunks = asyncio.run(_collect_chunks(response.body_iterator))

    assert len(chunks) >= 5
    assert chunks[0].startswith("event: meta")
    assert chunks[1] == 'event: status\ndata: {"phase": "thinking"}\n\n'
    assert chunks[2] == 'event: token\ndata: {"delta": "Here is "}\n\n'
    assert chunks[3] == 'event: token\ndata: {"delta": "a streamed reply."}\n\n'

    done_payload = json.loads(chunks[4].split("data: ", 1)[1])
    assert done_payload["assistant_message"]["content"] == "Here is a streamed reply."


def test_stream_client_message_emits_disabled_done_when_auto_reply_is_paused(monkeypatch):
    client_id = uuid.uuid4()
    conversation_id = uuid.uuid4()
    user_message_id = uuid.uuid4()

    conversation = _conversation(
        conversation_id,
        client_id,
        "Need help",
        ai_auto_reply_enabled=False,
    )
    user_message = _message(user_message_id, conversation_id, client_id, "Need help")

    async def fake_create_conversation(self, user_id, payload):
        assert user_id == client_id
        assert payload.subject == "Need help"
        return conversation

    async def fake_add_message(self, requested_conversation_id, sender_id, payload, **_kwargs):
        assert requested_conversation_id == conversation_id
        assert sender_id == client_id
        assert payload.content == "Need help"
        return user_message

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return conversation

    async def should_not_build_request(*_args, **_kwargs):
        raise AssertionError("Auto-reply request should not be built when auto-replies are disabled")

    monkeypatch.setattr(ConversationService, "create_conversation", fake_create_conversation)
    monkeypatch.setattr(ConversationService, "add_message", fake_add_message)
    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "build_support_reply_request", should_not_build_request)
    monkeypatch.setattr(
        "app.api.routes.conversations.async_session_factory",
        lambda: DummySessionContext(DummySession()),
    )

    response = asyncio.run(
        stream_client_message(
            payload=ConversationStreamRequest(content="Need help", subject="Need help"),
            db=DummySession(),
            current_user=SimpleNamespace(id=client_id, role=UserRole.CLIENT),
        )
    )

    chunks = asyncio.run(_collect_chunks(response.body_iterator))

    assert len(chunks) >= 3
    assert chunks[0].startswith("event: meta")
    assert chunks[1] == 'event: status\ndata: {"phase": "disabled"}\n\n'

    done_payload = json.loads(chunks[2].split("data: ", 1)[1])
    assert done_payload["assistant_message"] is None
    assert done_payload["auto_reply_enabled"] is False


def test_stream_client_message_rejects_non_clients():
    try:
        asyncio.run(
            stream_client_message(
                payload=ConversationStreamRequest(content="Need help"),
                db=DummySession(),
                current_user=SimpleNamespace(id=uuid.uuid4(), role=UserRole.AGENT),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 403
        assert exc.detail == "Only clients can use streamed chat replies"
    else:
        raise AssertionError("Expected non-client streamed chat attempt to be rejected")
