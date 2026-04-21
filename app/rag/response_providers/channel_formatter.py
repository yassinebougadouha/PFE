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

def format_for_chat(
    text: str,
    customer_name: Optional[str] = None,
    agent_name: Optional[str] = None,
    **_kwargs,
) -> str:
    """Chat: keep concise, without greeting or signature blocks."""
    text = text.replace("\r\n", "\n").replace("\r", "\n").strip()
    lines = text.split("\n")

    while lines and not lines[0].strip():
        lines.pop(0)

    if lines:
        first_line = lines[0].strip()
        greeting_prefix = (
            r"^(?:hello|hi|hey|bonjour|bonsoir|salut)\b"
            r"(?:\s+(?:there|client|customer|team|friend|everyone|all|[A-Za-z][\w'-]*)){0,4}"
            r"[,!:.-]*\s*"
        )
        updated_first_line = re.sub(greeting_prefix, "", first_line, count=1, flags=re.IGNORECASE)
        if updated_first_line != first_line:
            if updated_first_line.strip():
                lines[0] = updated_first_line.strip()
            else:
                lines.pop(0)

    while lines and not lines[-1].strip():
        lines.pop()

    signoff_pattern = re.compile(
        r"^(?:best regards|kind regards|regards|sincerely|thanks|thank you|cheers|warm regards|"
        r"cordialement|bien cordialement)[\s,!:.-]*$",
        re.IGNORECASE,
    )

    if lines and _is_chat_signature_line(lines[-1], agent_name=agent_name):
        lines.pop()
        while lines and not lines[-1].strip():
            lines.pop()

    if lines and signoff_pattern.fullmatch(lines[-1].strip()):
        lines.pop()
        while lines and not lines[-1].strip():
            lines.pop()
        if lines and _is_chat_signature_line(lines[-1], agent_name=agent_name):
            lines.pop()
            while lines and not lines[-1].strip():
                lines.pop()

    text = "\n".join(lines)
    text = _strip_chat_leading_punctuation(text)
    # Trim excessive whitespace / blank lines
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def _strip_chat_leading_punctuation(text: str) -> str:
    cleaned = (text or "").lstrip()
    while cleaned:
        updated = re.sub(r"^[!.,:;\-\u2022]+\s*", "", cleaned, count=1)
        if updated == cleaned:
            break
        cleaned = updated.lstrip()
    return cleaned


def _is_chat_signature_line(line: str, agent_name: Optional[str] = None) -> bool:
    stripped = line.strip()
    if not stripped:
        return False

    if agent_name and re.fullmatch(re.escape(agent_name.strip()), stripped, flags=re.IGNORECASE):
        return True

    normalized = re.sub(r"[^a-z0-9]+", " ", stripped.lower()).strip()
    if not normalized:
        return False

    tokens = normalized.split()
    if len(tokens) > 4:
        return False

    generic_signature_tokens = {
        "support",
        "assistant",
        "team",
        "service",
        "helpdesk",
        "agent",
        "customer",
        "client",
    }
    if tokens and all(token in generic_signature_tokens for token in tokens):
        return True

    return normalized in {
        "support assistant",
        "assistant support",
        "support team",
        "team support",
        "customer support",
        "client support",
        "support agent",
        "assistant service",
    }


def format_for_email(
    text: str,
    customer_name: Optional[str] = None,
    agent_name: Optional[str] = None,
    language: Optional[str] = None,
    **_kwargs,
) -> str:
    """Email: ensure greeting + sign-off structure."""
    text = text.strip()
    lang = (language or "").lower().strip()

    if not lang:
        lowered = text.lower()
        if any(m in lowered for m in ("bonjour", "cordialement", "merci", "votre", "nous", "vous")):
            lang = "fr"
        else:
            lang = "en"

    # Add greeting if missing (supports EN/FR openings).
    greeting_patterns = re.compile(
        r"^(dear|hello|hi|hey|good morning|good afternoon|good evening|bonjour|bonsoir|salut)",
        re.IGNORECASE,
    )
    if customer_name:
        # Upgrade generic "Bonjour," to "Bonjour <name>," when possible.
        if lang == "fr":
            text = re.sub(
                r"^(bonjour)\s*,",
                f"\\1 {customer_name.strip()},",
                text,
                flags=re.IGNORECASE,
                count=1,
            )
        else:
            text = re.sub(
                r"^(hello)\s*,",
                f"\\1 {customer_name.strip()},",
                text,
                flags=re.IGNORECASE,
                count=1,
            )

    if not greeting_patterns.match(text):
        name = (customer_name or "").strip()
        if lang == "fr":
            greeting = f"Bonjour {name}," if name else "Bonjour,"
        else:
            greeting = f"Hello {name}," if name else "Hello,"
        text = f"{greeting}\n\n{text}"

    # Add sign-off if missing (supports EN/FR closings).
    signoff_patterns = re.compile(
        r"(best regards|kind regards|regards|sincerely|thank you|thanks|cheers|warm regards|cordialement|bien cordialement|merci|merci d'avance)\s*[,.]?\s*$",
        re.IGNORECASE | re.MULTILINE,
    )
    if not signoff_patterns.search(text):
        agent = agent_name or "Support Team"
        signoff = "Cordialement," if lang == "fr" else "Best regards,"
        text = f"{text}\n\n{signoff}\n{agent}"

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
    language: Optional[str] = None,
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
        language=language,
    )
