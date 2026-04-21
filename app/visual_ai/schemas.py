"""
Pydantic schemas for the Visual AI module.
"""

from __future__ import annotations

import uuid
from datetime import datetime
from typing import Literal, Optional

from pydantic import BaseModel, Field

from app.visual_ai.enums import VisualAIProvider, GapSeverity, UIElementType


# ═══════════════════════════════════════════════════════════
#  Shared / Internal schemas
# ═══════════════════════════════════════════════════════════

class UIElement(BaseModel):
    """A detected UI element."""
    element_type: UIElementType
    label: str = ""
    bbox: Optional[list[float]] = None  # [x, y, w, h] normalised 0-1
    confidence: float = 1.0
    text: Optional[str] = None


class RegionDescription(BaseModel):
    """A described region within the screenshot."""
    bbox: Optional[list[float]] = None
    description: str = ""


class OCRResult(BaseModel):
    """OCR extraction output."""
    text: str = ""
    language: Optional[str] = None
    confidence: float = 0.0
    word_count: int = 0


class UIAnalysisResult(BaseModel):
    """UI analysis output (caption + elements + regions)."""
    caption: str = ""
    elements: list[UIElement] = []
    labels: list[str] = []
    regions: list[RegionDescription] = []


class FullAnalysisResult(BaseModel):
    """Complete analysis result from any provider."""
    ocr: OCRResult = Field(default_factory=OCRResult)
    ui_analysis: UIAnalysisResult = Field(default_factory=UIAnalysisResult)
    embedding: list[float] = []
    provider: str = ""
    processing_ms: int = 0
    confidence: float = 0.0
    raw_result: Optional[dict] = None


# ═══════════════════════════════════════════════════════════
#  Screenshot schemas
# ═══════════════════════════════════════════════════════════

class ScreenshotUpload(BaseModel):
    """Metadata for screenshot upload (sent as form fields alongside image)."""
    conversation_id: Optional[uuid.UUID] = None
    consent: bool = Field(..., description="User must explicitly consent to screen capture")
    metadata_: Optional[dict] = Field(None, alias="metadata")


class ScreenshotResponse(BaseModel):
    """Screenshot record response."""
    id: uuid.UUID
    conversation_id: Optional[uuid.UUID] = None
    user_id: Optional[uuid.UUID] = None
    filename: str
    file_size: int
    mime_type: str
    consent: bool
    metadata_: Optional[dict] = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


# ═══════════════════════════════════════════════════════════
#  Analysis schemas
# ═══════════════════════════════════════════════════════════

class AnalysisResponse(BaseModel):
    """Analysis result response."""
    id: uuid.UUID
    screenshot_id: uuid.UUID
    provider: str
    ocr_text: Optional[str] = None
    caption: Optional[str] = None
    elements: Optional[list] = None
    labels: Optional[list] = None
    regions: Optional[list] = None
    confidence: Optional[float] = None
    processing_ms: Optional[int] = None
    created_at: datetime

    model_config = {"from_attributes": True}


class AnalyzeRequest(BaseModel):
    """Request body for analyze-raw endpoint."""
    provider: Optional[VisualAIProvider] = None


# ═══════════════════════════════════════════════════════════
#  Gap Detection schemas
# ═══════════════════════════════════════════════════════════

class GapDetectRequest(BaseModel):
    """Request body for gap detection."""
    reference_key: Optional[str] = Field(None, description="screen_key of reference to compare against")
    reference_id: Optional[uuid.UUID] = Field(None, description="ID of reference screen")


class GapDiff(BaseModel):
    """Details about what differs between expected and observed."""
    visual_similarity: float = 0.0
    text_diff_ratio: float = 0.0
    element_diff_ratio: float = 0.0
    error_penalty: float = 0.0
    missing_keywords: list[str] = []
    unexpected_keywords: list[str] = []
    missing_elements: list[str] = []
    extra_elements: list[str] = []


class GapResult(BaseModel):
    """Gap detection result."""
    gap_score: float = 0.0
    severity: GapSeverity = GapSeverity.NO_GAP
    diffs: GapDiff = Field(default_factory=GapDiff)
    guidance_hints: list[str] = []


# ═══════════════════════════════════════════════════════════
#  Timeline schemas
# ═══════════════════════════════════════════════════════════

class UIStateResponse(BaseModel):
    """A single UI state in the timeline."""
    id: uuid.UUID
    conversation_id: uuid.UUID
    screenshot_id: Optional[uuid.UUID] = None
    analysis_id: Optional[uuid.UUID] = None
    state_label: Optional[str] = None
    sequence_num: int = 0
    gap_detected: bool = False
    gap_severity: Optional[GapSeverity] = None
    gap_details: Optional[dict] = None
    created_at: datetime

    model_config = {"from_attributes": True}


class TimelineResponse(BaseModel):
    """Full conversation UI timeline."""
    conversation_id: uuid.UUID
    states: list[UIStateResponse] = []
    total_states: int = 0
    gaps_detected: int = 0


# ═══════════════════════════════════════════════════════════
#  Reference Screen schemas
# ═══════════════════════════════════════════════════════════

class ReferenceScreenCreate(BaseModel):
    """Create a reference screen."""
    name: str = Field(..., min_length=1, max_length=200)
    description: Optional[str] = None
    screen_key: str = Field(..., min_length=1, max_length=100)
    expected_elements: Optional[list[dict]] = None
    expected_ocr_text: Optional[str] = None


class ReferenceScreenResponse(BaseModel):
    """Reference screen response."""
    id: uuid.UUID
    name: str
    description: Optional[str] = None
    screen_key: str
    file_path: str
    expected_elements: Optional[list] = None
    expected_ocr_text: Optional[str] = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class ReferenceScreenListResponse(BaseModel):
    """List of reference screens."""
    items: list[ReferenceScreenResponse] = []
    total: int = 0


# ═══════════════════════════════════════════════════════════
#  Guidance schemas
# ═══════════════════════════════════════════════════════════

class GuidanceRequest(BaseModel):
    """Request body for guidance generation."""
    screenshot_id: Optional[uuid.UUID] = None
    reference_key: Optional[str] = None


class GuidanceResponse(BaseModel):
    """Adaptive guidance response."""
    rule_based_guidance: str = ""
    ai_enhanced_guidance: Optional[str] = None
    suggested_actions: list[str] = []
    gap_result: Optional[GapResult] = None
    confidence: float = 0.0


# ═══════════════════════════════════════════════════════════
#  Screenshare Assistance schemas
# ═══════════════════════════════════════════════════════════

class ScreenShareFinalFrameSummary(BaseModel):
    provider: str
    caption: str = ""
    ocr_text_preview: str = ""
    element_count: int = 0
    labels: list[str] = []


class ScreenShareAssistResponse(BaseModel):
    source_fps: float
    target_fps: float
    uploaded_frames: int
    processed_frames: int
    embedding_backend: str
    embedding_dimension: int
    avg_transition_score: float
    max_transition_score: float
    reference_similarity: Optional[float] = None
    final_frame: ScreenShareFinalFrameSummary
    assistance_hints: list[str] = []


class ScreenShareRealtimeChunkResponse(ScreenShareAssistResponse):
    session_id: str
    chunk_index: int


# ═══════════════════════════════════════════════════════════
#  Troubleshooting Wizard schemas
# ═══════════════════════════════════════════════════════════

class TroubleshootingWizardRequest(BaseModel):
    goal: str = Field(..., min_length=5, max_length=240)
    issue_summary: Optional[str] = Field(None, max_length=1000)
    observed_screen_caption: Optional[str] = Field(None, max_length=1000)
    observed_text: Optional[str] = Field(None, max_length=4000)
    user_actions_attempted: list[str] = Field(default_factory=list)
    context_hints: list[str] = Field(default_factory=list)
    max_steps: int = Field(5, ge=3, le=8)


class TroubleshootingWizardStep(BaseModel):
    step_number: int = Field(..., ge=1)
    title: str
    why: str
    instructions: list[str] = Field(default_factory=list)
    expected_signal: str
    if_not_seen: str


class TroubleshootingWizardResponse(BaseModel):
    issue_summary: str
    diagnosis: str
    risk_level: Literal["low", "medium", "high"] = "medium"
    estimated_time_minutes: int = 10
    steps: list[TroubleshootingWizardStep] = Field(default_factory=list)
    escalation_hint: str
    provider: str = "rule-engine"
    model: str = "deterministic-v1"
    generated_at: datetime = Field(default_factory=datetime.utcnow)
