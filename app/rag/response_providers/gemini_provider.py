"""
Google Gemini provider implementation.
Supports Gemini 1.5 Pro, Gemini 1.5 Flash, Gemini 2.0 Flash.
"""

from __future__ import annotations

import logging
from typing import AsyncIterator, Optional

import httpx

from app.core.config import get_settings
from app.rag.response_providers.base import BaseProvider
from app.rag.response_providers.enums import AIProvider

logger = logging.getLogger(__name__)

GEMINI_BASE_URL = "https://generativelanguage.googleapis.com/v1beta/models"


class GeminiProvider(BaseProvider):
    """Google Gemini provider using the REST API (no SDK dependency)."""

    provider = AIProvider.GEMINI
    default_model = "gemini-2.5-flash-lite"
    available_models = [
        "gemini-2.5-flash",
        "gemini-2.5-pro",
        "gemini-2.5-flash-lite",
    ]

    def __init__(self) -> None:
        settings = get_settings()
        self.api_key: str = getattr(settings, "current_gemini_key", "")
        self.default_model = getattr(settings, "GEMINI_RESPONSE_MODEL", self.default_model) or self.default_model

    @property
    def _is_configured(self) -> bool:
        return bool(self.api_key)

    @staticmethod
    def _convert_messages(messages: list[dict]) -> tuple[str, list[dict]]:
        """
        Convert OpenAI-style messages to Gemini format.
        Returns (system_instruction, contents) where contents use 'user'/'model' roles.
        """
        system_instruction = ""
        contents: list[dict] = []
        for msg in messages:
            if msg["role"] == "system":
                system_instruction += msg["content"] + "\n"
            elif msg["role"] == "assistant":
                contents.append({"role": "model", "parts": [{"text": msg["content"]}]})
            else:
                contents.append({"role": "user", "parts": [{"text": msg["content"]}]})
        return system_instruction.strip(), contents

    # ── generate ────────────────────────────────────────

    async def generate(
        self,
        messages: list[dict],
        model: Optional[str] = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
    ) -> dict:
        if not self._is_configured:
            raise RuntimeError("Gemini API key is not configured (GEMINI_API_KEY)")

        model = model or self.default_model
        system_instruction, contents = self._convert_messages(messages)

        url = f"{GEMINI_BASE_URL}/{model}:generateContent?key={self.api_key}"
        payload: dict = {
            "contents": contents,
            "generationConfig": {
                "temperature": temperature,
                "maxOutputTokens": max_tokens,
            },
        }
        if system_instruction:
            payload["systemInstruction"] = {
                "parts": [{"text": system_instruction}]
            }

        async with httpx.AsyncClient(timeout=60.0) as client:
            resp = await client.post(url, json=payload)
            resp.raise_for_status()
            data = resp.json()

        candidates = data.get("candidates", [])
        if not candidates:
            raise RuntimeError(f"Gemini returned no candidates: {data}")

        parts = candidates[0].get("content", {}).get("parts", [])
        text = "".join(p.get("text", "") for p in parts)

        usage = data.get("usageMetadata", {})
        tokens_used = usage.get("totalTokenCount")

        return {
            "content": text,
            "model": model,
            "tokens_used": tokens_used,
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
            raise RuntimeError("Gemini API key is not configured (GEMINI_API_KEY)")

        model = model or self.default_model
        system_instruction, contents = self._convert_messages(messages)

        url = f"{GEMINI_BASE_URL}/{model}:streamGenerateContent?alt=sse&key={self.api_key}"
        payload: dict = {
            "contents": contents,
            "generationConfig": {
                "temperature": temperature,
                "maxOutputTokens": max_tokens,
            },
        }
        if system_instruction:
            payload["systemInstruction"] = {
                "parts": [{"text": system_instruction}]
            }

        async with httpx.AsyncClient(timeout=120.0) as client:
            async with client.stream("POST", url, json=payload) as resp:
                resp.raise_for_status()
                async for line in resp.aiter_lines():
                    if not line or not line.startswith("data: "):
                        continue
                    import json
                    try:
                        chunk = json.loads(line[len("data: "):])
                        candidates = chunk.get("candidates", [])
                        if candidates:
                            parts = candidates[0].get("content", {}).get("parts", [])
                            for p in parts:
                                text = p.get("text", "")
                                if text:
                                    yield text
                    except (json.JSONDecodeError, KeyError):
                        continue

    # ── health check ────────────────────────────────────

    async def health_check(self) -> bool:
        if not self._is_configured:
            return False
        try:
            url = f"{GEMINI_BASE_URL}?key={self.api_key}"
            async with httpx.AsyncClient(timeout=10.0) as client:
                resp = await client.get(url)
                return resp.status_code == 200
        except Exception:
            logger.warning("Gemini health check failed", exc_info=True)
            return False
