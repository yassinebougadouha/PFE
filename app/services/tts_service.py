"""
Text-to-Speech service — converts text responses to audio using edge-tts.
Uses Microsoft Edge's neural TTS voices (free, no API key needed).
"""

import logging
import uuid
from datetime import datetime
from pathlib import Path

import edge_tts

from app.core.config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()


def _ensure_uploads_dir() -> Path:
    """Create the uploads directory if it doesn't exist."""
    uploads = Path(settings.UPLOADS_DIR)
    uploads.mkdir(parents=True, exist_ok=True)
    return uploads


def estimate_audio_duration(text: str) -> int:
    """
    Estimate audio duration based on text length.
    Average speaking rate: ~150 words/min (~2.5 words/second).
    """
    word_count = len(text.split())
    return max(1, int(word_count / 2.5))


async def synthesize_speech(
    text: str,
    voice: str | None = None,
    rate: str | None = None,
    pitch: str | None = None,
) -> dict:
    """
    Convert text to an MP3 audio file using edge-tts.

    Args:
        text: The text content to synthesize.
        voice: TTS voice name (overrides config default).
        rate: Speech rate, e.g. "+10%" (overrides config default).
        pitch: Pitch adjustment, e.g. "-0Hz" (overrides config default).

    Returns:
        dict with keys: file_path, filename, duration_estimate
    """
    uploads = _ensure_uploads_dir()

    voice = voice or settings.TTS_VOICE
    rate = rate or settings.TTS_RATE
    pitch = pitch or settings.TTS_PITCH

    # Generate unique filename
    timestamp = datetime.utcnow().strftime("%Y%m%d_%H%M%S")
    unique_id = uuid.uuid4().hex[:8]
    filename = f"tts_{timestamp}_{unique_id}.mp3"
    file_path = uploads / filename

    logger.info(
        f"Synthesizing speech: {len(text)} chars, "
        f"voice={voice}, rate={rate}, file={filename}"
    )

    try:
        communicate = edge_tts.Communicate(
            text,
            voice,
            rate=rate,
            pitch=pitch,
        )
        await communicate.save(str(file_path))

        duration = estimate_audio_duration(text)
        logger.info(f"TTS complete: {filename} (~{duration}s)")

        return {
            "file_path": str(file_path),
            "filename": filename,
            "duration_estimate": duration,
        }

    except Exception:
        logger.exception("edge-tts synthesis failed")
        raise


async def list_available_voices(language: str = "en") -> list[dict]:
    """
    List available edge-tts voices, optionally filtered by language.

    Returns:
        List of dicts with keys: name, gender, language
    """
    voices = await edge_tts.list_voices()
    filtered = [
        {
            "name": v["ShortName"],
            "gender": v["Gender"],
            "language": v["Locale"],
        }
        for v in voices
        if v["Locale"].startswith(language)
    ]
    return filtered
