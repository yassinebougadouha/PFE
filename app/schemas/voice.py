"""
Voice (STT / TTS) schemas.
"""

import re
import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field, field_validator


# ── STT (Speech-to-Text) ────────────────────────────────

class TranscriptionSegment(BaseModel):
    start: float
    end: float
    text: str


class TranscriptionResult(BaseModel):
    """Response from the /voice/transcribe endpoint."""
    text: str
    language: str
    segments: list[TranscriptionSegment] = []


class VoiceMessageResult(BaseModel):
    """Response when a voice message is transcribed and processed as a ticket."""
    transcription: str
    language: str
    ticket_id: uuid.UUID
    email_id: uuid.UUID
    message: str = "Voice message processed successfully"


# ── TTS (Text-to-Speech) ────────────────────────────────

class TTSRequest(BaseModel):
    """Request body for text-to-speech synthesis."""
    text: str = Field(..., min_length=1, max_length=10000)
    voice: Optional[str] = Field(None, description="edge-tts voice name, e.g. 'fr-BE-CharlineNeural'")
    rate: Optional[str] = Field(None, description="Speech rate, e.g. '+10%' or '-5%'", examples=["+10%"])
    pitch: Optional[str] = Field(None, description="Pitch, e.g. '+0Hz' or '-2Hz'", examples=["+0Hz"])

    @field_validator("rate")
    @classmethod
    def validate_rate(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return v
        if not re.match(r"^[+-]\d+%$", v):
            return None  # fall back to config default
        return v

    @field_validator("pitch")
    @classmethod
    def validate_pitch(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return v
        if not re.match(r"^[+-]\d+Hz$", v):
            return None  # fall back to config default
        return v


class TTSResult(BaseModel):
    """Response from the /voice/synthesize endpoint."""
    audio_url: str
    filename: str
    duration_estimate: int
    text_length: int


class VoiceInfo(BaseModel):
    name: str
    gender: str
    language: str
