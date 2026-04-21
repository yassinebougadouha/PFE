import os
import asyncio
import uuid
from types import SimpleNamespace

os.environ["DEBUG"] = "true"

from app.db.models.enums import ChannelType
from app.services.conversation_service import ConversationService


class DummyDB:
    def __init__(self):
        self.items = []

    def add(self, item):
        self.items.append(item)

    async def flush(self):
        return None

    async def refresh(self, item):
        return None

    async def execute(self, query):
        class _Result:
            @staticmethod
            def scalar_one_or_none():
                return None

        return _Result()


def test_generate_support_reply_uses_image_analysis_in_query(monkeypatch):
    db = DummyDB()
    svc = ConversationService(db)
    conversation_id = uuid.uuid4()
    customer_id = uuid.uuid4()
    support_bot_id = uuid.uuid4()
    captured: dict[str, object] = {}

    async def fake_build_conversation_history(self, conversation_id, customer_id, latest_message_id):
        return []

    async def fake_build_attachment_context(self, latest_message):
        return {
            "kind": "image",
            "filename": "login-error.png",
            "content_type": "image/png",
            "summary": "A login page with a red error banner.",
            "visible_text": "Invalid password",
            "issue_signals": ["The screenshot shows a failed sign-in attempt."],
            "suggested_focus": "Help the customer regain access",
        }

    async def fake_generate_reply_text(self, request, *, attachment_context=None):
        captured["query"] = request.query
        captured["language"] = request.language
        captured["attachment_context"] = attachment_context
        return "I can see a login error on the screenshot, and the next step is to reset the password."

    async def fake_get_or_create_support_bot(self):
        return SimpleNamespace(id=support_bot_id)

    monkeypatch.setattr(ConversationService, "_build_conversation_history", fake_build_conversation_history)
    monkeypatch.setattr(ConversationService, "_build_attachment_context", fake_build_attachment_context)
    monkeypatch.setattr(ConversationService, "_generate_reply_text", fake_generate_reply_text)
    monkeypatch.setattr(ConversationService, "_get_or_create_support_bot", fake_get_or_create_support_bot)

    result = asyncio.run(
        svc.generate_support_reply(
            conversation=SimpleNamespace(
                id=conversation_id,
                channel=ChannelType.CHAT,
                updated_at=None,
            ),
            customer=SimpleNamespace(id=customer_id),
            latest_message=SimpleNamespace(
                id=uuid.uuid4(),
                content="Shared an image: login-error.png",
                is_internal=False,
                attachment_path="uploads/chat_attachments/login-error.png",
                attachment_content_type="image/png",
                attachment_filename="login-error.png",
            ),
        )
    )

    assert result.content == "I can see a login error on the screenshot, and the next step is to reset the password."
    assert "Summary: A login page with a red error banner." in captured["query"]
    assert "Visible text: Invalid password" in captured["query"]
    assert "Issue signal: The screenshot shows a failed sign-in attempt." in captured["query"]
    assert "Likely support focus: Help the customer regain access" in captured["query"]
    assert captured["language"] == "en"
    assert captured["attachment_context"]["visible_text"] == "Invalid password"


def test_fallback_image_reply_is_used_when_no_llm_answer(monkeypatch):
    svc = ConversationService(DummyDB())

    class FailingResponseService:
        def __init__(self, db):
            pass

        async def generate(self, request):
            raise RuntimeError("llm unavailable")

    async def fake_contextual_fallback(self, query):
        return None

    monkeypatch.setattr("app.services.conversation_service.ResponseGenerationService", FailingResponseService)
    monkeypatch.setattr(ConversationService, "_contextual_fallback_reply", fake_contextual_fallback)

    result = asyncio.run(
        svc._generate_reply_text(
            SimpleNamespace(query="Customer message: please check the screenshot", language="en"),
            attachment_context={
                "kind": "image",
                "summary": "A checkout page with a payment failure message",
                "visible_text": "Card declined",
                "issue_signals": ["The payment step is failing."],
                "suggested_focus": "Help the customer complete the purchase",
            },
        )
    )

    assert "A checkout page with a payment failure message." in result
    assert "Visible text: Card declined." in result
    assert "The payment step is failing." in result


def test_generate_support_reply_uses_text_file_context_and_marks_it_non_visual(monkeypatch):
    db = DummyDB()
    svc = ConversationService(db)
    conversation_id = uuid.uuid4()
    customer_id = uuid.uuid4()
    captured: dict[str, object] = {}

    async def fake_build_conversation_history(self, conversation_id, customer_id, latest_message_id):
        return []

    async def fake_build_attachment_context(self, latest_message):
        return {
            "kind": "text",
            "filename": "notes.txt",
            "content_type": "text/plain",
            "preview": "The login fails after password reset and shows code 403.",
        }

    async def fake_generate_reply_text(self, request, *, attachment_context=None):
        captured["query"] = request.query
        captured["attachment_context"] = attachment_context
        return "I read the text file and it mentions a 403 error after password reset."

    async def fake_get_or_create_support_bot(self):
        return SimpleNamespace(id=uuid.uuid4())

    monkeypatch.setattr(ConversationService, "_build_conversation_history", fake_build_conversation_history)
    monkeypatch.setattr(ConversationService, "_build_attachment_context", fake_build_attachment_context)
    monkeypatch.setattr(ConversationService, "_generate_reply_text", fake_generate_reply_text)
    monkeypatch.setattr(ConversationService, "_get_or_create_support_bot", fake_get_or_create_support_bot)

    result = asyncio.run(
        svc.generate_support_reply(
            conversation=SimpleNamespace(
                id=conversation_id,
                channel=ChannelType.CHAT,
                updated_at=None,
            ),
            customer=SimpleNamespace(id=customer_id),
            latest_message=SimpleNamespace(
                id=uuid.uuid4(),
                content="Shared a file: notes.txt",
                is_internal=False,
                attachment_path="uploads/chat_attachments/notes.txt",
                attachment_content_type="text/plain",
                attachment_filename="notes.txt",
            ),
        )
    )

    assert result.content == "I read the text file and it mentions a 403 error after password reset."
    assert "This attachment is a file, not an image. Do not describe it visually." in captured["query"]
    assert "Extracted file text:\nThe login fails after password reset and shows code 403." in captured["query"]
    assert "Attached image analysis:" not in captured["query"]


def test_build_attachment_context_reads_text_preview_from_text_file(tmp_path):
    svc = ConversationService(DummyDB())
    file_path = tmp_path / "notes.txt"
    file_path.write_text("Line one\nLine two\nLine three", encoding="utf-8")

    context = asyncio.run(
        svc._build_attachment_context(
            SimpleNamespace(
                id=uuid.uuid4(),
                attachment_filename="notes.txt",
                attachment_path=str(file_path),
                attachment_content_type="text/plain",
                content="Shared a file: notes.txt",
            )
        )
    )

    assert context["kind"] == "text"
    assert context["filename"] == "notes.txt"
    assert "Line one" in context["preview"]
