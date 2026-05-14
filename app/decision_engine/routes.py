"""
Decision Engine API routes.

Endpoints:
  POST /decision-engine/analyze           — Full analysis of a ticket
  POST /decision-engine/analyze-text      — Analyze free text (preview)
  GET  /decision-engine/decisions/{id}    — Decision history for a ticket
  POST /decision-engine/route/{id}        — Route ticket to best agent
  GET  /decision-engine/suggestions/{id}  — Get response suggestions
  POST /decision-engine/escalate/{id}     — Trigger escalation package
    GET  /decision-engine/config            — Read runtime thresholds and rule toggles
    PUT  /decision-engine/config            — Update runtime thresholds and rule toggles
    GET  /decision-engine/outcomes-docs     — Outcome docs and current decision matrix
  GET  /decision-engine/agent-skills      — List agent skills
  POST /decision-engine/agent-skills      — Create/update agent skill
  DELETE /decision-engine/agent-skills/{id} — Delete agent skill
  GET  /decision-engine/stats             — Dashboard statistics
"""

import uuid
import logging
from typing import Annotated, Optional

logger = logging.getLogger(__name__)

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.api.deps import require_agent_or_admin, require_admin
from app.services.ticket_service import TicketService
from app.services.settings_service import SettingsService
from app.decision_engine.enums import IntentCategory, DecisionOutcome, ConfidenceLevel, RiskLevel
from app.decision_engine.decision_engine import analyze_ticket, analyze_text_only
from app.decision_engine.router_engine import find_best_agent
from app.decision_engine.classifier import classify_text
from app.decision_engine.scorer import assess_risk
from app.decision_engine.response_suggester import get_response_suggestions
from app.decision_engine.escalation import build_escalation_package
from app.decision_engine.rules import apply_rules, DecisionRuleConfig
from app.decision_engine.config import (
    load_runtime_config,
    normalize_runtime_config,
    runtime_config_to_settings,
    DecisionEngineRuntimeConfig,
)
from app.decision_engine.service import DecisionService
from app.decision_engine.schemas import (
    AnalyzeTicketRequest,
    AnalyzeTextRequest,
    DecisionResult,
    DecisionHistoryResponse,
    RoutingResponse,
    SuggestionResponse,
    EscalationPackage,
    AgentSkillCreate,
    AgentSkillResponse,
    AgentSkillListResponse,
    DecisionStats,
    DecisionEngineConfigResponse,
    DecisionEngineConfigUpdate,
    DecisionOutcomeDoc,
    DecisionMatrixRow,
    DecisionOutcomeDocsResponse,
)

router = APIRouter(prefix="/decision-engine", tags=["Decision Engine"])


def _to_rule_config(config: DecisionEngineRuntimeConfig) -> DecisionRuleConfig:
    return DecisionRuleConfig(
        enforce_critical_escalation=config.enforce_critical_escalation,
        enforce_security_escalation=config.enforce_security_escalation,
        low_confidence_general_suggest=config.low_confidence_general_suggest,
    )


def _outcome_docs() -> list[DecisionOutcomeDoc]:
    return [
        DecisionOutcomeDoc(
            outcome=DecisionOutcome.AUTO_RESOLVE,
            title="Auto Resolve",
            description="Engine has high confidence with low operational risk.",
            operator_guidance="Review briefly, then close or keep automation enabled for similar tickets.",
        ),
        DecisionOutcomeDoc(
            outcome=DecisionOutcome.SUGGEST_RESPONSE,
            title="Suggest Response",
            description="Engine is confident enough to draft a reply, but prefers operator validation.",
            operator_guidance="Edit the suggested response if needed, then send to customer.",
        ),
        DecisionOutcomeDoc(
            outcome=DecisionOutcome.CLARIFY,
            title="Clarify",
            description="Confidence or context is insufficient and more customer detail is needed.",
            operator_guidance="Ask focused follow-up questions to reduce ambiguity.",
        ),
        DecisionOutcomeDoc(
            outcome=DecisionOutcome.ESCALATE_HUMAN,
            title="Escalate Human",
            description="Risk is high or confidence is too low for safe automation.",
            operator_guidance="Prioritize manual review and involve senior support if needed.",
        ),
        DecisionOutcomeDoc(
            outcome=DecisionOutcome.ROUTE_AGENT,
            title="Route Agent",
            description="No strong rule match; assign to the best available skilled agent.",
            operator_guidance="Confirm routing target and transfer ownership.",
        ),
    ]


def _build_decision_matrix(config: DecisionEngineRuntimeConfig) -> list[DecisionMatrixRow]:
    matrix: list[DecisionMatrixRow] = []
    rule_config = _to_rule_config(config)

    categories = list(IntentCategory)
    for category in categories:
        for confidence_level in (ConfidenceLevel.HIGH, ConfidenceLevel.MEDIUM, ConfidenceLevel.LOW):
            for risk_level in (RiskLevel.LOW, RiskLevel.MEDIUM, RiskLevel.HIGH, RiskLevel.CRITICAL):
                outcome, matched_rules = apply_rules(
                    confidence_level=confidence_level,
                    risk_level=risk_level,
                    category=category,
                    rule_config=rule_config,
                )
                matrix.append(
                    DecisionMatrixRow(
                        confidence_level=confidence_level,
                        risk_level=risk_level,
                        category=category,
                        outcome=outcome,
                        matched_rule=matched_rules[0] if matched_rules else "fallback_route_agent",
                        notes=f"{category.value.replace('_', ' ').title()} category path",
                    )
                )
    return matrix


# ── Analyze a ticket ──────────────────────────────────────

@router.post("/analyze", response_model=DecisionResult, status_code=status.HTTP_200_OK)
async def analyze_ticket_endpoint(
    payload: AnalyzeTicketRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """
    Run the full decision engine pipeline on a ticket.
    Classifies intent, scores confidence/risk, decides outcome,
    suggests responses, and optionally auto-assigns/updates priority.
    """
    svc = TicketService(db)
    ticket = await svc.get_ticket(payload.ticket_id)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found",
        )

    result = await analyze_ticket(
        db=db,
        ticket=ticket,
        auto_assign=payload.auto_assign,
        auto_update_priority=payload.auto_update_priority,
    )
    return result


# ── Analyze free text (preview) ───────────────────────────

@router.post("/analyze-text", response_model=DecisionResult, status_code=status.HTTP_200_OK)
async def analyze_text_endpoint(
    payload: AnalyzeTextRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """
    Analyze free text without an existing ticket.
    Useful for previewing classification before ticket creation.
    No DB persistence.
    """
    runtime_config = await load_runtime_config(db)
    result = analyze_text_only(
        text=payload.text,
        subject=payload.subject or "",
        runtime_config=runtime_config,
    )
    return result


# ── Decision history ──────────────────────────────────────

@router.get("/decisions", response_model=DecisionHistoryResponse)
async def list_decision_history(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    ticket_id: Optional[uuid.UUID] = Query(None),
    skip: int = Query(0, ge=0),
    limit: int = Query(20, ge=1, le=1000),
):
    """Get AI decision logs across all tickets, optionally filtered by ticket_id."""
    svc = DecisionService(db)
    return await svc.get_decision_history(ticket_id=ticket_id, skip=skip, limit=limit)

@router.get("/decisions/{ticket_id}", response_model=DecisionHistoryResponse)
async def get_decision_history(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    skip: int = Query(0, ge=0),
    limit: int = Query(20, ge=1, le=1000),
):
    """Get all AI decision logs for a specific ticket."""
    svc = DecisionService(db)
    return await svc.get_decision_history(ticket_id, skip=skip, limit=limit)


# ── Route ticket ──────────────────────────────────────────

@router.post("/route/{ticket_id}", response_model=RoutingResponse)
async def route_ticket(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    auto_assign: bool = Query(False),
):
    """
    Find the best agent for a ticket based on skills and workload.
    Optionally auto-assign the agent.
    """
    ticket_svc = TicketService(db)
    ticket = await ticket_svc.get_ticket(ticket_id)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found",
        )

    # Get the latest classification or classify now
    decision_svc = DecisionService(db)
    latest = await decision_svc.get_latest_decision(ticket_id)

    if latest:
        category = latest.intent_category
    else:
        # Classify on the fly
        runtime_config = await load_runtime_config(db)
        classification = classify_text(
            text=ticket.description,
            subject=ticket.subject,
            high_confidence_threshold=runtime_config.confidence_high_threshold,
            medium_confidence_threshold=runtime_config.confidence_medium_threshold,
        )
        category = classification.intent_category

    routing = await find_best_agent(db=db, ticket_id=ticket_id, category=category)

    # Auto-assign if requested
    if auto_assign and routing.selected_agent and not ticket.assigned_agent_id:
        ticket.assigned_agent_id = routing.selected_agent.agent_id
        from app.db.models.enums import TicketStatus
        if ticket.status == TicketStatus.OPEN:
            ticket.status = TicketStatus.IN_PROGRESS
        await db.flush()
        routing.auto_assigned = True

    return routing


# ── Response suggestions ──────────────────────────────────

@router.get("/suggestions/{ticket_id}", response_model=SuggestionResponse)
async def get_suggestions(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """Get AI-generated response suggestions for a ticket."""
    ticket_svc = TicketService(db)
    ticket = await ticket_svc.get_ticket(ticket_id)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found",
        )

    # Get latest decision or classify fresh
    decision_svc = DecisionService(db)
    latest = await decision_svc.get_latest_decision(ticket_id)

    if latest:
        category = latest.intent_category
        confidence = latest.confidence_score
        from app.decision_engine.enums import ConfidenceLevel
        confidence_level = latest.confidence_level
        outcome = latest.decision_outcome
    else:
        runtime_config = await load_runtime_config(db)
        classification = classify_text(
            text=ticket.description,
            subject=ticket.subject,
            high_confidence_threshold=runtime_config.confidence_high_threshold,
            medium_confidence_threshold=runtime_config.confidence_medium_threshold,
        )
        category = classification.intent_category
        confidence = classification.confidence_score
        confidence_level = classification.confidence_level
        risk = assess_risk(
            text=ticket.description,
            subject=ticket.subject,
            classification=classification,
            existing_priority=ticket.priority,
            has_escalation_flag=ticket.escalation_flag,
            critical_threshold=runtime_config.risk_critical_threshold,
            high_threshold=runtime_config.risk_high_threshold,
            medium_threshold=runtime_config.risk_medium_threshold,
            low_confidence_risk_boost=runtime_config.low_confidence_risk_boost,
            medium_confidence_risk_boost=runtime_config.medium_confidence_risk_boost,
        )
        outcome, _ = apply_rules(
            confidence_level=confidence_level,
            risk_level=risk.risk_level,
            category=category,
            rule_config=_to_rule_config(runtime_config),
        )

    suggestions = get_response_suggestions(
        category=category,
        confidence_level=confidence_level,
        outcome=outcome,
    )

    return SuggestionResponse(
        ticket_id=ticket_id,
        intent_category=category,
        suggestions=suggestions,
        confidence=confidence,
    )


# ── Escalation ───────────────────────────────────────────

@router.post("/escalate/{ticket_id}", response_model=EscalationPackage)
async def escalate_ticket(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """
    Generate a structured escalation package for human handoff.
    Also sets the ticket status to ESCALATED.
    """
    try:
        ticket_svc = TicketService(db)
        ticket = await ticket_svc.get_ticket(ticket_id)
        if not ticket:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail="Ticket not found",
            )

        try:
            runtime_config = await load_runtime_config(db)

            classification = classify_text(
                text=ticket.description,
                subject=ticket.subject,
                high_confidence_threshold=runtime_config.confidence_high_threshold,
                medium_confidence_threshold=runtime_config.confidence_medium_threshold,
            )
            risk = assess_risk(
                text=ticket.description,
                subject=ticket.subject,
                classification=classification,
                existing_priority=ticket.priority,
                has_escalation_flag=ticket.escalation_flag,
                critical_threshold=runtime_config.risk_critical_threshold,
                high_threshold=runtime_config.risk_high_threshold,
                medium_threshold=runtime_config.risk_medium_threshold,
                low_confidence_risk_boost=runtime_config.low_confidence_risk_boost,
                medium_confidence_risk_boost=runtime_config.medium_confidence_risk_boost,
            )

            package = await build_escalation_package(
                db=db,
                ticket=ticket,
                category=classification.intent_category,
                confidence_score=classification.confidence_score,
                risk_score=risk.risk_score,
                risk_level=risk.risk_level,
                confidence_level=classification.confidence_level,
                risk_factors=risk.risk_factors,
            )

            # Update ticket status
            from app.db.models.enums import TicketStatus
            if ticket.status not in (TicketStatus.ESCALATED, TicketStatus.RESOLVED, TicketStatus.CLOSED):
                ticket.escalation_flag = True
                ticket.status = TicketStatus.ESCALATED
                await db.flush()

            return package
        except Exception as e:
            logger.exception("Escalation package generation failed for ticket %s", ticket_id)
            from app.decision_engine.enums import IntentCategory, RiskLevel, ConfidenceLevel
            return EscalationPackage(
                ticket_id=ticket.id,
                ticket_subject=ticket.subject,
                ticket_description=ticket.description[:2000],
                intent_category=IntentCategory.GENERAL,
                confidence_score=0.1,
                risk_score=0.1,
                risk_level=RiskLevel.LOW,
                conversation_history=[],
                previous_decisions=[],
                summary=f"=== ESCALATION SUMMARY ===\nTicket: {ticket.subject}\nStatus: {ticket.status.value}\nPriority: {ticket.priority.value}\n\n--- Description ---\n{ticket.description[:1000]}\n\n=== ACTION REQUIRED ===\nThis ticket requires human review.",
                recommended_actions=["Review the ticket details carefully", "Contact the customer for clarification if needed"],
            )
    except Exception as e:
        logger.exception("Fatal error in escalate endpoint for ticket %s", ticket_id)
        from app.decision_engine.enums import IntentCategory, RiskLevel, ConfidenceLevel
        return EscalationPackage(
            ticket_id=ticket_id,
            ticket_subject="Escalation",
            ticket_description="",
            intent_category=IntentCategory.GENERAL,
            confidence_score=0.1,
            risk_score=0.1,
            risk_level=RiskLevel.LOW,
            conversation_history=[],
            previous_decisions=[],
            summary="=== ESCALATION SUMMARY ===\nThis ticket requires human review.\n\n=== ACTION REQUIRED ===\nReview the ticket details carefully.",
            recommended_actions=["Review the ticket details carefully"],
        )


# ── Agent Skills Management ──────────────────────────────

@router.get("/config", response_model=DecisionEngineConfigResponse)
async def get_decision_engine_config(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """Return normalized runtime decision engine configuration."""
    config = await load_runtime_config(db)
    return DecisionEngineConfigResponse.model_validate(config.to_dict())


@router.put("/config", response_model=DecisionEngineConfigResponse)
async def update_decision_engine_config(
    payload: DecisionEngineConfigUpdate,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    """Update decision engine thresholds and rule toggles."""
    normalized = normalize_runtime_config(
        {
            "decision_confidence_high_threshold": payload.confidence_high_threshold,
            "decision_confidence_medium_threshold": payload.confidence_medium_threshold,
            "decision_risk_critical_threshold": payload.risk_critical_threshold,
            "decision_risk_high_threshold": payload.risk_high_threshold,
            "decision_risk_medium_threshold": payload.risk_medium_threshold,
            "decision_low_confidence_risk_boost": payload.low_confidence_risk_boost,
            "decision_medium_confidence_risk_boost": payload.medium_confidence_risk_boost,
            "decision_enforce_security_escalation": payload.enforce_security_escalation,
            "decision_enforce_critical_escalation": payload.enforce_critical_escalation,
            "decision_low_confidence_general_suggest": payload.low_confidence_general_suggest,
        }
    )

    settings_service = SettingsService(db)
    await settings_service.update_section("decision_engine", runtime_config_to_settings(normalized))
    return DecisionEngineConfigResponse.model_validate(normalized.to_dict())


@router.get("/outcomes-docs", response_model=DecisionOutcomeDocsResponse)
async def get_decision_outcomes_docs(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """Return decision outcome documentation and the current rule matrix."""
    config = await load_runtime_config(db)
    return DecisionOutcomeDocsResponse(
        outcomes=_outcome_docs(),
        matrix=_build_decision_matrix(config),
    )

@router.post("/agent-skills", response_model=AgentSkillResponse, status_code=status.HTTP_201_CREATED)
async def create_agent_skill(
    payload: AgentSkillCreate,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    """Create or update an agent's skill (admin only)."""
    svc = DecisionService(db)
    try:
        skill = await svc.create_agent_skill(payload)
        return AgentSkillResponse.model_validate(skill)
    except ValueError as e:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=str(e),
        )


@router.get("/agent-skills", response_model=AgentSkillListResponse)
async def list_agent_skills(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    agent_id: Optional[uuid.UUID] = Query(None),
    category: Optional[IntentCategory] = Query(None),
):
    """List agent skills with optional filtering."""
    svc = DecisionService(db)
    return await svc.list_agent_skills(agent_id=agent_id, category=category)


@router.delete("/agent-skills/{skill_id}", status_code=status.HTTP_200_OK)
async def delete_agent_skill(
    skill_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    """Delete an agent skill (admin only)."""
    svc = DecisionService(db)
    deleted = await svc.delete_agent_skill(skill_id)
    if not deleted:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Agent skill not found",
        )
    return {"message": "Agent skill deleted"}


# ── Dashboard Stats ───────────────────────────────────────

@router.get("/stats", response_model=DecisionStats)
async def get_decision_stats(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """Get decision engine dashboard statistics."""
    svc = DecisionService(db)
    return await svc.get_stats()
