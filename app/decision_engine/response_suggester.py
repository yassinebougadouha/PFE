"""
Response Suggester — generates template-based response suggestions
based on intent category and confidence level.

Pure rule-based / template approach. LLMs would only be used for
reformulation in a future iteration (as per spec: "LLMs utilisés
uniquement pour reformulation").
"""

import logging

from app.decision_engine.enums import IntentCategory, ConfidenceLevel, DecisionOutcome

logger = logging.getLogger(__name__)


# ── Response templates per category ──────────────────────

RESPONSE_TEMPLATES: dict[IntentCategory, list[str]] = {
    IntentCategory.BILLING: [
        "Thank you for reaching out about your billing concern. Let me review your account details and the recent transactions to assist you.",
        "I understand you have a billing question. Could you please provide your invoice number or the date of the charge in question?",
        "I can see the charge on your account. Let me look into this and get back to you with a resolution shortly.",
    ],
    IntentCategory.TECHNICAL: [
        "Thank you for reporting this technical issue. Could you please provide more details about the error message or behavior you're experiencing?",
        "I'd like to help resolve this technical problem. Can you share the steps to reproduce the issue and your environment details (browser, OS, version)?",
        "I've identified a potential cause for the issue you're describing. Let me walk you through the troubleshooting steps.",
    ],
    IntentCategory.ACCOUNT: [
        "I can help you with your account. For security purposes, could you please verify your email address associated with the account?",
        "I understand you're having trouble accessing your account. Let me help you reset your credentials securely.",
        "Your account details have been updated. You should receive a confirmation email shortly.",
    ],
    IntentCategory.COMPLAINT: [
        "I sincerely apologize for the inconvenience you've experienced. Your feedback is important to us, and I want to ensure we resolve this properly.",
        "I understand your frustration and I'm sorry for the negative experience. Let me escalate this to ensure it's addressed thoroughly.",
        "Thank you for bringing this to our attention. I'm going to make sure your concern is handled with the priority it deserves.",
    ],
    IntentCategory.FEATURE_REQUEST: [
        "Thank you for your suggestion! I've noted your feature request and will forward it to our product team for consideration.",
        "That's great feedback. I can see how this feature would be valuable. Let me log this in our feature request system.",
        "I appreciate you taking the time to share this idea. Our product roadmap is reviewed regularly and your input helps us prioritize.",
    ],
    IntentCategory.SECURITY: [
        "I take your security concern very seriously. Let me immediately review your account for any unauthorized activity.",
        "For your safety, I'm going to secure your account right away. Please do not share any sensitive information in this chat.",
        "I've flagged this security concern for our security team. They will investigate immediately and take necessary action.",
    ],
    IntentCategory.URGENT: [
        "I understand this is urgent and I'm prioritizing your request immediately. Let me look into this right now.",
        "I can see this needs immediate attention. I'm escalating this to our priority support team.",
        "I'm on this right away. While I investigate, could you confirm the impact — how many users/services are affected?",
    ],
    IntentCategory.GENERAL: [
        "Thank you for contacting us. How can I assist you today?",
        "I'd be happy to help. Could you provide more details about what you need assistance with?",
        "Thank you for reaching out. Let me look into this for you.",
    ],
}

# ── Clarification templates ──────────────────────────────

CLARIFICATION_TEMPLATES: dict[IntentCategory, list[str]] = {
    IntentCategory.BILLING: [
        "Could you please clarify: is this about a specific charge, a subscription renewal, or a refund request?",
        "To help you better, could you provide your invoice number or the approximate date and amount of the transaction?",
    ],
    IntentCategory.TECHNICAL: [
        "I'd like to understand the issue better. Could you describe exactly what happens, step by step?",
        "Could you share any error messages, screenshots, or logs that might help diagnose the problem?",
    ],
    IntentCategory.ACCOUNT: [
        "Just to make sure I can help effectively — is this about logging in, updating your profile, or managing account settings?",
        "Could you let me know which specific account action you need help with?",
    ],
    IntentCategory.GENERAL: [
        "I'd like to help but I need a bit more information. Could you describe your issue or question in more detail?",
        "Could you provide more context about what you're looking for? This will help me direct you to the right solution.",
    ],
}

# Default for categories not in CLARIFICATION_TEMPLATES
DEFAULT_CLARIFICATION = [
    "Could you provide more details about your request so I can assist you more effectively?",
    "I want to make sure I understand your needs correctly. Could you elaborate a bit more?",
]


def get_response_suggestions(
    category: IntentCategory,
    confidence_level: ConfidenceLevel,
    outcome: DecisionOutcome,
    max_suggestions: int = 3,
) -> list[str]:
    """
    Generate response suggestions based on classification and decision outcome.

    Args:
        category: The classified intent category.
        confidence_level: The confidence level of the classification.
        outcome: The decision outcome.
        max_suggestions: Maximum number of suggestions to return.

    Returns:
        List of suggested response strings.
    """
    suggestions: list[str] = []

    if outcome == DecisionOutcome.CLARIFY:
        # Use clarification templates
        clarification = CLARIFICATION_TEMPLATES.get(category, DEFAULT_CLARIFICATION)
        suggestions.extend(clarification)
    else:
        # Use response templates
        templates = RESPONSE_TEMPLATES.get(category, RESPONSE_TEMPLATES[IntentCategory.GENERAL])
        suggestions.extend(templates)

    # If confidence is low and not clarifying, prepend a clarification
    if confidence_level == ConfidenceLevel.LOW and outcome != DecisionOutcome.CLARIFY:
        clarification = CLARIFICATION_TEMPLATES.get(category, DEFAULT_CLARIFICATION)
        if clarification:
            suggestions.insert(0, clarification[0])

    return suggestions[:max_suggestions]
