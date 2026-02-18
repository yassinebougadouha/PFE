"""
Speech-to-Text service — converts audio files to text using faster-whisper.
Runs the Whisper model locally via CTranslate2 (no external API calls).
"""

import logging
import os
import uuid
from pathlib import Path

from faster_whisper import WhisperModel

from app.core.config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()

# ── Lazy-loaded singleton model ──────────────────────────

_whisper_model = None


def get_whisper_model() -> WhisperModel:
    """Load the faster-whisper model once and cache it."""
    global _whisper_model
    if _whisper_model is None:
        logger.info(f"Loading faster-whisper model: {settings.WHISPER_MODEL}")
        _whisper_model = WhisperModel(
            settings.WHISPER_MODEL,
            device="cpu",
            compute_type="int8",
        )
        logger.info("faster-whisper model loaded successfully")
    return _whisper_model


def _ensure_uploads_dir() -> Path:
    """Create the uploads directory if it doesn't exist."""
    uploads = Path(settings.UPLOADS_DIR)
    uploads.mkdir(parents=True, exist_ok=True)
    return uploads


def transcribe_audio_file(file_path: str) -> dict:
    """
    Transcribe an audio file to text using faster-whisper.

    Args:
        file_path: Path to the audio file (wav, mp3, webm, etc.)

    Returns:
        dict with keys: text, language, segments
    """
    model = get_whisper_model()

    logger.info(f"Transcribing audio file: {file_path}")
    segments_gen, info = model.transcribe(file_path, beam_size=5)

    segments = []
    text_parts = []
    for seg in segments_gen:
        segments.append({
            "start": seg.start,
            "end": seg.end,
            "text": seg.text.strip(),
        })
        text_parts.append(seg.text.strip())

    transcript = " ".join(text_parts)
    language = info.language or "unknown"

    logger.info(
        f"Transcription complete: {len(transcript)} chars, "
        f"language={language}"
    )

    return {
        "text": transcript,
        "language": language,
        "segments": segments,
    }


async def save_upload_and_transcribe(file_content: bytes, filename: str) -> dict:
    """
    Save uploaded audio bytes to a temp file, transcribe, and clean up.

    Args:
        file_content: Raw audio bytes from the upload.
        filename: Original filename (used for extension detection).

    Returns:
        dict with keys: text, language, segments
    """
    uploads = _ensure_uploads_dir()

    # Generate unique temp filename
    ext = Path(filename).suffix or ".wav"
    temp_name = f"stt_{uuid.uuid4().hex[:12]}{ext}"
    temp_path = uploads / temp_name

    try:
        # Write to disk (Whisper needs a file path)
        temp_path.write_bytes(file_content)
        logger.debug(f"Saved temp audio: {temp_path} ({len(file_content)} bytes)")

        # Transcribe
        result = transcribe_audio_file(str(temp_path))
        return result

    finally:
        # Always clean up the input file
        if temp_path.exists():
            temp_path.unlink()
            logger.debug(f"Cleaned up temp audio: {temp_path}")


def cleanup_file(file_path: str) -> None:
    """Delete a file if it exists (used for deferred cleanup)."""
    try:
        if os.path.exists(file_path):
            os.remove(file_path)
            logger.debug(f"Deleted file: {file_path}")
    except Exception:
        logger.exception(f"Failed to delete file: {file_path}")
