"""
Confidence & Risk Scorer — evaluates risk factors and adjusts confidence.

Risk scoring uses:
  - Sentiment indicators (negative language)
  - Urgency markers
  - Security-related keywords
  - Customer history signals (escalation flag, ticket priority)
  - Text complexity (very short or very long texts = higher risk)
"""

import re
import logging

from app.db.models.enums import TicketPriority
from app.decision_engine.enums import (
    IntentCategory,
    RiskLevel,
    ConfidenceLevel,
)
from app.decision_engine.schemas import RiskAssessment, ClassificationResult

logger = logging.getLogger(__name__)


# ── Risk factor dictionaries ─────────────────────────────

NEGATIVE_SENTIMENT_WORDS = {
    "angry", "furious", "outraged", "disgusted", "terrible", "horrible",
    "awful", "worst", "hate", "unacceptable", "ridiculous", "absurd",
    "incompetent", "useless", "scam", "fraud", "lie", "liar", "cheat",
    "threatening", "lawsuit", "lawyer", "legal", "sue", "court",
    "en colère", "furieux", "dégoûté", "horrible", "arnaque", "avocat",
}

URGENCY_MARKERS = {
    "urgent", "asap", "immediately", "emergency", "critical",
    "right now", "production down", "outage", "deadline",
    "blocked", "can't work", "system down",
    "urgent", "immédiatement", "critique",
}

ESCALATION_TRIGGERS = {
    "manager", "supervisor", "escalate", "escalation",
    "higher authority", "speak to someone", "not resolved",
    "already contacted", "multiple times", "third time",
    "responsable", "superviseur", "escalader",
}

SECURITY_RISK_WORDS = {
    "breach", "hack", "hacked", "compromised", "phishing",
    "unauthorized", "stolen", "theft", "vulnerability",
    "exploit", "malware", "data leak",
    "piratage", "piraté", "vol de données",
}

# ── Category → inherent risk mapping ─────────────────────

CATEGORY_BASE_RISK: dict[IntentCategory, float] = {
    IntentCategory.SECURITY: 0.7,
    IntentCategory.URGENT: 0.6,
    IntentCategory.COMPLAINT: 0.5,
    IntentCategory.BILLING: 0.3,
    IntentCategory.TECHNICAL: 0.25,
    IntentCategory.ACCOUNT: 0.2,
    IntentCategory.FEATURE_REQUEST: 0.1,
    IntentCategory.GENERAL: 0.1,
}


def _count_matches(text: str, word_set: set[str]) -> tuple[int, list[str]]:
    """Count how many words from the set appear in the text."""
    text_lower = text.lower()
    matched = []
    for word in word_set:
        if word.lower() in text_lower:
            matched.append(word)
    return len(matched), matched


def assess_risk(
    text: str,
    subject: str,
    classification: ClassificationResult,
    existing_priority: TicketPriority = TicketPriority.MEDIUM,
    has_escalation_flag: bool = False,
    critical_threshold: float = 0.7,
    high_threshold: float = 0.5,
    medium_threshold: float = 0.3,
    low_confidence_risk_boost: float = 0.08,
    medium_confidence_risk_boost: float = 0.03,
) -> RiskAssessment:
    """
    Compute a risk score (0.0–1.0) based on text analysis and context.

    Factors:
      1. Category inherent risk
      2. Negative sentiment density
      3. Urgency markers
      4. Escalation triggers
      5. Security risk words
      6. Text length extremes
      7. Existing ticket priority
      8. Existing escalation flag
      9. Low classification confidence as risk amplifier
    """
    combined = f"{subject} {text}" if subject else text
    risk_score = 0.0
    risk_factors: list[str] = []

    # 1. Category base risk (0.0 – 0.7)
    base_risk = CATEGORY_BASE_RISK.get(classification.intent_category, 0.1)
    risk_score += base_risk * 0.3
    if base_risk >= 0.5:
        risk_factors.append(f"High-risk category: {classification.intent_category.value}")

    # 2. Negative sentiment (0.0 – 0.2)
    neg_count, neg_matched = _count_matches(combined, NEGATIVE_SENTIMENT_WORDS)
    sentiment_factor = min(neg_count * 0.05, 0.2)
    risk_score += sentiment_factor
    if neg_count > 0:
        risk_factors.append(f"Negative sentiment ({neg_count} indicators): {', '.join(neg_matched[:3])}")

    # 3. Urgency markers (0.0 – 0.15)
    urg_count, urg_matched = _count_matches(combined, URGENCY_MARKERS)
    urgency_factor = min(urg_count * 0.05, 0.15)
    risk_score += urgency_factor
    if urg_count > 0:
        risk_factors.append(f"Urgency detected ({urg_count} markers)")

    # 4. Escalation triggers (0.0 – 0.15)
    esc_count, esc_matched = _count_matches(combined, ESCALATION_TRIGGERS)
    escalation_factor = min(esc_count * 0.05, 0.15)
    risk_score += escalation_factor
    if esc_count > 0:
        risk_factors.append(f"Escalation language detected ({esc_count} triggers)")

    # 5. Security risk words (0.0 – 0.15)
    sec_count, sec_matched = _count_matches(combined, SECURITY_RISK_WORDS)
    security_factor = min(sec_count * 0.05, 0.15)
    risk_score += security_factor
    if sec_count > 0:
        risk_factors.append(f"Security concerns ({sec_count} indicators)")

    # 6. Text length (0.0 – 0.05)
    word_count = len(combined.split())
    if word_count < 5:
        risk_score += 0.05
        risk_factors.append("Very short text (insufficient context)")
    elif word_count > 500:
        risk_score += 0.03
        risk_factors.append("Very long text (complex issue)")

    # 7. Existing priority amplifier
    priority_risk_map = {
        TicketPriority.CRITICAL: 0.1,
        TicketPriority.HIGH: 0.05,
        TicketPriority.MEDIUM: 0.0,
        TicketPriority.LOW: -0.03,
    }
    risk_score += priority_risk_map.get(existing_priority, 0.0)
    if existing_priority in (TicketPriority.CRITICAL, TicketPriority.HIGH):
        risk_factors.append(f"Existing priority: {existing_priority.value}")

    # 8. Escalation flag
    if has_escalation_flag:
        risk_score += 0.1
        risk_factors.append("Previously flagged for escalation")

    # 9. Low confidence amplifies risk
    if classification.confidence_level == ConfidenceLevel.LOW:
        risk_score += low_confidence_risk_boost
        risk_factors.append("Low classification confidence increases uncertainty")
    elif classification.confidence_level == ConfidenceLevel.MEDIUM:
        risk_score += medium_confidence_risk_boost

    # Clamp to [0.0, 1.0]
    risk_score = round(min(max(risk_score, 0.0), 1.0), 3)

    # Determine risk level
    if risk_score >= critical_threshold:
        risk_level = RiskLevel.CRITICAL
    elif risk_score >= high_threshold:
        risk_level = RiskLevel.HIGH
    elif risk_score >= medium_threshold:
        risk_level = RiskLevel.MEDIUM
    else:
        risk_level = RiskLevel.LOW

    # Suggest priority based on risk
    if risk_score >= critical_threshold:
        suggested_priority = TicketPriority.CRITICAL
    elif risk_score >= high_threshold:
        suggested_priority = TicketPriority.HIGH
    elif risk_score >= medium_threshold:
        suggested_priority = TicketPriority.MEDIUM
    else:
        suggested_priority = TicketPriority.LOW

    return RiskAssessment(
        risk_score=risk_score,
        risk_level=risk_level,
        risk_factors=risk_factors,
        suggested_priority=suggested_priority,
    )
