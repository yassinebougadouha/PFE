"""
Visual AI Service — orchestration layer.

Coordinates: screenshot storage → provider analysis → gap detection →
timeline tracking → guidance generation.

Instantiated per-request in route handlers:
    svc = VisualAIService(db)
"""

from __future__ import annotations

import asyncio
import logging
import uuid
from typing import Optional

import numpy as np
from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.visual_ai.models import (
    Screenshot, VisualAnalysis, UIState, ReferenceScreen,
)
from app.visual_ai.enums import VisualAIProvider
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


def _cosine(a: list[float], b: list[float]) -> float:
    a_arr = np.array(a, dtype=np.float32)
    b_arr = np.array(b, dtype=np.float32)
    if a_arr.size == 0 or b_arr.size == 0:
        return 0.0
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
    if norm == 0:
        return mean_vec.tolist()
    return (mean_vec / norm).tolist()


def _truncate_single_line(text: str, limit: int = 160) -> str:
    compact = " ".join((text or "").split())
    if len(compact) <= limit:
        return compact
    return compact[: limit - 1].rstrip() + "…"


def _frame_looks_uninformative(
    *,
    caption: str,
    ocr_text: str,
    labels: list[str],
    element_count: int,
) -> bool:
    """
    Detect truly blank/unreadable frames based on EXPLICIT provider signals.
    
    Only mark as uninformative if:
    1. Caption explicitly says frame is blank/dark/obstructed
    2. AND there's no contradicting content signal (text, labels, elements)
    
    Do NOT infer "blank" from missing caption alone—that's likely a timeout
    or temporary analysis failure, not a blank frame.
    """
    normalized = (caption or "").strip().lower()
    has_visible_content_signal = bool((ocr_text or "").strip()) or bool(labels) or element_count > 0
    
    # If caption is empty, don't assume the frame is blank (likely a timeout)
    if not normalized:
        return False
    
    # Caption explicitly says frame is blank
    blank_markers = (
        "completely black",
        "black image",
        "blank image",
        "blank screen",
        "nothing visible",
        "no visible content",
        "no discernible content",
        "entirely obscured",
        "fully obscured",
    )
    if any(marker in normalized for marker in blank_markers):
        # But trust content signals over caption
        return not has_visible_content_signal
    
    # Caption explicitly says frame is unreadable/dark
    unreadable_markers = (
        "dark image",
        "dark screen",
        "too dark to read",
        "badly occluded",
        "obstructed",
    )
    if any(marker in normalized for marker in unreadable_markers):
        return not has_visible_content_signal
    
    # No explicit blank/dark signal in caption → frame looks informative
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
        hints.append(
            "The shared frame looks blank or obstructed. Keep the target app visible and avoid sharing the live call page itself."
        )

    preview_text = _truncate_single_line(final_ocr_text, limit=140)
    if preview_text:
        hints.append(f"Visible text includes: {preview_text}.")
    elif final_labels:
        label_preview = ", ".join(label for label in final_labels[:4] if label)
        if label_preview:
            hints.append(f"Visible interface cues include: {label_preview}.")
    elif final_element_count > 0:
        hints.append(
            f"Detected about {final_element_count} visible interface element"
            f"{'' if final_element_count == 1 else 's'} in the latest frame."
        )

    if max_transition > 0.45:
        hints.append("A noticeable UI change just happened, so the user may be navigating or opening a new step.")

    if ref_similarity is not None:
        hints.append(f"Reference similarity: {ref_similarity:.3f}.")

    hints.append(f"Processed {processed_frames} low-FPS frames (from {uploaded_frames} uploaded).")
    hints.append(f"Average UI transition score: {avg_transition:.3f}.")
    return hints


class VisualAIService:
    """High-level orchestrator for all Visual AI operations."""

    def __init__(self, db: AsyncSession):
        self.db = db

    # ══════════════════════════════════════════════════════
    #  Screenshot CRUD
    # ══════════════════════════════════════════════════════

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
        """Save screenshot to disk and create DB record."""
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
        """Get screenshot by ID."""
        result = await self.db.execute(
            select(Screenshot)
            .options(selectinload(Screenshot.analyses))
            .where(Screenshot.id == screenshot_id)
        )
        return result.scalar_one_or_none()

    async def list_screenshots(
        self,
        conversation_id: Optional[uuid.UUID] = None,
        user_id: Optional[uuid.UUID] = None,
        limit: int = 50,
        offset: int = 0,
    ) -> tuple[list[Screenshot], int]:
        """List screenshots with optional filters."""
        stmt = select(Screenshot)
        count_stmt = select(func.count(Screenshot.id))

        if conversation_id:
            stmt = stmt.where(Screenshot.conversation_id == conversation_id)
            count_stmt = count_stmt.where(Screenshot.conversation_id == conversation_id)
        if user_id:
            stmt = stmt.where(Screenshot.user_id == user_id)
            count_stmt = count_stmt.where(Screenshot.user_id == user_id)

        stmt = stmt.order_by(Screenshot.created_at.desc()).offset(offset).limit(limit)

        result = await self.db.execute(stmt)
        count_result = await self.db.execute(count_stmt)

        return list(result.scalars().all()), count_result.scalar_one()

    # ══════════════════════════════════════════════════════
    #  Analysis
    # ══════════════════════════════════════════════════════

    async def analyze_screenshot(
        self,
        screenshot_id: uuid.UUID,
        provider_name: Optional[str] = None,
    ) -> VisualAnalysis:
        """
        Run full analysis on a stored screenshot.
        Returns persisted VisualAnalysis record.
        """
        screenshot = await self.get_screenshot(screenshot_id)
        if not screenshot:
            raise ValueError(f"Screenshot {screenshot_id} not found")

        image_bytes = read_screenshot(screenshot.file_path)
        provider = get_visual_provider(provider_name)
        result: FullAnalysisResult = await provider.full_analysis(image_bytes)

        analysis = VisualAnalysis(
            screenshot_id=screenshot.id,
            provider=result.provider,
            ocr_text=result.ocr.text if result.ocr else None,
            caption=result.ui_analysis.caption if result.ui_analysis else None,
            elements=[e.model_dump() for e in result.ui_analysis.elements] if result.ui_analysis else [],
            labels=result.ui_analysis.labels if result.ui_analysis else [],
            regions=[r.model_dump() for r in result.ui_analysis.regions] if result.ui_analysis else [],
            embedding=result.embedding or None,
            confidence=result.confidence,
            processing_ms=result.processing_ms,
            raw_result=result.raw_result,
        )
        self.db.add(analysis)
        await self.db.flush()
        await self.db.refresh(analysis)
        return analysis

    async def analyze_raw(
        self,
        image_bytes: bytes,
        provider_name: Optional[str] = None,
    ) -> FullAnalysisResult:
        """Run analysis on raw image bytes without storing."""
        provider = get_visual_provider(provider_name)
        return await provider.full_analysis(image_bytes)

    async def get_analysis(self, analysis_id: uuid.UUID) -> Optional[VisualAnalysis]:
        """Get analysis by ID."""
        result = await self.db.execute(
            select(VisualAnalysis).where(VisualAnalysis.id == analysis_id)
        )
        return result.scalar_one_or_none()

    async def list_analyses(
        self,
        screenshot_id: uuid.UUID,
    ) -> list[VisualAnalysis]:
        """List all analyses for a screenshot."""
        result = await self.db.execute(
            select(VisualAnalysis)
            .where(VisualAnalysis.screenshot_id == screenshot_id)
            .order_by(VisualAnalysis.created_at.desc())
        )
        return list(result.scalars().all())

    # ══════════════════════════════════════════════════════
    #  Gap Detection
    # ══════════════════════════════════════════════════════

    async def detect_gap_for_analysis(
        self,
        analysis_id: uuid.UUID,
        *,
        reference_key: Optional[str] = None,
        reference_id: Optional[uuid.UUID] = None,
    ) -> GapResult:
        """
        Run gap detection for an analysis against a reference screen.
        """
        analysis = await self.get_analysis(analysis_id)
        if not analysis:
            raise ValueError(f"Analysis {analysis_id} not found")

        # Load reference
        ref = await self._get_reference(reference_key=reference_key, reference_id=reference_id)
        if not ref:
            raise ValueError("No reference screen found. Provide reference_key or reference_id.")

        # Build FullAnalysisResult from the stored analysis
        from app.visual_ai.schemas import OCRResult, UIAnalysisResult, UIElement
        full = FullAnalysisResult(
            ocr=OCRResult(text=analysis.ocr_text or ""),
            ui_analysis=UIAnalysisResult(
                caption=analysis.caption or "",
                elements=[UIElement(**e) for e in (analysis.elements or [])],
                labels=analysis.labels or [],
            ),
            embedding=list(analysis.embedding) if analysis.embedding is not None else [],
            provider=analysis.provider,
        )

        # Run gap detection
        ref_embed = list(ref.embedding) if ref.embedding is not None else []
        return detect_gap(
            full,
            reference_embedding=ref_embed,
            reference_ocr_text=ref.expected_ocr_text,
            reference_elements=ref.expected_elements or [],
        )

    async def _get_reference(
        self,
        *,
        reference_key: Optional[str] = None,
        reference_id: Optional[uuid.UUID] = None,
    ) -> Optional[ReferenceScreen]:
        """Find a reference screen by key or ID."""
        if reference_key:
            result = await self.db.execute(
                select(ReferenceScreen).where(ReferenceScreen.screen_key == reference_key)
            )
            return result.scalar_one_or_none()
        if reference_id:
            result = await self.db.execute(
                select(ReferenceScreen).where(ReferenceScreen.id == reference_id)
            )
            return result.scalar_one_or_none()
        return None

    # ══════════════════════════════════════════════════════
    #  Full Pipeline: capture → analyze → gap → timeline → guide
    # ══════════════════════════════════════════════════════

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
    ) -> dict:
        """
        Complete pipeline: store → analyze → gap detect → timeline → guidance.

        Returns a dict with all results for the route to return.
        """
        # 1. Store screenshot
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

        # 3. Gap detection (optional, if reference provided)
        gap_result = None
        if reference_key:
            try:
                gap_result = await self.detect_gap_for_analysis(
                    analysis.id, reference_key=reference_key,
                )
            except ValueError as e:
                logger.warning("Gap detection skipped: %s", e)

        # 4. Timeline entry (if conversation context exists)
        ui_state = None
        if conversation_id:
            state_label = analysis.caption[:100] if analysis.caption else None
            ui_state = await timeline_mod.add_state(
                self.db,
                conversation_id=conversation_id,
                analysis_id=analysis.id,
                screenshot_id=screenshot.id,
                state_label=state_label,
                state_data={
                    "ocr_preview": (analysis.ocr_text or "")[:200],
                    "element_count": len(analysis.elements or []),
                },
                embedding=list(analysis.embedding) if analysis.embedding is not None else None,
                gap_result=gap_result,
            )

        # 5. Guidance (if gap detected)
        guidance_resp = None
        if gap_result and gap_result.gap_score > 0.0:
            from app.core.config import get_settings
            settings = get_settings()
            use_llm = getattr(settings, "VISUAL_GUIDANCE_USE_LLM", False)

            if use_llm and gap_result.gap_score > 0.40:
                guidance_resp = await guidance_mod.generate_ai_guidance(
                    gap_result,
                    ocr_text=analysis.ocr_text or "",
                    caption=analysis.caption or "",
                )
            else:
                guidance_resp = guidance_mod.generate_rule_guidance(gap_result)

        return {
            "screenshot": screenshot,
            "analysis": analysis,
            "gap_result": gap_result.model_dump() if gap_result else None,
            "ui_state": ui_state,
            "guidance": guidance_resp.model_dump() if guidance_resp else None,
        }

    # ══════════════════════════════════════════════════════
    #  Reference Screens
    # ══════════════════════════════════════════════════════

    async def create_reference(
        self,
        payload: ReferenceScreenCreate,
        image_bytes: bytes,
        filename: str,
    ) -> ReferenceScreen:
        """Create a reference screen with image and visual embedding."""
        file_path, _ = save_screenshot(
            image_bytes,
            filename=filename,
            conversation_id="_references",
        )

        # Get embedding
        provider = get_visual_provider()
        embedding = await provider.encode_embedding(image_bytes)

        ref = ReferenceScreen(
            name=payload.name,
            description=payload.description,
            screen_key=payload.screen_key,
            file_path=file_path,
            embedding=embedding,
            expected_elements=payload.expected_elements,
            expected_ocr_text=payload.expected_ocr_text,
        )
        self.db.add(ref)
        await self.db.flush()
        await self.db.refresh(ref)
        return ref

    async def get_reference(self, ref_id: uuid.UUID) -> Optional[ReferenceScreen]:
        """Get reference screen by ID."""
        result = await self.db.execute(
            select(ReferenceScreen).where(ReferenceScreen.id == ref_id)
        )
        return result.scalar_one_or_none()

    async def list_references(
        self,
        limit: int = 50,
        offset: int = 0,
    ) -> tuple[list[ReferenceScreen], int]:
        """List all reference screens."""
        stmt = (
            select(ReferenceScreen)
            .order_by(ReferenceScreen.created_at.desc())
            .offset(offset).limit(limit)
        )
        count_stmt = select(func.count(ReferenceScreen.id))

        result = await self.db.execute(stmt)
        count_result = await self.db.execute(count_stmt)

        return list(result.scalars().all()), count_result.scalar_one()

    async def delete_reference(self, ref_id: uuid.UUID) -> bool:
        """Delete a reference screen."""
        ref = await self.get_reference(ref_id)
        if not ref:
            return False
        await self.db.delete(ref)
        await self.db.flush()
        return True

    # ══════════════════════════════════════════════════════
    #  Timeline
    # ══════════════════════════════════════════════════════

    async def get_timeline(
        self,
        conversation_id: uuid.UUID,
        limit: int = 100,
        offset: int = 0,
    ) -> TimelineResponse:
        """Get conversation UI timeline."""
        return await timeline_mod.get_timeline(
            self.db, conversation_id, limit=limit, offset=offset,
        )

    # ══════════════════════════════════════════════════════
    #  Guidance (standalone)
    # ══════════════════════════════════════════════════════

    async def generate_guidance(
        self,
        analysis_id: uuid.UUID,
        reference_key: Optional[str] = None,
    ) -> GuidanceResponse:
        """
        Generate guidance for a specific analysis result,
        optionally compared against a reference screen.
        """
        analysis = await self.get_analysis(analysis_id)
        if not analysis:
            raise ValueError(f"Analysis {analysis_id} not found")

        # Run gap detection if reference provided
        gap_result = None
        if reference_key:
            gap_result = await self.detect_gap_for_analysis(
                analysis.id, reference_key=reference_key,
            )

        if not gap_result:
            # No reference — provide basic guidance from analysis alone
            from app.visual_ai.schemas import GapDiff
            from app.visual_ai.enums import GapSeverity

            # Check for errors in detected elements
            has_error = any(
                e.get("element_type") == "ERROR_MESSAGE"
                for e in (analysis.elements or [])
            )
            if has_error:
                gap_result = GapResult(
                    gap_score=0.5,
                    severity=GapSeverity.SIGNIFICANT,
                    diffs=GapDiff(error_penalty=1.0),
                    guidance_hints=["An error is visible on the screen."],
                )
            else:
                gap_result = GapResult(
                    gap_score=0.0,
                    severity=GapSeverity.NO_GAP,
                    diffs=GapDiff(),
                    guidance_hints=["Screen appears normal."],
                )

        from app.core.config import get_settings
        settings = get_settings()
        use_llm = getattr(settings, "VISUAL_GUIDANCE_USE_LLM", False)

        if use_llm and gap_result.gap_score > 0.40:
            return await guidance_mod.generate_ai_guidance(
                gap_result,
                ocr_text=analysis.ocr_text or "",
                caption=analysis.caption or "",
            )

        return guidance_mod.generate_rule_guidance(gap_result)

    # ══════════════════════════════════════════════════════
    #  Screenshare Assistance (low-FPS frame sequence)
    # ══════════════════════════════════════════════════════

    @staticmethod
    def sample_frames_low_fps(
        frames: list[tuple[bytes, str]],
        source_fps: float,
        target_fps: float,
        max_frames: int,
    ) -> list[tuple[bytes, str]]:
        """Downsample a frame list to target FPS and cap processed frame count."""
        if not frames:
            return []

        src = max(source_fps, 0.1)
        tgt = max(target_fps, 0.1)
        stride = max(1, int(round(src / tgt)))

        sampled = frames[::stride]
        if len(sampled) > max_frames:
            sampled = sampled[:max_frames]

        return sampled

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
        """Analyze low-FPS sampled screenshare frames for guidance support."""
        from app.core.config import get_settings

        settings = get_settings()
        if not frames:
            raise ValueError("No frames were provided")

        sampled = self.sample_frames_low_fps(
            frames,
            source_fps=source_fps,
            target_fps=target_fps,
            max_frames=max(1, int(settings.VISUAL_SCREENSHARE_MAX_FRAMES)),
        )
        if not sampled:
            raise ValueError("No frames remained after low-FPS sampling")

        use_gemini = (
            settings.VISUAL_SCREENSHARE_USE_GEMINI_EMBEDDINGS
            if use_gemini_embeddings is None
            else bool(use_gemini_embeddings)
        )
        provider_step_timeout_seconds = max(
            0.5,
            float(getattr(settings, "VISUAL_SCREENSHARE_PROVIDER_STEP_TIMEOUT_SECONDS", 8.0)),
        )
        provider_embedding_timeout_seconds = max(
            0.5,
            float(
                getattr(
                    settings,
                    "VISUAL_SCREENSHARE_PROVIDER_EMBEDDING_TIMEOUT_SECONDS",
                    provider_step_timeout_seconds,
                )
            ),
        )

        provider = get_visual_provider(provider_name)
        vectors: list[list[float]] = []
        embedding_backend = provider.provider_name
        gemini_failed = False
        skip_embeddings_for_snapshot = (
            len(frames) == 1
            and len(sampled) == 1
            and not reference_key
            and provider_name is None
        )
        needs_embeddings = not skip_embeddings_for_snapshot

        if needs_embeddings:
            if use_gemini:
                try:
                    for image_bytes, mime_type in sampled:
                        vec = embed_image_with_gemini(
                            image_bytes,
                            mime_type=mime_type,
                            output_dimensionality=settings.VISUAL_SCREENSHARE_EMBEDDING_DIMENSION,
                        )
                        vectors.append(vec)
                    embedding_backend = "gemini"
                except Exception as exc:
                    gemini_failed = True
                    vectors = []
                    logger.warning(
                        "Gemini screenshare embeddings failed; falling back to %s embeddings: %s",
                        provider.provider_name,
                        exc,
                    )

            if not vectors:
                for idx, (image_bytes, _mime_type) in enumerate(sampled, start=1):
                    try:
                        vec = await asyncio.wait_for(
                            provider.encode_embedding(image_bytes),
                            timeout=provider_embedding_timeout_seconds,
                        )
                    except asyncio.TimeoutError:
                        logger.warning(
                            "Provider embeddings timed out for sampled frame %s/%s after %.1fs",
                            idx,
                            len(sampled),
                            provider_embedding_timeout_seconds,
                        )
                        continue
                    except Exception as exc:
                        logger.warning(
                            "Provider embeddings failed for sampled frame %s/%s: %s",
                            idx,
                            len(sampled),
                            exc,
                        )
                        continue
                    vectors.append(vec)

            if use_gemini and gemini_failed and vectors:
                embedding_backend = f"{provider.provider_name} (gemini-fallback)"
            elif not vectors:
                embedding_backend = "unavailable"
        else:
            embedding_backend = "skipped-single-frame"

        transition_scores: list[float] = []
        for i in range(1, len(vectors)):
            transition_scores.append(1.0 - _cosine(vectors[i - 1], vectors[i]))

        avg_transition = float(np.mean(transition_scores)) if transition_scores else 0.0
        max_transition = float(np.max(transition_scores)) if transition_scores else 0.0

        # Run final-frame OCR/UI analysis for actionable hints.
        # Avoid forcing a full embedding pass here so realtime chunks remain resilient
        # even when local embedding backends are unavailable.
        final_bytes, _final_mime = sampled[-1]
        final_ocr_text = ""
        final_caption = ""
        final_element_count = 0
        final_labels: list[str] = []

        try:
            final_ocr = await asyncio.wait_for(
                provider.extract_ocr(final_bytes),
                timeout=provider_step_timeout_seconds,
            )
            final_ocr_text = (final_ocr.text or "").strip()
        except asyncio.TimeoutError:
            logger.warning(
                "Final-frame OCR timed out during screenshare analysis after %.1fs",
                provider_step_timeout_seconds,
            )
        except Exception as exc:
            logger.warning("Final-frame OCR failed during screenshare analysis: %s", exc)

        try:
            final_ui = await asyncio.wait_for(
                provider.analyze_ui(final_bytes),
                timeout=provider_step_timeout_seconds,
            )
            final_caption = (final_ui.caption or "").strip()
            final_element_count = len(final_ui.elements or [])
            final_labels = final_ui.labels or []
        except asyncio.TimeoutError:
            logger.warning(
                "Final-frame UI analysis timed out during screenshare analysis after %.1fs",
                provider_step_timeout_seconds,
            )
        except Exception as exc:
            logger.warning("Final-frame UI analysis failed during screenshare analysis: %s", exc)

        aggregate = _mean_embedding(vectors)

        ref_similarity = None
        if reference_key:
            ref = await self._get_reference(reference_key=reference_key)
            if ref and ref.embedding is not None and aggregate:
                ref_vec = list(ref.embedding)
                if len(ref_vec) == len(aggregate):
                    ref_similarity = _cosine(aggregate, ref_vec)

        assistance = _build_screenshare_assistance_hints(
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
        if needs_embeddings and not vectors:
            assistance.insert(
                0,
                "Embedding analysis is temporarily unavailable right now, but live OCR and UI hints are still provided.",
            )
        elif needs_embeddings and use_gemini and gemini_failed:
            assistance.insert(
                0,
                "Gemini embeddings were temporarily unavailable, so fallback embeddings were used to keep analysis running.",
            )

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
            "assistance_hints": assistance,
        }
