"""
Rule-based decision logic — configurable rules that map
(confidence_level, risk_level) → DecisionOutcome.

Implements the adaptive decision matrix from the spec:
  - Haute confiance + faible risque → Résolution automatique
  - Confiance moyenne → Clarification guidée
  - Faible confiance ou risque élevé → Escalade humaine
"""

import logging
from dataclasses import dataclass

from app.decision_engine.enums import (
    ConfidenceLevel,
    RiskLevel,
    DecisionOutcome,
    IntentCategory,
)

logger = logging.getLogger(__name__)


@dataclass
class DecisionRule:
    """A single decision rule with optional category constraint."""
    name: str
    confidence_levels: list[ConfidenceLevel]
    risk_levels: list[RiskLevel]
    outcome: DecisionOutcome
    category: IntentCategory | None = None  # None = applies to all categories
    priority: int = 0  # higher priority rules are checked first


# ── Decision rule set ─────────────────────────────────────
# Rules are ordered by priority (highest first).

DECISION_RULES: list[DecisionRule] = [
    # ── Critical: always escalate ──
    DecisionRule(
        name="critical_risk_always_escalate",
        confidence_levels=[ConfidenceLevel.HIGH, ConfidenceLevel.MEDIUM, ConfidenceLevel.LOW],
        risk_levels=[RiskLevel.CRITICAL],
        outcome=DecisionOutcome.ESCALATE_HUMAN,
        priority=100,
    ),

    # ── Security: always escalate regardless of confidence ──
    DecisionRule(
        name="security_always_escalate",
        confidence_levels=[ConfidenceLevel.HIGH, ConfidenceLevel.MEDIUM, ConfidenceLevel.LOW],
        risk_levels=[RiskLevel.LOW, RiskLevel.MEDIUM, RiskLevel.HIGH, RiskLevel.CRITICAL],
        outcome=DecisionOutcome.ESCALATE_HUMAN,
        category=IntentCategory.SECURITY,
        priority=95,
    ),

    # ── Low confidence: always escalate ──
    DecisionRule(
        name="low_confidence_escalate",
        confidence_levels=[ConfidenceLevel.LOW],
        risk_levels=[RiskLevel.LOW, RiskLevel.MEDIUM, RiskLevel.HIGH, RiskLevel.CRITICAL],
        outcome=DecisionOutcome.ESCALATE_HUMAN,
        priority=90,
    ),

    # ── High risk + any confidence: escalate ──
    DecisionRule(
        name="high_risk_escalate",
        confidence_levels=[ConfidenceLevel.HIGH, ConfidenceLevel.MEDIUM],
        risk_levels=[RiskLevel.HIGH],
        outcome=DecisionOutcome.ESCALATE_HUMAN,
        priority=85,
    ),

    # ── High confidence + low risk: auto-resolve ──
    DecisionRule(
        name="high_confidence_low_risk_auto_resolve",
        confidence_levels=[ConfidenceLevel.HIGH],
        risk_levels=[RiskLevel.LOW],
        outcome=DecisionOutcome.AUTO_RESOLVE,
        priority=70,
    ),

    # ── High confidence + medium risk: suggest response ──
    DecisionRule(
        name="high_confidence_medium_risk_suggest",
        confidence_levels=[ConfidenceLevel.HIGH],
        risk_levels=[RiskLevel.MEDIUM],
        outcome=DecisionOutcome.SUGGEST_RESPONSE,
        priority=60,
    ),

    # ── Medium confidence + low risk: suggest response ──
    DecisionRule(
        name="medium_confidence_low_risk_suggest",
        confidence_levels=[ConfidenceLevel.MEDIUM],
        risk_levels=[RiskLevel.LOW],
        outcome=DecisionOutcome.SUGGEST_RESPONSE,
        priority=50,
    ),

    # ── Medium confidence + medium risk: clarify ──
    DecisionRule(
        name="medium_confidence_medium_risk_clarify",
        confidence_levels=[ConfidenceLevel.MEDIUM],
        risk_levels=[RiskLevel.MEDIUM],
        outcome=DecisionOutcome.CLARIFY,
        priority=40,
    ),
]

# Sort rules by priority (descending)
DECISION_RULES.sort(key=lambda r: r.priority, reverse=True)


def apply_rules(
    confidence_level: ConfidenceLevel,
    risk_level: RiskLevel,
    category: IntentCategory,
) -> tuple[DecisionOutcome, list[str]]:
    """
    Apply the decision rule set to determine the outcome.

    Returns:
        (DecisionOutcome, list of matched rule names)
    """
    matched_rules: list[str] = []

    for rule in DECISION_RULES:
        # Check category constraint
        if rule.category is not None and rule.category != category:
            continue

        # Check if confidence and risk match
        if confidence_level in rule.confidence_levels and risk_level in rule.risk_levels:
            matched_rules.append(rule.name)
            logger.info(
                f"Decision rule matched: {rule.name} → {rule.outcome.value} "
                f"(confidence={confidence_level.value}, risk={risk_level.value}, "
                f"category={category.value})"
            )
            return rule.outcome, matched_rules

    # Fallback: if no rule matched, route to agent
    logger.warning(
        f"No decision rule matched for confidence={confidence_level.value}, "
        f"risk={risk_level.value}, category={category.value}. "
        f"Falling back to ROUTE_AGENT."
    )
    matched_rules.append("fallback_route_agent")
    return DecisionOutcome.ROUTE_AGENT, matched_rules
