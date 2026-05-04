"""
Decision Engine Orchestrator — the main entry point that combines:
  - Intent classification
  - Confidence & risk scoring
  - Rule-based decision logic
  - Smart agent routing
  - Response suggestions
  - Escalation handling

This is the heart of Sprint 2: the adaptive decision engine.
"""

import uuid
import logging
from datetime import datetime, timezone

from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.ticket import Ticket
from app.db.models.enums import TicketStatus, TicketPriority
from app.decision_engine.classifier import classify_text
from app.decision_engine.scorer import assess_risk
from app.decision_engine.rules import apply_rules, DecisionRuleConfig
from app.decision_engine.config import load_runtime_config, DecisionEngineRuntimeConfig
from app.decision_engine.router_engine import find_best_agent
from app.decision_engine.response_suggester import get_response_suggestions
from app.decision_engine.escalation import build_escalation_package
from app.decision_engine.enums import DecisionOutcome
from app.decision_engine.models import DecisionLog
from app.decision_engine.schemas import (
    DecisionResult,
    ClassificationResult,
    RiskAssessment,
    EscalationPackage,
    RoutingResponse,
)

logger = logging.getLogger(__name__)


async def analyze_ticket(
    db: AsyncSession,
    ticket: Ticket,
    auto_assign: bool = False,
    auto_update_priority: bool = False,
) -> DecisionResult:
    """
    Run the full decision engine pipeline on a ticket.

    Pipeline:
      1. Classify intent from ticket text
      2. Score confidence and risk
      3. Apply decision rules → outcome
      4. Generate response suggestions
      5. Route to agent if needed
      6. Build escalation package if needed
      7. Persist decision log
      8. Optionally auto-assign agent / update priority

    Args:
        db: Async database session.
        ticket: The ticket to analyze.
        auto_assign: If True, automatically assign the suggested agent.
        auto_update_priority: If True, update ticket priority based on risk.

    Returns:
        DecisionResult with full analysis.
    """
    logger.info(f"Analyzing ticket {ticket.id}: {ticket.subject}")

    runtime_config = await load_runtime_config(db)
    rule_config = DecisionRuleConfig(
        enforce_critical_escalation=runtime_config.enforce_critical_escalation,
        enforce_security_escalation=runtime_config.enforce_security_escalation,
        low_confidence_general_suggest=runtime_config.low_confidence_general_suggest,
    )

    # ── Step 1: Classify intent ───────────────────────
    classification: ClassificationResult = classify_text(
        text=ticket.description,
        subject=ticket.subject,
        high_confidence_threshold=runtime_config.confidence_high_threshold,
        medium_confidence_threshold=runtime_config.confidence_medium_threshold,
    )
    logger.info(
        f"Classification: {classification.intent_category.value} "
        f"(confidence={classification.confidence_score:.3f}, "
        f"level={classification.confidence_level.value})"
    )

    # ── Step 2: Score risk ────────────────────────────
    risk: RiskAssessment = assess_risk(
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
    logger.info(
        f"Risk: score={risk.risk_score:.3f}, level={risk.risk_level.value}, "
        f"suggested_priority={risk.suggested_priority.value}"
    )

    # ── Step 3: Apply decision rules ──────────────────
    outcome, matched_rules = apply_rules(
        confidence_level=classification.confidence_level,
        risk_level=risk.risk_level,
        category=classification.intent_category,
        rule_config=rule_config,
    )
    logger.info(f"Decision outcome: {outcome.value} (rules: {matched_rules})")

    # ── Step 4: Response suggestions ──────────────────
    suggestions = get_response_suggestions(
        category=classification.intent_category,
        confidence_level=classification.confidence_level,
        outcome=outcome,
    )

    # ── Step 5: Routing (if outcome needs an agent) ───
    suggested_agent_id = None
    suggested_agent_name = None
    if outcome in (DecisionOutcome.ROUTE_AGENT, DecisionOutcome.SUGGEST_RESPONSE, DecisionOutcome.ESCALATE_HUMAN):
        routing: RoutingResponse = await find_best_agent(
            db=db,
            ticket_id=ticket.id,
            category=classification.intent_category,
        )
        if routing.selected_agent:
            suggested_agent_id = routing.selected_agent.agent_id
            suggested_agent_name = routing.selected_agent.agent_name

    # ── Step 6: Escalation package ────────────────────
    escalation_summary = None
    if outcome == DecisionOutcome.ESCALATE_HUMAN:
        escalation: EscalationPackage = await build_escalation_package(
            db=db,
            ticket=ticket,
            category=classification.intent_category,
            confidence_score=classification.confidence_score,
            risk_score=risk.risk_score,
            risk_level=risk.risk_level,
            confidence_level=classification.confidence_level,
            risk_factors=risk.risk_factors,
        )
        escalation_summary = escalation.summary

    # ── Step 7: Build reasoning explanation ───────────
    reasoning = _build_reasoning(
        classification=classification,
        risk=risk,
        outcome=outcome,
        matched_rules=matched_rules,
    )

    # ── Step 8: Persist decision log ──────────────────
    decision_log = DecisionLog(
        ticket_id=ticket.id,
        intent_category=classification.intent_category,
        confidence_score=classification.confidence_score,
        confidence_level=classification.confidence_level,
        risk_score=risk.risk_score,
        risk_level=risk.risk_level,
        decision_outcome=outcome,
        suggested_agent_id=suggested_agent_id,
        response_suggestions={"suggestions": suggestions},
        reasoning=reasoning,
        matched_rules={"rules": matched_rules},
        escalation_summary=escalation_summary,
    )
    db.add(decision_log)
    await db.flush()

    # ── Step 9: Auto-actions ──────────────────────────
    if auto_update_priority and risk.suggested_priority != ticket.priority:
        logger.info(
            f"Auto-updating ticket priority: "
            f"{ticket.priority.value} → {risk.suggested_priority.value}"
        )
        ticket.priority = risk.suggested_priority
        await db.flush()

    if auto_assign and suggested_agent_id and not ticket.assigned_agent_id:
        logger.info(f"Auto-assigning ticket to agent {suggested_agent_id}")
        ticket.assigned_agent_id = suggested_agent_id
        if ticket.status == TicketStatus.OPEN:
            ticket.status = TicketStatus.IN_PROGRESS
        await db.flush()

    if outcome == DecisionOutcome.ESCALATE_HUMAN and not ticket.escalation_flag:
        ticket.escalation_flag = True
        if ticket.status not in (TicketStatus.ESCALATED, TicketStatus.RESOLVED, TicketStatus.CLOSED):
            ticket.status = TicketStatus.ESCALATED
        await db.flush()

    if outcome == DecisionOutcome.AUTO_RESOLVE:
        if ticket.status not in (TicketStatus.RESOLVED, TicketStatus.CLOSED):
            ticket.status = TicketStatus.RESOLVED
            ticket.resolved_at = datetime.now(timezone.utc)
        if not ticket.resolution_note:
            suggestion = suggestions[0] if suggestions else None
            ticket.resolution_note = (
                "Auto-resolved by the decision engine."
                if not suggestion
                else f"Auto-resolved by the decision engine. Suggested response: {suggestion}"
            )
        await db.flush()
    elif outcome == DecisionOutcome.CLARIFY and ticket.status in (TicketStatus.OPEN, TicketStatus.IN_PROGRESS):
        ticket.status = TicketStatus.WAITING_ON_CUSTOMER
        await db.flush()

    return DecisionResult(
        ticket_id=ticket.id,
        intent_category=classification.intent_category,
        confidence_score=classification.confidence_score,
        confidence_level=classification.confidence_level,
        risk_score=risk.risk_score,
        risk_level=risk.risk_level,
        decision_outcome=outcome,
        suggested_agent_id=suggested_agent_id,
        suggested_agent_name=suggested_agent_name,
        response_suggestions=suggestions,
        reasoning=reasoning,
        matched_rules=matched_rules,
        escalation_summary=escalation_summary,
        suggested_priority=risk.suggested_priority,
    )


def analyze_text_only(
    text: str,
    subject: str = "",
    runtime_config: DecisionEngineRuntimeConfig | None = None,
) -> DecisionResult:
    """
    Analyze free text without a ticket (no DB operations).
    Useful for preview / testing classification.
    """
    config = runtime_config or DecisionEngineRuntimeConfig(
        confidence_high_threshold=0.7,
        confidence_medium_threshold=0.4,
        risk_critical_threshold=0.7,
        risk_high_threshold=0.5,
        risk_medium_threshold=0.3,
        low_confidence_risk_boost=0.08,
        medium_confidence_risk_boost=0.03,
        enforce_security_escalation=True,
        enforce_critical_escalation=True,
        low_confidence_general_suggest=True,
    )

    classification = classify_text(
        text=text,
        subject=subject,
        high_confidence_threshold=config.confidence_high_threshold,
        medium_confidence_threshold=config.confidence_medium_threshold,
    )
    risk = assess_risk(
        text=text,
        subject=subject,
        classification=classification,
        critical_threshold=config.risk_critical_threshold,
        high_threshold=config.risk_high_threshold,
        medium_threshold=config.risk_medium_threshold,
        low_confidence_risk_boost=config.low_confidence_risk_boost,
        medium_confidence_risk_boost=config.medium_confidence_risk_boost,
    )
    outcome, matched_rules = apply_rules(
        confidence_level=classification.confidence_level,
        risk_level=risk.risk_level,
        category=classification.intent_category,
        rule_config=DecisionRuleConfig(
            enforce_critical_escalation=config.enforce_critical_escalation,
            enforce_security_escalation=config.enforce_security_escalation,
            low_confidence_general_suggest=config.low_confidence_general_suggest,
        ),
    )
    suggestions = get_response_suggestions(
        category=classification.intent_category,
        confidence_level=classification.confidence_level,
        outcome=outcome,
    )
    reasoning = _build_reasoning(classification, risk, outcome, matched_rules)

    return DecisionResult(
        ticket_id=uuid.UUID(int=0),  # placeholder
        intent_category=classification.intent_category,
        confidence_score=classification.confidence_score,
        confidence_level=classification.confidence_level,
        risk_score=risk.risk_score,
        risk_level=risk.risk_level,
        decision_outcome=outcome,
        suggested_agent_id=None,
        suggested_agent_name=None,
        response_suggestions=suggestions,
        reasoning=reasoning,
        matched_rules=matched_rules,
        escalation_summary=None,
        suggested_priority=risk.suggested_priority,
    )


def _build_reasoning(
    classification: ClassificationResult,
    risk: RiskAssessment,
    outcome: DecisionOutcome,
    matched_rules: list[str],
) -> str:
    """Build a human-readable explanation of the decision."""
    parts = [
        f"Intent classified as {classification.intent_category.value} "
        f"with {classification.confidence_score:.1%} confidence "
        f"({classification.confidence_level.value}).",
    ]

    if classification.matched_keywords:
        keywords_str = ", ".join(classification.matched_keywords[:5])
        parts.append(f"Matched keywords: {keywords_str}.")

    parts.append(
        f"Risk assessed at {risk.risk_score:.1%} ({risk.risk_level.value})."
    )

    if risk.risk_factors:
        factors_str = "; ".join(risk.risk_factors[:3])
        parts.append(f"Risk factors: {factors_str}.")

    parts.append(
        f"Decision: {outcome.value} (rules: {', '.join(matched_rules)})."
    )

    return " ".join(parts)
