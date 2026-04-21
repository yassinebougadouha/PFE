"""
OpenAI / ChatGPT provider implementation.
Supports GPT-4o, GPT-4o-mini, GPT-3.5-turbo.
"""

from __future__ import annotations

import logging
from typing import AsyncIterator, Optional

import httpx

from app.core.config import get_settings
from app.rag.response_providers.base import BaseProvider
from app.rag.response_providers.enums import AIProvider

logger = logging.getLogger(__name__)

OPENAI_CHAT_URL = "https://api.openai.com/v1/chat/completions"


class OpenAIProvider(BaseProvider):
    """OpenAI ChatGPT provider using httpx (no SDK dependency)."""

    provider = AIProvider.OPENAI
    default_model = "gpt-4o-mini"
    available_models = ["gpt-4o", "gpt-4o-mini", "gpt-3.5-turbo"]

    def __init__(self) -> None:
        settings = get_settings()
        self.api_key: str = getattr(settings, "OPENAI_API_KEY", "")
        self.default_model = getattr(settings, "OPENAI_RESPONSE_MODEL", self.default_model) or self.default_model

    @property
    def _is_configured(self) -> bool:
        return bool(self.api_key)

    def _headers(self) -> dict:
        return {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json",
        }

    # ── generate ────────────────────────────────────────

    async def generate(
        self,
        messages: list[dict],
        model: Optional[str] = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
    ) -> dict:
        if not self._is_configured:
            raise RuntimeError("OpenAI API key is not configured (OPENAI_API_KEY)")

        model = model or self.default_model
        payload = {
            "model": model,
            "messages": messages,
            "temperature": temperature,
            "max_tokens": max_tokens,
        }

        async with httpx.AsyncClient(timeout=60.0) as client:
            resp = await client.post(
                OPENAI_CHAT_URL,
                headers=self._headers(),
                json=payload,
            )
            resp.raise_for_status()
            data = resp.json()

        choice = data["choices"][0]
        usage = data.get("usage", {})

        return {
            "content": choice["message"]["content"],
            "model": data.get("model", model),
            "tokens_used": usage.get("total_tokens"),
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
            raise RuntimeError("OpenAI API key is not configured (OPENAI_API_KEY)")

        model = model or self.default_model
        payload = {
            "model": model,
            "messages": messages,
            "temperature": temperature,
            "max_tokens": max_tokens,
            "stream": True,
        }

        async with httpx.AsyncClient(timeout=120.0) as client:
            async with client.stream(
                "POST",
                OPENAI_CHAT_URL,
                headers=self._headers(),
                json=payload,
            ) as resp:
                resp.raise_for_status()
                async for line in resp.aiter_lines():
                    if not line or not line.startswith("data: "):
                        continue
                    data_str = line[len("data: "):]
                    if data_str.strip() == "[DONE]":
                        break
                    import json
                    try:
                        chunk = json.loads(data_str)
                        delta = chunk["choices"][0].get("delta", {})
                        content = delta.get("content", "")
                        if content:
                            yield content
                    except (json.JSONDecodeError, KeyError, IndexError):
                        continue

    # ── health check ────────────────────────────────────

    async def health_check(self) -> bool:
        if not self._is_configured:
            return False
        try:
            async with httpx.AsyncClient(timeout=10.0) as client:
                resp = await client.get(
                    "https://api.openai.com/v1/models",
                    headers=self._headers(),
                )
                return resp.status_code == 200
        except Exception:
            logger.warning("OpenAI health check failed", exc_info=True)
            return False
