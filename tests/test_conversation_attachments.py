import asyncio
import os
import uuid
from io import BytesIO
from types import SimpleNamespace

from fastapi import BackgroundTasks
from starlette.datastructures import UploadFile

os.environ["DEBUG"] = "true"

from app.api.routes.conversations import send_attachment_message
from app.db.models.enums import ChannelType, UserRole
from app.services.conversation_playbook_service import ConversationPlaybookService
from app.services.conversation_service import ConversationService


def test_client_attachment_message_queues_support_auto_reply(monkeypatch, tmp_path):
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

    async def fake_add_message(self, requested_id, sender_id, payload, **kwargs):
        captured["add_message"] = {
            "conversation_id": requested_id,
            "sender_id": sender_id,
            "content": payload.content,
            "is_internal": payload.is_internal,
            **kwargs,
        }
        return SimpleNamespace(
            id=message_id,
            conversation_id=requested_id,
            sender_id=sender_id,
            content=payload.content,
            is_internal=payload.is_internal,
            attachment_filename=kwargs.get("attachment_filename"),
            attachment_content_type=kwargs.get("attachment_content_type"),
            attachment_size=kwargs.get("attachment_size"),
        )

    async def fake_store_attachment(requested_conversation_id, upload):
        assert requested_conversation_id == conversation_id
        assert upload.filename == "invoice.png"
        return {
            "filename": "invoice.png",
            "content_type": "image/png",
            "size": 12,
            "path": str(tmp_path / "invoice.png"),
        }

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(ConversationService, "add_message", fake_add_message)
    monkeypatch.setattr(
        "app.api.routes.conversations._store_chat_attachment",
        fake_store_attachment,
    )

    background_tasks = BackgroundTasks()
    upload = UploadFile(file=BytesIO(b"fake-image"), filename="invoice.png")
    upload.headers = {"content-type": "image/png"}

    result = asyncio.run(
        send_attachment_message(
            conversation_id=conversation_id,
            background_tasks=background_tasks,
            db=object(),
            current_user=SimpleNamespace(id=user_id, role=UserRole.CLIENT),
            file=upload,
            content="",
            is_internal=False,
        )
    )

    assert result.id == message_id
    assert captured["add_message"] == {
        "conversation_id": conversation_id,
        "sender_id": user_id,
        "content": "Shared an image: invoice.png",
        "is_internal": False,
        "attachment_path": str(tmp_path / "invoice.png"),
        "attachment_filename": "invoice.png",
        "attachment_content_type": "image/png",
        "attachment_size": 12,
    }
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
        "event": "customer_attachment_message",
    }
