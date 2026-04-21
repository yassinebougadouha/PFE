"""
Schemas for admin voice-agent process control and configuration.
"""

from __future__ import annotations

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
    gemini_api_key: str = ""

    gemini_model: str = "gemini-2.5-flash-lite"
    openai_model: str = "gpt-4o-mini"

    backend_api_url: str = "http://localhost:8000"
    internal_service_key: str = "change-me-internal-key"

    voice_recordings_dir: str = "recordings"
    database_url: str = ""


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
