"""
Abstract base class for LLM providers.
All provider implementations (OpenAI, Claude, Gemini) inherit from this.
"""

from __future__ import annotations

import abc
import logging
import time
from typing import AsyncIterator, Optional

from app.rag.response_providers.enums import AIProvider, ResponseChannel, ResponseTone

logger = logging.getLogger(__name__)


# ═══════════════════════════════════════════════════════════
#  System prompt templates by channel & tone
# ═══════════════════════════════════════════════════════════

CHANNEL_INSTRUCTIONS: dict[ResponseChannel, str] = {
    ResponseChannel.CHAT: (
        "You are a live-chat support agent. Keep answers concise (2-4 paragraphs max). "
        "Write like a direct live-chat reply, not an email. "
        "Use short sentences and only use bullet points when they add clarity. "
        "Be conversational but professional. "
        "Do not start with hello/hi/bonjour or address the customer by name. "
        "Do not end with a sign-off, closing formula, signature, team label, or agent name."
    ),
    ResponseChannel.EMAIL: (
        "You are composing a professional support email. "
        "Begin with an appropriate greeting (use the customer's name if provided). "
        "Structure the body with clear paragraphs. "
        "End with a polite sign-off and the agent's name if provided. "
        "Use full sentences — no bullet-heavy formatting."
    ),
    ResponseChannel.WHATSAPP: (
        "You are replying on WhatsApp. Keep messages SHORT — mobile-friendly. "
        "Use 1-3 short paragraphs max. Emoji are acceptable where natural. "
        "Avoid markdown formatting (bold/italic/links). Use plain text only. "
        "If lists are needed, use simple line breaks with dashes."
    ),
    ResponseChannel.VOICE: (
        "Your response will be read aloud by a text-to-speech engine. "
        "Write in natural spoken language — no markdown, no bullet points, "
        "no special characters, no URLs. Use short, clear sentences. "
        "Avoid abbreviations. Spell out numbers when under 10."
    ),
    ResponseChannel.TICKET: (
        "You are composing a detailed support ticket response. "
        "Be thorough and reference-rich. Include step-by-step instructions "
        "where applicable. Use markdown formatting for structure. "
        "Include source article references at the end if available."
    ),
}

TONE_INSTRUCTIONS: dict[ResponseTone, str] = {
    ResponseTone.PROFESSIONAL: "Maintain a professional, neutral tone throughout.",
    ResponseTone.FRIENDLY: "Be warm and approachable while staying helpful.",
    ResponseTone.EMPATHETIC: (
        "Show empathy and understanding for the customer's situation. "
        "Acknowledge their feelings before providing solutions."
    ),
    ResponseTone.CONCISE: "Be extremely brief and to-the-point. Minimize filler words.",
    ResponseTone.TECHNICAL: (
        "Use precise technical language. Include relevant technical details, "
        "error codes, or configuration specifics where applicable."
    ),
}


# ═══════════════════════════════════════════════════════════
#  Abstract base
# ═══════════════════════════════════════════════════════════

class BaseProvider(abc.ABC):
    """Abstract interface every LLM provider must implement."""

    provider: AIProvider  # set by each subclass
    default_model: str
    available_models: list[str]

    # ── prompt building ─────────────────────────────────

    def build_system_prompt(
        self,
        channel: ResponseChannel,
        tone: ResponseTone,
        rag_context: list[dict],
        language: Optional[str] = None,
        customer_name: Optional[str] = None,
        agent_name: Optional[str] = None,
    ) -> str:
        """
        Assemble the system prompt from channel rules, tone, and RAG context.
        """
        parts: list[str] = [
            "You are an AI-powered customer support assistant.",
            "",
            "## Channel instructions",
            CHANNEL_INSTRUCTIONS.get(channel, CHANNEL_INSTRUCTIONS[ResponseChannel.CHAT]),
            "",
            "## Tone",
            TONE_INSTRUCTIONS.get(tone, TONE_INSTRUCTIONS[ResponseTone.PROFESSIONAL]),
        ]

        if language:
            lang = language.strip().lower()
            if lang in {"fr", "french"}:
                lang_rule = (
                    "Respond strictly in French only. Do not include English sentences, "
                    "English greetings, or English sign-offs."
                )
            elif lang in {"en", "english"}:
                lang_rule = (
                    "Respond strictly in English only. Do not include French sentences, "
                    "French greetings, or French sign-offs."
                )
            elif lang in {"ar", "arabic"}:
                lang_rule = (
                    "Respond strictly in Arabic only. Do not include English or French sentences."
                )
            else:
                lang_rule = f"Respond strictly in {language}."

            parts += ["", "## Language", lang_rule]

        include_identity_hints = channel != ResponseChannel.CHAT

        if include_identity_hints and customer_name:
            parts += ["", f"## Customer name: {customer_name}"]
        if include_identity_hints and agent_name:
            parts += [f"## Agent name (for sign-off): {agent_name}"]

        # RAG context — inject knowledge base chunks
        if rag_context:
            parts += ["", "## Knowledge base context (use to answer the question)"]
            for i, ctx in enumerate(rag_context, 1):
                title = ctx.get("article_title", "Untitled")
                content = ctx.get("chunk_content", "")
                parts.append(f"\n### Source {i}: {title}\n{content}")
            parts.append(
                "\nBase your answer on the knowledge base context above. "
                "If the context doesn't contain enough information, say so clearly."
            )
        else:
            if channel == ResponseChannel.CHAT:
                parts.append(
                    "\nNo knowledge base context was found. Answer naturally from the "
                    "conversation and any attachment-analysis context that was provided. "
                    "Do not mention missing documentation unless the customer explicitly "
                    "asks for sources or references."
                )
            else:
                parts.append(
                    "\nNo knowledge base context was found. Answer based on general knowledge "
                    "and clearly indicate that no specific documentation was found."
                )

        return "\n".join(parts)

    def _build_messages(
        self,
        system_prompt: str,
        query: str,
        conversation_history: list[dict],
    ) -> list[dict]:
        """Build the messages list for the LLM (OpenAI / Gemini compatible format)."""
        messages = [{"role": "system", "content": system_prompt}]
        for msg in conversation_history[-10:]:  # keep last 10 turns
            role = msg.get("role", "user")
            content = msg.get("content", "")
            if role in ("user", "assistant") and content:
                messages.append({"role": role, "content": content})
        messages.append({"role": "user", "content": query})
        return messages

    # ── abstract methods ────────────────────────────────

    @abc.abstractmethod
    async def generate(
        self,
        messages: list[dict],
        model: Optional[str] = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
    ) -> dict:
        """
        Send messages to the LLM and get a response.

        Returns:
            dict with keys: content (str), model (str), tokens_used (int|None)
        """
        ...

    @abc.abstractmethod
    async def stream(
        self,
        messages: list[dict],
        model: Optional[str] = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
    ) -> AsyncIterator[str]:
        """Stream response tokens as they arrive."""
        ...

    @abc.abstractmethod
    async def health_check(self) -> bool:
        """Return True if the provider API is reachable and the key is valid."""
        ...

    # ── convenience wrapper ─────────────────────────────

    async def generate_response(
        self,
        query: str,
        channel: ResponseChannel,
        tone: ResponseTone,
        rag_context: list[dict],
        conversation_history: list[dict] | None = None,
        model: str | None = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
        language: str | None = None,
        customer_name: str | None = None,
        agent_name: str | None = None,
    ) -> dict:
        """
        High-level: build prompt → call LLM → return result dict.
        """
        system_prompt = self.build_system_prompt(
            channel=channel,
            tone=tone,
            rag_context=rag_context,
            language=language,
            customer_name=customer_name,
            agent_name=agent_name,
        )
        messages = self._build_messages(
            system_prompt=system_prompt,
            query=query,
            conversation_history=conversation_history or [],
        )

        start = time.time()
        result = await self.generate(
            messages=messages,
            model=model,
            temperature=temperature,
            max_tokens=max_tokens,
        )
        latency_ms = int((time.time() - start) * 1000)

        return {
            "content": result["content"],
            "model": result["model"],
            "tokens_used": result.get("tokens_used"),
            "latency_ms": latency_ms,
            "provider": self.provider,
        }
