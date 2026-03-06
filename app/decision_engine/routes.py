"""
Decision Engine API routes.

Endpoints:
  POST /decision-engine/analyze           — Full analysis of a ticket
  POST /decision-engine/analyze-text      — Analyze free text (preview)
  GET  /decision-engine/decisions/{id}    — Decision history for a ticket
  POST /decision-engine/route/{id}        — Route ticket to best agent
  GET  /decision-engine/suggestions/{id}  — Get response suggestions
  POST /decision-engine/escalate/{id}     — Trigger escalation package
  GET  /decision-engine/agent-skills      — List agent skills
  POST /decision-engine/agent-skills      — Create/update agent skill
  DELETE /decision-engine/agent-skills/{id} — Delete agent skill
  GET  /decision-engine/stats             — Dashboard statistics
"""

import uuid
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.enums import UserRole
from app.api.deps import get_current_user, require_agent_or_admin, require_admin
from app.services.ticket_service import TicketService
from app.decision_engine.enums import IntentCategory
from app.decision_engine.decision_engine import analyze_ticket, analyze_text_only
from app.decision_engine.router_engine import find_best_agent
from app.decision_engine.classifier import classify_text
from app.decision_engine.scorer import assess_risk
from app.decision_engine.response_suggester import get_response_suggestions
from app.decision_engine.escalation import build_escalation_package
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
)

router = APIRouter(prefix="/decision-engine", tags=["Decision Engine"])


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
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """
    Analyze free text without an existing ticket.
    Useful for previewing classification before ticket creation.
    No DB persistence.
    """
    result = analyze_text_only(text=payload.text, subject=payload.subject or "")
    return result


# ── Decision history ──────────────────────────────────────

@router.get("/decisions/{ticket_id}", response_model=DecisionHistoryResponse)
async def get_decision_history(
    ticket_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    skip: int = Query(0, ge=0),
    limit: int = Query(20, ge=1, le=100),
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
        classification = classify_text(text=ticket.description, subject=ticket.subject)
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
        classification = classify_text(text=ticket.description, subject=ticket.subject)
        category = classification.intent_category
        confidence = classification.confidence_score
        confidence_level = classification.confidence_level
        risk = assess_risk(text=ticket.description, subject=ticket.subject, classification=classification)
        from app.decision_engine.rules import apply_rules
        outcome, _ = apply_rules(confidence_level, risk.risk_level, category)

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
    ticket_svc = TicketService(db)
    ticket = await ticket_svc.get_ticket(ticket_id)
    if not ticket:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Ticket not found",
        )

    # Classify if not already done
    classification = classify_text(text=ticket.description, subject=ticket.subject)
    risk = assess_risk(
        text=ticket.description,
        subject=ticket.subject,
        classification=classification,
        existing_priority=ticket.priority,
        has_escalation_flag=ticket.escalation_flag,
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


# ── Agent Skills Management ──────────────────────────────

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
