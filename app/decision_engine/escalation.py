"""
Escalation Handler — creates structured escalation packages for HITL.

When the decision engine determines ESCALATE_HUMAN, this module
generates a comprehensive handoff package containing:
  - Problem summary
  - Conversation history
  - Previous decision attempts
  - Confidence / Risk scores
  - Recommended actions
"""

import uuid
import logging

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.ticket import Ticket
from app.db.models.conversation import Conversation, Message
from app.decision_engine.enums import (
    IntentCategory,
    RiskLevel,
    ConfidenceLevel,
    DecisionOutcome,
)
from app.decision_engine.models import DecisionLog
from app.decision_engine.schemas import EscalationPackage, DecisionLogResponse

logger = logging.getLogger(__name__)


def _generate_summary(
    ticket: Ticket,
    category: IntentCategory,
    confidence: float,
    risk: float,
    risk_factors: list[str],
) -> str:
    """Generate a structured plain-text summary for the agent."""
    lines = [
        f"=== ESCALATION SUMMARY ===",
        f"Ticket: {ticket.subject}",
        f"Status: {ticket.status.value}",
        f"Priority: {ticket.priority.value}",
        f"Channel: {ticket.channel_source.value}",
        f"",
        f"--- AI Analysis ---",
        f"Intent Category: {category.value}",
        f"Confidence Score: {confidence:.1%}",
        f"Risk Score: {risk:.1%}",
        f"",
    ]
    if risk_factors:
        lines.append("Risk Factors:")
        for rf in risk_factors:
            lines.append(f"  • {rf}")
        lines.append("")

    lines.extend([
        f"--- Description ---",
        ticket.description[:1000],
        f"",
        f"=== ACTION REQUIRED ===",
        f"This ticket requires human review due to low confidence or high risk.",
    ])
    return "\n".join(lines)


def _suggest_actions(
    category: IntentCategory,
    risk_level: RiskLevel,
    confidence_level: ConfidenceLevel,
) -> list[str]:
    """Suggest recommended actions based on classification."""
    actions = []

    if risk_level in (RiskLevel.HIGH, RiskLevel.CRITICAL):
        actions.append("Review immediately — high risk situation detected")

    if confidence_level == ConfidenceLevel.LOW:
        actions.append("Verify the intent manually — AI classification confidence is low")

    category_actions = {
        IntentCategory.SECURITY: [
            "Check account for unauthorized access",
            "Verify customer identity through secondary channel",
            "Review recent login activity",
        ],
        IntentCategory.BILLING: [
            "Review transaction history",
            "Check for duplicate charges",
            "Verify subscription status",
        ],
        IntentCategory.TECHNICAL: [
            "Check system status and known issues",
            "Review error logs for the customer's account",
            "Consider escalating to engineering if not a known issue",
        ],
        IntentCategory.COMPLAINT: [
            "Review full interaction history",
            "Consider offering goodwill gesture or compensation",
            "Document the complaint for quality improvement",
        ],
        IntentCategory.URGENT: [
            "Prioritize immediate response",
            "Check for system-wide outage or incident",
            "Notify relevant engineering team if infrastructure-related",
        ],
    }

    actions.extend(category_actions.get(category, [
        "Review the ticket details carefully",
        "Contact the customer for clarification if needed",
    ]))

    return actions


async def build_escalation_package(
    db: AsyncSession,
    ticket: Ticket,
    category: IntentCategory,
    confidence_score: float,
    risk_score: float,
    risk_level: RiskLevel,
    confidence_level: ConfidenceLevel,
    risk_factors: list[str],
) -> EscalationPackage:
    """
    Build a complete escalation package for human handoff.

    Includes conversation history if the ticket has an associated conversation.
    """
    # Fetch conversation history if linked
    conversation_history: list[dict] = []
    if ticket.conversation_id:
        conv_result = await db.execute(
            select(Conversation).where(Conversation.id == ticket.conversation_id)
        )
        conversation = conv_result.scalar_one_or_none()
        if conversation:
            msg_result = await db.execute(
                select(Message)
                .where(Message.conversation_id == conversation.id)
                .order_by(Message.created_at.asc())
                .limit(50)
            )
            messages = msg_result.scalars().all()
            for msg in messages:
                conversation_history.append({
                    "sender_id": str(msg.sender_id),
                    "content": msg.content[:500],  # truncate long messages
                    "is_internal": msg.is_internal,
                    "created_at": msg.created_at.isoformat(),
                })

    # Fetch previous decision logs
    prev_decisions_result = await db.execute(
        select(DecisionLog)
        .where(DecisionLog.ticket_id == ticket.id)
        .order_by(DecisionLog.created_at.desc())
        .limit(10)
    )
    prev_decisions = [
        DecisionLogResponse.model_validate(d)
        for d in prev_decisions_result.scalars().all()
    ]

    # Generate summary and actions
    summary = _generate_summary(ticket, category, confidence_score, risk_score, risk_factors)
    recommended_actions = _suggest_actions(category, risk_level, confidence_level)

    return EscalationPackage(
        ticket_id=ticket.id,
        ticket_subject=ticket.subject,
        ticket_description=ticket.description[:2000],
        intent_category=category,
        confidence_score=confidence_score,
        risk_score=risk_score,
        risk_level=risk_level,
        conversation_history=conversation_history,
        previous_decisions=prev_decisions,
        summary=summary,
        recommended_actions=recommended_actions,
    )
