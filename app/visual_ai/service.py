"""
Visual AI Service — orchestration layer.

Coordinates: screenshot storage → provider analysis → gap detection →
timeline tracking → guidance generation.
"""

from __future__ import annotations

import asyncio
import logging
import uuid
from typing import Any, Optional

import numpy as np
from sqlalchemy import select, func, desc
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.visual_ai.models import (
    Screenshot, VisualAnalysis, ReferenceScreen,
)
from app.visual_ai.schemas import (
    FullAnalysisResult, GapResult, GuidanceResponse,
    ReferenceScreenCreate, TimelineResponse,
)
from app.visual_ai.providers import get_visual_provider
from app.visual_ai.screenshot_store import save_screenshot, read_screenshot
from app.visual_ai.gap_detector import detect_gap
from app.visual_ai import timeline as timeline_mod
from app.visual_ai import guidance as guidance_mod
from app.visual_ai.gemini_embeddings import embed_image_with_gemini

logger = logging.getLogger(__name__)


# ─────────────────────────────────────────────────────────
# Utils
# ─────────────────────────────────────────────────────────

def _cosine(a: list[float], b: list[float]) -> float:
    if not a or not b:
        return 0.0
    a_arr = np.array(a, dtype=np.float32)
    b_arr = np.array(b, dtype=np.float32)
    denom = np.linalg.norm(a_arr) * np.linalg.norm(b_arr)
    if denom == 0:
        return 0.0
    return float(np.dot(a_arr, b_arr) / denom)


def _mean_embedding(vectors: list[list[float]]) -> list[float]:
    if not vectors:
        return []
    arr = np.array(vectors, dtype=np.float32)
    mean_vec = arr.mean(axis=0)
    norm = np.linalg.norm(mean_vec)
    return mean_vec.tolist() if norm == 0 else (mean_vec / norm).tolist()


def _truncate_single_line(text: str, limit: int = 160) -> str:
    compact = " ".join((text or "").split())
    return compact if len(compact) <= limit else compact[:limit - 1].rstrip() + "…"


def _frame_looks_uninformative(
    *,
    caption: str,
    ocr_text: str,
    labels: list[str],
    element_count: int,
) -> bool:
    normalized = (caption or "").strip().lower()
    has_signal = bool(ocr_text.strip()) or bool(labels) or element_count > 0
    if not normalized:
        return False
    blank_markers = (
        "completely black", "black image", "blank image", "blank screen",
        "nothing visible", "no visible content", "no discernible content",
        "entirely obscured", "fully obscured",
    )
    unreadable_markers = (
        "dark image", "dark screen", "too dark to read",
        "badly occluded", "obstructed",
    )
    if any(m in normalized for m in blank_markers + unreadable_markers):
        return not has_signal
    return False


def _build_screenshare_assistance_hints(
    *,
    final_caption: str,
    final_ocr_text: str,
    final_labels: list[str],
    final_element_count: int,
    processed_frames: int,
    uploaded_frames: int,
    avg_transition: float,
    max_transition: float,
    ref_similarity: Optional[float],
) -> list[str]:
    hints: list[str] = []
    if _frame_looks_uninformative(
        caption=final_caption,
        ocr_text=final_ocr_text,
        labels=final_labels,
        element_count=final_element_count,
    ):
        hints.append("Frame appears blank or obstructed.")
    preview = _truncate_single_line(final_ocr_text, 140)
    if preview:
        hints.append(f"Visible text: {preview}")
    elif final_labels:
        hints.append(f"UI cues: {', '.join(final_labels[:4])}")
    elif final_element_count:
        hints.append(f"{final_element_count} UI elements detected.")
    if max_transition > 0.45:
        hints.append("Significant UI change detected.")
    if ref_similarity is not None:
        hints.append(f"Reference similarity: {ref_similarity:.3f}")
    hints.append(f"Frames processed: {processed_frames}/{uploaded_frames}")
    hints.append(f"Avg transition: {avg_transition:.3f}")
    return hints


# ─────────────────────────────────────────────────────────
# Service
# ─────────────────────────────────────────────────────────

class VisualAIService:
    def __init__(self, db: AsyncSession):
        self.db = db

    # ───────────── Screenshot ─────────────

    async def store_screenshot(
        self,
        *,
        image_bytes: bytes,
        filename: str,
        mime_type: str,
        consent: bool,
        user_id: Optional[uuid.UUID] = None,
        conversation_id: Optional[uuid.UUID] = None,
        metadata: Optional[dict] = None,
    ) -> Screenshot:
        file_path, file_size = save_screenshot(
            image_bytes,
            filename=filename,
            conversation_id=str(conversation_id) if conversation_id else None,
        )
        screenshot = Screenshot(
            conversation_id=conversation_id,
            user_id=user_id,
            filename=filename,
            file_path=file_path,
            file_size=file_size,
            mime_type=mime_type,
            consent=consent,
            metadata_=metadata or {},
        )
        self.db.add(screenshot)
        await self.db.flush()
        await self.db.refresh(screenshot)
        return screenshot

    async def get_screenshot(self, screenshot_id: uuid.UUID) -> Optional[Screenshot]:
        result = await self.db.execute(
            select(Screenshot)
            .options(selectinload(Screenshot.analyses))
            .where(Screenshot.id == screenshot_id)
        )
        return result.scalar_one_or_none()

    async def list_screenshots(
        self,
        *,
        conversation_id: Optional[uuid.UUID] = None,
        limit: int = 50,
        offset: int = 0,
    ) -> tuple[list[Screenshot], int]:
        count_query = select(func.count(Screenshot.id))
        query = select(Screenshot)
        if conversation_id:
            count_query = count_query.where(Screenshot.conversation_id == conversation_id)
            query = query.where(Screenshot.conversation_id == conversation_id)
        total = (await self.db.execute(count_query)).scalar_one()
        items = (
            await self.db.execute(
                query.order_by(desc(Screenshot.created_at)).limit(limit).offset(offset)
            )
        ).scalars().all()
        return list(items), total

    # ───────────── Analysis ─────────────

    async def analyze_raw(
        self,
        image_bytes: bytes,
        provider_name: Optional[str] = None,
    ) -> FullAnalysisResult:
        provider = get_visual_provider(provider_name)
        return await provider.full_analysis(image_bytes)

    async def analyze_screenshot(
        self,
        screenshot_id: uuid.UUID,
        provider_name: Optional[str] = None,
    ) -> VisualAnalysis:
        screenshot = await self.get_screenshot(screenshot_id)
        if not screenshot:
            raise ValueError("Screenshot not found")
        image_bytes = read_screenshot(screenshot.file_path)
        provider = get_visual_provider(provider_name)
        result: FullAnalysisResult = await provider.full_analysis(image_bytes)
        analysis = VisualAnalysis(
            screenshot_id=screenshot.id,
            provider=result.provider,
            ocr_text=result.ocr.text if result.ocr else "",
            caption=result.ui_analysis.caption if result.ui_analysis else "",
            elements=[e.model_dump() for e in result.ui_analysis.elements] if result.ui_analysis else [],
            labels=result.ui_analysis.labels if result.ui_analysis else [],
            regions=[r.model_dump() for r in result.ui_analysis.regions] if result.ui_analysis else [],
            embedding=result.embedding or [],
            confidence=result.confidence,
            processing_ms=result.processing_ms,
            raw_result=result.raw_result,
        )
        self.db.add(analysis)
        await self.db.flush()
        await self.db.refresh(analysis)
        return analysis

    async def get_analysis(self, analysis_id: uuid.UUID) -> Optional[VisualAnalysis]:
        result = await self.db.execute(
            select(VisualAnalysis).where(VisualAnalysis.id == analysis_id)
        )
        return result.scalar_one_or_none()

    # ───────────── Gap Detection ─────────────

    async def detect_gap_for_analysis(
        self,
        analysis_id: uuid.UUID,
        *,
        reference_key: Optional[str] = None,
        reference_id: Optional[uuid.UUID] = None,
    ) -> GapResult:
        analysis = await self.get_analysis(analysis_id)
        if not analysis:
            raise ValueError("Analysis not found")
        ref = None
        if reference_id:
            ref = await self._get_reference_by_id(reference_id)
        elif reference_key:
            ref = await self._get_reference(reference_key=reference_key)
        if not ref:
            raise ValueError("Reference screen not found")
        return detect_gap(analysis, ref)

    # ───────────── References ─────────────

    async def _get_reference(self, *, reference_key: str) -> Optional[ReferenceScreen]:
        result = await self.db.execute(
            select(ReferenceScreen).where(ReferenceScreen.screen_key == reference_key)
        )
        return result.scalar_one_or_none()

    async def _get_reference_by_id(self, reference_id: uuid.UUID) -> Optional[ReferenceScreen]:
        result = await self.db.execute(
            select(ReferenceScreen).where(ReferenceScreen.id == reference_id)
        )
        return result.scalar_one_or_none()

    async def get_reference(self, ref_id: uuid.UUID) -> Optional[ReferenceScreen]:
        return await self._get_reference_by_id(ref_id)

    async def create_reference(
        self,
        payload: ReferenceScreenCreate,
        image_bytes: bytes,
        filename: str,
    ) -> ReferenceScreen:
        file_path, _ = save_screenshot(image_bytes, filename=filename, conversation_id=None)
        provider = get_visual_provider()
        embedding: list[float] = []
        try:
            embedding = await provider.encode_embedding(image_bytes)
        except Exception as e:
            logger.warning("Failed to embed reference image: %s", e)
        ref = ReferenceScreen(
            name=payload.name,
            screen_key=payload.screen_key,
            description=payload.description,
            file_path=file_path,
            expected_elements=payload.expected_elements,
            expected_ocr_text=payload.expected_ocr_text,
            embedding=embedding,
        )
        self.db.add(ref)
        await self.db.flush()
        await self.db.refresh(ref)
        return ref

    async def list_references(
        self,
        *,
        limit: int = 50,
        offset: int = 0,
    ) -> tuple[list[ReferenceScreen], int]:
        total = (await self.db.execute(select(func.count(ReferenceScreen.id)))).scalar_one()
        items = (
            await self.db.execute(
                select(ReferenceScreen)
                .order_by(desc(ReferenceScreen.created_at))
                .limit(limit)
                .offset(offset)
            )
        ).scalars().all()
        return list(items), total

    async def delete_reference(self, ref_id: uuid.UUID) -> bool:
        ref = await self._get_reference_by_id(ref_id)
        if not ref:
            return False
        await self.db.delete(ref)
        await self.db.flush()
        return True

    # ───────────── Timeline ─────────────

    async def get_timeline(
        self,
        conversation_id: uuid.UUID,
        *,
        limit: int = 100,
        offset: int = 0,
    ) -> TimelineResponse:
        return await timeline_mod.get_timeline(
            self.db,
            conversation_id=conversation_id,
            limit=limit,
            offset=offset,
        )

    # ───────────── Guidance ─────────────

    async def generate_guidance(
        self,
        analysis_id: uuid.UUID,
        *,
        reference_key: Optional[str] = None,
    ) -> GuidanceResponse:
        analysis = await self.get_analysis(analysis_id)
        if not analysis:
            raise ValueError("Analysis not found")
        ref = None
        if reference_key:
            ref = await self._get_reference(reference_key=reference_key)
        return await guidance_mod.generate_guidance(analysis, ref)

    # ───────────── Full Pipeline ─────────────

    async def process_screenshot(
        self,
        *,
        image_bytes: bytes,
        filename: str,
        mime_type: str,
        consent: bool,
        user_id: Optional[uuid.UUID] = None,
        conversation_id: Optional[uuid.UUID] = None,
        metadata: Optional[dict] = None,
        provider_name: Optional[str] = None,
        reference_key: Optional[str] = None,
    ) -> dict[str, Any]:
        # 1. Store
        screenshot = await self.store_screenshot(
            image_bytes=image_bytes,
            filename=filename,
            mime_type=mime_type,
            consent=consent,
            user_id=user_id,
            conversation_id=conversation_id,
            metadata=metadata,
        )
        # 2. Analyze
        analysis = await self.analyze_screenshot(screenshot.id, provider_name=provider_name)
        result: dict[str, Any] = {"screenshot": screenshot, "analysis": analysis}

        # 3. Gap detect
        gap_result = None
        if reference_key:
            try:
                gap_result = await self.detect_gap_for_analysis(
                    analysis.id, reference_key=reference_key
                )
                result["gap_result"] = gap_result
            except ValueError:
                pass

        # 4. Timeline
        if conversation_id:
            try:
                ui_state = await timeline_mod.add_to_timeline(
                    self.db,
                    screenshot=screenshot,
                    analysis=analysis,
                    conversation_id=conversation_id,
                    gap_result=gap_result,
                )
                result["ui_state"] = ui_state
            except Exception as e:
                logger.warning("Timeline update failed: %s", e)

        # 5. Guidance
        if gap_result:
            try:
                result["guidance"] = await guidance_mod.generate_guidance(analysis, None)
            except Exception as e:
                logger.warning("Guidance generation failed: %s", e)

        return result

    # ───────────── Screenshare ─────────────

    @staticmethod
    def sample_frames_low_fps(
        frames: list[tuple[bytes, str]],
        source_fps: float,
        target_fps: float,
        max_frames: int,
    ) -> list[tuple[bytes, str]]:
        if not frames:
            return []
        stride = max(1, int(round(source_fps / max(target_fps, 0.1))))
        return frames[::stride][:max_frames]

    async def analyze_screenshare_frames(
        self,
        *,
        frames: list[tuple[bytes, str]],
        source_fps: float,
        target_fps: float,
        provider_name: Optional[str] = None,
        reference_key: Optional[str] = None,
        use_gemini_embeddings: Optional[bool] = None,
    ) -> dict:
        """Analyze low-FPS sampled screenshare frames with reference comparison."""
        from app.core.config import get_settings
        settings = get_settings()

        if not frames:
            raise ValueError("No frames provided")

        sampled = self.sample_frames_low_fps(
            frames,
            source_fps=source_fps,
            target_fps=target_fps,
            max_frames=max(1, int(settings.VISUAL_SCREENSHARE_MAX_FRAMES)),
        )
        if not sampled:
            raise ValueError("No frames after sampling")

        provider = get_visual_provider(provider_name)
        vectors: list[list[float]] = []
        embedding_backend = provider.provider_name
        gemini_failed = False

        use_gemini = (
            settings.VISUAL_SCREENSHARE_USE_GEMINI_EMBEDDINGS
            if use_gemini_embeddings is None
            else bool(use_gemini_embeddings)
        )

        if use_gemini:
            try:
                for img, mime in sampled:
                    vec = embed_image_with_gemini(
                        img,
                        mime_type=mime,
                        output_dimensionality=settings.VISUAL_SCREENSHARE_EMBEDDING_DIMENSION,
                    )
                    vectors.append(vec)
                embedding_backend = "gemini"
            except Exception as e:
                gemini_failed = True
                vectors = []
                logger.warning("Gemini embeddings failed → fallback: %s", e)

        if not vectors:
            for i, (img, _) in enumerate(sampled, 1):
                try:
                    vec = await asyncio.wait_for(
                        provider.encode_embedding(img), timeout=5.0
                    )
                    vectors.append(vec)
                except Exception as e:
                    logger.warning("Embedding failed frame %s: %s", i, e)
            if gemini_failed and vectors:
                embedding_backend = f"{provider.provider_name} (fallback)"
            elif not vectors:
                embedding_backend = "unavailable"

        transition_scores = [
            1.0 - _cosine(vectors[i - 1], vectors[i])
            for i in range(1, len(vectors))
        ]
        avg_transition = float(np.mean(transition_scores)) if transition_scores else 0.0
        max_transition = float(np.max(transition_scores)) if transition_scores else 0.0

        final_bytes, _ = sampled[-1]
        final_ocr_text = ""
        final_caption = ""
        final_labels: list[str] = []
        final_element_count = 0

        try:
            ocr = await provider.extract_ocr(final_bytes)
            final_ocr_text = (ocr.text or "").strip()
        except Exception as e:
            logger.warning("Final OCR failed: %s", e)

        try:
            ui = await provider.analyze_ui(final_bytes)
            final_caption = (ui.caption or "").strip()
            final_labels = ui.labels or []
            final_element_count = len(ui.elements or [])
        except Exception as e:
            logger.warning("Final UI analysis failed: %s", e)

        aggregate = _mean_embedding(vectors)
        ref_similarity = None
        if reference_key:
            try:
                ref = await self._get_reference(reference_key=reference_key)
                if ref and ref.embedding and aggregate:
                    ref_vec = list(ref.embedding)
                    if len(ref_vec) == len(aggregate):
                        ref_similarity = _cosine(aggregate, ref_vec)
            except Exception as e:
                logger.warning("Reference comparison failed: %s", e)

        hints = _build_screenshare_assistance_hints(
            final_caption=final_caption,
            final_ocr_text=final_ocr_text,
            final_labels=final_labels,
            final_element_count=final_element_count,
            processed_frames=len(sampled),
            uploaded_frames=len(frames),
            avg_transition=avg_transition,
            max_transition=max_transition,
            ref_similarity=ref_similarity,
        )
        if not vectors:
            hints.insert(0, "Embedding analysis unavailable, using OCR/UI only.")
        elif gemini_failed:
            hints.insert(0, "Gemini embeddings failed, fallback used.")

        return {
            "source_fps": source_fps,
            "target_fps": target_fps,
            "uploaded_frames": len(frames),
            "processed_frames": len(sampled),
            "embedding_backend": embedding_backend,
            "embedding_dimension": len(vectors[0]) if vectors else 0,
            "avg_transition_score": round(avg_transition, 4),
            "max_transition_score": round(max_transition, 4),
            "reference_similarity": round(ref_similarity, 4) if ref_similarity is not None else None,
            "final_frame": {
                "provider": provider.provider_name,
                "caption": final_caption,
                "ocr_text_preview": final_ocr_text[:500],
                "element_count": final_element_count,
                "labels": final_labels,
            },
            "assistance_hints": hints,
        }