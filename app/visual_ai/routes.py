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

import uuid
from typing import Annotated, Optional

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
)
from app.visual_ai.service import VisualAIService

router = APIRouter(prefix="/visual-ai", tags=["Visual AI"])

# ── Type aliases ──────────────────────────────────────────
DB = Annotated[AsyncSession, Depends(get_db)]
AnyUser = Annotated[User, Depends(require_any_authenticated)]
AgentOrAdmin = Annotated[User, Depends(require_agent_or_admin)]
Admin = Annotated[User, Depends(require_admin)]


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
    provider: Optional[str] = Form(None, description="Provider: local-basic, local-advanced, google"),
):
    """Analyze an uploaded image without persisting it. Returns full analysis result."""
    image_bytes = await file.read()
    if not image_bytes:
        raise HTTPException(status_code=400, detail="Empty file")

    svc = VisualAIService(db)
    result = await svc.analyze_raw(image_bytes, provider_name=provider)
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
    provider: Optional[str] = Form(None, description="Provider: local-basic, local-advanced, google"),
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
