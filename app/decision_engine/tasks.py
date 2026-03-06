"""
Decision Engine Celery tasks — async ticket analysis.

Provides a fire-and-forget task that can be triggered when a ticket is created,
allowing the decision engine to run without blocking the API response.
"""

import uuid
import logging

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.workers.celery_app import celery_app

logger = logging.getLogger(__name__)


@celery_app.task(
    name="app.decision_engine.tasks.analyze_ticket_task",
    bind=True,
    max_retries=3,
    default_retry_delay=30,
)
def analyze_ticket_task(
    self,
    ticket_id: str,
    auto_assign: bool = False,
    auto_update_priority: bool = True,
):
    """
    Celery task to run the decision engine on a ticket asynchronously.

    Uses synchronous DB session since Celery workers run in sync context.
    Imports decision engine components and runs classification/scoring/routing.
    """
    from app.core.config import get_settings
    from sqlalchemy import create_engine
    from sqlalchemy.orm import sessionmaker

    settings = get_settings()
    _sync_url = settings.DATABASE_URL.replace("+asyncpg", "+psycopg2")
    engine = create_engine(_sync_url, pool_size=5, max_overflow=5)
    SyncSessionLocal = sessionmaker(bind=engine)

    logger.info(f"[Decision Engine Task] Analyzing ticket {ticket_id}")

    with SyncSessionLocal() as db:
        try:
            from app.db.models.ticket import Ticket
            from app.db.models.enums import TicketStatus

            # Fetch ticket
            ticket = db.execute(
                select(Ticket).where(
                    Ticket.id == uuid.UUID(ticket_id),
                    Ticket.is_deleted == False,
                )
            ).scalar_one_or_none()

            if not ticket:
                logger.error(f"Ticket {ticket_id} not found")
                return {"status": "error", "detail": "Ticket not found"}

            # Run classification
            from app.decision_engine.classifier import classify_text
            classification = classify_text(
                text=ticket.description,
                subject=ticket.subject,
            )

            # Run risk assessment
            from app.decision_engine.scorer import assess_risk
            risk = assess_risk(
                text=ticket.description,
                subject=ticket.subject,
                classification=classification,
                existing_priority=ticket.priority,
                has_escalation_flag=ticket.escalation_flag,
            )

            # Apply rules
            from app.decision_engine.rules import apply_rules
            from app.decision_engine.enums import DecisionOutcome
            outcome, matched_rules = apply_rules(
                confidence_level=classification.confidence_level,
                risk_level=risk.risk_level,
                category=classification.intent_category,
            )

            # Get response suggestions
            from app.decision_engine.response_suggester import get_response_suggestions
            suggestions = get_response_suggestions(
                category=classification.intent_category,
                confidence_level=classification.confidence_level,
                outcome=outcome,
            )

            # Persist decision log (sync)
            from app.decision_engine.models import DecisionLog
            decision_log = DecisionLog(
                ticket_id=ticket.id,
                intent_category=classification.intent_category,
                confidence_score=classification.confidence_score,
                confidence_level=classification.confidence_level,
                risk_score=risk.risk_score,
                risk_level=risk.risk_level,
                decision_outcome=outcome,
                response_suggestions={"suggestions": suggestions},
                reasoning=(
                    f"Intent: {classification.intent_category.value} "
                    f"(confidence={classification.confidence_score:.3f}). "
                    f"Risk: {risk.risk_score:.3f} ({risk.risk_level.value}). "
                    f"Decision: {outcome.value}."
                ),
                matched_rules={"rules": matched_rules},
            )
            db.add(decision_log)

            # Auto-update priority
            if auto_update_priority and risk.suggested_priority != ticket.priority:
                logger.info(
                    f"Auto-updating priority: {ticket.priority.value} → "
                    f"{risk.suggested_priority.value}"
                )
                ticket.priority = risk.suggested_priority

            # Auto-escalate
            if outcome == DecisionOutcome.ESCALATE_HUMAN:
                ticket.escalation_flag = True
                if ticket.status not in (
                    TicketStatus.ESCALATED,
                    TicketStatus.RESOLVED,
                    TicketStatus.CLOSED,
                ):
                    ticket.status = TicketStatus.ESCALATED

            db.commit()

            logger.info(
                f"[Decision Engine Task] Ticket {ticket_id} analyzed: "
                f"intent={classification.intent_category.value}, "
                f"confidence={classification.confidence_score:.3f}, "
                f"risk={risk.risk_score:.3f}, "
                f"outcome={outcome.value}"
            )

            return {
                "status": "success",
                "ticket_id": ticket_id,
                "intent": classification.intent_category.value,
                "confidence": classification.confidence_score,
                "risk": risk.risk_score,
                "outcome": outcome.value,
            }

        except Exception as exc:
            db.rollback()
            logger.exception(f"[Decision Engine Task] Failed for ticket {ticket_id}")
            raise self.retry(exc=exc, countdown=60)
