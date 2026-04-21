"""
Intent Classifier — keyword-based + weighted rule classification.

Uses a hybrid approach: keyword matching with TF-IDF-like weighting.
No external ML dependencies needed — pure Python, deterministic, and interpretable.
"""

import re
import logging
from dataclasses import dataclass, field

from app.decision_engine.enums import IntentCategory, ConfidenceLevel
from app.decision_engine.schemas import ClassificationResult

logger = logging.getLogger(__name__)


@dataclass
class CategoryRule:
    """A rule that maps keywords/patterns to an intent category."""
    category: IntentCategory
    keywords: list[str] = field(default_factory=list)
    patterns: list[str] = field(default_factory=list)  # regex patterns
    weight: float = 1.0  # multiplier for this category


# ── Keyword dictionaries per category ─────────────────────

CATEGORY_RULES: list[CategoryRule] = [
    CategoryRule(
        category=IntentCategory.BILLING,
        keywords=[
            "invoice", "bill", "billing", "payment", "charge", "refund",
            "subscription", "plan", "pricing", "price", "cost", "fee",
            "credit", "debit", "discount", "coupon", "promo", "overcharge",
            "receipt", "transaction", "renewal", "cancel subscription",
            "facture", "paiement", "remboursement", "abonnement", "tarif",
        ],
        patterns=[
            r"\$\d+", r"€\d+", r"\d+\s*(?:dollars|euros|usd|eur)",
            r"(?:charged|billed)\s+(?:twice|double|wrong)",
        ],
        weight=1.0,
    ),
    CategoryRule(
        category=IntentCategory.TECHNICAL,
        keywords=[
            "bug", "error", "crash", "broken", "fix", "issue", "problem",
            "not working", "doesn't work", "can't", "cannot", "fail",
            "failure", "timeout", "slow", "lag", "freeze", "frozen",
            "loading", "stuck", "api", "endpoint", "server", "database",
            "exception", "traceback", "debug", "install", "update",
            "version", "compatibility", "integration", "configuration",
            "erreur", "problème", "ne fonctionne pas", "lent", "bloqué",
        ],
        patterns=[
            r"(?:error|status)\s*(?:code)?\s*\d{3}",
            r"(?:http|https)://",
            r"stack\s*trace",
        ],
        weight=1.0,
    ),
    CategoryRule(
        category=IntentCategory.ACCOUNT,
        keywords=[
            "account", "login", "password", "reset", "register",
            "sign up", "sign in", "profile", "settings", "email change",
            "username", "authentication", "two-factor", "2fa", "mfa",
            "locked out", "locked", "access", "permissions", "role",
            "deactivate", "delete account", "close account",
            "compte", "mot de passe", "connexion", "inscription",
        ],
        patterns=[
            r"(?:forgot|reset|change)\s+(?:my\s+)?password",
            r"can'?t\s+(?:log\s*in|sign\s*in|access)",
        ],
        weight=1.0,
    ),
    CategoryRule(
        category=IntentCategory.COMPLAINT,
        keywords=[
            "complaint", "complain", "unhappy", "dissatisfied", "terrible",
            "awful", "horrible", "worst", "unacceptable", "angry",
            "frustrated", "disappointed", "disgusted", "outraged",
            "ridiculous", "incompetent", "useless", "waste",
            "plainte", "mécontent", "insatisfait", "déçu", "inacceptable",
        ],
        patterns=[
            r"(?:i\s+)?(?:want|need)\s+(?:to\s+)?(?:speak|talk)\s+(?:to|with)\s+(?:a\s+)?(?:manager|supervisor)",
            r"(?:never|worst)\s+(?:again|experience|service)",
        ],
        weight=1.2,  # slightly higher weight for complaints
    ),
    CategoryRule(
        category=IntentCategory.FEATURE_REQUEST,
        keywords=[
            "feature", "request", "suggestion", "would be nice",
            "could you add", "please add", "wishlist", "improvement",
            "enhance", "enhancement", "new feature", "idea",
            "propose", "roadmap", "upcoming",
            "fonctionnalité", "suggestion", "amélioration",
        ],
        patterns=[
            r"(?:can|could)\s+you\s+(?:add|implement|create|build)",
            r"(?:it\s+)?would\s+be\s+(?:great|nice|helpful)",
        ],
        weight=0.8,
    ),
    CategoryRule(
        category=IntentCategory.SECURITY,
        keywords=[
            "security", "breach", "hack", "hacked", "compromised",
            "phishing", "spam", "suspicious", "unauthorized", "theft",
            "stolen", "vulnerability", "exploit", "malware", "virus",
            "data leak", "privacy", "gdpr", "data protection",
            "sécurité", "piratage", "piraté", "suspect", "vol",
        ],
        patterns=[
            r"(?:someone|somebody)\s+(?:accessed|hacked|stole)",
            r"(?:unauthorized|suspicious)\s+(?:access|activity|login)",
        ],
        weight=1.3,  # security is high-weight
    ),
    CategoryRule(
        category=IntentCategory.URGENT,
        keywords=[
            "urgent", "emergency", "critical", "asap", "immediately",
            "right now", "can't wait", "production down", "outage",
            "downtime", "system down", "site down", "blocked",
            "deadline", "time sensitive",
            "urgente", "critique", "immédiatement",
        ],
        patterns=[
            r"(?:production|system|site|server|service)\s+(?:is\s+)?(?:down|offline|unavailable)",
            r"(?:need|require)\s+(?:this\s+)?(?:immediately|asap|now|urgent)",
        ],
        weight=1.5,  # urgent gets highest weight
    ),
]


def _normalize_text(text: str) -> str:
    """Lowercase and normalize whitespace."""
    text = text.lower().strip()
    text = re.sub(r"\s+", " ", text)
    return text


def _score_category(text: str, rule: CategoryRule) -> tuple[float, list[str]]:
    """
    Score how well a text matches a category rule.
    Returns (score, matched_keywords).
    """
    score = 0.0
    matched = []
    normalized = _normalize_text(text)

    # Keyword matching
    for keyword in rule.keywords:
        kw_lower = keyword.lower()
        if kw_lower in normalized:
            # Multi-word keywords get bonus points
            word_count = len(kw_lower.split())
            score += (1.0 + 0.5 * (word_count - 1))
            matched.append(keyword)

    # Regex pattern matching
    for pattern in rule.patterns:
        try:
            if re.search(pattern, normalized, re.IGNORECASE):
                score += 2.0  # patterns are more specific → higher score
                matched.append(f"pattern:{pattern}")
        except re.error:
            logger.warning(f"Invalid regex pattern: {pattern}")

    # Apply category weight
    score *= rule.weight

    return score, matched


def classify_text(
    text: str,
    subject: str = "",
    high_confidence_threshold: float = 0.7,
    medium_confidence_threshold: float = 0.4,
) -> ClassificationResult:
    """
    Classify text into an intent category using keyword + pattern matching.

    Args:
        text: The main text body (ticket description or message content).
        subject: Optional subject line for additional signal.

    Returns:
        ClassificationResult with category, confidence, and matched keywords.
    """
    combined_text = f"{subject} {text}" if subject else text

    if not combined_text.strip():
        return ClassificationResult(
            intent_category=IntentCategory.GENERAL,
            confidence_score=0.1,
            confidence_level=ConfidenceLevel.LOW,
            matched_keywords=[],
        )

    # Score each category
    scores: list[tuple[IntentCategory, float, list[str]]] = []
    for rule in CATEGORY_RULES:
        score, matched = _score_category(combined_text, rule)
        if score > 0:
            scores.append((rule.category, score, matched))

    if not scores:
        return ClassificationResult(
            intent_category=IntentCategory.GENERAL,
            confidence_score=0.2,
            confidence_level=ConfidenceLevel.LOW,
            matched_keywords=[],
        )

    # Sort by score descending
    scores.sort(key=lambda x: x[1], reverse=True)
    best_category, best_score, best_matched = scores[0]

    # Calculate confidence: ratio of best score to total + absolute factor
    total_score = sum(s[1] for s in scores)
    dominance_ratio = best_score / total_score if total_score > 0 else 0

    # Normalize absolute score (diminishing returns above 10)
    absolute_factor = min(best_score / 10.0, 1.0)

    # Combined confidence: 60% dominance + 40% absolute
    confidence = 0.6 * dominance_ratio + 0.4 * absolute_factor
    confidence = round(min(max(confidence, 0.05), 0.99), 3)

    # Determine confidence level
    if confidence >= high_confidence_threshold:
        level = ConfidenceLevel.HIGH
    elif confidence >= medium_confidence_threshold:
        level = ConfidenceLevel.MEDIUM
    else:
        level = ConfidenceLevel.LOW

    return ClassificationResult(
        intent_category=best_category,
        confidence_score=confidence,
        confidence_level=level,
        matched_keywords=best_matched,
    )
