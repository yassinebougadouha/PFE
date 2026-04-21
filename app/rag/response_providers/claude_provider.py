"""
Anthropic Claude provider implementation.
Supports Claude 3.5 Sonnet, Claude 3 Haiku.
"""

from __future__ import annotations

import logging
from typing import AsyncIterator, Optional

import httpx

from app.core.config import get_settings
from app.rag.response_providers.base import BaseProvider
from app.rag.response_providers.enums import AIProvider

logger = logging.getLogger(__name__)

ANTHROPIC_MESSAGES_URL = "https://api.anthropic.com/v1/messages"
ANTHROPIC_API_VERSION = "2023-06-01"


class ClaudeProvider(BaseProvider):
    """Anthropic Claude provider using httpx (no SDK dependency)."""

    provider = AIProvider.CLAUDE
    default_model = "claude-sonnet-4-20250514"
    available_models = [
        "claude-sonnet-4-20250514",
        "claude-3-5-sonnet-20241022",
        "claude-3-haiku-20240307",
    ]

    def __init__(self) -> None:
        settings = get_settings()
        self.api_key: str = getattr(settings, "ANTHROPIC_API_KEY", "")
        self.default_model = getattr(settings, "ANTHROPIC_RESPONSE_MODEL", self.default_model) or self.default_model

    @property
    def _is_configured(self) -> bool:
        return bool(self.api_key)

    def _headers(self) -> dict:
        return {
            "x-api-key": self.api_key,
            "anthropic-version": ANTHROPIC_API_VERSION,
            "Content-Type": "application/json",
        }

    @staticmethod
    def _convert_messages(messages: list[dict]) -> tuple[str, list[dict]]:
        """
        Claude uses a separate `system` parameter instead of a system message.
        Split OpenAI-style messages into (system_prompt, user/assistant messages).
        """
        system_prompt = ""
        user_messages: list[dict] = []
        for msg in messages:
            if msg["role"] == "system":
                system_prompt += msg["content"] + "\n"
            else:
                user_messages.append({"role": msg["role"], "content": msg["content"]})
        return system_prompt.strip(), user_messages

    # ── generate ────────────────────────────────────────

    async def generate(
        self,
        messages: list[dict],
        model: Optional[str] = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
    ) -> dict:
        if not self._is_configured:
            raise RuntimeError("Anthropic API key is not configured (ANTHROPIC_API_KEY)")

        model = model or self.default_model
        system_prompt, user_messages = self._convert_messages(messages)

        payload: dict = {
            "model": model,
            "messages": user_messages,
            "max_tokens": max_tokens,
            "temperature": temperature,
        }
        if system_prompt:
            payload["system"] = system_prompt

        async with httpx.AsyncClient(timeout=60.0) as client:
            resp = await client.post(
                ANTHROPIC_MESSAGES_URL,
                headers=self._headers(),
                json=payload,
            )
            resp.raise_for_status()
            data = resp.json()

        content_blocks = data.get("content", [])
        text = "".join(
            block["text"] for block in content_blocks if block.get("type") == "text"
        )
        usage = data.get("usage", {})
        tokens_used = (usage.get("input_tokens", 0) or 0) + (usage.get("output_tokens", 0) or 0)

        return {
            "content": text,
            "model": data.get("model", model),
            "tokens_used": tokens_used or None,
        }

    # ── stream ──────────────────────────────────────────

    async def stream(
        self,
        messages: list[dict],
        model: Optional[str] = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
    ) -> AsyncIterator[str]:
        if not self._is_configured:
            raise RuntimeError("Anthropic API key is not configured (ANTHROPIC_API_KEY)")

        model = model or self.default_model
        system_prompt, user_messages = self._convert_messages(messages)

        payload: dict = {
            "model": model,
            "messages": user_messages,
            "max_tokens": max_tokens,
            "temperature": temperature,
            "stream": True,
        }
        if system_prompt:
            payload["system"] = system_prompt

        async with httpx.AsyncClient(timeout=120.0) as client:
            async with client.stream(
                "POST",
                ANTHROPIC_MESSAGES_URL,
                headers=self._headers(),
                json=payload,
            ) as resp:
                resp.raise_for_status()
                async for line in resp.aiter_lines():
                    if not line or not line.startswith("data: "):
                        continue
                    import json
                    try:
                        event = json.loads(line[len("data: "):])
                        if event.get("type") == "content_block_delta":
                            delta = event.get("delta", {})
                            text = delta.get("text", "")
                            if text:
                                yield text
                    except (json.JSONDecodeError, KeyError):
                        continue

    # ── health check ────────────────────────────────────

    async def health_check(self) -> bool:
        if not self._is_configured:
            return False
        try:
            # Claude doesn't have a /models endpoint, so we do a minimal request
            system_prompt, user_messages = self._convert_messages([
                {"role": "system", "content": "Reply with OK."},
                {"role": "user", "content": "ping"},
            ])
            payload: dict = {
                "model": "claude-3-haiku-20240307",
                "messages": user_messages,
                "max_tokens": 5,
            }
            if system_prompt:
                payload["system"] = system_prompt

            async with httpx.AsyncClient(timeout=10.0) as client:
                resp = await client.post(
                    ANTHROPIC_MESSAGES_URL,
                    headers=self._headers(),
                    json=payload,
                )
                return resp.status_code == 200
        except Exception:
            logger.warning("Claude health check failed", exc_info=True)
            return False
