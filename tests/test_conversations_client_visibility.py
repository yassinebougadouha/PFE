import asyncio
import os
import uuid
from types import SimpleNamespace

os.environ["DEBUG"] = "true"

from fastapi import BackgroundTasks, HTTPException

from app.api.routes.conversations import get_messages, send_message
from app.db.models.enums import ChannelType, UserRole
from app.schemas.conversation import MessageCreate
from app.services.conversation_playbook_service import ConversationPlaybookService
from app.services.conversation_service import ConversationService


def test_client_message_list_excludes_internal(monkeypatch):
    captured: dict[str, object] = {}
    conversation_id = uuid.uuid4()
    user_id = uuid.uuid4()

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return SimpleNamespace(user_id=user_id)

    async def fake_get_messages(self, requested_id, skip=0, limit=100, include_internal=True):
        captured["conversation_id"] = requested_id
        captured["skip"] = skip
        captured["limit"] = limit
        captured["include_internal"] = include_internal
        return []

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "get_messages", fake_get_messages)

    result = asyncio.run(
        get_messages(
            conversation_id=conversation_id,
            db=object(),
            current_user=SimpleNamespace(id=user_id, role=UserRole.CLIENT),
            skip=5,
            limit=25,
        )
    )

    assert result == []
    assert captured == {
        "conversation_id": conversation_id,
        "skip": 5,
        "limit": 25,
        "include_internal": False,
    }


def test_client_cannot_send_internal_message(monkeypatch):
    conversation_id = uuid.uuid4()
    user_id = uuid.uuid4()

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return SimpleNamespace(user_id=user_id)

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)

    try:
        asyncio.run(
            send_message(
                conversation_id=conversation_id,
                payload=MessageCreate(content="secret note", is_internal=True),
                background_tasks=BackgroundTasks(),
                db=object(),
                current_user=SimpleNamespace(id=user_id, role=UserRole.CLIENT),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 403
        assert exc.detail == "Clients cannot send internal messages"
    else:
        raise AssertionError("Expected client internal message attempt to be rejected")


def test_client_cannot_send_message_to_another_clients_conversation(monkeypatch):
    conversation_id = uuid.uuid4()
    owner_id = uuid.uuid4()
    other_user_id = uuid.uuid4()

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return SimpleNamespace(user_id=owner_id)

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)

    try:
        asyncio.run(
            send_message(
                conversation_id=conversation_id,
                payload=MessageCreate(content="hello support"),
                background_tasks=BackgroundTasks(),
                db=object(),
                current_user=SimpleNamespace(id=other_user_id, role=UserRole.CLIENT),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 403
        assert exc.detail == "Access denied"
    else:
        raise AssertionError("Expected cross-conversation client message attempt to be rejected")


def test_client_message_queues_support_auto_reply(monkeypatch):
    conversation_id = uuid.uuid4()
    user_id = uuid.uuid4()
    message_id = uuid.uuid4()
    captured: dict[str, object] = {}

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return SimpleNamespace(
            id=conversation_id,
            user_id=user_id,
            channel=ChannelType.CHAT,
        )

    async def fake_add_message(self, requested_id, sender_id, payload):
        captured["add_message"] = (requested_id, sender_id, payload.content, payload.is_internal)
        return SimpleNamespace(
            id=message_id,
            conversation_id=requested_id,
            sender_id=sender_id,
            content=payload.content,
            is_internal=payload.is_internal,
        )

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "add_message", fake_add_message)

    background_tasks = BackgroundTasks()

    result = asyncio.run(
        send_message(
            conversation_id=conversation_id,
            payload=MessageCreate(content="I need help with billing"),
            background_tasks=background_tasks,
            db=object(),
            current_user=SimpleNamespace(
                id=user_id,
                role=UserRole.CLIENT,
                full_name="Jane Client",
            ),
        )
    )

    assert result.id == message_id
    assert captured["add_message"] == (
        conversation_id,
        user_id,
        "I need help with billing",
        False,
    )
    assert len(background_tasks.tasks) == 2

    support_task = next(task for task in background_tasks.tasks if task.func == ConversationService.generate_support_reply_for_message)
    assert support_task.kwargs == {
        "conversation_id": conversation_id,
        "customer_id": user_id,
        "latest_message_id": message_id,
    }

    playbook_task = next(task for task in background_tasks.tasks if task.func == ConversationPlaybookService.evaluate_playbooks_for_conversation)
    assert playbook_task.args == (conversation_id,)
    assert playbook_task.kwargs == {
        "event": "customer_message",
    }
