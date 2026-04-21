from app.rag.response_providers.channel_formatter import format_for_chat
from app.rag.response_providers.base import BaseProvider
from app.rag.response_providers.enums import ResponseChannel, ResponseTone


class DummyProvider(BaseProvider):
    async def generate(self, messages, model=None, temperature=0.3, max_tokens=1024):
        return {"content": "ok", "model": "dummy"}

    async def stream(self, messages, model=None, temperature=0.3, max_tokens=1024):
        if False:
            yield ""

    async def health_check(self) -> bool:
        return True


def test_chat_formatter_removes_greeting_and_signature():
    raw = "Hello client,\n\nHere is the answer you need.\n\nBest regards,\nSupport Assistant"

    formatted = format_for_chat(
        raw,
        customer_name="Jane Client",
        agent_name="Support Assistant",
    )

    assert formatted == "Here is the answer you need."


def test_chat_prompt_does_not_include_customer_name_or_agent_signoff_hint():
    provider = DummyProvider()

    prompt = provider.build_system_prompt(
        channel=ResponseChannel.CHAT,
        tone=ResponseTone.PROFESSIONAL,
        rag_context=[],
        customer_name="Jane Client",
        agent_name="Support Assistant",
    )

    assert "Jane Client" not in prompt
    assert "Support Assistant" not in prompt
    assert "Do not start with hello" in prompt
    assert "not an email" in prompt
    assert "Do not mention missing documentation" in prompt


def test_chat_formatter_removes_generic_support_signature_without_agent_name():
    raw = "Comment puis-je vous aider aujourd'hui ?\n\nAssistant Support"

    formatted = format_for_chat(raw)

    assert formatted == "Comment puis-je vous aider aujourd'hui ?"


def test_chat_formatter_strips_leading_exclamation_prefix():
    raw = "! Here is the answer you need."

    formatted = format_for_chat(raw)

    assert formatted == "Here is the answer you need."
