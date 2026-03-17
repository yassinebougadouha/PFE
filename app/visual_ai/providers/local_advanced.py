"""
Local Advanced provider — Florence-2 (OCR + Caption + OD + Regions) + CLIP embeddings.

Uses Microsoft's Florence-2-base model for comprehensive UI understanding:
  - <OCR>            → text extraction (better than Tesseract for UI text)
  - <CAPTION>        → natural-language image caption
  - <OD>             → object detection with bounding boxes
  - <DENSE_REGION_CAPTION> → region-level descriptions

CLIP is still used for embeddings (Florence-2 doesn't produce vector embeddings).

Dependencies:
  - transformers (pip install transformers)
  - sentence-transformers (already installed, for CLIP)

Cost: FREE | Speed: 3-8s on CPU | Quality: ★★★★★
"""

from __future__ import annotations

import io
import logging
import time
from functools import lru_cache

from PIL import Image

from app.visual_ai.providers.base import BaseVisualProvider
from app.visual_ai.schemas import (
    OCRResult, UIAnalysisResult, UIElement, RegionDescription, FullAnalysisResult,
)
from app.visual_ai.enums import UIElementType

logger = logging.getLogger(__name__)

# ── Florence-2 Model Singleton ────────────────────────────

_FLORENCE_LABEL_MAP: dict[str, UIElementType] = {
    "button": UIElementType.BUTTON,
    "input": UIElementType.INPUT_FIELD,
    "text field": UIElementType.INPUT_FIELD,
    "text box": UIElementType.INPUT_FIELD,
    "error": UIElementType.ERROR_MESSAGE,
    "form": UIElementType.FORM,
    "table": UIElementType.TABLE,
    "image": UIElementType.IMAGE,
    "link": UIElementType.LINK,
    "menu": UIElementType.NAVIGATION,
    "navigation": UIElementType.NAVIGATION,
    "modal": UIElementType.MODAL,
    "dialog": UIElementType.MODAL,
    "header": UIElementType.HEADER,
    "heading": UIElementType.HEADER,
}


@lru_cache(maxsize=1)
def _get_florence_model(model_name: str = "microsoft/Florence-2-base"):
    """Load Florence-2 model + processor once and cache."""
    from transformers import AutoProcessor, AutoModelForCausalLM
    import torch

    logger.info("Loading Florence-2 model: %s (this may take a while on first run)", model_name)
    device = "cuda" if torch.cuda.is_available() else "cpu"
    dtype = torch.float16 if device == "cuda" else torch.float32

    processor = AutoProcessor.from_pretrained(model_name, trust_remote_code=True)
    model = AutoModelForCausalLM.from_pretrained(
        model_name,
        dtype=dtype,
        trust_remote_code=True,
        attn_implementation="eager",
    ).to(device)

    # ── Monkey-patch: fix prepare_inputs_for_generation for transformers ≥ 4.46 ──
    # Florence-2's custom code checks `past_key_values[0][0].shape` but newer
    # transformers can pass a non-None tuple whose entries are None, causing an
    # AttributeError.  Guard the access so generation works correctly.
    _original_prepare = model.language_model.prepare_inputs_for_generation

    def _patched_prepare(decoder_input_ids, past_key_values=None, **kwargs):
        if past_key_values is not None:
            first = past_key_values[0] if past_key_values else None
            if first is None or (isinstance(first, (list, tuple)) and first[0] is None):
                past_key_values = None
        return _original_prepare(decoder_input_ids, past_key_values=past_key_values, **kwargs)

    model.language_model.prepare_inputs_for_generation = _patched_prepare

    logger.info("Florence-2 loaded on %s (%.0fM params)", device, sum(p.numel() for p in model.parameters()) / 1e6)
    return model, processor, device


def _florence_run(image: Image.Image, task: str, text_input: str = "") -> dict:
    """Run a Florence-2 task on an image."""
    import torch

    model, processor, device = _get_florence_model()

    prompt = task if not text_input else f"{task}{text_input}"
    inputs = processor(text=prompt, images=image, return_tensors="pt").to(device)

    with torch.inference_mode():
        generated_ids = model.generate(
            **inputs,
            max_new_tokens=1024,
            num_beams=3,
            do_sample=False,
        )

    generated_text = processor.batch_decode(generated_ids, skip_special_tokens=False)[0]
    result = processor.post_process_generation(
        generated_text, task=task, image_size=(image.width, image.height),
    )
    return result


# ── Provider ──────────────────────────────────────────────


class LocalAdvancedProvider(BaseVisualProvider):
    """Florence-2 + CLIP for comprehensive local visual analysis."""

    @property
    def provider_name(self) -> str:
        return "local-advanced"

    async def extract_ocr(self, image: bytes) -> OCRResult:
        """Extract text using Florence-2 <OCR> task."""
        try:
            img = Image.open(io.BytesIO(image)).convert("RGB")
            result = _florence_run(img, "<OCR>")
            text = result.get("<OCR>", "")
            if isinstance(text, dict):
                text = text.get("text", "")
            words = [w for w in str(text).split() if w.strip()]
            return OCRResult(
                text=str(text).strip(),
                confidence=0.9,  # Florence-2 OCR is generally high-quality
                word_count=len(words),
            )
        except Exception as e:
            logger.error("Florence-2 OCR failed: %s", e)
            return OCRResult(text="", confidence=0.0)

    async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
        """Analyze UI with Florence-2 caption + object detection + region descriptions."""
        img = Image.open(io.BytesIO(image)).convert("RGB")

        # Run tasks
        try:
            caption_result = _florence_run(img, "<CAPTION>")
            caption = caption_result.get("<CAPTION>", "")
        except Exception as e:
            logger.error("Florence-2 CAPTION failed: %s", e)
            caption = ""

        elements: list[UIElement] = []
        try:
            od_result = _florence_run(img, "<OD>")
            od_data = od_result.get("<OD>", {})
            bboxes = od_data.get("bboxes", [])
            labels = od_data.get("labels", [])

            for i, (bbox, label) in enumerate(zip(bboxes, labels)):
                label_lower = label.lower().strip()
                elem_type = _FLORENCE_LABEL_MAP.get(label_lower, UIElementType.UNKNOWN)
                # Normalise bbox to 0-1
                norm_bbox = [
                    bbox[0] / img.width, bbox[1] / img.height,
                    (bbox[2] - bbox[0]) / img.width, (bbox[3] - bbox[1]) / img.height,
                ] if len(bbox) >= 4 else None

                elements.append(UIElement(
                    element_type=elem_type,
                    label=label,
                    bbox=norm_bbox,
                    confidence=0.85,
                ))
        except Exception as e:
            logger.error("Florence-2 OD failed: %s", e)

        regions: list[RegionDescription] = []
        try:
            region_result = _florence_run(img, "<DENSE_REGION_CAPTION>")
            region_data = region_result.get("<DENSE_REGION_CAPTION>", {})
            r_bboxes = region_data.get("bboxes", [])
            r_labels = region_data.get("labels", [])

            for bbox, desc in zip(r_bboxes, r_labels):
                norm_bbox = [
                    bbox[0] / img.width, bbox[1] / img.height,
                    (bbox[2] - bbox[0]) / img.width, (bbox[3] - bbox[1]) / img.height,
                ] if len(bbox) >= 4 else None
                regions.append(RegionDescription(bbox=norm_bbox, description=desc))
        except Exception as e:
            logger.error("Florence-2 DENSE_REGION_CAPTION failed: %s", e)

        return UIAnalysisResult(
            caption=str(caption),
            elements=elements,
            labels=[e.label for e in elements],
            regions=regions,
        )

    async def encode_embedding(self, image: bytes) -> list[float]:
        """Generate CLIP visual embedding."""
        from app.visual_ai.clip_encoder import encode_image
        return encode_image(image)

    async def full_analysis(self, image: bytes) -> FullAnalysisResult:
        """Run complete pipeline: Florence-2 OCR + UI analysis + CLIP embedding."""
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
