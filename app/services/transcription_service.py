"""
Speech-to-Text service powered by the Gemini API.

The public helpers keep the previous shape so the voice routes can continue
returning `{text, language, segments}` without a wider refactor.
"""

from __future__ import annotations

import asyncio
import base64
import json
import logging
import mimetypes
import os
from pathlib import Path
from typing import Any

import httpx

from app.core.config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()

GEMINI_API_BASE_URL = "https://generativelanguage.googleapis.com"
GEMINI_GENERATE_URL = f"{GEMINI_API_BASE_URL}/v1beta/models"
GEMINI_FILES_URL = f"{GEMINI_API_BASE_URL}/upload/v1beta/files"
DEFAULT_TRANSCRIPTION_LANGUAGE = "unknown"
MIME_FALLBACKS = {
    ".m4a": "audio/mp4",
    ".mp3": "audio/mpeg",
    ".oga": "audio/ogg",
    ".ogg": "audio/ogg",
    ".wav": "audio/wav",
    ".webm": "audio/webm",
}
TRANSCRIPTION_PROMPT = """You are transcribing a customer support voice message.
Return valid JSON only with this exact shape:
{
  "text": "full transcript as a single string",
  "language": "detected language",
  "segments": [
    {"start": 0.0, "end": 0.0, "text": "segment transcript"}
  ]
}

Requirements:
- Transcribe the spoken words accurately.
- Preserve the speaker's meaning without adding commentary.
- If there is no speech, return {"text":"","language":"unknown","segments":[]}.
- If reliable timestamps are not available, return an empty segments array.
- Do not wrap the JSON in markdown fences."""


def _gemini_headers() -> dict[str, str]:
    if not settings.current_gemini_key:
        raise RuntimeError("Gemini API key is not configured (GEMINI_API_KEY)")
    return {"x-goog-api-key": settings.current_gemini_key}


def _guess_mime_type(filename: str, content_type: str | None = None) -> str:
    normalized = (content_type or "").split(";", 1)[0].strip().lower()
    if normalized:
        return normalized

    suffix = Path(filename).suffix.lower()
    if suffix in MIME_FALLBACKS:
        return MIME_FALLBACKS[suffix]

    guessed, _ = mimetypes.guess_type(filename)
    return (guessed or "audio/wav").lower()


def _extract_text_from_gemini_response(data: dict[str, Any]) -> str:
    candidates = data.get("candidates") or []
    if not candidates:
        raise RuntimeError(f"Gemini returned no candidates: {data}")

    parts = candidates[0].get("content", {}).get("parts", [])
    raw_text = "".join(part.get("text", "") for part in parts).strip()
    if not raw_text:
        raise RuntimeError(f"Gemini returned an empty transcription payload: {data}")
    return raw_text


def _strip_code_fences(value: str) -> str:
    stripped = value.strip()
    if stripped.startswith("```"):
        stripped = stripped.split("\n", 1)[-1]
    if stripped.endswith("```"):
        stripped = stripped.rsplit("```", 1)[0]
    return stripped.strip()


def _coerce_float(value: Any) -> float:
    try:
        return float(value)
    except (TypeError, ValueError):
        return 0.0


def _parse_transcription_result(data: dict[str, Any]) -> dict[str, Any]:
    raw_text = _strip_code_fences(_extract_text_from_gemini_response(data))
    payload = json.loads(raw_text)

    transcript = str(payload.get("text") or "").strip()
    language = str(payload.get("language") or DEFAULT_TRANSCRIPTION_LANGUAGE).strip()
    if not language:
        language = DEFAULT_TRANSCRIPTION_LANGUAGE

    segments: list[dict[str, Any]] = []
    for segment in payload.get("segments") or []:
        if not isinstance(segment, dict):
            continue

        text = str(segment.get("text") or "").strip()
        if not text:
            continue

        segments.append(
            {
                "start": _coerce_float(segment.get("start")),
                "end": _coerce_float(segment.get("end")),
                "text": text,
            }
        )

    return {
        "text": transcript,
        "language": language,
        "segments": segments,
    }


def _build_generation_payload(
    parts: list[dict[str, Any]],
    *,
    prompt: str = TRANSCRIPTION_PROMPT,
) -> dict[str, Any]:
    return {
        "contents": [
            {
                "parts": [
                    {"text": prompt},
                    *parts,
                ]
            }
        ],
        "generationConfig": {
            "temperature": 0,
            "maxOutputTokens": 2048,
            "responseMimeType": "application/json",
        },
    }


async def _generate_transcription_with_inline_audio(
    client: httpx.AsyncClient,
    audio_bytes: bytes,
    mime_type: str,
    *,
    prompt: str = TRANSCRIPTION_PROMPT,
) -> dict[str, Any]:
    payload = _build_generation_payload(
        [
            {
                "inline_data": {
                    "mime_type": mime_type,
                    "data": base64.b64encode(audio_bytes).decode("utf-8"),
                }
            }
        ],
        prompt=prompt,
    )

    response = await client.post(
        f"{GEMINI_GENERATE_URL}/{settings.GEMINI_TRANSCRIPTION_MODEL}:generateContent",
        headers=_gemini_headers(),
        json=payload,
    )
    response.raise_for_status()
    return response.json()


async def _upload_audio_file(
    client: httpx.AsyncClient,
    audio_bytes: bytes,
    filename: str,
    mime_type: str,
) -> tuple[str, str]:
    start_response = await client.post(
        GEMINI_FILES_URL,
        headers={
            **_gemini_headers(),
            "X-Goog-Upload-Protocol": "resumable",
            "X-Goog-Upload-Command": "start",
            "X-Goog-Upload-Header-Content-Length": str(len(audio_bytes)),
            "X-Goog-Upload-Header-Content-Type": mime_type,
            "Content-Type": "application/json",
        },
        json={"file": {"display_name": Path(filename).name or "voice-message"}},
    )
    start_response.raise_for_status()

    upload_url = start_response.headers.get("x-goog-upload-url")
    if not upload_url:
        raise RuntimeError("Gemini did not return a resumable upload URL for audio transcription")

    upload_response = await client.post(
        upload_url,
        headers={
            "Content-Length": str(len(audio_bytes)),
            "X-Goog-Upload-Offset": "0",
            "X-Goog-Upload-Command": "upload, finalize",
        },
        content=audio_bytes,
    )
    upload_response.raise_for_status()

    file_info = upload_response.json().get("file") or {}
    file_uri = file_info.get("uri")
    file_name = file_info.get("name")
    if not file_uri or not file_name:
        raise RuntimeError(f"Gemini file upload completed without file metadata: {upload_response.text}")

    return str(file_uri), str(file_name)


async def _delete_uploaded_audio_file(
    client: httpx.AsyncClient,
    file_name: str,
) -> None:
    try:
        response = await client.delete(
            f"{GEMINI_API_BASE_URL}/v1beta/{file_name}",
            headers=_gemini_headers(),
        )
        response.raise_for_status()
    except Exception:
        logger.warning("Failed to delete uploaded Gemini audio file %s", file_name, exc_info=True)


async def _generate_transcription_with_uploaded_file(
    client: httpx.AsyncClient,
    file_uri: str,
    mime_type: str,
    *,
    prompt: str = TRANSCRIPTION_PROMPT,
) -> dict[str, Any]:
    payload = _build_generation_payload(
        [
            {
                "file_data": {
                    "mime_type": mime_type,
                    "file_uri": file_uri,
                }
            }
        ],
        prompt=prompt,
    )

    response = await client.post(
        f"{GEMINI_GENERATE_URL}/{settings.GEMINI_TRANSCRIPTION_MODEL}:generateContent",
        headers=_gemini_headers(),
        json=payload,
    )
    response.raise_for_status()
    return response.json()


async def save_upload_and_transcribe(
    file_content: bytes,
    filename: str,
    content_type: str | None = None,
    prompt: str = TRANSCRIPTION_PROMPT,
) -> dict[str, Any]:
    """
    Transcribe uploaded audio bytes using Gemini.

    Small recordings go inline for lower latency. Larger uploads use Gemini's
    Files API so the existing backend upload limit can stay intact.
    """
    mime_type = _guess_mime_type(filename, content_type)
    use_inline_audio = len(file_content) <= settings.GEMINI_TRANSCRIPTION_INLINE_MAX_BYTES

    logger.info(
        "Transcribing audio with Gemini model=%s filename=%s bytes=%s inline=%s mime=%s",
        settings.GEMINI_TRANSCRIPTION_MODEL,
        filename,
        len(file_content),
        use_inline_audio,
        mime_type,
    )

    async with httpx.AsyncClient(timeout=120.0) as client:
        if use_inline_audio:
            response_data = await _generate_transcription_with_inline_audio(
                client,
                file_content,
                mime_type,
                prompt=prompt,
            )
        else:
            file_uri, file_name = await _upload_audio_file(client, file_content, filename, mime_type)
            try:
                response_data = await _generate_transcription_with_uploaded_file(
                    client,
                    file_uri,
                    mime_type,
                    prompt=prompt,
                )
            finally:
                await _delete_uploaded_audio_file(client, file_name)

    result = _parse_transcription_result(response_data)
    logger.info(
        "Gemini transcription complete: chars=%s language=%s segments=%s",
        len(result["text"]),
        result["language"],
        len(result["segments"]),
    )
    return result


def transcribe_audio_file(file_path: str, content_type: str | None = None) -> dict[str, Any]:
    """
    Convenience wrapper for scripts that already have a file path on disk.

    The FastAPI routes use `save_upload_and_transcribe` directly.
    """
    path = Path(file_path)
    return asyncio.run(
        save_upload_and_transcribe(
            path.read_bytes(),
            path.name,
            content_type=content_type,
        )
    )


def cleanup_file(file_path: str) -> None:
    """Delete a file if it exists (used for deferred cleanup)."""
    try:
        if os.path.exists(file_path):
            os.remove(file_path)
            logger.debug("Deleted file: %s", file_path)
    except Exception:
        logger.exception("Failed to delete file: %s", file_path)
