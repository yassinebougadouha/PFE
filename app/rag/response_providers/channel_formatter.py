"""
Channel-specific post-processing for AI responses.
Formats the raw LLM output for the target channel (chat, email, WhatsApp, voice, ticket).
"""

from __future__ import annotations

import re
from typing import Optional

from app.rag.response_providers.enums import ResponseChannel


# ═══════════════════════════════════════════════════════════
#  Per-channel formatters
# ═══════════════════════════════════════════════════════════

def format_for_chat(text: str, **_kwargs) -> str:
    """Chat: keep concise, light markdown OK."""
    # Trim excessive whitespace / blank lines
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def format_for_email(
    text: str,
    customer_name: Optional[str] = None,
    agent_name: Optional[str] = None,
    **_kwargs,
) -> str:
    """Email: ensure greeting + sign-off structure."""
    text = text.strip()

    # Add greeting if missing
    greeting_patterns = re.compile(
        r"^(dear|hello|hi|hey|good morning|good afternoon|good evening)",
        re.IGNORECASE,
    )
    if not greeting_patterns.match(text):
        name = customer_name or "there"
        text = f"Hello {name},\n\n{text}"

    # Add sign-off if missing
    signoff_patterns = re.compile(
        r"(best regards|kind regards|regards|sincerely|thank you|thanks|cheers|warm regards)\s*[,.]?\s*$",
        re.IGNORECASE | re.MULTILINE,
    )
    if not signoff_patterns.search(text):
        agent = agent_name or "Support Team"
        text = f"{text}\n\nBest regards,\n{agent}"

    return text


def format_for_whatsapp(text: str, **_kwargs) -> str:
    """WhatsApp: strip markdown, shorten, mobile-friendly."""
    # Remove markdown headers
    text = re.sub(r"^#{1,6}\s+", "", text, flags=re.MULTILINE)
    # Convert bold **text** → text (WhatsApp has its own bold with *)
    text = re.sub(r"\*\*(.*?)\*\*", r"*\1*", text)
    # Remove image/link markdown (keep link text)
    text = re.sub(r"!\[.*?\]\(.*?\)", "", text)
    text = re.sub(r"\[([^\]]+)\]\(([^\)]+)\)", r"\1 (\2)", text)
    # Collapse blank lines
    text = re.sub(r"\n{3,}", "\n\n", text)
    # Convert bullet lists from "- " to "• "
    text = re.sub(r"^[-*]\s+", "• ", text, flags=re.MULTILINE)

    return text.strip()


def format_for_voice(text: str, **_kwargs) -> str:
    """Voice / TTS: clean spoken-word format, no visual elements."""
    # Strip all markdown
    text = re.sub(r"^#{1,6}\s+", "", text, flags=re.MULTILINE)
    text = re.sub(r"\*\*(.*?)\*\*", r"\1", text)
    text = re.sub(r"\*(.*?)\*", r"\1", text)
    text = re.sub(r"`{1,3}.*?`{1,3}", "", text, flags=re.DOTALL)
    # Remove URLs
    text = re.sub(r"\[([^\]]+)\]\([^\)]+\)", r"\1", text)
    text = re.sub(r"https?://\S+", "", text)
    # Remove bullet markers
    text = re.sub(r"^[-*•]\s+", "", text, flags=re.MULTILINE)
    # Remove numbered list markers
    text = re.sub(r"^\d+\.\s+", "", text, flags=re.MULTILINE)
    # Collapse whitespace
    text = re.sub(r"\n{2,}", ". ", text)
    text = re.sub(r"\n", " ", text)
    text = re.sub(r"\s{2,}", " ", text)
    # Spell out common abbreviations for TTS
    abbreviations = {
        "e.g.": "for example",
        "i.e.": "that is",
        "etc.": "and so on",
        "vs.": "versus",
        "approx.": "approximately",
    }
    for abbr, full in abbreviations.items():
        text = text.replace(abbr, full)

    return text.strip()


def format_for_ticket(
    text: str,
    sources: list[dict] | None = None,
    **_kwargs,
) -> str:
    """Ticket: detailed with source references."""
    text = text.strip()

    # Append source references if provided and not already in the text
    if sources and "source" not in text.lower()[-200:]:
        text += "\n\n---\n**References:**\n"
        for i, src in enumerate(sources, 1):
            title = src.get("article_title", "Untitled")
            similarity = src.get("similarity", 0)
            text += f"{i}. {title} (relevance: {similarity:.0%})\n"

    return text


# ═══════════════════════════════════════════════════════════
#  Dispatcher
# ═══════════════════════════════════════════════════════════

_FORMATTERS = {
    ResponseChannel.CHAT: format_for_chat,
    ResponseChannel.EMAIL: format_for_email,
    ResponseChannel.WHATSAPP: format_for_whatsapp,
    ResponseChannel.VOICE: format_for_voice,
    ResponseChannel.TICKET: format_for_ticket,
}


def format_response(
    text: str,
    channel: ResponseChannel,
    customer_name: Optional[str] = None,
    agent_name: Optional[str] = None,
    sources: list[dict] | None = None,
) -> str:
    """
    Apply channel-specific formatting to a raw LLM response.
    Falls back to chat formatting if the channel is unknown.
    """
    formatter = _FORMATTERS.get(channel, format_for_chat)
    return formatter(
        text,
        customer_name=customer_name,
        agent_name=agent_name,
        sources=sources,
    )
