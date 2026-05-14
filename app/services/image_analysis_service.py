"""
Gemini-powered image analysis for customer support chat attachments.
"""

from __future__ import annotations

import base64
import json
import logging
from pathlib import Path
from typing import Any

import httpx

from app.core.config import get_settings

logger = logging.getLogger(__name__)
settings = get_settings()

_GEMINI_BASE_URL = "https://generativelanguage.googleapis.com/v1beta/models"
_DEFAULT_IMAGE_MIME = "image/png"
_CHAT_IMAGE_PROMPT = """You are analyzing an image a customer attached in a support chat.
Return valid JSON only with exactly these fields:
{
  "summary": "one or two short sentences describing what the image shows",
  "visible_text": "relevant text visible in the image, or an empty string",
  "issue_signals": ["short support-relevant observations"],
  "suggested_focus": "the most likely thing the customer wants help with"
}

Rules:
- Focus on what is useful for customer support.
- If the image looks like a screenshot, mention the page state, errors, or visible UI.
- If the image looks like a document or photo, describe the relevant details plainly.
- Keep every field concise.
- If the image is unclear, say that in the summary.
- Do not include markdown or extra commentary."""


def _strip_code_fences(value: str) -> str:
    text = value.strip()
    if text.startswith("```"):
        text = text.split("\n", 1)[-1]
    if text.endswith("```"):
        text = text.rsplit("```", 1)[0]
    return text.strip()


def _extract_candidate_text(data: dict[str, Any]) -> str:
    candidates = data.get("candidates") or []
    if not candidates:
        raise RuntimeError(f"Gemini returned no image-analysis candidates: {data}")

    parts = candidates[0].get("content", {}).get("parts", [])
    text = "".join(part.get("text", "") for part in parts).strip()
    if not text:
        raise RuntimeError(f"Gemini returned an empty image-analysis payload: {data}")
    return text


def _normalize_analysis_payload(data: dict[str, Any]) -> dict[str, Any]:
    raw_text = _strip_code_fences(_extract_candidate_text(data))

    try:
        payload = json.loads(raw_text)
    except json.JSONDecodeError:
        logger.warning("Falling back to plain-text Gemini image summary")
        payload = {
            "summary": raw_text,
            "visible_text": "",
            "issue_signals": [],
            "suggested_focus": "",
        }

    issue_signals = [
        str(item).strip()
        for item in (payload.get("issue_signals") or [])
        if str(item).strip()
    ]

    return {
        "summary": str(payload.get("summary") or "").strip(),
        "visible_text": str(payload.get("visible_text") or "").strip(),
        "issue_signals": issue_signals[:4],
        "suggested_focus": str(payload.get("suggested_focus") or "").strip(),
    }


def _resolve_image_mime_type(mime_type: str | None, filename: str | None = None) -> str:
    normalized = (mime_type or "").split(";", 1)[0].strip().lower()
    if normalized.startswith("image/"):
        return normalized

    suffix = Path(filename or "").suffix.lower()
    if suffix in {".jpg", ".jpeg"}:
        return "image/jpeg"
    if suffix == ".webp":
        return "image/webp"
    if suffix == ".gif":
        return "image/gif"

    return _DEFAULT_IMAGE_MIME


async def analyze_chat_image(
    image_bytes: bytes,
    *,
    mime_type: str | None = None,
    filename: str | None = None,
    customer_message: str | None = None,
) -> dict[str, Any]:
    """
    Analyze an attached customer image with Gemini and return a compact summary.
    """
    if not settings.current_gemini_key:
        raise RuntimeError("Gemini API key is not configured (GEMINI_API_KEY)")

    resolved_mime_type = _resolve_image_mime_type(mime_type, filename=filename)
    prompt = _CHAT_IMAGE_PROMPT
    if customer_message and customer_message.strip():
        prompt += f"\n\nCustomer message context: {customer_message.strip()}"

    payload = {
        "contents": [
            {
                "parts": [
                    {"text": prompt},
                    {
                        "inline_data": {
                            "mime_type": resolved_mime_type,
                            "data": base64.b64encode(image_bytes).decode("utf-8"),
                        }
                    },
                ]
            }
        ],
        "generationConfig": {
            "temperature": 0.1,
            "maxOutputTokens": 1024,
            "responseMimeType": "application/json",
        },
    }

    async with httpx.AsyncClient(timeout=45.0) as client:
        response = await client.post(
            f"{_GEMINI_BASE_URL}/{settings.GEMINI_IMAGE_ANALYSIS_MODEL}:generateContent?key={settings.current_gemini_key}",
            json=payload,
        )
        if not response.is_success:
            error_body = response.text[:500]
            logger.warning("Gemini image analysis HTTP %s: %s", response.status_code, error_body)
            return {"summary": "", "visible_text": "", "issue_signals": [], "suggested_focus": ""}
        data = response.json()

    result = _normalize_analysis_payload(data)
    logger.info(
        "Gemini image analysis complete: summary_chars=%s visible_text_chars=%s issues=%s",
        len(result["summary"]),
        len(result["visible_text"]),
        len(result["issue_signals"]),
    )
    return result
