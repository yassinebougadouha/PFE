"""
Schemas for admin voice-agent process control and configuration.
"""

from __future__ import annotations

import random
from datetime import datetime
from typing import Literal

from pydantic import BaseModel, Field


class VoiceAgentConfig(BaseModel):
    livekit_api_key: str = ""
    livekit_api_secret: str = ""
    livekit_url: str = "ws://localhost:7880"

    ai_response_provider: str = "gemini"
    use_realtime: bool = False

    google_api_key: str = ""
    openai_api_key: str = ""
    anthropic_api_key: str = ""
    gemini_api_key: str = ""  # Comma-separated keys for rotation: "key1,key2,key3"

    gemini_model: str = "gemini-2.5-flash-lite"
    openai_model: str = "gpt-4o-mini"

    backend_api_url: str = "http://localhost:8600"
    internal_service_key: str = "change-me-internal-key"

    voice_recordings_dir: str = "recordings"
    database_url: str = ""

    # ── Key rotation helpers ──────────────────────────────────────────────────
    # These mirror the same properties on VoiceAgentSettings so that
    # VoiceAgentProcessManagerProxy.start() can call cfg.current_gemini_key
    # directly on the Pydantic model without hitting an AttributeError.

    @property
    def current_gemini_key(self) -> str:
        """Pick a random key from the comma-separated gemini_api_key list."""
        keys = [k.strip() for k in self.gemini_api_key.split(",") if k.strip()]
        return random.choice(keys) if keys else ""

    @property
    def current_google_key(self) -> str:
        """Pick a random key from the comma-separated google_api_key list."""
        keys = [k.strip() for k in self.google_api_key.split(",") if k.strip()]
        return random.choice(keys) if keys else ""


class VoiceAgentConfigResponse(BaseModel):
    config: VoiceAgentConfig


class VoiceAgentStartRequest(BaseModel):
    mode: Literal["dev", "start"] = "start"


class VoiceAgentActionResponse(BaseModel):
    message: str


class VoiceAgentStatusResponse(BaseModel):
    running: bool
    pid: int | None = None
    mode: Literal["dev", "start"] | None = None
    started_at: datetime | None = None
    uptime_seconds: float | None = None
    log_file: str | None = None
    last_exit_code: int | None = None


class VoiceAgentLogsResponse(BaseModel):
    lines: list[str] = Field(default_factory=list)


class VoiceEscalationRequest(BaseModel):
    room_name: str = Field(..., min_length=1)
    reason: str = Field(..., min_length=1)
    transcript: str | None = None
    audio_file_path: str | None = None


class VoiceEscalationResponse(BaseModel):
    room_name: str
    ticket_id: str
    ticket_subject: str
    status: str
    escalation_flag: bool