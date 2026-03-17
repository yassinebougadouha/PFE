"""
Gap Detection Engine — compares observed screenshot analysis against a reference screen.

4-step pipeline:
  1. Visual similarity  — cosine similarity of CLIP embeddings (weight 0.40)
  2. Text diff          — compare OCR text via SequenceMatcher (weight 0.30)
  3. Element diff       — compare detected UI element sets (weight 0.20)
  4. Error penalty      — add penalty if error/warning elements detected (weight 0.10)

gap_score ∈ [0, 1]  →  higher = bigger gap
  NO_GAP       < 0.15
  MINOR        0.15 – 0.40
  SIGNIFICANT  0.40 – 0.70
  CRITICAL     0.70 – 1.00
"""

from __future__ import annotations

import difflib
import logging
from typing import Optional

from app.visual_ai.enums import GapSeverity, UIElementType
from app.visual_ai.schemas import (
    FullAnalysisResult, GapDiff, GapResult,
)

logger = logging.getLogger(__name__)

# ── Weights ───────────────────────────────────────────────
W_VISUAL = 0.40
W_TEXT = 0.30
W_ELEMENT = 0.20
W_ERROR = 0.10

# ── Severity thresholds ──────────────────────────────────
THRESHOLD_MINOR = 0.15
THRESHOLD_SIGNIFICANT = 0.40
THRESHOLD_CRITICAL = 0.70

# ── Error-related element types ──────────────────────────
_ERROR_TYPES = {UIElementType.ERROR_MESSAGE, UIElementType.LOADING_STATE}


def _cosine_similarity(a: list[float], b: list[float]) -> float:
    """Compute cosine similarity between two vectors."""
    if not a or not b or len(a) != len(b):
        return 0.0
    dot = sum(x * y for x, y in zip(a, b))
    norm_a = sum(x * x for x in a) ** 0.5
    norm_b = sum(x * x for x in b) ** 0.5
    if norm_a == 0 or norm_b == 0:
        return 0.0
    return dot / (norm_a * norm_b)


def _text_diff_ratio(observed: str, expected: str) -> float:
    """
    Compute text difference as 1 − SequenceMatcher.ratio().
    0 = identical, 1 = completely different.
    """
    if not expected and not observed:
        return 0.0
    if not expected or not observed:
        return 1.0
    ratio = difflib.SequenceMatcher(None, observed.lower(), expected.lower()).ratio()
    return 1.0 - ratio


def _element_diff(
    observed_elements: list[dict],
    expected_elements: list[dict],
) -> tuple[float, list[str], list[str]]:
    """
    Compare observed vs expected UI element sets.
    Returns (diff_ratio, missing_elements, extra_elements).
    """
    observed_types = {e.get("element_type") or e.get("type", "") for e in observed_elements}
    expected_types = {e.get("element_type") or e.get("type", "") for e in expected_elements}

    missing = expected_types - observed_types
    extra = observed_types - expected_types

    union = expected_types | observed_types
    if not union:
        return 0.0, [], []

    symmetric_diff = len(missing) + len(extra)
    ratio = symmetric_diff / len(union) if union else 0.0
    return min(ratio, 1.0), list(missing), list(extra)


def _error_penalty(observed_elements: list[dict]) -> float:
    """
    Returns a penalty ∈ [0, 1] based on presence of error-like elements.
    - ERROR_MESSAGE found  → 1.0
    - LOADING_STATE found  → 0.5
    - otherwise            → 0.0
    """
    for e in observed_elements:
        etype = e.get("element_type") or e.get("type", "")
        if etype == UIElementType.ERROR_MESSAGE.value or etype == UIElementType.ERROR_MESSAGE:
            return 1.0
    for e in observed_elements:
        etype = e.get("element_type") or e.get("type", "")
        if etype == UIElementType.LOADING_STATE.value or etype == UIElementType.LOADING_STATE:
            return 0.5
    return 0.0


def _text_keyword_diff(observed_text: str, expected_text: str) -> tuple[list[str], list[str]]:
    """Find missing / unexpected keywords between texts."""
    obs_words = set(observed_text.lower().split()) if observed_text else set()
    exp_words = set(expected_text.lower().split()) if expected_text else set()

    # Only consider "meaningful" words (len > 3) to reduce noise
    obs_meaningful = {w for w in obs_words if len(w) > 3}
    exp_meaningful = {w for w in exp_words if len(w) > 3}

    missing = sorted(exp_meaningful - obs_meaningful)
    unexpected = sorted(obs_meaningful - exp_meaningful)
    return missing, unexpected


def _severity_from_score(score: float) -> GapSeverity:
    """Map gap score to severity enum."""
    if score < THRESHOLD_MINOR:
        return GapSeverity.NO_GAP
    if score < THRESHOLD_SIGNIFICANT:
        return GapSeverity.MINOR
    if score < THRESHOLD_CRITICAL:
        return GapSeverity.SIGNIFICANT
    return GapSeverity.CRITICAL


def _generate_hints(diffs: GapDiff, severity: GapSeverity) -> list[str]:
    """Generate human-readable guidance hints from gap diffs."""
    hints: list[str] = []

    if severity == GapSeverity.NO_GAP:
        hints.append("Screen matches the expected reference.")
        return hints

    if diffs.error_penalty > 0.5:
        hints.append("An error message is visible on screen. Please read and address it.")

    if diffs.visual_similarity < 0.70:
        hints.append("The screen looks very different from what's expected. You may be on the wrong page.")

    if diffs.missing_elements:
        hints.append(f"Missing expected UI elements: {', '.join(diffs.missing_elements)}")

    if diffs.extra_elements:
        hints.append(f"Unexpected UI elements found: {', '.join(diffs.extra_elements)}")

    if diffs.missing_keywords:
        top = diffs.missing_keywords[:5]
        hints.append(f"Expected text not found: {', '.join(top)}")

    if diffs.text_diff_ratio > 0.5:
        hints.append("The text content on screen differs significantly from expected.")

    return hints


def detect_gap(
    analysis: FullAnalysisResult,
    reference_embedding: Optional[list[float]] = None,
    reference_ocr_text: Optional[str] = None,
    reference_elements: Optional[list[dict]] = None,
) -> GapResult:
    """
    Run the 4-step gap detection pipeline.

    Parameters
    ----------
    analysis : FullAnalysisResult
        The observed screenshot analysis.
    reference_embedding : list[float] | None
        CLIP embedding of the reference/expected screen.
    reference_ocr_text : str | None
        Expected OCR text for the reference screen.
    reference_elements : list[dict] | None
        Expected UI elements for the reference screen.

    Returns
    -------
    GapResult with gap_score, severity, diffs, guidance_hints.
    """
    ref_embed = reference_embedding or []
    ref_text = reference_ocr_text or ""
    ref_elems = reference_elements or []

    obs_embed = analysis.embedding or []
    obs_text = analysis.ocr.text if analysis.ocr else ""
    obs_elems = [e.model_dump() for e in analysis.ui_analysis.elements] if analysis.ui_analysis else []

    # ── Step 1: Visual similarity ─────────────────────────
    if ref_embed and obs_embed:
        visual_sim = _cosine_similarity(obs_embed, ref_embed)
    else:
        visual_sim = 1.0  # no reference → assume match
    visual_gap = 1.0 - visual_sim

    # ── Step 2: Text diff ─────────────────────────────────
    text_gap = _text_diff_ratio(obs_text, ref_text)

    # ── Step 3: Element diff ──────────────────────────────
    elem_gap, missing_elems, extra_elems = _element_diff(obs_elems, ref_elems)

    # ── Step 4: Error penalty ─────────────────────────────
    err_penalty = _error_penalty(obs_elems)

    # ── Keyword analysis ──────────────────────────────────
    missing_kw, unexpected_kw = _text_keyword_diff(obs_text, ref_text)

    # ── Weighted score ────────────────────────────────────
    gap_score = (
        W_VISUAL * visual_gap
        + W_TEXT * text_gap
        + W_ELEMENT * elem_gap
        + W_ERROR * err_penalty
    )
    gap_score = min(max(gap_score, 0.0), 1.0)

    severity = _severity_from_score(gap_score)

    diffs = GapDiff(
        visual_similarity=round(visual_sim, 4),
        text_diff_ratio=round(text_gap, 4),
        element_diff_ratio=round(elem_gap, 4),
        error_penalty=round(err_penalty, 4),
        missing_keywords=missing_kw,
        unexpected_keywords=unexpected_kw,
        missing_elements=missing_elems,
        extra_elements=extra_elems,
    )

    hints = _generate_hints(diffs, severity)

    return GapResult(
        gap_score=round(gap_score, 4),
        severity=severity,
        diffs=diffs,
        guidance_hints=hints,
    )
