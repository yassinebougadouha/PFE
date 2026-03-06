"""
Decision Engine enumerations.
"""

import enum


class IntentCategory(str, enum.Enum):
    """Predicted intent categories for incoming tickets/messages."""
    BILLING = "BILLING"
    TECHNICAL = "TECHNICAL"
    ACCOUNT = "ACCOUNT"
    GENERAL = "GENERAL"
    COMPLAINT = "COMPLAINT"
    FEATURE_REQUEST = "FEATURE_REQUEST"
    SECURITY = "SECURITY"
    URGENT = "URGENT"


class DecisionOutcome(str, enum.Enum):
    """Outcome of the adaptive decision engine."""
    AUTO_RESOLVE = "AUTO_RESOLVE"       # High confidence + low risk
    SUGGEST_RESPONSE = "SUGGEST_RESPONSE"  # High confidence, moderate risk
    CLARIFY = "CLARIFY"                 # Medium confidence
    ESCALATE_HUMAN = "ESCALATE_HUMAN"   # Low confidence or high risk
    ROUTE_AGENT = "ROUTE_AGENT"         # Routed to specific agent


class RiskLevel(str, enum.Enum):
    """Computed risk level."""
    LOW = "LOW"
    MEDIUM = "MEDIUM"
    HIGH = "HIGH"
    CRITICAL = "CRITICAL"


class ConfidenceLevel(str, enum.Enum):
    """Computed confidence level."""
    HIGH = "HIGH"
    MEDIUM = "MEDIUM"
    LOW = "LOW"
