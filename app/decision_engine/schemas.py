"""
Decision Engine Pydantic schemas — request/response models.
"""

import uuid
from datetime import datetime
from typing import Optional

from pydantic import BaseModel, Field

from app.decision_engine.enums import (
    IntentCategory,
    DecisionOutcome,
    RiskLevel,
    ConfidenceLevel,
)
from app.db.models.enums import TicketPriority


# ── Classification ───────────────────────────────────────

class ClassificationResult(BaseModel):
    """Result of the intent classification."""
    intent_category: IntentCategory
    confidence_score: float = Field(..., ge=0.0, le=1.0)
    confidence_level: ConfidenceLevel
    matched_keywords: list[str] = []


class RiskAssessment(BaseModel):
    """Result of the risk scoring."""
    risk_score: float = Field(..., ge=0.0, le=1.0)
    risk_level: RiskLevel
    risk_factors: list[str] = []
    suggested_priority: TicketPriority


# ── Decision ─────────────────────────────────────────────

class DecisionResult(BaseModel):
    """Full result from the decision engine."""
    ticket_id: uuid.UUID
    intent_category: IntentCategory
    confidence_score: float
    confidence_level: ConfidenceLevel
    risk_score: float
    risk_level: RiskLevel
    decision_outcome: DecisionOutcome
    suggested_agent_id: Optional[uuid.UUID] = None
    suggested_agent_name: Optional[str] = None
    response_suggestions: list[str] = []
    reasoning: str
    matched_rules: list[str] = []
    escalation_summary: Optional[str] = None
    suggested_priority: TicketPriority


class DecisionLogResponse(BaseModel):
    """API response for a single decision log."""
    id: uuid.UUID
    ticket_id: uuid.UUID
    intent_category: IntentCategory
    confidence_score: float
    confidence_level: ConfidenceLevel
    risk_score: float
    risk_level: RiskLevel
    decision_outcome: DecisionOutcome
    suggested_agent_id: Optional[uuid.UUID]
    response_suggestions: Optional[dict]
    reasoning: Optional[str]
    matched_rules: Optional[dict]
    escalation_summary: Optional[str]
    created_at: datetime

    model_config = {"from_attributes": True}


class DecisionHistoryResponse(BaseModel):
    """List of decision logs for a ticket."""
    ticket_id: Optional[uuid.UUID] = None
    decisions: list[DecisionLogResponse]
    total: int


# ── Routing ──────────────────────────────────────────────

class RoutingResult(BaseModel):
    """Result of the smart routing engine."""
    agent_id: uuid.UUID
    agent_name: str
    agent_email: str
    skill_match_score: float = Field(..., ge=0.0, le=1.0)
    current_workload: int
    max_capacity: int
    reasoning: str


class RoutingResponse(BaseModel):
    """API response for routing."""
    ticket_id: uuid.UUID
    selected_agent: Optional[RoutingResult] = None
    candidates: list[RoutingResult] = []
    auto_assigned: bool = False


# ── Agent Skills ─────────────────────────────────────────

class AgentSkillCreate(BaseModel):
    agent_id: uuid.UUID
    skill_category: IntentCategory
    proficiency: float = Field(0.5, ge=0.0, le=1.0)
    max_concurrent_tickets: int = Field(10, ge=1, le=1000)


class AgentSkillResponse(BaseModel):
    id: uuid.UUID
    agent_id: uuid.UUID
    skill_category: IntentCategory
    proficiency: float
    max_concurrent_tickets: int
    created_at: datetime

    model_config = {"from_attributes": True}


class AgentSkillListResponse(BaseModel):
    skills: list[AgentSkillResponse]
    total: int


# ── Response Suggestions ────────────────────────────────

class SuggestionResponse(BaseModel):
    """Response suggestions for a ticket."""
    ticket_id: uuid.UUID
    intent_category: IntentCategory
    suggestions: list[str]
    confidence: float


# ── Escalation ──────────────────────────────────────────

class EscalationPackage(BaseModel):
    """Structured escalation data for HITL."""
    ticket_id: uuid.UUID
    ticket_subject: str
    ticket_description: str
    intent_category: IntentCategory
    confidence_score: float
    risk_score: float
    risk_level: RiskLevel
    conversation_history: list[dict] = []
    previous_decisions: list[DecisionLogResponse] = []
    summary: str
    recommended_actions: list[str] = []


# ── Analyze request ─────────────────────────────────────

class AnalyzeTicketRequest(BaseModel):
    """Request to analyze a ticket (optionally provide text to override)."""
    ticket_id: uuid.UUID
    auto_assign: bool = Field(False, description="Automatically assign to suggested agent")
    auto_update_priority: bool = Field(False, description="Automatically update ticket priority")


class AnalyzeTextRequest(BaseModel):
    """Analyze free text without an existing ticket."""
    text: str = Field(..., min_length=1, max_length=10000)
    subject: Optional[str] = None


# ── Dashboard Stats ─────────────────────────────────────

class DecisionStats(BaseModel):
    """Decision engine dashboard statistics."""
    total_decisions: int
    auto_resolved: int
    escalated: int
    routed: int
    clarification_needed: int
    avg_confidence: float
    avg_risk: float
    decisions_by_category: dict[str, int]
    decisions_by_outcome: dict[str, int]
    escalation_rate: float


class DecisionEngineConfigResponse(BaseModel):
    confidence_high_threshold: float = Field(..., ge=0.45, le=0.98)
    confidence_medium_threshold: float = Field(..., ge=0.05, le=0.94)
    risk_critical_threshold: float = Field(..., ge=0.45, le=1.0)
    risk_high_threshold: float = Field(..., ge=0.2, le=0.98)
    risk_medium_threshold: float = Field(..., ge=0.05, le=0.9)
    low_confidence_risk_boost: float = Field(..., ge=0.0, le=0.4)
    medium_confidence_risk_boost: float = Field(..., ge=0.0, le=0.25)
    enforce_security_escalation: bool
    enforce_critical_escalation: bool
    low_confidence_general_suggest: bool


class DecisionEngineConfigUpdate(BaseModel):
    confidence_high_threshold: float = Field(..., ge=0.45, le=0.98)
    confidence_medium_threshold: float = Field(..., ge=0.05, le=0.94)
    risk_critical_threshold: float = Field(..., ge=0.45, le=1.0)
    risk_high_threshold: float = Field(..., ge=0.2, le=0.98)
    risk_medium_threshold: float = Field(..., ge=0.05, le=0.9)
    low_confidence_risk_boost: float = Field(..., ge=0.0, le=0.4)
    medium_confidence_risk_boost: float = Field(..., ge=0.0, le=0.25)
    enforce_security_escalation: bool
    enforce_critical_escalation: bool
    low_confidence_general_suggest: bool


class DecisionOutcomeDoc(BaseModel):
    outcome: DecisionOutcome
    title: str
    description: str
    operator_guidance: str


class DecisionMatrixRow(BaseModel):
    confidence_level: ConfidenceLevel
    risk_level: RiskLevel
    category: IntentCategory
    outcome: DecisionOutcome
    matched_rule: str
    notes: str


class DecisionOutcomeDocsResponse(BaseModel):
    outcomes: list[DecisionOutcomeDoc]
    matrix: list[DecisionMatrixRow]
