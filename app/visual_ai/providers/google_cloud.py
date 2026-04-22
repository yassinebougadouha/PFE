"""
Google Cloud provider — Cloud Vision API + Vertex AI Multimodal Embeddings + Gemini Vision.

  - Cloud Vision: TEXT_DETECTION + LABEL_DETECTION (fast, accurate OCR + labels)
  - Vertex AI: multimodal embedding (if available)
  - Gemini Vision: UI analysis, captioning, element detection via gemini-2.5-flash

Falls back to CLIP for embeddings if Vertex AI multimodal embedding is not available.

Dependencies:
  - google-cloud-vision (pip install google-cloud-vision)
  - google-cloud-aiplatform (pip install google-cloud-aiplatform)  — optional
  - google-generativeai / httpx (Gemini API, already installed)

Cost: ~$1.50/1K images | Speed: <1s | Quality: ★★★★★
"""

from __future__ import annotations

import base64
import io
import json
import logging
import time
from typing import Any

import httpx
from PIL import Image

from app.visual_ai.providers.base import BaseVisualProvider
from app.visual_ai.schemas import (
    OCRResult, UIAnalysisResult, UIElement, RegionDescription, FullAnalysisResult,
)
from app.visual_ai.enums import UIElementType

logger = logging.getLogger(__name__)

# ── Gemini UI Analysis Prompt ─────────────────────────────

_GEMINI_UI_ANALYSIS_PROMPT = """Analyze this UI screenshot for a customer support system.
Return a JSON object with exactly these fields:

{
  "caption": "One sentence describing the visible page, key controls, and what the user appears to be doing right now",
  "elements": [
    {"type": "BUTTON|INPUT_FIELD|ERROR_MESSAGE|SUCCESS_MESSAGE|LOADING_STATE|NAVIGATION|FORM|MODAL|TABLE|LINK|HEADER|TEXT_BLOCK", "label": "element text or name", "confidence": 0.95}
  ],
  "labels": ["label1", "label2"],
  "regions": [
    {"description": "description of a notable region"}
  ],
  "detected_page": "login_page|dashboard|settings|error_page|form|other",
  "has_error": true/false,
  "error_text": "the error message text if any, otherwise null"
}

Be precise and thorough. Prefer page-specific wording over generic phrases like "screen shows a UI".
Only describe the frame as blank, unreadable, or obstructed when almost no meaningful content is visible.
Do not call normal dark-mode interfaces, dark scenes, or video content "blank" just because they are dim.
If the frame mostly mirrors the support call UI itself, say that clearly in the caption.
List ALL visible UI elements. Return ONLY valid JSON, no markdown."""


class GoogleCloudProvider(BaseVisualProvider):
    """Google Cloud Vision + Gemini Vision for comprehensive analysis."""

    @property
    def provider_name(self) -> str:
        return "gemini"

    def _get_gemini_key(self) -> str:
        from app.core.config import get_settings
        settings = get_settings()
        return settings.current_gemini_key

    async def extract_ocr(self, image: bytes) -> OCRResult:
        """
        Extract text using Gemini Vision.
        """
        return await self._gemini_ocr(image)

    async def _gemini_ocr(self, image: bytes) -> OCRResult:
        """Fallback OCR using Gemini Vision."""
        key = self._get_gemini_key()
        if not key:
            return OCRResult(text="", confidence=0.0)

        b64 = base64.b64encode(image).decode("utf-8")
        payload = {
            "contents": [{
                "parts": [
                    {"text": "Extract ALL text visible in this screenshot. Return only the raw text, no formatting or explanation."},
                    {"inline_data": {"mime_type": "image/png", "data": b64}},
                ],
            }],
        }

        data: dict[str, Any] = {}
        try:
            async with httpx.AsyncClient(timeout=30.0) as client:
                resp = await client.post(
                    f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={key}",
                    json=payload,
                )

            if resp.status_code == 429:
                raise RuntimeError("Gemini API Rate Limit Exceeded (429). Please wait and try again.")
            
            if resp.status_code >= 500:
                logger.warning("Gemini OCR returned upstream %s", resp.status_code)
                return OCRResult(text="", confidence=0.0)

            if resp.status_code >= 400:
                logger.warning("Gemini OCR returned client error %s: %s", resp.status_code, resp.text)
                return OCRResult(text="", confidence=0.0)

            data = resp.json()
        except httpx.HTTPError as exc:
            logger.warning("Gemini OCR request failed: %s", exc)
            return OCRResult(text="", confidence=0.0)
        except ValueError as exc:
            logger.warning("Gemini OCR returned non-JSON response: %s", exc)
            return OCRResult(text="", confidence=0.0)

        text = ""
        try:
            text = data["candidates"][0]["content"]["parts"][0]["text"].strip()
        except (KeyError, IndexError):
            pass

        words = [w for w in text.split() if w.strip()]
        return OCRResult(text=text, confidence=0.85, word_count=len(words))

    async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
        """Analyze UI using Gemini Vision for caption + element detection."""
        key = self._get_gemini_key()
        if not key:
            logger.error("GEMINI_API_KEY not set — cannot perform UI analysis")
            return UIAnalysisResult()

        b64 = base64.b64encode(image).decode("utf-8")
        payload = {
            "contents": [{
                "parts": [
                    {"text": _GEMINI_UI_ANALYSIS_PROMPT},
                    {"inline_data": {"mime_type": "image/png", "data": b64}},
                ],
            }],
            "generationConfig": {
                "temperature": 0.1,
                "maxOutputTokens": 2048,
            },
        }

        data: dict[str, Any] = {}
        try:
            async with httpx.AsyncClient(timeout=30.0) as client:
                resp = await client.post(
                    f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={key}",
                    json=payload,
                )

            if resp.status_code == 429:
                raise RuntimeError("Gemini API Rate Limit Exceeded (429). Please wait and try again.")
            
            if resp.status_code >= 500:
                logger.warning("Gemini UI analysis returned upstream %s", resp.status_code)
                return UIAnalysisResult()

            if resp.status_code >= 400:
                logger.warning("Gemini UI analysis returned client error %s: %s", resp.status_code, resp.text)
                return UIAnalysisResult()

            data = resp.json()
        except httpx.HTTPError as exc:
            logger.warning("Gemini UI analysis request failed: %s", exc)
            return UIAnalysisResult()
        except ValueError as exc:
            logger.warning("Gemini UI analysis returned non-JSON response: %s", exc)
            return UIAnalysisResult()

        try:
            raw_text = data["candidates"][0]["content"]["parts"][0]["text"]
            # Strip markdown code block if present
            raw_text = raw_text.strip()
            if raw_text.startswith("```"):
                raw_text = raw_text.split("\n", 1)[-1]
            if raw_text.endswith("```"):
                raw_text = raw_text.rsplit("```", 1)[0]
            raw_text = raw_text.strip()

            result = json.loads(raw_text)
        except (json.JSONDecodeError, KeyError, IndexError) as e:
            logger.error("Failed to parse Gemini UI analysis: %s", e)
            return UIAnalysisResult()

        # Parse elements
        elements: list[UIElement] = []
        for elem in result.get("elements", []):
            elem_type_str = elem.get("type", "UNKNOWN")
            try:
                elem_type = UIElementType(elem_type_str)
            except ValueError:
                elem_type = UIElementType.UNKNOWN
            elements.append(UIElement(
                element_type=elem_type,
                label=elem.get("label", ""),
                confidence=elem.get("confidence", 0.9),
            ))

        regions = [
            RegionDescription(description=r.get("description", ""))
            for r in result.get("regions", [])
        ]

        return UIAnalysisResult(
            caption=result.get("caption", ""),
            elements=elements,
            labels=result.get("labels", []),
            regions=regions,
        )

    async def encode_embedding(self, image: bytes) -> list[float]:
        """
        Generate visual embedding using Gemini embeddings model.
        Falls back to CLIP.
        """
        import asyncio
        from app.visual_ai.gemini_embeddings import embed_image_with_gemini
        
        try:
            return await asyncio.to_thread(embed_image_with_gemini, image)
        except Exception as e:
            logger.warning("Gemini embedding unavailable (%s), using CLIP fallback", e)
            from app.visual_ai.clip_encoder import encode_image
            return encode_image(image)

    async def full_analysis(self, image: bytes) -> FullAnalysisResult:
        """Run complete pipeline: Cloud Vision OCR + Gemini UI + embedding."""
        start = time.perf_counter()

        try:
            ocr = await self.extract_ocr(image)
        except RuntimeError as exc:
            if "Rate Limit" in str(exc):
                raise  # Propagate rate limit up to user
            logger.warning("Visual AI OCR stage failed: %s", exc)
            ocr = OCRResult(text="", confidence=0.0)
        except Exception as exc:
            logger.warning("Visual AI OCR stage failed: %s", exc)
            ocr = OCRResult(text="", confidence=0.0)

        try:
            ui_analysis = await self.analyze_ui(image)
        except RuntimeError as exc:
            if "Rate Limit" in str(exc):
                raise
            logger.warning("Visual AI UI analysis stage failed: %s", exc)
            ui_analysis = UIAnalysisResult()
        except Exception as exc:
            logger.warning("Visual AI UI analysis stage failed: %s", exc)
            ui_analysis = UIAnalysisResult()

        try:
            embedding = await self.encode_embedding(image)
        except Exception as exc:
            logger.warning("Visual AI embedding stage failed: %s", exc)
            embedding = []

        elapsed_ms = int((time.perf_counter() - start) * 1000)

        return FullAnalysisResult(
            ocr=ocr,
            ui_analysis=ui_analysis,
            embedding=embedding,
            provider=self.provider_name,
            processing_ms=elapsed_ms,
            confidence=0.95 if (ocr.text or ui_analysis.caption or embedding) else 0.0,
            raw_result={"provider": "gemini", "services": ["cloud_vision", "gemini", "clip_or_vertex"]},
        )
