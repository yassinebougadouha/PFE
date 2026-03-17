"""
Local Basic provider — Tesseract OCR + CLIP embeddings + rule-based UI detection.

Dependencies:
  - pytesseract (pip install pytesseract)
  - tesseract-ocr system package (apt-get install tesseract-ocr)
  - sentence-transformers (already installed, for CLIP)
  - Pillow (already installed)

Cost: FREE | Speed: <1s | Quality: ★★★☆☆
"""

from __future__ import annotations

import io
import logging
import re
import time

from PIL import Image

from app.visual_ai.providers.base import BaseVisualProvider
from app.visual_ai.schemas import (
    OCRResult, UIAnalysisResult, UIElement, FullAnalysisResult,
)
from app.visual_ai.enums import UIElementType

logger = logging.getLogger(__name__)

# ── Rule-based UI element patterns ────────────────────────

_ELEMENT_RULES: list[tuple[str, UIElementType]] = [
    (r"error|erreur|failed|failure|échec|invalide|invalid|denied|refusé", UIElementType.ERROR_MESSAGE),
    (r"success|succès|done|terminé|confirmed|confirmé|completed", UIElementType.SUCCESS_MESSAGE),
    (r"loading|chargement|wait|patientez|please wait|spinner", UIElementType.LOADING_STATE),
    (r"button|btn|click|cliquez|submit|envoyer|valider|confirm|cancel|annuler", UIElementType.BUTTON),
    (r"input|field|champ|enter|saisir|username|password|email|mot de passe", UIElementType.INPUT_FIELD),
    (r"menu|nav|navigation|sidebar|header|footer|breadcrumb", UIElementType.NAVIGATION),
    (r"form|formulaire|sign.?in|sign.?up|login|connexion|register|inscription", UIElementType.FORM),
    (r"modal|dialog|popup|alert|notification", UIElementType.MODAL),
    (r"table|tableau|grid|list|liste|row|column", UIElementType.TABLE),
    (r"link|lien|href|url|http|www\.", UIElementType.LINK),
    (r"<h[1-6]>|title|titre|heading", UIElementType.HEADER),
]


class LocalBasicProvider(BaseVisualProvider):
    """Tesseract OCR + CLIP embedding + rule-based UI element detection."""

    @property
    def provider_name(self) -> str:
        return "local-basic"

    async def extract_ocr(self, image: bytes) -> OCRResult:
        """Extract text using Tesseract OCR."""
        try:
            import pytesseract
        except ImportError:
            logger.warning("pytesseract not installed. Install with: pip install pytesseract")
            return OCRResult(text="", confidence=0.0)

        try:
            img = Image.open(io.BytesIO(image)).convert("RGB")
            # Get detailed data for confidence
            data = pytesseract.image_to_data(img, output_type=pytesseract.Output.DICT)
            text = pytesseract.image_to_string(img).strip()

            # Compute average confidence (filter out -1 entries)
            confs = [c for c in data.get("conf", []) if isinstance(c, (int, float)) and c >= 0]
            avg_conf = sum(confs) / len(confs) / 100.0 if confs else 0.0

            words = [w for w in text.split() if w.strip()]

            return OCRResult(
                text=text,
                confidence=round(avg_conf, 3),
                word_count=len(words),
            )
        except Exception as e:
            logger.error("Tesseract OCR failed: %s", e)
            return OCRResult(text="", confidence=0.0)

    async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
        """Detect UI elements using regex rules on OCR text."""
        ocr = await self.extract_ocr(image)
        text_lower = ocr.text.lower()

        elements: list[UIElement] = []
        detected_types: set[UIElementType] = set()

        for pattern, elem_type in _ELEMENT_RULES:
            matches = re.findall(pattern, text_lower, re.IGNORECASE)
            if matches and elem_type not in detected_types:
                detected_types.add(elem_type)
                elements.append(UIElement(
                    element_type=elem_type,
                    label=matches[0],
                    confidence=0.7,  # rule-based, lower confidence
                    text=matches[0],
                ))

        # Generate a basic caption
        elem_types = [e.element_type.value for e in elements]
        if elements:
            caption = f"Screenshot with {len(elements)} detected elements: {', '.join(elem_types)}"
        else:
            caption = "Screenshot with no clearly identifiable UI elements"

        return UIAnalysisResult(
            caption=caption,
            elements=elements,
            labels=[],
            regions=[],
        )

    async def encode_embedding(self, image: bytes) -> list[float]:
        """Generate CLIP visual embedding."""
        from app.visual_ai.clip_encoder import encode_image
        return encode_image(image)

    async def full_analysis(self, image: bytes) -> FullAnalysisResult:
        """Run complete pipeline: OCR + UI analysis + embedding."""
        start = time.perf_counter()

        ocr = await self.extract_ocr(image)
        ui_analysis = await self.analyze_ui(image)
        embedding = await self.encode_embedding(image)

        elapsed_ms = int((time.perf_counter() - start) * 1000)

        return FullAnalysisResult(
            ocr=ocr,
            ui_analysis=ui_analysis,
            embedding=embedding,
            provider=self.provider_name,
            processing_ms=elapsed_ms,
            confidence=ocr.confidence,
        )
