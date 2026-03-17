"""
Local LLM provider implementation.
Supports any OpenAI-compatible local server:
  - Ollama          (http://localhost:11434/v1)
  - llama.cpp       (http://localhost:8080/v1)
  - vLLM            (http://localhost:8000/v1)
  - LM Studio       (http://localhost:1234/v1)
  - LocalAI         (http://localhost:8080/v1)
  - text-generation-webui (http://localhost:5000/v1)

All these tools expose an OpenAI-compatible /v1/chat/completions endpoint
so a single provider implementation covers them all.
"""

from __future__ import annotations

import json
import logging
from typing import AsyncIterator, Optional

import httpx

from app.core.config import get_settings
from app.rag.response_providers.base import BaseProvider
from app.rag.response_providers.enums import AIProvider

logger = logging.getLogger(__name__)


class LocalProvider(BaseProvider):
    """
    Local / self-hosted LLM provider using the OpenAI-compatible API.

    Works with Ollama, llama.cpp, vLLM, LM Studio, LocalAI, and any
    server that exposes a /v1/chat/completions endpoint.

    Configuration (via .env):
        LOCAL_LLM_BASE_URL   — Base URL of the local server
                               (default: http://localhost:11434/v1)
        LOCAL_LLM_MODEL      — Model name to use (default: llama3.2)
        LOCAL_LLM_API_KEY    — Optional API key (some servers require one,
                               Ollama does not)
        LOCAL_LLM_MODELS     — Comma-separated list of available model names
                               (default: llama3.2,mistral,gemma2,phi3,qwen2.5)
    """

    provider = AIProvider.LOCAL
    default_model = "llama3.2"
    available_models = ["llama3.2", "mistral", "gemma2", "phi3", "qwen2.5"]

    def __init__(self) -> None:
        settings = get_settings()
        self.base_url: str = getattr(settings, "LOCAL_LLM_BASE_URL", "http://localhost:11434/v1")
        self.api_key: str = getattr(settings, "LOCAL_LLM_API_KEY", "")
        self.default_model: str = getattr(settings, "LOCAL_LLM_MODEL", "llama3.2")

        # Parse available models from comma-separated config
        raw_models: str = getattr(settings, "LOCAL_LLM_MODELS", "")
        if raw_models.strip():
            self.available_models = [m.strip() for m in raw_models.split(",") if m.strip()]
        else:
            self.available_models = [self.default_model, "mistral", "gemma2", "phi3", "qwen2.5"]

        # Ensure default model is in available list
        if self.default_model not in self.available_models:
            self.available_models.insert(0, self.default_model)

        # Normalise base URL (strip trailing slash)
        self.base_url = self.base_url.rstrip("/")

    @property
    def _is_configured(self) -> bool:
        return bool(self.base_url)

    @property
    def _chat_url(self) -> str:
        return f"{self.base_url}/chat/completions"

    def _headers(self) -> dict:
        headers: dict[str, str] = {"Content-Type": "application/json"}
        if self.api_key:
            headers["Authorization"] = f"Bearer {self.api_key}"
        return headers

    # ── generate ────────────────────────────────────────

    async def generate(
        self,
        messages: list[dict],
        model: Optional[str] = None,
        temperature: float = 0.3,
        max_tokens: int = 1024,
    ) -> dict:
        if not self._is_configured:
            raise RuntimeError(
                "Local LLM base URL is not configured (LOCAL_LLM_BASE_URL)"
            )

        model = model or self.default_model
        payload = {
            "model": model,
            "messages": messages,
            "temperature": temperature,
            "max_tokens": max_tokens,
            "stream": False,
        }

        async with httpx.AsyncClient(timeout=180.0) as client:
            resp = await client.post(
                self._chat_url,
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
            raise RuntimeError(
                "Local LLM base URL is not configured (LOCAL_LLM_BASE_URL)"
            )

        model = model or self.default_model
        payload = {
            "model": model,
            "messages": messages,
            "temperature": temperature,
            "max_tokens": max_tokens,
            "stream": True,
        }

        async with httpx.AsyncClient(timeout=300.0) as client:
            async with client.stream(
                "POST",
                self._chat_url,
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
        """
        Check if the local LLM server is reachable.
        Tries /v1/models first (standard OpenAI-compat), then falls back
        to /api/tags (Ollama-specific) and finally a simple GET on the base URL.
        """
        if not self._is_configured:
            return False
        try:
            async with httpx.AsyncClient(timeout=10.0) as client:
                # Try OpenAI-compat /v1/models
                resp = await client.get(
                    f"{self.base_url}/models",
                    headers=self._headers(),
                )
                if resp.status_code == 200:
                    return True

                # Fallback: Ollama /api/tags (base_url may end with /v1)
                base = self.base_url.rstrip("/v1").rstrip("/")
                resp = await client.get(f"{base}/api/tags")
                if resp.status_code == 200:
                    return True

                return False
        except Exception:
            logger.warning("Local LLM health check failed", exc_info=True)
            return False
