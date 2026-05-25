"""
Voice Agent configuration — loads settings from .env / environment variables.

These settings drive the LiveKit agent server, LLM selection
(multi-provider: Gemini / OpenAI / Claude), RAG integration, and API keys.
"""

from __future__ import annotations

import os
from dataclasses import dataclass, field
from functools import lru_cache

from dotenv import load_dotenv


@dataclass(frozen=True)
class VoiceAgentConfig:
    """Immutable settings for the voice-agent subsystem."""

    # ── LiveKit ──────────────────────────────────────────
    livekit_api_key: str = ""
    livekit_api_secret: str = ""
    livekit_url: str = "ws://localhost:7880"

    # ── LLM provider keys ────────────────────────────────
    google_api_key: str = ""
    openai_api_key: str = ""
    anthropic_api_key: str = ""
    gemini_api_key: str = ""

    # ── Provider selection ───────────────────────────────
    # "gemini" → Google Gemini (livekit-plugins-google)
    # "openai" → OpenAI GPT   (livekit-plugins-openai)
    ai_provider: str = "gemini"

    # ── Mode toggle ──────────────────────────────────────
    # "true"  → Gemini Realtime API (single model, low latency)
    # "false" → Pipeline STT → LLM → TTS (reliable on free tier)
    use_realtime: bool = False

    # ── LLM defaults ────────────────────────────────────
    gemini_model: str = "gemini-2.5-flash-lite"
    openai_model: str = "gpt-4o-mini"

    # ── Backend API (for RAG knowledge base) ─────────────
    backend_api_url: str = "http://localhost:8600"
    internal_service_key: str = "change-me-internal-key"

    # ── Recording & Transcript ───────────────────────────
    voice_recordings_dir: str = "recordings"
    database_url: str = ""

    @property
    def current_gemini_key(self) -> str:
        import random
        keys = [k.strip() for k in self.gemini_api_key.split(",") if k.strip()]
        return random.choice(keys) if keys else ""

    @property
    def current_google_key(self) -> str:
        import random
        keys = [k.strip() for k in self.google_api_key.split(",") if k.strip()]
        return random.choice(keys) if keys else ""


def _bool(val: str | None) -> bool:
    return (val or "").strip().lower() in ("true", "1", "yes")


@lru_cache(maxsize=1)
def get_voice_settings() -> VoiceAgentConfig:
    """
    Load settings from environment / .env file.
    Cached — only parsed once per process.
    """
    # Try loading from both .env files (project root + voice-agents-specific)
    load_dotenv(".env", override=False)
    load_dotenv(".env.local", override=True)

    # (Removed os.environ manipulation since we pass api_key explicitly now to handle rotating keys)

    return VoiceAgentConfig(
        livekit_api_key=os.getenv("LIVEKIT_API_KEY", "devkey"),
        livekit_api_secret=os.getenv("LIVEKIT_API_SECRET", "secret"),
        livekit_url=os.getenv("LIVEKIT_URL", "ws://localhost:7880"),
        google_api_key=os.getenv("GOOGLE_API_KEY", "") or os.getenv("GEMINI_API_KEY", ""),
        openai_api_key=os.getenv("OPENAI_API_KEY", ""),
        anthropic_api_key=os.getenv("ANTHROPIC_API_KEY", ""),
        gemini_api_key=os.getenv("GEMINI_API_KEY", ""),
        ai_provider=os.getenv("AI_RESPONSE_PROVIDER", "gemini"),
        use_realtime=_bool(os.getenv("USE_REALTIME", "false")),
        gemini_model=os.getenv("GEMINI_MODEL", "gemini-2.5-flash-lite"),
        openai_model=os.getenv("OPENAI_MODEL", "gpt-4o-mini"),
        backend_api_url=os.getenv("BACKEND_API_URL", "http://localhost:8600"),
        internal_service_key=os.getenv("INTERNAL_SERVICE_KEY", "change-me-internal-key"),
        voice_recordings_dir=os.getenv("VOICE_RECORDINGS_DIR", "recordings"),
        database_url=os.getenv("DATABASE_URL", ""),
    )
