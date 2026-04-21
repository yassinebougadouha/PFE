"""
Shared guardrails for channel auto-replies.

These helpers keep automated/system/newsletter traffic from triggering
customer-facing replies while still allowing normal support questions through.
"""

from __future__ import annotations

import json
import re
import unicodedata
from email.utils import parseaddr
from typing import Any

AUTOMATED_SENDER_TOKENS = (
    "mailer-daemon",
    "postmaster",
    "no-reply",
    "noreply",
    "do-not-reply",
    "donotreply",
    "bounce",
)

AUTOMATED_SUBJECT_TOKENS = (
    "delivery status notification",
    "undeliverable",
    "mail delivery subsystem",
    "failure notice",
    "returned mail",
    "mail delivery failed",
    "delivery has failed",
    "out of office",
    "automatic reply",
    "autoreply",
    "automatic response",
)

AUTOMATED_BODY_TOKENS = (
    "this is an automated message",
    "this is an automatic message",
    "this email was sent automatically",
    "auto-generated",
    "generated automatically",
    "please do not reply",
    "do not reply",
    "no reply is necessary",
    "message automatique",
    "ne pas repondre",
    "merci de ne pas repondre",
)

MAILING_LIST_HEADERS = (
    "list-unsubscribe",
    "list-unsubscribe-post",
    "list-id",
    "mailing-list",
)

MARKETING_BODY_TOKENS = (
    "unsubscribe",
    "manage preferences",
    "view in browser",
    "email preferences",
    "subscription preferences",
    "update your preferences",
    "premium subscribers",
    "top job picks for you",
    "recommended for you",
    "read more",
    "see all jobs",
    "career-changing programs",
    "annual subscription",
    "special offer",
    "limited time offer",
    "promotional",
)

MARKETING_SUBJECT_TOKENS = (
    "newsletter",
    "digest",
    "roundup",
    "weekly update",
    "daily update",
    "profile views",
    "top job picks",
    "recommended jobs",
    "hiring",
    "premium",
)

MARKETING_HEADER_TOKENS = (
    "list-unsubscribe",
    "list-unsubscribe-post",
    "list-id",
    "mailing-list",
    "feedback-id",
    "unsubscribe",
    "preferences",
)

MARKETING_SUBJECT_PATTERNS = (
    r"\b\d+%\s+off\b",
    r"\bsave\s+\d+%\b",
    r"\bends\s+(today|tomorrow|tonight)\b",
    r"\blimited(?:\s+time)?\s+offer\b",
    r"\bexclusive\s+offer\b",
    r"\bspecial\s+offer\b",
)

SUPPORT_SYSTEM_SENDER_TOKENS = (
    "support",
    "helpdesk",
    "ticket",
    "notification",
)

SUPPORT_SYSTEM_SUBJECT_TOKENS = (
    "ticket #",
    "your ticket",
    "votre ticket",
    "case #",
    "incident #",
    "created automatically",
    "cree automatiquement",
    "account created automatically",
    "a bien ete recu",
    "has been received",
    "response to your ticket",
    "reponse a votre ticket",
)


def _simplify_text(value: str | None) -> str:
    raw = (value or "").strip().lower()
    if not raw:
        return ""

    normalized = unicodedata.normalize("NFKD", raw)
    ascii_only = normalized.encode("ascii", "ignore").decode("ascii")
    return re.sub(r"\s+", " ", ascii_only)


def normalize_email_address(value: str | None) -> str:
    _, parsed = parseaddr(value or "")
    candidate = parsed or (value or "")
    candidate = candidate.strip().lower()
    return candidate if "@" in candidate else ""


def parse_email_headers(raw_headers: str | dict[str, Any] | None) -> dict[str, str]:
    if not raw_headers:
        return {}

    if isinstance(raw_headers, dict):
        return {
            str(name).strip().lower(): str(value).strip()
            for name, value in raw_headers.items()
            if name
        }

    text = str(raw_headers).strip()
    if not text:
        return {}

    try:
        parsed = json.loads(text)
    except json.JSONDecodeError:
        parsed = None

    if isinstance(parsed, dict):
        return {
            str(name).strip().lower(): str(value).strip()
            for name, value in parsed.items()
            if name
        }

    headers: dict[str, str] = {}
    for line in text.splitlines():
        if ":" not in line:
            continue
        name, value = line.split(":", 1)
        name = name.strip().lower()
        if not name:
            continue
        headers[name] = value.strip()
    return headers


def _build_headers_text(
    raw_headers: str | dict[str, Any] | None,
    headers: dict[str, str],
) -> str:
    if isinstance(raw_headers, dict):
        source = json.dumps(raw_headers, sort_keys=True)
    else:
        source = str(raw_headers or "")

    if headers:
        rendered = "\n".join(f"{name}: {value}" for name, value in headers.items())
        source = f"{source}\n{rendered}" if source else rendered

    return _simplify_text(source)


def _matches_any_pattern(text: str, patterns: tuple[str, ...]) -> bool:
    return any(re.search(pattern, text) for pattern in patterns)


def get_email_auto_reply_skip_reason(
    sender: str,
    subject: str,
    *,
    raw_headers: str | dict[str, Any] | None = None,
    recipient: str | None = None,
    body: str | None = None,
) -> str | None:
    sender_text = _simplify_text(sender)
    subject_text = _simplify_text(subject)
    body_text = _simplify_text(body)
    headers = parse_email_headers(raw_headers)
    headers_text = _build_headers_text(raw_headers, headers)

    sender_email = normalize_email_address(sender)
    recipient_email = normalize_email_address(recipient)

    if any(token in sender_text for token in AUTOMATED_SENDER_TOKENS):
        return "automated_sender"

    if sender_email and recipient_email and sender_email == recipient_email:
        return "self_sent"

    auto_submitted = _simplify_text(headers.get("auto-submitted"))
    if auto_submitted and auto_submitted != "no":
        return "auto_submitted"

    precedence = _simplify_text(headers.get("precedence"))
    if precedence in {"bulk", "list", "junk", "auto_reply"} or "precedence" in headers_text and any(
        token in headers_text for token in ("bulk", "list", "junk", "auto_reply")
    ):
        return "bulk_precedence"

    if any(headers.get(name) for name in MAILING_LIST_HEADERS) or any(
        token in headers_text for token in MAILING_LIST_HEADERS
    ):
        return "mailing_list_header"

    if headers.get("feedback-id") or "feedback-id" in headers_text:
        return "feedback_id_header"

    auto_response_suppress = _simplify_text(headers.get("x-auto-response-suppress"))
    if any(token in auto_response_suppress for token in ("all", "dr", "oof", "rn", "autoreply", "autorespond")):
        return "auto_response_suppress"

    if headers.get("x-autoreply") or headers.get("x-autorespond"):
        return "autoresponder_header"

    if any(token in subject_text for token in AUTOMATED_SUBJECT_TOKENS):
        return "automated_subject"

    if any(token in body_text for token in AUTOMATED_BODY_TOKENS):
        return "automated_body"

    if (
        any(token in sender_text for token in SUPPORT_SYSTEM_SENDER_TOKENS)
        and any(token in subject_text for token in SUPPORT_SYSTEM_SUBJECT_TOKENS)
    ):
        return "support_system_notification"

    marketing_subject = any(token in subject_text for token in MARKETING_SUBJECT_TOKENS) or _matches_any_pattern(
        subject_text,
        MARKETING_SUBJECT_PATTERNS,
    )
    marketing_body = any(token in body_text for token in MARKETING_BODY_TOKENS)
    marketing_headers = any(token in headers_text for token in MARKETING_HEADER_TOKENS)

    if marketing_headers and (marketing_subject or marketing_body):
        return "marketing_email"

    if marketing_subject and marketing_body:
        return "marketing_email"

    return None
