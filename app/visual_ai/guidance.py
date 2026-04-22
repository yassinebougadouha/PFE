"""
Adaptive Guidance Engine — generates contextual help based on gap detection.

Two tiers:
  1. Rule-based guidance — deterministic templates for common gap patterns
  2. AI-enhanced guidance — optional LLM call for complex/nuanced situations

Rule templates are keyed by detected gap conditions:
  - ERROR_DETECTED   — error message visible on screen
  - WRONG_PAGE       — very low visual similarity to reference
  - MISSING_ELEMENT  — expected UI elements not found
  - LOADING_STATE    — page still loading
  - SUCCESS_STATE    — screen matches reference (no gap)
  - TEXT_MISMATCH    — text significantly differs from expected
"""

from __future__ import annotations

import logging
from typing import Optional

from app.visual_ai.enums import GapSeverity
from app.visual_ai.schemas import GapResult, GuidanceResponse

logger = logging.getLogger(__name__)

# ── Rule Templates ────────────────────────────────────────

_TEMPLATES: dict[str, str] = {
    "ERROR_DETECTED": (
        "An error is displayed on your screen. "
        "Please read the error message carefully. Common fixes:\n"
        "• Check your input for typos or missing fields\n"
        "• Refresh the page and try again\n"
        "• If the error persists, take note of the error code and contact support"
    ),
    "WRONG_PAGE": (
        "It looks like you're on a different page than expected. "
        "Please navigate to the correct page:\n"
        "• Use the navigation menu/sidebar to find the right section\n"
        "• Check if you were redirected due to a session timeout\n"
        "• Try using the browser's back button to return to the previous page"
    ),
    "MISSING_ELEMENT": (
        "Some expected elements are missing from your screen. This could mean:\n"
        "• The page hasn't fully loaded — wait a moment and refresh\n"
        "• You may not have the right permissions to see certain elements\n"
        "• The page layout may have changed — scroll down to look for the missing items"
    ),
    "LOADING_STATE": (
        "The page appears to still be loading. Please:\n"
        "• Wait a few seconds for the page to fully load\n"
        "• Check your internet connection\n"
        "• If loading takes too long, try refreshing the page"
    ),
    "SUCCESS_STATE": (
        "Your screen matches what's expected — you're on the right track! "
        "Continue with the next step in the process."
    ),
    "TEXT_MISMATCH": (
        "The text on your screen differs from what's expected. "
        "This could indicate:\n"
        "• You may be viewing different data or a different version\n"
        "• The content may have been updated since the reference was created\n"
        "• Double-check that you're looking at the correct item/record"
    ),
}


def _determine_condition(gap_result: GapResult) -> str:
    """Determine the primary condition from gap detection results."""
    diffs = gap_result.diffs

    if gap_result.severity == GapSeverity.NO_GAP:
        return "SUCCESS_STATE"

    if diffs.error_penalty >= 1.0:
        return "ERROR_DETECTED"

    if diffs.error_penalty >= 0.5:
        return "LOADING_STATE"

    if diffs.visual_similarity < 0.50:
        return "WRONG_PAGE"

    if diffs.missing_elements:
        return "MISSING_ELEMENT"

    if diffs.text_diff_ratio > 0.5:
        return "TEXT_MISMATCH"

    # Default to the most relevant based on highest weight contribution
    return "MISSING_ELEMENT" if diffs.element_diff_ratio > diffs.text_diff_ratio else "TEXT_MISMATCH"


def _build_suggested_actions(gap_result: GapResult, condition: str) -> list[str]:
    """Build a list of concrete suggested actions."""
    actions: list[str] = []

    if condition == "SUCCESS_STATE":
        actions.append("Continue to the next step")
        return actions

    if condition == "ERROR_DETECTED":
        actions.append("Read the error message on screen")
        actions.append("Fix the issue indicated by the error")
        actions.append("Retry the action after fixing")

    if condition == "WRONG_PAGE":
        actions.append("Navigate to the correct page")
        actions.append("Check the URL or page title")

    if condition == "LOADING_STATE":
        actions.append("Wait for the page to finish loading")
        actions.append("Refresh the page if it takes too long")

    if condition == "MISSING_ELEMENT":
        diffs = gap_result.diffs
        for elem in diffs.missing_elements[:3]:
            actions.append(f"Look for: {elem}")
        actions.append("Scroll down to check if elements are below the fold")

    if condition == "TEXT_MISMATCH":
        actions.append("Verify you're viewing the correct page/record")

    return actions


def generate_rule_guidance(gap_result: GapResult) -> GuidanceResponse:
    """
    Generate rule-based guidance from gap detection results.
    This is fast, deterministic, and requires no LLM call.
    """
    condition = _determine_condition(gap_result)
    template = _TEMPLATES.get(condition, _TEMPLATES["MISSING_ELEMENT"])
    actions = _build_suggested_actions(gap_result, condition)

    # Add any hints from gap detector itself
    if gap_result.guidance_hints:
        for hint in gap_result.guidance_hints:
            if hint not in actions:
                actions.append(hint)

    return GuidanceResponse(
        rule_based_guidance=template,
        suggested_actions=actions,
        gap_result=gap_result,
        confidence=0.7 if gap_result.severity != GapSeverity.NO_GAP else 0.95,
    )


async def generate_ai_guidance(
    gap_result: GapResult,
    ocr_text: str = "",
    caption: str = "",
    conversation_context: str = "",
) -> GuidanceResponse:
    """
    Generate AI-enhanced guidance using an LLM for complex situations.

    Combines rule-based guidance with an LLM call that has full context
    about the gap, the screen content, and the conversation history.

    Only called when gap_score > threshold and LLM guidance is enabled.
    """
    # Start with rule-based response
    response = generate_rule_guidance(gap_result)

    # Build LLM prompt with full context
    prompt = _build_guidance_prompt(gap_result, ocr_text, caption, conversation_context)

    try:
        ai_text = await _call_llm_for_guidance(prompt)
        if ai_text:
            response.ai_enhanced_guidance = ai_text
            response.confidence = min(response.confidence + 0.15, 0.98)
    except Exception as e:
        logger.warning("AI guidance enhancement failed: %s", e)
        # Rule-based guidance is still returned

    return response


def _build_guidance_prompt(
    gap_result: GapResult,
    ocr_text: str,
    caption: str,
    conversation_context: str,
) -> str:
    """Build the LLM prompt for guidance generation."""
    parts = [
        "You are a helpful customer support assistant analyzing a user's screen.",
        "",
        f"Screen Caption: {caption}" if caption else "",
        f"Screen Text (OCR): {ocr_text[:500]}" if ocr_text else "",
        "",
        f"Gap Analysis:",
        f"  - Gap Score: {gap_result.gap_score:.2f} ({gap_result.severity.value})",
        f"  - Visual Similarity: {gap_result.diffs.visual_similarity:.2f}",
        f"  - Text Difference: {gap_result.diffs.text_diff_ratio:.2f}",
        f"  - Element Difference: {gap_result.diffs.element_diff_ratio:.2f}",
    ]

    if gap_result.diffs.missing_elements:
        parts.append(f"  - Missing Elements: {', '.join(gap_result.diffs.missing_elements)}")
    if gap_result.diffs.missing_keywords:
        parts.append(f"  - Missing Keywords: {', '.join(gap_result.diffs.missing_keywords[:5])}")

    if conversation_context:
        parts.append(f"\nConversation Context: {conversation_context[:300]}")

    parts.extend([
        "",
        "Based on this analysis, provide specific, actionable guidance to help the user.",
        "Be concise, friendly, and focus on the most important next step.",
        "Response should be 2-4 sentences maximum.",
    ])

    return "\n".join(parts)


async def _call_llm_for_guidance(prompt: str) -> Optional[str]:
    """Call the configured LLM provider for guidance text."""
    from app.core.config import get_settings
    settings = get_settings()

    # Use Gemini if available (already configured for visual tasks)
    if settings.current_gemini_key:
        import httpx
        payload = {
            "contents": [{"parts": [{"text": prompt}]}],
            "generationConfig": {"temperature": 0.3, "maxOutputTokens": 300},
        }
        async with httpx.AsyncClient(timeout=15.0) as client:
            resp = await client.post(
                f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={settings.current_gemini_key}",
                json=payload,
            )
            resp.raise_for_status()
            data = resp.json()
            return data["candidates"][0]["content"]["parts"][0]["text"].strip()

    logger.info("No LLM provider available for AI guidance enhancement")
    return None
