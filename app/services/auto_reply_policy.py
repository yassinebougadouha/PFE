"""
Helpers for resolving AI auto-reply policy from persisted settings.
"""

from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime, timezone
import uuid
from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import Session

from app.db.models.conversation import Conversation
from app.db.models.setting import Setting


AUTO_REPLY_CHANNEL_SETTINGS: dict[str, str] = {
    "chat": "ai_auto_reply_chat_enabled",
    "whatsapp": "ai_auto_reply_whatsapp_enabled",
    "email": "ai_auto_reply_email_enabled",
}


@dataclass(frozen=True)
class ConversationAutoReplyEvaluation:
    channel_enabled: bool
    conversation_enabled: bool
    paused_until: datetime | None
    pause_active: bool
    effective_enabled: bool
    block_reason: str | None


def coerce_bool(value: Any, default: bool = False) -> bool:
    if isinstance(value, bool):
        return value
    if value is None:
        return default
    if isinstance(value, (int, float)):
        return value != 0
    if isinstance(value, str):
        normalized = value.strip().lower()
        if normalized in {"true", "1", "yes", "on"}:
            return True
        if normalized in {"false", "0", "no", "off"}:
            return False
    return bool(value)


def _setting_key_for_channel(channel: str) -> str:
    normalized = (channel or "").strip().lower()
    key = AUTO_REPLY_CHANNEL_SETTINGS.get(normalized)
    if not key:
        raise ValueError(f"Unsupported auto-reply channel: {channel}")
    return key


def _normalize_aware_datetime(value: datetime | None) -> datetime | None:
    if value is None:
        return None
    if value.tzinfo is None:
        return value.replace(tzinfo=timezone.utc)
    return value


def is_conversation_pause_active(
    paused_until: datetime | None,
    *,
    now: datetime | None = None,
) -> bool:
    normalized = _normalize_aware_datetime(paused_until)
    if normalized is None:
        return False

    reference = now or datetime.now(timezone.utc)
    if reference.tzinfo is None:
        reference = reference.replace(tzinfo=timezone.utc)
    return normalized > reference


def evaluate_conversation_auto_reply(
    *,
    channel_enabled: bool,
    conversation_enabled: bool,
    paused_until: datetime | None,
    now: datetime | None = None,
) -> ConversationAutoReplyEvaluation:
    normalized_paused_until = _normalize_aware_datetime(paused_until)
    pause_active = is_conversation_pause_active(normalized_paused_until, now=now)

    if not channel_enabled:
        block_reason = "channel_disabled"
    elif not conversation_enabled:
        block_reason = "conversation_disabled"
    elif pause_active:
        block_reason = "pause_active"
    else:
        block_reason = None

    return ConversationAutoReplyEvaluation(
        channel_enabled=channel_enabled,
        conversation_enabled=conversation_enabled,
        paused_until=normalized_paused_until,
        pause_active=pause_active,
        effective_enabled=block_reason is None,
        block_reason=block_reason,
    )


async def is_channel_auto_reply_enabled(
    db: AsyncSession,
    channel: str,
    *,
    default: bool = True,
) -> bool:
    key = _setting_key_for_channel(channel)
    result = await db.execute(
        select(Setting.value)
        .where(Setting.key == key)
        .limit(1)
    )
    value = result.scalar_one_or_none()
    return coerce_bool(value, default=default)


def is_channel_auto_reply_enabled_sync(
    db: Session,
    channel: str,
    *,
    default: bool = True,
) -> bool:
    key = _setting_key_for_channel(channel)
    value = db.execute(
        select(Setting.value)
        .where(Setting.key == key)
        .limit(1)
    ).scalar_one_or_none()
    return coerce_bool(value, default=default)


async def is_conversation_auto_reply_enabled(
    db: AsyncSession,
    conversation_id: uuid.UUID,
    *,
    default: bool = True,
) -> bool:
    result = await db.execute(
        select(
            Conversation.ai_auto_reply_enabled,
            Conversation.ai_auto_reply_paused_until,
        )
        .where(Conversation.id == conversation_id)
        .limit(1)
    )
    row = result.first()
    if row is None:
        return default

    conversation_enabled = coerce_bool(row[0], default=default)
    paused_until = row[1]
    evaluation = evaluate_conversation_auto_reply(
        channel_enabled=True,
        conversation_enabled=conversation_enabled,
        paused_until=paused_until,
    )
    return evaluation.effective_enabled


def is_conversation_auto_reply_enabled_sync(
    db: Session,
    conversation_id: uuid.UUID,
    *,
    default: bool = True,
) -> bool:
    row = db.execute(
        select(
            Conversation.ai_auto_reply_enabled,
            Conversation.ai_auto_reply_paused_until,
        )
        .where(Conversation.id == conversation_id)
        .limit(1)
    ).first()
    if row is None:
        return default

    conversation_enabled = coerce_bool(row[0], default=default)
    paused_until = row[1]
    evaluation = evaluate_conversation_auto_reply(
        channel_enabled=True,
        conversation_enabled=conversation_enabled,
        paused_until=paused_until,
    )
    return evaluation.effective_enabled
