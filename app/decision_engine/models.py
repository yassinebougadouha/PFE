"""
Decision Engine DB models — DecisionLog and AgentSkill.
"""

import uuid

from sqlalchemy import String, Text, Float, ForeignKey, Index, JSON
from sqlalchemy.dialects.postgresql import UUID, ENUM
from sqlalchemy.orm import Mapped, mapped_column, relationship

from app.db.base import Base, TimestampMixin, UUIDPrimaryKeyMixin
from app.decision_engine.enums import (
    IntentCategory,
    DecisionOutcome,
    RiskLevel,
    ConfidenceLevel,
)

# Use PostgreSQL-native ENUM with create_type=False to prevent
# SQLAlchemy from trying to CREATE TYPE (migration handles that).
_intent_category = ENUM(IntentCategory, name="intent_category", create_type=False)
_confidence_level = ENUM(ConfidenceLevel, name="confidence_level", create_type=False)
_risk_level = ENUM(RiskLevel, name="risk_level", create_type=False)
_decision_outcome = ENUM(DecisionOutcome, name="decision_outcome", create_type=False)


class DecisionLog(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    """
    Records every AI decision made for a ticket.
    Provides full traceability and auditability as required by the spec.
    """
    __tablename__ = "decision_logs"

    # ── Link to ticket ────────────────────────────
    ticket_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("tickets.id"), nullable=False, index=True,
    )

    # ── Classification results ────────────────────
    intent_category: Mapped[IntentCategory] = mapped_column(
        _intent_category, nullable=False,
    )
    confidence_score: Mapped[float] = mapped_column(
        Float, nullable=False, doc="0.0–1.0 confidence of the classification",
    )
    confidence_level: Mapped[ConfidenceLevel] = mapped_column(
        _confidence_level, nullable=False,
    )
    risk_score: Mapped[float] = mapped_column(
        Float, nullable=False, doc="0.0–1.0 risk score",
    )
    risk_level: Mapped[RiskLevel] = mapped_column(
        _risk_level, nullable=False,
    )

    # ── Decision outcome ──────────────────────────
    decision_outcome: Mapped[DecisionOutcome] = mapped_column(
        _decision_outcome, nullable=False, index=True,
    )

    # ── Routing ───────────────────────────────────
    suggested_agent_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=True,
    )

    # ── Suggestions & reasoning ───────────────────
    response_suggestions: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, doc="List of suggested responses as JSON",
    )
    reasoning: Mapped[str | None] = mapped_column(
        Text, nullable=True, doc="Explanation of why this decision was made",
    )
    matched_rules: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, doc="Rules that triggered this decision",
    )

    # ── Escalation data ──────────────────────────
    escalation_summary: Mapped[str | None] = mapped_column(
        Text, nullable=True, doc="Structured summary for HITL escalation",
    )

    # ── Relationships ─────────────────────────────
    ticket = relationship("Ticket", backref="decision_logs")
    suggested_agent = relationship("User", foreign_keys=[suggested_agent_id])

    __table_args__ = (
        Index("ix_decision_logs_ticket_created", "ticket_id", "created_at"),
        Index("ix_decision_logs_outcome", "decision_outcome"),
    )


class AgentSkill(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    """
    Maps agents to their skill categories for smart routing.
    An agent can have multiple skills with proficiency levels.
    """
    __tablename__ = "agent_skills"

    agent_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id"), nullable=False, index=True,
    )
    skill_category: Mapped[IntentCategory] = mapped_column(
        _intent_category, nullable=False,
    )
    proficiency: Mapped[float] = mapped_column(
        Float, default=0.5, nullable=False,
        doc="0.0–1.0 proficiency level in this category",
    )
    max_concurrent_tickets: Mapped[int] = mapped_column(
        default=10, nullable=False,
        doc="Maximum concurrent tickets this agent can handle for this skill",
    )

    # ── Relationships ─────────────────────────────
    agent = relationship("User", backref="skills")

    __table_args__ = (
        Index("ix_agent_skills_agent_category", "agent_id", "skill_category", unique=True),
    )
