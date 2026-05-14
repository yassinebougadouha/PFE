import asyncio
import uuid
from types import SimpleNamespace

from app.db.models.enums import ChannelType, TicketPriority, TicketStatus
from app.db.models.ticket import Ticket
from app.decision_engine.config import DecisionEngineRuntimeConfig
from app.decision_engine.decision_engine import analyze_ticket
from app.decision_engine.enums import ConfidenceLevel, DecisionOutcome, IntentCategory, RiskLevel
from app.decision_engine.schemas import ClassificationResult, RiskAssessment
from app.schemas.ticket import TicketCreate
from app.services.ticket_service import TicketService


class DummyDB:
    def __init__(self):
        self.added = []
        self.flush_count = 0
        self.refresh_count = 0

    def add(self, item):
        self.added.append(item)

    async def flush(self):
        self.flush_count += 1

    async def refresh(self, _item):
        self.refresh_count += 1

    async def get(self, model, id):
        return None


def _runtime_config() -> DecisionEngineRuntimeConfig:
    return DecisionEngineRuntimeConfig(
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


def _ticket() -> Ticket:
    return Ticket(
        id=uuid.uuid4(),
        subject="Question about account setup",
        description="How do I update my profile?",
        priority=TicketPriority.MEDIUM,
        channel_source=ChannelType.TICKET,
        creator_id=uuid.uuid4(),
        status=TicketStatus.OPEN,
        escalation_flag=False,
    )


def test_decision_engine_auto_resolve_marks_ticket_resolved(monkeypatch):
    async def fake_config(_db):
        return _runtime_config()

    def fake_classify_text(**_kwargs):
        return ClassificationResult(
            intent_category=IntentCategory.GENERAL,
            confidence_score=0.9,
            confidence_level=ConfidenceLevel.HIGH,
            matched_keywords=[],
        )

    def fake_assess_risk(**_kwargs):
        return RiskAssessment(
            risk_score=0.1,
            risk_level=RiskLevel.LOW,
            risk_factors=[],
            suggested_priority=TicketPriority.LOW,
        )

    monkeypatch.setattr("app.decision_engine.decision_engine.load_runtime_config", fake_config)
    monkeypatch.setattr("app.decision_engine.decision_engine.classify_text", fake_classify_text)
    monkeypatch.setattr("app.decision_engine.decision_engine.assess_risk", fake_assess_risk)
    monkeypatch.setattr(
        "app.decision_engine.decision_engine.apply_rules",
        lambda **_kwargs: (DecisionOutcome.AUTO_RESOLVE, ["test_auto_resolve"]),
    )
    monkeypatch.setattr(
        "app.decision_engine.decision_engine.get_response_suggestions",
        lambda **_kwargs: ["This is resolved."],
    )

    ticket = _ticket()
    result = asyncio.run(
        analyze_ticket(
            db=DummyDB(),
            ticket=ticket,
            auto_assign=True,
            auto_update_priority=True,
        )
    )

    assert result.decision_outcome == DecisionOutcome.AUTO_RESOLVE
    assert ticket.status == TicketStatus.RESOLVED
    assert ticket.priority == TicketPriority.LOW
    assert ticket.resolved_at is not None
    assert "Auto-resolved by the decision engine" in ticket.resolution_note


def test_decision_engine_clarify_marks_ticket_waiting_on_customer(monkeypatch):
    async def fake_config(_db):
        return _runtime_config()

    monkeypatch.setattr("app.decision_engine.decision_engine.load_runtime_config", fake_config)
    monkeypatch.setattr(
        "app.decision_engine.decision_engine.classify_text",
        lambda **_kwargs: ClassificationResult(
            intent_category=IntentCategory.GENERAL,
            confidence_score=0.2,
            confidence_level=ConfidenceLevel.LOW,
            matched_keywords=[],
        ),
    )
    monkeypatch.setattr(
        "app.decision_engine.decision_engine.assess_risk",
        lambda **_kwargs: RiskAssessment(
            risk_score=0.1,
            risk_level=RiskLevel.LOW,
            risk_factors=[],
            suggested_priority=TicketPriority.MEDIUM,
        ),
    )
    monkeypatch.setattr(
        "app.decision_engine.decision_engine.apply_rules",
        lambda **_kwargs: (DecisionOutcome.CLARIFY, ["test_clarify"]),
    )
    monkeypatch.setattr(
        "app.decision_engine.decision_engine.get_response_suggestions",
        lambda **_kwargs: ["Could you share more detail?"],
    )

    ticket = _ticket()
    result = asyncio.run(
        analyze_ticket(
            db=DummyDB(),
            ticket=ticket,
            auto_assign=True,
            auto_update_priority=True,
        )
    )

    assert result.decision_outcome == DecisionOutcome.CLARIFY
    assert ticket.status == TicketStatus.WAITING_ON_CUSTOMER


def test_ticket_creation_runs_decision_engine(monkeypatch):
    db = DummyDB()
    service = TicketService(db)
    creator_id = uuid.uuid4()
    assigned_agent_id = uuid.uuid4()
    calls: dict[str, object] = {}

    async def fake_analyze_ticket(*, db, ticket, auto_assign, auto_update_priority):
        calls["auto_assign"] = auto_assign
        calls["auto_update_priority"] = auto_update_priority
        ticket.id = uuid.uuid4()
        ticket.assigned_agent_id = assigned_agent_id
        ticket.status = TicketStatus.IN_PROGRESS
        return SimpleNamespace(
                decision_outcome=DecisionOutcome.ROUTE_AGENT,
                response_suggestions=[],
                confidence_score=0.8,
                risk_score=0.2,
                escalation_summary=None,
            )

    async def fake_notify_new_ticket(ticket):
        calls["new_ticket"] = ticket.id

    async def fake_notify_assignment(ticket, user_id):
        calls["assigned"] = (ticket.id, user_id)

    async def fake_creator_notification(_ticket, _decision):
        calls["creator_notification"] = True

    monkeypatch.setattr("app.services.ticket_service.analyze_ticket", fake_analyze_ticket)
    monkeypatch.setattr(service.notification_service, "notify_new_ticket", fake_notify_new_ticket)
    monkeypatch.setattr(service.notification_service, "notify_assignment", fake_notify_assignment)
    monkeypatch.setattr(service, "_notify_creator_decision_action", fake_creator_notification)

    ticket = asyncio.run(
        service.create_ticket(
            creator_id,
            TicketCreate(
                subject="Route this ticket",
                description="Please route this technical issue to support.",
            ),
        )
    )

    assert calls["auto_assign"] is True
    assert calls["auto_update_priority"] is True
    assert calls["new_ticket"] == ticket.id
    assert calls["assigned"] == (ticket.id, assigned_agent_id)
    assert calls["creator_notification"] is True
