"""Utilities to normalize noisy mail-like content before persistence."""

from __future__ import annotations

import html
import quopri
import re
from urllib.parse import parse_qsl, urlencode, urlsplit, urlunsplit

_URL_REGEX = re.compile(r"(https?://[^\s<>\")\]]+)")
_FOOTER_SEPARATOR_REGEX = re.compile(r"^-{8,}\s*$")
_TRAILING_PUNCTUATION_REGEX = re.compile(r"[),.;!?]+$")
_TRACKING_QUERY_KEYS = {
    "trk",
    "trkemail",
    "lipi",
    "midtoken",
    "midsig",
    "eid",
    "otptoken",
    "loid",
    "upsellorderorigin",
    "referenceid",
    "isss",
    "origin",
}


def sanitize_tracking_url(raw_url: str) -> str:
    trimmed = _TRAILING_PUNCTUATION_REGEX.sub("", raw_url)
    trailing = raw_url[len(trimmed):]

    try:
        parts = urlsplit(trimmed)
        query_items = parse_qsl(parts.query, keep_blank_values=True)
        kept_items = []
        for key, value in query_items:
            lower = key.lower()
            if lower in _TRACKING_QUERY_KEYS or lower.startswith("utm_"):
                continue
            kept_items.append((key, value))

        clean_query = urlencode(kept_items, doseq=True)
        rebuilt = urlunsplit((parts.scheme, parts.netloc, parts.path, clean_query, parts.fragment))
        return f"{rebuilt}{trailing}"
    except Exception:
        return raw_url


def normalize_email_subject(raw_subject: str | None) -> str:
    subject = (raw_subject or "").replace("\r", " ").replace("\n", " ")
    subject = html.unescape(subject)
    subject = re.sub(r"\s+", " ", subject).strip()
    return subject or "(No Subject)"


def _decode_quoted_printable(content: str) -> str:
    if not content:
        return ""

    if re.search(r"=\r?\n|=[0-9A-Fa-f]{2}", content):
        try:
            decoded = quopri.decodestring(content.encode("utf-8", errors="ignore"))
            return decoded.decode("utf-8", errors="replace")
        except Exception:
            return content
    return content


def _html_to_plain_text(content: str) -> str:
    if not re.search(r"<[a-zA-Z][\s\S]*>", content):
        return content

    text = content
    text = re.sub(r"<\s*br\s*/?>", "\n", text, flags=re.IGNORECASE)
    text = re.sub(r"</\s*(p|div|li|h[1-6]|tr|section|article)\s*>", "\n", text, flags=re.IGNORECASE)
    text = re.sub(r"<style[\s\S]*?</style>", " ", text, flags=re.IGNORECASE)
    text = re.sub(r"<script[\s\S]*?</script>", " ", text, flags=re.IGNORECASE)
    text = re.sub(r"<[^>]+>", " ", text)
    text = html.unescape(text)
    return text


def _remove_common_footer_noise(lines: list[str]) -> list[str]:
    cutoff_index = -1
    for index, line in enumerate(lines):
        trimmed = line.strip().lower()
        if (
            trimmed.startswith("this email was intended for")
            or trimmed.startswith("you are receiving linkedin invitations emails.")
            or re.match(r"^©\s*\d{4}\s+linkedin", trimmed)
        ):
            cutoff_index = index
            break

    kept = lines[:cutoff_index] if cutoff_index >= 0 else list(lines)

    promo_index = -1
    for index, line in enumerate(kept):
        if line.strip().lower().startswith("build your network with inmail"):
            promo_index = index
            break

    if promo_index >= 0:
        separator_after = -1
        for index, line in enumerate(kept):
            if index <= promo_index:
                continue
            if _FOOTER_SEPARATOR_REGEX.match(line.strip()):
                separator_after = index
                break

        remove_until = separator_after if separator_after >= 0 else min(promo_index + 4, len(kept) - 1)
        kept = [*kept[:promo_index], *kept[remove_until + 1:]]

    filtered: list[str] = []
    for line in kept:
        trimmed = line.strip().lower()
        if not trimmed:
            filtered.append("")
            continue

        if _FOOTER_SEPARATOR_REGEX.match(trimmed):
            continue
        if trimmed.startswith("unsubscribe:"):
            continue
        if trimmed.startswith("help:"):
            continue
        if trimmed.startswith("learn why we included this:"):
            continue

        filtered.append(line)

    return filtered


def normalize_mail_like_text(raw_text: str | None) -> str:
    content = (raw_text or "").replace("\r\n", "\n")
    content = _decode_quoted_printable(content)
    content = _html_to_plain_text(content)

    content = re.sub(r"([^:\n]{2,80}):(https?://)", r"\1: \2", content)
    content = _URL_REGEX.sub(lambda match: sanitize_tracking_url(match.group(1)), content)

    lines = [line.replace("\t", " ").rstrip() for line in content.split("\n")]
    lines = _remove_common_footer_noise(lines)

    normalized = "\n".join(lines)
    normalized = re.sub(r"\n{3,}", "\n\n", normalized)
    normalized = normalized.strip()
    return normalized
