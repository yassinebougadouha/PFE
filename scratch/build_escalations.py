"""Build escalation packages for all existing escalated tickets."""
import asyncio, sys, os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

from sqlalchemy import select
from app.db.session import async_session_factory
from app.db.models.enums import TicketStatus
from app.db.models.ticket import Ticket
from app.decision_engine.classifier import classify_text
from app.decision_engine.scorer import assess_risk
from app.decision_engine.escalation import build_escalation_package
from app.decision_engine.config import load_runtime_config

async def main():
    async with async_session_factory() as db:
        result = await db.execute(
            select(Ticket).where(
                Ticket.status == TicketStatus.ESCALATED,
                Ticket.is_deleted == False,
            )
        )
        tickets = list(result.scalars().all())
        print(f"Found {len(tickets)} escalated tickets")

        runtime_config = await load_runtime_config(db)

        for ticket in tickets:
            print(f"\nProcessing ticket {ticket.id} ({ticket.subject[:50]})...")
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
            # Set escalation flag and status
            if ticket.status not in (TicketStatus.RESOLVED, TicketStatus.CLOSED):
                ticket.escalation_flag = True
                ticket.status = TicketStatus.ESCALATED
                await db.flush()
            print(f"  risk={risk.risk_level.value} confidence={classification.confidence_level.value} summary={package.summary[:80] if package.summary else 'N/A'}")

        print(f"\nDone. Processed {len(tickets)} escalation packages.")

asyncio.run(main())
