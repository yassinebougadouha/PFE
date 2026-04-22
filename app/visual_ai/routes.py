"""
API routes for the Visual AI module.

Prefix: /visual-ai
Tag: Visual AI

Endpoints:
  Screenshots:   POST /upload, GET /{id}, GET /list, DELETE /{id}
  Analysis:      POST /{id}/analyze, POST /analyze-raw, GET /analysis/{id}
  Gap Detection: POST /analysis/{id}/detect-gap
  References:    POST /references, GET /references, GET /references/{id}, DELETE /references/{id}
  Timeline:      GET /timeline/{conversation_id}
  Guidance:      POST /analysis/{id}/guidance
  Pipeline:      POST /process (full capture→analyze→gap→guide)
"""

from __future__ import annotations

import logging
import uuid
from datetime import datetime, timezone
from typing import Any, Annotated, Optional

from fastapi import (
    APIRouter, Depends, HTTPException, Query,
    UploadFile, File, Form, status,
)
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.api.deps import require_agent_or_admin, require_admin, require_any_authenticated

from app.visual_ai.schemas import (
    ScreenshotResponse,
    AnalysisResponse,
    AnalyzeRequest,
    GapDetectRequest,
    GapResult,
    ReferenceScreenCreate,
    ReferenceScreenResponse,
    ReferenceScreenListResponse,
    TimelineResponse,
    GuidanceRequest,
    GuidanceResponse,
    ScreenShareAssistResponse,
    ScreenShareRealtimeChunkResponse,
    TroubleshootingWizardRequest,
    TroubleshootingWizardResponse,
    TroubleshootingWizardStep,
)
from app.schemas.support_call_screen_context import SupportCallScreenContextIngestRequest
from app.services.support_call_screen_context import support_call_screen_context_store
from app.visual_ai.service import VisualAIService
from app.visual_ai.video_frames import extract_frames_from_video_bytes

router = APIRouter(prefix="/visual-ai", tags=["Visual AI"])
logger = logging.getLogger(__name__)

# ── Type aliases ──────────────────────────────────────────
DB = Annotated[AsyncSession, Depends(get_db)]
AnyUser = Annotated[User, Depends(require_any_authenticated)]
AgentOrAdmin = Annotated[User, Depends(require_agent_or_admin)]
Admin = Annotated[User, Depends(require_admin)]


def _caption_suggests_visible_content(caption: str | None) -> bool:
    normalized = (caption or "").strip().lower()
    if not normalized:
        return False

    low_signal_markers = [
        "black image",
        "blank image",
        "blank screen",
        "nothing visible",
        "no visible content",
        "no discernible content",
        "fully obscured",
        "entirely obscured",
    ]
    return not any(marker in normalized for marker in low_signal_markers)


def _get_preferred_screen_analysis_hint(
    hints: list[str] | None,
    caption: str | None,
) -> str:
    if not hints:
        return ""

    low_signal_frame_warning = next(
        (
            hint
            for hint in hints
            if (hint or "").strip().lower().startswith("the shared frame looks blank")
            or (hint or "").strip().lower().startswith("the shared frame looks unreadable")
        ),
        None,
    )

    preferred_hint = next(
        (
            hint
            for hint in hints
            if (hint or "").strip()
            and not (hint or "").strip().lower().startswith("processed ")
            and not (hint or "").strip().lower().startswith("average ui transition score:")
            and (hint or "").strip().lower() != (low_signal_frame_warning or "").strip().lower()
        ),
        None,
    )

    if preferred_hint:
        return preferred_hint.strip()

    if low_signal_frame_warning and _caption_suggests_visible_content(caption):
        return ""

    return (hints[0] or "").strip()


def _build_support_call_analysis_text(result: dict[str, Any]) -> tuple[str, str | None, list[str]]:
    final_frame = result.get("final_frame") or {}
    caption = (final_frame.get("caption") or "").strip() or None
    hints = [
        hint.strip()
        for hint in (result.get("assistance_hints") or [])
        if isinstance(hint, str) and hint.strip()
    ]
    preferred_hint = _get_preferred_screen_analysis_hint(hints, caption)

    if caption:
        summary = f"The user is doing: {caption}"
    else:
        summary = "The user is interacting with the app interface."

    analysis_text = f"{summary} {preferred_hint}".strip() if preferred_hint else summary
    return analysis_text, caption, hints


def _maybe_publish_support_call_context(
    *,
    room_name: str | None,
    result: dict[str, Any],
    capture_mode: str,
    frame_number: int | None,
    chunk_index: int | None,
) -> None:
    if not isinstance(room_name, str):
        return

    normalized_room = room_name.strip()
    if not normalized_room:
        return

    analysis_text, caption, hints = _build_support_call_analysis_text(result)
    if not analysis_text.strip():
        return

    payload = SupportCallScreenContextIngestRequest(
        analysis_text=analysis_text,
        caption=caption,
        assistance_hints=hints,
        frame_number=frame_number,
        capture_mode=capture_mode,
        recorded_at=datetime.now(timezone.utc),
        session_id=normalized_room,
        chunk_index=chunk_index,
    )
    support_call_screen_context_store.upsert(normalized_room, payload)


def _compact_line(text: str | None, *, default: str = "", limit: int = 240) -> str:
    compact = " ".join((text or "").split()).strip()
    if not compact:
        compact = default
    if len(compact) <= limit:
        return compact
    return compact[: limit - 3].rstrip() + "..."


def _clean_string_list(values: list[str] | None, *, max_items: int = 8) -> list[str]:
    if not values:
        return []

    cleaned: list[str] = []
    for value in values:
        item = _compact_line(value, default="", limit=180)
        if not item:
            continue
        if item in cleaned:
            continue
        cleaned.append(item)
        if len(cleaned) >= max_items:
            break
    return cleaned


def _infer_wizard_risk_level(*, issue_summary: str, observed_text: str, attempted_actions: list[str]) -> str:
    signal = " ".join([issue_summary, observed_text, " ".join(attempted_actions)]).lower()

    high_markers = {
        "error",
        "exception",
        "fail",
        "failed",
        "timeout",
        "denied",
        "forbidden",
        "payment declined",
        "security",
        "locked",
    }
    if any(marker in signal for marker in high_markers):
        return "high"

    medium_markers = {
        "not working",
        "stuck",
        "cannot",
        "unable",
        "incorrect",
        "mismatch",
    }
    if len(attempted_actions) >= 2 or any(marker in signal for marker in medium_markers):
        return "medium"

    return "low"


def _build_troubleshooting_steps(
    *,
    issue_summary: str,
    goal: str,
    observed_caption: str,
    attempted_actions: list[str],
    context_hints: list[str],
    max_steps: int,
) -> list[TroubleshootingWizardStep]:
    attempted_text = ", ".join(attempted_actions) if attempted_actions else "no prior actions captured"
    hints_text = ", ".join(context_hints[:3]) if context_hints else "no extra context hints"

    steps_data = [
        {
            "title": "Align on the exact target flow",
            "why": "Misaligned goals are a common source of repeated troubleshooting loops.",
            "instructions": [
                f"Confirm the user goal in one sentence: {goal}.",
                f"Restate the observed issue: {issue_summary}.",
                "Keep the user on the relevant page before trying fixes.",
            ],
            "expected_signal": "Agent and user agree on one reproducible issue path.",
            "if_not_seen": "Pause remediation and clarify the expected outcome before continuing.",
        },
        {
            "title": "Validate visible UI state",
            "why": "Current screen state determines whether remediation should target navigation, data entry, or backend state.",
            "instructions": [
                f"Use caption evidence: {observed_caption}.",
                f"Cross-check with context hints: {hints_text}.",
                "Ask the user to keep the problem area visible while you inspect cues.",
            ],
            "expected_signal": "UI cues match the stage where the issue is expected to occur.",
            "if_not_seen": "Navigate back to the last confirmed good step and re-open this flow.",
        },
        {
            "title": "Verify prerequisites before retry",
            "why": "Many failures come from missing permissions, stale sessions, or incomplete required fields.",
            "instructions": [
                "Check account/session status and required input fields.",
                "Confirm environment assumptions (role, workspace, selected item).",
                f"Record what has already been attempted: {attempted_text}.",
            ],
            "expected_signal": "All prerequisites are validated and documented.",
            "if_not_seen": "Fix prerequisite gaps first, then retry the user flow.",
        },
        {
            "title": "Apply the smallest corrective action",
            "why": "Small, isolated fixes reduce side effects and make root cause easier to confirm.",
            "instructions": [
                "Perform one controlled fix (refresh, field correction, or targeted configuration update).",
                "Avoid batching multiple changes in a single attempt.",
                "Immediately retry the failing step after each change.",
            ],
            "expected_signal": "Issue either resolves or changes in a way that narrows the diagnosis.",
            "if_not_seen": "Roll back to known-good state and escalate with captured evidence.",
        },
        {
            "title": "Confirm outcome and customer impact",
            "why": "A technical fix is only complete when user impact is verified.",
            "instructions": [
                "Ask the user to repeat the original action end-to-end.",
                "Confirm expected result and note any residual friction.",
                "Capture concise proof points for the support record.",
            ],
            "expected_signal": "User can complete the target flow without the original blocker.",
            "if_not_seen": "Create a linked escalation ticket with summary and reproduction notes.",
        },
    ]

    steps: list[TroubleshootingWizardStep] = []
    for index, item in enumerate(steps_data[:max_steps], start=1):
        steps.append(
            TroubleshootingWizardStep(
                step_number=index,
                title=item["title"],
                why=item["why"],
                instructions=item["instructions"],
                expected_signal=item["expected_signal"],
                if_not_seen=item["if_not_seen"],
            )
        )
    return steps


# ═══════════════════════════════════════════════════════════
#  Screenshot Endpoints
# ═══════════════════════════════════════════════════════════

@router.post(
    "/upload",
    response_model=ScreenshotResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Upload a screenshot",
)
async def upload_screenshot(
    db: DB,
    user: AnyUser,
    file: UploadFile = File(..., description="Screenshot image (PNG/JPEG/WebP)"),
    consent: bool = Form(..., description="User must consent to screen capture"),
    conversation_id: Optional[uuid.UUID] = Form(None, description="Conversation ID to link"),
    metadata: Optional[str] = Form(None, description="Optional JSON metadata string"),
):
    """Upload a screenshot and store it on disk."""
    if not consent:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Screenshot capture requires user consent",
        )

    # Validate file type
    allowed_types = {"image/png", "image/jpeg", "image/webp", "image/bmp"}
    if file.content_type not in allowed_types:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Unsupported image type: {file.content_type}. Allowed: {', '.join(allowed_types)}",
        )

    image_bytes = await file.read()
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty file")

    # Parse metadata JSON if provided
    meta = None
    if metadata:
        import json
        try:
            meta = json.loads(metadata)
        except json.JSONDecodeError:
            raise HTTPException(status_code=400, detail="Invalid metadata JSON")

    svc = VisualAIService(db)
    screenshot = await svc.store_screenshot(
        image_bytes=image_bytes,
        filename=file.filename or "screenshot.png",
        mime_type=file.content_type or "image/png",
        consent=consent,
        user_id=user.id,
        conversation_id=conversation_id,
        metadata=meta,
    )
    return screenshot


@router.get(
    "/screenshots/{screenshot_id}",
    response_model=ScreenshotResponse,
    summary="Get screenshot details",
)
async def get_screenshot(screenshot_id: uuid.UUID, db: DB, user: AnyUser):
    """Get screenshot metadata by ID."""
    svc = VisualAIService(db)
    screenshot = await svc.get_screenshot(screenshot_id)
    if not screenshot:
        raise HTTPException(status_code=404, detail="Screenshot not found")
    return screenshot


@router.get(
    "/screenshots",
    summary="List screenshots",
)
async def list_screenshots(
    db: DB,
    user: AgentOrAdmin,
    conversation_id: Optional[uuid.UUID] = Query(None),
    limit: int = Query(50, ge=1, le=200),
    offset: int = Query(0, ge=0),
):
    """List screenshots with optional conversation filter."""
    svc = VisualAIService(db)
    items, total = await svc.list_screenshots(
        conversation_id=conversation_id,
        limit=limit,
        offset=offset,
    )
    return {"items": items, "total": total}


# ═══════════════════════════════════════════════════════════
#  Analysis Endpoints
# ═══════════════════════════════════════════════════════════

@router.post(
    "/screenshots/{screenshot_id}/analyze",
    response_model=AnalysisResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Analyze a stored screenshot",
)
async def analyze_screenshot(
    screenshot_id: uuid.UUID,
    db: DB,
    user: AnyUser,
    payload: Optional[AnalyzeRequest] = None,
):
    """Run visual analysis on a stored screenshot."""
    svc = VisualAIService(db)
    provider_name = payload.provider.value if payload and payload.provider else None

    try:
        analysis = await svc.analyze_screenshot(screenshot_id, provider_name=provider_name)
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))

    return analysis


@router.post(
    "/analyze-raw",
    summary="Analyze raw image without storing",
)
async def analyze_raw(
    db: DB,
    user: AnyUser,
    file: UploadFile = File(..., description="Screenshot image"),
    provider: Optional[str] = Form(None, description="Provider override (Gemini-only mode)"),
):
    """Analyze an uploaded image without persisting it. Returns full analysis result."""
    image_bytes = await file.read()
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty file")

    svc = VisualAIService(db)
    try:
        result = await svc.analyze_raw(image_bytes, provider_name=provider)
    except Exception as exc:
        logger.exception("Visual AI analyze-raw failed: %s", exc)
        raise HTTPException(
            status_code=503,
            detail="Visual analysis provider is temporarily unavailable. Please retry.",
        )

    return result.model_dump()


@router.get(
    "/analysis/{analysis_id}",
    response_model=AnalysisResponse,
    summary="Get analysis result",
)
async def get_analysis(analysis_id: uuid.UUID, db: DB, user: AnyUser):
    """Get a specific analysis result by ID."""
    svc = VisualAIService(db)
    analysis = await svc.get_analysis(analysis_id)
    if not analysis:
        raise HTTPException(status_code=404, detail="Analysis not found")
    return analysis


# ═══════════════════════════════════════════════════════════
#  Gap Detection Endpoints
# ═══════════════════════════════════════════════════════════

@router.post(
    "/analysis/{analysis_id}/detect-gap",
    response_model=GapResult,
    summary="Detect gap between analysis and reference",
)
async def detect_gap_endpoint(
    analysis_id: uuid.UUID,
    payload: GapDetectRequest,
    db: DB,
    user: AnyUser,
):
    """
    Compare an analysis result against a reference screen.
    Returns gap score, severity, diffs, and guidance hints.
    """
    svc = VisualAIService(db)
    try:
        result = await svc.detect_gap_for_analysis(
            analysis_id,
            reference_key=payload.reference_key,
            reference_id=payload.reference_id,
        )
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))

    return result


# ═══════════════════════════════════════════════════════════
#  Reference Screen Endpoints
# ═══════════════════════════════════════════════════════════

@router.post(
    "/references",
    response_model=ReferenceScreenResponse,
    status_code=status.HTTP_201_CREATED,
    summary="Create reference screen",
)
async def create_reference(
    db: DB,
    user: Admin,
    file: UploadFile = File(..., description="Reference screenshot image"),
    name: str = Form(..., min_length=1, max_length=200),
    screen_key: str = Form(..., min_length=1, max_length=100),
    description: Optional[str] = Form(None),
    expected_elements: Optional[str] = Form(None, description="JSON array of expected elements"),
    expected_ocr_text: Optional[str] = Form(None),
):
    """Create a reference screen for gap detection comparison."""
    image_bytes = await file.read()
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty file")

    elems = None
    if expected_elements:
        import json
        try:
            elems = json.loads(expected_elements)
        except json.JSONDecodeError:
            raise HTTPException(status_code=400, detail="Invalid expected_elements JSON")

    payload = ReferenceScreenCreate(
        name=name,
        screen_key=screen_key,
        description=description,
        expected_elements=elems,
        expected_ocr_text=expected_ocr_text,
    )

    svc = VisualAIService(db)
    try:
        ref = await svc.create_reference(payload, image_bytes, file.filename or "reference.png")
    except Exception as e:
        if "unique" in str(e).lower() or "duplicate" in str(e).lower():
            raise HTTPException(status_code=409, detail=f"Reference with screen_key '{screen_key}' already exists")
        raise

    return ref


@router.get(
    "/references",
    response_model=ReferenceScreenListResponse,
    summary="List reference screens",
)
async def list_references(
    db: DB,
    user: AgentOrAdmin,
    limit: int = Query(50, ge=1, le=200),
    offset: int = Query(0, ge=0),
):
    """List all reference screens."""
    svc = VisualAIService(db)
    items, total = await svc.list_references(limit=limit, offset=offset)
    return ReferenceScreenListResponse(items=items, total=total)


@router.get(
    "/references/{ref_id}",
    response_model=ReferenceScreenResponse,
    summary="Get reference screen",
)
async def get_reference(ref_id: uuid.UUID, db: DB, user: AgentOrAdmin):
    """Get reference screen details by ID."""
    svc = VisualAIService(db)
    ref = await svc.get_reference(ref_id)
    if not ref:
        raise HTTPException(status_code=404, detail="Reference screen not found")
    return ref


@router.delete(
    "/references/{ref_id}",
    status_code=status.HTTP_204_NO_CONTENT,
    summary="Delete reference screen",
)
async def delete_reference(ref_id: uuid.UUID, db: DB, user: Admin):
    """Delete a reference screen."""
    svc = VisualAIService(db)
    deleted = await svc.delete_reference(ref_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="Reference screen not found")


# ═══════════════════════════════════════════════════════════
#  Timeline Endpoint
# ═══════════════════════════════════════════════════════════

@router.get(
    "/timeline/{conversation_id}",
    response_model=TimelineResponse,
    summary="Get conversation UI timeline",
)
async def get_timeline(
    conversation_id: uuid.UUID,
    db: DB,
    user: AnyUser,
    limit: int = Query(100, ge=1, le=500),
    offset: int = Query(0, ge=0),
):
    """Get the ordered list of UI states for a conversation."""
    svc = VisualAIService(db)
    return await svc.get_timeline(conversation_id, limit=limit, offset=offset)


# ═══════════════════════════════════════════════════════════
#  Guidance Endpoint
# ═══════════════════════════════════════════════════════════

@router.post(
    "/analysis/{analysis_id}/guidance",
    response_model=GuidanceResponse,
    summary="Generate adaptive guidance",
)
async def generate_guidance(
    analysis_id: uuid.UUID,
    db: DB,
    user: AnyUser,
    payload: Optional[GuidanceRequest] = None,
):
    """
    Generate contextual guidance for an analysis result.
    Optionally compare against a reference screen.
    """
    svc = VisualAIService(db)
    ref_key = payload.reference_key if payload else None

    try:
        guidance = await svc.generate_guidance(analysis_id, reference_key=ref_key)
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))

    return guidance


@router.post(
    "/troubleshooting/wizard",
    response_model=TroubleshootingWizardResponse,
    summary="Generate a step-by-step troubleshooting wizard",
)
async def generate_troubleshooting_wizard(
    payload: TroubleshootingWizardRequest,
    user: AnyUser,
):
    """Generate a guided troubleshooting sequence from current visual context."""
    del user

    issue_summary = _compact_line(payload.issue_summary, default=payload.goal, limit=500)
    observed_caption = _compact_line(
        payload.observed_screen_caption,
        default="Current screen state is partially known.",
        limit=500,
    )
    observed_text = _compact_line(payload.observed_text, default="", limit=1500)
    attempted_actions = _clean_string_list(payload.user_actions_attempted, max_items=6)
    context_hints = _clean_string_list(payload.context_hints, max_items=6)

    risk_level = _infer_wizard_risk_level(
        issue_summary=issue_summary,
        observed_text=observed_text,
        attempted_actions=attempted_actions,
    )
    steps = _build_troubleshooting_steps(
        issue_summary=issue_summary,
        goal=_compact_line(payload.goal, default="Resolve the support issue", limit=240),
        observed_caption=observed_caption,
        attempted_actions=attempted_actions,
        context_hints=context_hints,
        max_steps=payload.max_steps,
    )

    diagnosis = _compact_line(
        f"Likely friction in the '{payload.goal}' workflow. "
        f"Observed state: {observed_caption}. "
        f"Issue focus: {issue_summary}.",
        default="The issue requires guided validation across UI state and user workflow.",
        limit=700,
    )

    escalation_hint = (
        "Escalate with screenshot evidence, observed error text, and attempted actions "
        "if the issue persists after completing the wizard steps."
    )

    estimated_time_minutes = len(steps) * 3
    if risk_level == "medium":
        estimated_time_minutes += 2
    elif risk_level == "high":
        estimated_time_minutes += 4

    return TroubleshootingWizardResponse(
        issue_summary=issue_summary,
        diagnosis=diagnosis,
        risk_level=risk_level,
        estimated_time_minutes=estimated_time_minutes,
        steps=steps,
        escalation_hint=escalation_hint,
    )


# ═══════════════════════════════════════════════════════════
#  Full Pipeline Endpoint
# ═══════════════════════════════════════════════════════════

@router.post(
    "/process",
    status_code=status.HTTP_201_CREATED,
    summary="Full pipeline: upload → analyze → gap detect → guidance",
)
async def process_screenshot(
    db: DB,
    user: AnyUser,
    file: UploadFile = File(..., description="Screenshot image"),
    consent: bool = Form(..., description="User must consent"),
    conversation_id: Optional[uuid.UUID] = Form(None),
    reference_key: Optional[str] = Form(None, description="Reference screen key for gap detection"),
    provider: Optional[str] = Form(None, description="Provider override (Gemini-only mode)"),
    metadata: Optional[str] = Form(None, description="JSON metadata"),
):
    """
    Complete Visual AI pipeline in one call:
    1. Store screenshot
    2. Run analysis
    3. Detect gaps (if reference_key provided)
    4. Add to timeline (if conversation_id provided)
    5. Generate guidance (if gap detected)
    """
    if not consent:
        raise HTTPException(status_code=400, detail="Screenshot capture requires user consent")

    image_bytes = await file.read()
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty file")

    meta = None
    if metadata:
        import json
        try:
            meta = json.loads(metadata)
        except json.JSONDecodeError:
            raise HTTPException(status_code=400, detail="Invalid metadata JSON")

    svc = VisualAIService(db)
    result = await svc.process_screenshot(
        image_bytes=image_bytes,
        filename=file.filename or "screenshot.png",
        mime_type=file.content_type or "image/png",
        consent=consent,
        user_id=user.id,
        conversation_id=conversation_id,
        metadata=meta,
        provider_name=provider,
        reference_key=reference_key,
    )

    # Convert ORM objects in result to dicts for JSON serialization
    response = {}
    if result.get("screenshot"):
        s = result["screenshot"]
        response["screenshot"] = {
            "id": str(s.id),
            "filename": s.filename,
            "file_size": s.file_size,
            "mime_type": s.mime_type,
        }
    if result.get("analysis"):
        a = result["analysis"]
        response["analysis"] = {
            "id": str(a.id),
            "provider": a.provider,
            "ocr_text_preview": (a.ocr_text or "")[:200],
            "caption": a.caption,
            "element_count": len(a.elements or []),
            "processing_ms": a.processing_ms,
        }
    if result.get("gap_result"):
        response["gap_result"] = result["gap_result"]
    if result.get("ui_state"):
        st = result["ui_state"]
        response["ui_state"] = {
            "id": str(st.id),
            "sequence_num": st.sequence_num,
            "gap_detected": st.gap_detected,
        }
    if result.get("guidance"):
        response["guidance"] = result["guidance"]

    return response


@router.post(
    "/screenshare/assist",
    response_model=ScreenShareAssistResponse,
    summary="Screenshare assistance from low-FPS sampled frames",
)
async def screenshare_assist(
    db: DB,
    user: AnyUser,
    frames: list[UploadFile] = File(..., description="Ordered frame images from screen recording"),
    consent: bool = Form(..., description="User must consent to screen recording analysis"),
    source_fps: float = Form(8.0, description="Original capture FPS before downsampling"),
    target_fps: float = Form(1.0, description="Low FPS to process for assistance"),
    provider: Optional[str] = Form(None, description="Provider for OCR/UI analysis"),
    reference_key: Optional[str] = Form(None, description="Optional reference screen key"),
    use_gemini_embeddings: Optional[bool] = Form(None, description="Override embedding toggle for this request"),
    support_call_room_name: Optional[str] = Form(
        None,
        description="Optional support-call room name to publish live context for voice agents",
    ),
    frame_number: Optional[int] = Form(None, ge=1, description="Frame number for voice-agent context publishing"),
    chunk_index: Optional[int] = Form(None, ge=1, description="Chunk/frame sequence number for voice-agent context"),
):
    """Accept frame sequence, downsample to low FPS, embed, and return assistance hints."""
    if not consent:
        raise HTTPException(status_code=400, detail="Screen recording analysis requires user consent")

    if source_fps <= 0 or target_fps <= 0:
        raise HTTPException(status_code=400, detail="source_fps and target_fps must be positive")

    allowed_types = {"image/png", "image/jpeg", "image/webp", "image/bmp"}
    frame_payload: list[tuple[bytes, str]] = []
    for frame in frames:
        mime_type = (frame.content_type or "image/png").lower()
        if mime_type not in allowed_types:
            raise HTTPException(
                status_code=400,
                detail=f"Unsupported frame type: {mime_type}",
            )
        data = await frame.read()
        if not data:
            continue
        frame_payload.append((data, mime_type))

    if not frame_payload:
        raise HTTPException(status_code=400, detail="No non-empty frames provided")

    svc = VisualAIService(db)
    try:
        result = await svc.analyze_screenshare_frames(
            frames=frame_payload,
            source_fps=source_fps,
            target_fps=target_fps,
            provider_name=provider,
            reference_key=reference_key,
            use_gemini_embeddings=use_gemini_embeddings,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    _maybe_publish_support_call_context(
        room_name=support_call_room_name,
        result=result,
        capture_mode="frame",
        frame_number=frame_number or chunk_index,
        chunk_index=chunk_index,
    )

    return result


@router.post(
    "/screenshare/assist-video",
    response_model=ScreenShareAssistResponse,
    summary="Screenshare assistance from a single uploaded video",
)
async def screenshare_assist_video(
    db: DB,
    user: AnyUser,
    file: UploadFile = File(..., description="Screen recording video"),
    consent: bool = Form(..., description="User must consent to screen recording analysis"),
    target_fps: Optional[float] = Form(None, description="Low FPS to process for assistance"),
    provider: Optional[str] = Form(None, description="Provider for OCR/UI analysis"),
    reference_key: Optional[str] = Form(None, description="Optional reference screen key"),
    use_gemini_embeddings: Optional[bool] = Form(None, description="Override embedding toggle for this request"),
    support_call_room_name: Optional[str] = Form(
        None,
        description="Optional support-call room name to publish live context for voice agents",
    ),
    chunk_index: Optional[int] = Form(None, ge=1, description="Video chunk sequence number for voice-agent context"),
):
    """Accept a video, extract low-FPS frames server-side, and run screenshare assistance."""
    if not consent:
        raise HTTPException(status_code=400, detail="Screen recording analysis requires user consent")

    allowed_video_types = {
        "video/mp4",
        "video/webm",
        "video/quicktime",
        "video/x-matroska",
    }
    mime_type = (file.content_type or "").lower()
    if mime_type not in allowed_video_types:
        raise HTTPException(status_code=400, detail=f"Unsupported video type: {mime_type}")

    video_bytes = await file.read()
    if not video_bytes:
        raise HTTPException(status_code=400, detail="Empty video file")

    from app.core.config import get_settings
    settings = get_settings()
    max_bytes = int(settings.VISUAL_SCREENSHARE_MAX_VIDEO_MB) * 1024 * 1024
    if len(video_bytes) > max_bytes:
        raise HTTPException(
            status_code=400,
            detail=(
                f"Video file too large: {len(video_bytes)} bytes exceeds max "
                f"{settings.VISUAL_SCREENSHARE_MAX_VIDEO_MB} MB"
            ),
        )

    effective_target_fps = target_fps if target_fps is not None else settings.VISUAL_SCREENSHARE_TARGET_FPS
    if effective_target_fps <= 0:
        raise HTTPException(status_code=400, detail="target_fps must be positive")

    try:
        frames, source_fps = extract_frames_from_video_bytes(
            video_bytes,
            mime_type=mime_type,
            target_fps=effective_target_fps,
            max_frames=settings.VISUAL_SCREENSHARE_MAX_FRAMES,
            max_duration_seconds=settings.VISUAL_SCREENSHARE_MAX_VIDEO_DURATION_SECONDS,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    svc = VisualAIService(db)
    try:
        result = await svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=source_fps,
            target_fps=effective_target_fps,
            provider_name=provider,
            reference_key=reference_key,
            use_gemini_embeddings=use_gemini_embeddings,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    _maybe_publish_support_call_context(
        room_name=support_call_room_name,
        result=result,
        capture_mode="chunk",
        frame_number=chunk_index,
        chunk_index=chunk_index,
    )

    return result


@router.post(
    "/screenshare/assist-realtime-chunk",
    response_model=ScreenShareRealtimeChunkResponse,
    summary="Realtime screenshare assistance from a short video chunk",
)
async def screenshare_assist_realtime_chunk(
    db: DB,
    user: AnyUser,
    consent: bool = Form(..., description="User must consent to screen recording analysis"),
    session_id: str = Form(..., description="Client-side screenshare session id"),
    chunk_index: int = Form(1, ge=1, description="1-based chunk sequence number"),
    target_fps: Optional[float] = Form(None, description="Low FPS to process for assistance"),
    provider: Optional[str] = Form(None, description="Provider for OCR/UI analysis"),
    reference_key: Optional[str] = Form(None, description="Optional reference screen key"),
    use_gemini_embeddings: Optional[bool] = Form(None, description="Override embedding toggle for this request"),
    support_call_room_name: Optional[str] = Form(
        None,
        description="Optional support-call room name to publish live context for voice agents",
    ),
    file: Optional[UploadFile] = File(None, description="Screen recording chunk video"),
    video: Optional[UploadFile] = File(None, description="Alias for screen recording chunk video"),
):
    """Accept a short screenshare chunk and return a realtime analysis packet."""
    if not consent:
        raise HTTPException(status_code=400, detail="Screen recording analysis requires user consent")

    if not session_id.strip():
        raise HTTPException(status_code=400, detail="session_id is required")

    upload = file or video
    if upload is None:
        raise HTTPException(status_code=400, detail="No video chunk was uploaded")

    allowed_video_types = {
        "video/mp4",
        "video/webm",
        "video/quicktime",
        "video/x-matroska",
    }
    mime_type = (upload.content_type or "").lower()
    if mime_type not in allowed_video_types:
        raise HTTPException(status_code=400, detail=f"Unsupported video type: {mime_type}")

    video_bytes = await upload.read()
    if not video_bytes:
        raise HTTPException(status_code=400, detail="Empty video file")

    from app.core.config import get_settings

    settings = get_settings()
    max_bytes = int(settings.VISUAL_SCREENSHARE_MAX_VIDEO_MB) * 1024 * 1024
    if len(video_bytes) > max_bytes:
        raise HTTPException(
            status_code=400,
            detail=(
                f"Video file too large: {len(video_bytes)} bytes exceeds max "
                f"{settings.VISUAL_SCREENSHARE_MAX_VIDEO_MB} MB"
            ),
        )

    effective_target_fps = target_fps if target_fps is not None else settings.VISUAL_SCREENSHARE_TARGET_FPS
    if effective_target_fps <= 0:
        raise HTTPException(status_code=400, detail="target_fps must be positive")

    try:
        frames, source_fps = extract_frames_from_video_bytes(
            video_bytes,
            mime_type=mime_type,
            target_fps=effective_target_fps,
            max_frames=settings.VISUAL_SCREENSHARE_MAX_FRAMES,
            max_duration_seconds=settings.VISUAL_SCREENSHARE_MAX_VIDEO_DURATION_SECONDS,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    svc = VisualAIService(db)
    try:
        result = await svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=source_fps,
            target_fps=effective_target_fps,
            provider_name=provider,
            reference_key=reference_key,
            use_gemini_embeddings=use_gemini_embeddings,
        )
    except ValueError as exc:
        raise HTTPException(status_code=400, detail=str(exc))

    _maybe_publish_support_call_context(
        room_name=support_call_room_name,
        result=result,
        capture_mode="chunk",
        frame_number=chunk_index,
        chunk_index=chunk_index,
    )

    return {
        **result,
        "session_id": session_id,
        "chunk_index": chunk_index,
    }
