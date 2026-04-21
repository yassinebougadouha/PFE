"""
Generate post-call summaries and action items for voice call logs.
"""

from __future__ import annotations

import asyncio
import json
import re
from datetime import datetime, timezone
from typing import Any

from app.core.config import get_settings
from app.db.models.voice_call_log import VoiceCallLog
from app.rag.response_providers.enums import AIProvider
from app.rag.response_providers.service import get_provider
from app.schemas.voice_call import VoiceCallActionItem, VoiceCallPostCallSummaryResponse

_ALLOWED_RESOLUTION_STATUSES = {"unresolved", "in_progress", "resolved", "unknown"}
_ALLOWED_OWNERS = {"agent", "client", "system"}
_ALLOWED_PRIORITIES = {"low", "medium", "high"}


def _provider_order() -> list[AIProvider]:
    settings = get_settings()
    raw = (getattr(settings, "AI_RESPONSE_PROVIDER", "") or "").strip().lower()
    try:
        preferred = AIProvider(raw)
    except ValueError:
        preferred = AIProvider.OPENAI

    order = [preferred]
    for provider in AIProvider:
        if provider not in order:
            order.append(provider)
    return order


def _extract_json_object(raw_text: str) -> dict[str, Any]:
    text = (raw_text or "").strip()
    if not text:
        return {}

    try:
        parsed = json.loads(text)
        return parsed if isinstance(parsed, dict) else {}
    except json.JSONDecodeError:
        pass

    match = re.search(r"\{.*\}", text, flags=re.DOTALL)
    if not match:
        return {}

    try:
        parsed = json.loads(match.group(0))
        return parsed if isinstance(parsed, dict) else {}
    except json.JSONDecodeError:
        return {}


def _compact_text(value: Any, *, default: str, max_len: int) -> str:
    text = " ".join(str(value or "").split()).strip()
    if not text:
        return default
    if len(text) <= max_len:
        return text
    return text[: max_len - 3].rstrip() + "..."


def _normalize_resolution_status(value: Any) -> str:
    normalized = str(value or "").strip().lower().replace("-", "_")
    if normalized in _ALLOWED_RESOLUTION_STATUSES:
        return normalized
    return "unknown"


def _fallback_summary(transcript: str) -> str:
    if not transcript:
        return "No transcript was available to summarize this call."
    preview = transcript if len(transcript) <= 280 else transcript[:277].rstrip() + "..."
    return f"Customer discussed this issue during the call: {preview}"


def _fallback_action_items(transcript: str) -> list[VoiceCallActionItem]:
    normalized = transcript.lower()
    items: list[VoiceCallActionItem] = []

    if any(token in normalized for token in ("error", "failed", "failure", "exception", "blocked")):
        items.append(
            VoiceCallActionItem(
                title="Validate and reproduce the reported error with exact steps",
                owner="agent",
                priority="high",
            )
        )

    if any(token in normalized for token in ("login", "password", "auth", "account")):
        items.append(
            VoiceCallActionItem(
                title="Verify account access and reset credentials if needed",
                owner="agent",
                priority="medium",
            )
        )

    if any(token in normalized for token in ("billing", "payment", "invoice", "card")):
        items.append(
            VoiceCallActionItem(
                title="Review billing profile and confirm payment status",
                owner="agent",
                priority="medium",
            )
        )

    items.append(
        VoiceCallActionItem(
            title="Send a concise follow-up update to the customer",
            owner="agent",
            priority="medium",
        )
    )

    # Keep the list focused for operator handoff.
    deduped: list[VoiceCallActionItem] = []
    seen = set()
    for item in items:
        key = item.title.lower()
        if key in seen:
            continue
        seen.add(key)
        deduped.append(item)

    return deduped[:5]


def _normalize_action_items(raw: Any, transcript: str) -> list[VoiceCallActionItem]:
    if not isinstance(raw, list):
        return _fallback_action_items(transcript)

    action_items: list[VoiceCallActionItem] = []
    for candidate in raw:
        if isinstance(candidate, str):
            title = " ".join(candidate.split()).strip()
            if len(title) >= 3:
                action_items.append(
                    VoiceCallActionItem(title=title[:220], owner="agent", priority="medium")
                )
            continue

        if not isinstance(candidate, dict):
            continue

        title = " ".join(
            str(
                candidate.get("title")
                or candidate.get("action")
                or candidate.get("description")
                or ""
            ).split()
        ).strip()
        if len(title) < 3:
            continue

        owner = str(candidate.get("owner") or "agent").strip().lower()
        if owner not in _ALLOWED_OWNERS:
            owner = "agent"

        priority = str(candidate.get("priority") or "medium").strip().lower()
        if priority not in _ALLOWED_PRIORITIES:
            priority = "medium"

        action_items.append(
            VoiceCallActionItem(
                title=title[:220],
                owner=owner,
                priority=priority,
            )
        )

    if not action_items:
        return _fallback_action_items(transcript)

    return action_items[:5]


def _build_ticket_subject(room_name: str, customer_issue: str) -> str:
    issue = customer_issue if len(customer_issue) <= 90 else customer_issue[:87].rstrip() + "..."
    return f"Voice call follow-up ({room_name}): {issue}"


def _build_ticket_description(
    *,
    summary: str,
    customer_issue: str,
    follow_up_recommendation: str,
    action_items: list[VoiceCallActionItem],
) -> str:
    lines = [
        "Voice call post-call summary:",
        summary,
        "",
        f"Customer issue: {customer_issue}",
        f"Recommended follow-up: {follow_up_recommendation}",
        "",
        "Action items:",
    ]
    for item in action_items:
        lines.append(f"- [{item.priority}] ({item.owner}) {item.title}")
    return "\n".join(lines).strip()


class VoiceCallPostCallService:
    async def summarize_call(
        self,
        call: VoiceCallLog,
        *,
        max_transcript_chars: int = 12_000,
    ) -> VoiceCallPostCallSummaryResponse:
        transcript = "\n".join((call.transcript or "").splitlines()).strip()
        if len(transcript) > max_transcript_chars:
            transcript = transcript[-max_transcript_chars:]

        if not transcript:
            return self._fallback_response(call, transcript)

        messages = self._build_summary_messages(call=call, transcript=transcript)
        settings = get_settings()
        timeout_seconds = max(2, int(getattr(settings, "CONVERSATION_SUMMARY_TIMEOUT_SECONDS", 25)))

        for provider_enum in _provider_order():
            provider = get_provider(provider_enum)
            if not getattr(provider, "_is_configured", False):
                continue

            try:
                generated = await asyncio.wait_for(
                    provider.generate(
                        messages=messages,
                        temperature=0.2,
                        max_tokens=900,
                    ),
                    timeout=timeout_seconds,
                )
                return self._build_response_from_generated(
                    call=call,
                    transcript=transcript,
                    generated=generated,
                    provider=provider_enum.value,
                )
            except Exception:
                continue

        return self._fallback_response(call, transcript)

    @staticmethod
    def _build_summary_messages(*, call: VoiceCallLog, transcript: str) -> list[dict[str, str]]:
        system = (
            "You are a support quality analyst. "
            "Return valid JSON only, with no markdown or extra text."
        )
        user_prompt = (
            "Analyze this support voice call transcript and produce a post-call handoff.\n"
            "Return JSON with exactly these keys:\n"
            "summary, customer_issue, resolution_status, follow_up_recommendation, "
            "action_items, ticket_subject_suggestion, ticket_description_suggestion\n"
            "Rules:\n"
            "- resolution_status must be one of: unresolved, in_progress, resolved, unknown\n"
            "- action_items must be an array (2-5 items) of objects with keys: title, owner, priority\n"
            "- owner must be: agent, client, or system\n"
            "- priority must be: low, medium, or high\n"
            "- Keep summary concise (1-3 sentences).\n"
            "- Keep ticket_subject_suggestion under 120 characters.\n"
            "- ticket_description_suggestion should be actionable and include key context.\n\n"
            f"Room: {call.room_name}\n"
            f"Duration seconds: {call.duration_seconds or 0}\n"
            "Transcript:\n"
            f"{transcript}"
        )
        return [
            {"role": "system", "content": system},
            {"role": "user", "content": user_prompt},
        ]

    def _build_response_from_generated(
        self,
        *,
        call: VoiceCallLog,
        transcript: str,
        generated: dict[str, Any],
        provider: str,
    ) -> VoiceCallPostCallSummaryResponse:
        parsed = _extract_json_object(str(generated.get("content", "")))

        summary = _compact_text(
            parsed.get("summary") or parsed.get("problem_summary"),
            default=_fallback_summary(transcript),
            max_len=500,
        )
        customer_issue = _compact_text(
            parsed.get("customer_issue"),
            default=summary,
            max_len=320,
        )
        follow_up_recommendation = _compact_text(
            parsed.get("follow_up_recommendation") or parsed.get("next_action"),
            default="Follow up with the customer after validating the issue path and next remediation step.",
            max_len=320,
        )
        resolution_status = _normalize_resolution_status(parsed.get("resolution_status") or parsed.get("resolution_state"))
        action_items = _normalize_action_items(parsed.get("action_items"), transcript)

        ticket_subject_suggestion = _compact_text(
            parsed.get("ticket_subject_suggestion"),
            default=_build_ticket_subject(call.room_name, customer_issue),
            max_len=500,
        )
        ticket_description_suggestion = _compact_text(
            parsed.get("ticket_description_suggestion"),
            default=_build_ticket_description(
                summary=summary,
                customer_issue=customer_issue,
                follow_up_recommendation=follow_up_recommendation,
                action_items=action_items,
            ),
            max_len=8_000,
        )

        return VoiceCallPostCallSummaryResponse(
            call_id=call.id,
            room_name=call.room_name,
            provider=provider,
            model=str(generated.get("model") or "unknown"),
            summary=summary,
            customer_issue=customer_issue,
            resolution_status=resolution_status,
            follow_up_recommendation=follow_up_recommendation,
            action_items=action_items,
            ticket_subject_suggestion=ticket_subject_suggestion,
            ticket_description_suggestion=ticket_description_suggestion,
            generated_at=datetime.now(timezone.utc),
        )

    def _fallback_response(
        self,
        call: VoiceCallLog,
        transcript: str,
    ) -> VoiceCallPostCallSummaryResponse:
        summary = _fallback_summary(transcript)
        customer_issue = _compact_text(summary, default="Customer issue was not captured.", max_len=320)
        action_items = _fallback_action_items(transcript)
        follow_up_recommendation = "Confirm issue reproduction, then update the customer with the remediation plan."

        return VoiceCallPostCallSummaryResponse(
            call_id=call.id,
            room_name=call.room_name,
            provider="fallback",
            model="deterministic-v1",
            summary=summary,
            customer_issue=customer_issue,
            resolution_status="unknown",
            follow_up_recommendation=follow_up_recommendation,
            action_items=action_items,
            ticket_subject_suggestion=_build_ticket_subject(call.room_name, customer_issue),
            ticket_description_suggestion=_build_ticket_description(
                summary=summary,
                customer_issue=customer_issue,
                follow_up_recommendation=follow_up_recommendation,
                action_items=action_items,
            ),
            generated_at=datetime.now(timezone.utc),
        )
