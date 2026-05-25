"""
Chat / conversation routes.
"""

import asyncio
import logging
import json
import re
import uuid
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Annotated, Optional

from fastapi import APIRouter, BackgroundTasks, Depends, File, Form, HTTPException, Query, UploadFile, status
from fastapi.responses import FileResponse, StreamingResponse
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.conversation import Message, ConversationAgentReplySuspension
from app.db.models.user import User
from app.db.models.enums import AuditAction, ChannelType, ConversationStatus, UserRole
from app.db.session import async_session_factory
from app.api.deps import (
    RedisService,
    get_current_user,
    get_redis,
    require_admin,
    require_agent_or_admin,
)
from app.core.config import get_settings
from app.rag.response_providers.enums import AIProvider
from app.rag.response_providers.channel_formatter import format_response
from app.rag.response_providers.service import ResponseGenerationService, get_provider
from app.schemas.conversation import (
    ConversationAiJobQueuedResponse,
    ConversationAssistedDraftJobStatusResponse,
    ConversationCreate, ConversationListResponse, ConversationResponse, ConversationUpdate,
    ConversationStreamRequest, ConversationSummaryResponse, MessageCreate, MessageResponse,
    ConversationAgentReplySuspensionResponse, ConversationAgentReplySuspensionUpdate,
    ConversationAssistedDraftResponse,
    ConversationAutoReplyPauseUpdate,
    ConversationAutoReplyResponse,
    ConversationSummaryJobStatusResponse,
    ConversationAutoReplyUpdate,
    ConversationSlaActionResponse,
    ConversationSlaAssignRequest,
    ConversationSlaEscalateRequest,
    ConversationSlaPredictorResponse,
    ConversationSlaSnoozeRequest,
    ConversationSnippetCreate,
    ConversationSnippetListResponse,
    ConversationSnippetResponse,
    ConversationSnippetUpdate,
)
from app.services.auto_reply_policy import (
    evaluate_conversation_auto_reply,
    is_channel_auto_reply_enabled,
)
from app.services.conversation_service import ConversationService
from app.services.conversation_playbook_service import ConversationPlaybookService
from app.services.conversation_snippet_service import ConversationSnippetService
from app.services.audit_service import AuditService
from app.services.notification_service import NotificationService

router = APIRouter(prefix="/conversations", tags=["Chat / Conversations"])
logger = logging.getLogger(__name__)
settings = get_settings()
MAX_CHAT_ATTACHMENT_SIZE = settings.MAX_CHAT_ATTACHMENT_SIZE_MB * 1024 * 1024
_SUMMARY_ALLOWED_STATES = {
    "unresolved",
    "in_progress",
    "partially_resolved",
    "resolved",
    "unknown",
}
_SUMMARY_ALLOWED_SENTIMENTS = {
    "calm",
    "frustrated",
    "urgent",
    "neutral",
    "unknown",
}


def _conversation_ai_job_meta_key(job_id: str) -> str:
    return f"conversation:ai-job:{job_id}"


async def _store_conversation_ai_job_meta(
    redis: RedisService,
    *,
    job_id: str,
    conversation_id: uuid.UUID,
    job_type: str,
) -> None:
    payload = json.dumps(
        {
            "conversation_id": str(conversation_id),
            "job_type": job_type,
        }
    )
    await redis.set(
        _conversation_ai_job_meta_key(job_id),
        payload,
        ttl=max(60, int(settings.CONVERSATION_AI_JOB_METADATA_TTL_SECONDS)),
    )


async def _load_conversation_ai_job_meta(
    redis: RedisService,
    *,
    job_id: str,
) -> dict | None:
    raw = await redis.get(_conversation_ai_job_meta_key(job_id))
    if not raw:
        return None

    try:
        parsed = json.loads(raw)
    except json.JSONDecodeError:
        return None

    if not isinstance(parsed, dict):
        return None
    return parsed


def _normalize_job_status(celery_state: str) -> str:
    normalized = (celery_state or "").upper()
    if normalized in {"PENDING", "RECEIVED", "RETRY"}:
        return "queued"
    if normalized == "STARTED":
        return "started"
    if normalized == "SUCCESS":
        return "succeeded"
    return "failed"


def _job_error_message(raw_error: object) -> str:
    text = str(raw_error or "Job failed")
    return text if len(text) <= 500 else text[:500].rstrip() + "..."


def _ensure_operator_can_reply(current_user: User) -> None:
    if current_user.role in {UserRole.AGENT, UserRole.ADMIN} and not current_user.can_reply_conversations:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Read-only mode: conversation replies are disabled for this account",
        )


async def _ensure_agent_not_suspended_from_conversation(
    db: AsyncSession,
    conversation_id: uuid.UUID,
    current_user: User,
) -> None:
    if current_user.role != UserRole.AGENT:
        return

    suspension_result = await db.execute(
        select(ConversationAgentReplySuspension)
        .where(
            ConversationAgentReplySuspension.conversation_id == conversation_id,
            ConversationAgentReplySuspension.agent_id == current_user.id,
        )
        .limit(1)
    )
    if suspension_result.scalar_one_or_none():
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Reply suspended by admin for this conversation",
        )


async def _is_chat_auto_reply_allowed(
    db: AsyncSession,
    conversation,
) -> bool:
    if conversation.channel != ChannelType.CHAT:
        return False
    _, _, evaluation = await _evaluate_conversation_auto_reply(db, conversation)
    return evaluation.effective_enabled


def _conversation_channel_key(conversation) -> str | None:
    raw = getattr(conversation, "channel", None)
    channel = (getattr(raw, "value", raw) or "").strip().lower()
    if channel in {"chat", "whatsapp", "email"}:
        return channel
    return None


async def _evaluate_conversation_auto_reply(
    db: AsyncSession,
    conversation,
):
    channel_key = _conversation_channel_key(conversation)
    channel_enabled = True

    if channel_key and hasattr(db, "execute"):
        try:
            channel_enabled = await is_channel_auto_reply_enabled(db, channel_key, default=True)
        except Exception:
            logger.warning(
                "Failed to resolve %s auto-reply setting for conversation %s; defaulting to enabled",
                channel_key,
                getattr(conversation, "id", "unknown"),
                exc_info=True,
            )
            channel_enabled = True

    evaluation = evaluate_conversation_auto_reply(
        channel_enabled=channel_enabled,
        conversation_enabled=bool(getattr(conversation, "ai_auto_reply_enabled", True)),
        paused_until=getattr(conversation, "ai_auto_reply_paused_until", None),
    )
    return channel_key, channel_enabled, evaluation


async def _build_conversation_auto_reply_response(
    db: AsyncSession,
    conversation,
) -> ConversationAutoReplyResponse:
    channel_key, channel_enabled, evaluation = await _evaluate_conversation_auto_reply(db, conversation)
    return ConversationAutoReplyResponse(
        conversation_id=conversation.id,
        channel=conversation.channel,
        channel_auto_reply_enabled=channel_enabled,
        ai_auto_reply_enabled=bool(getattr(conversation, "ai_auto_reply_enabled", True)),
        ai_auto_reply_paused_until=evaluation.paused_until,
        pause_active=evaluation.pause_active,
        effective_ai_auto_reply_enabled=evaluation.effective_enabled,
        block_reason=evaluation.block_reason,
        assisted_draft_available=channel_key in {"chat", "whatsapp"},
        updated_at=conversation.updated_at,
    )


def _schedule_playbook_evaluation(
    background_tasks: BackgroundTasks,
    *,
    conversation_id: uuid.UUID,
    event: str,
) -> None:
    background_tasks.add_task(
        ConversationPlaybookService.evaluate_playbooks_for_conversation,
        conversation_id,
        event=event,
    )


async def _log_assisted_draft_event(
    db: AsyncSession,
    *,
    event: str,
    conversation_id: uuid.UUID,
    channel: ChannelType,
    user_id: uuid.UUID,
    source_message_id: uuid.UUID | None = None,
    sent_message_id: uuid.UUID | None = None,
    assisted_draft_edited: bool | None = None,
    assisted_draft_generated_at: datetime | None = None,
    sent_content: str | None = None,
) -> None:
    channel_key = str(getattr(channel, "value", channel)).strip().lower() or "unknown"
    meta: dict[str, object] = {
        "event": event,
        "channel": channel_key,
    }
    if source_message_id:
        meta["source_message_id"] = str(source_message_id)
    if sent_message_id:
        meta["sent_message_id"] = str(sent_message_id)

    if sent_content:
        meta["sent_char_count"] = len(sent_content.strip())

    if event == "accepted":
        if assisted_draft_edited is not None:
            meta["assisted_draft_edited"] = bool(assisted_draft_edited)
        if assisted_draft_generated_at is not None:
            generated_at = (
                assisted_draft_generated_at.astimezone(timezone.utc)
                if assisted_draft_generated_at.tzinfo
                else assisted_draft_generated_at.replace(tzinfo=timezone.utc)
            )
            meta["assisted_draft_generated_at"] = generated_at.isoformat()
            meta["assisted_draft_seconds_to_send"] = max(
                0,
                int((datetime.now(timezone.utc) - generated_at).total_seconds()),
            )

    audit = AuditService(db)
    await audit.log(
        action=AuditAction.REPLY,
        resource_type="assisted_draft",
        resource_id=str(conversation_id),
        user_id=user_id,
        description=f"Assisted draft {event} on {channel_key} conversation {conversation_id}",
        meta=meta,
    )


def _summary_provider_order() -> list[AIProvider]:
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


def _normalize_choice(raw_value: object, allowed: set[str], default: str) -> str:
    value = str(raw_value or "").strip().lower().replace("-", "_")
    return value if value in allowed else default


def _extract_json_object(raw_text: str) -> dict:
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


def _build_conversation_summary_messages(
    *,
    customer_label: str,
    conversation_status: str,
    transcript: str,
) -> list[dict]:
    system = (
        "You are a customer-support QA analyst. "
        "Analyze the conversation and return only valid JSON. "
        "No markdown, no explanations outside JSON."
    )

    user_prompt = (
        "Analyze the support conversation and summarize it.\n"
        "Return JSON with exactly these keys:\n"
        "problem_summary, resolution_state, resolution_description, next_action, customer_sentiment, language\n"
        "Allowed values:\n"
        "resolution_state: unresolved | in_progress | partially_resolved | resolved | unknown\n"
        "customer_sentiment: calm | frustrated | urgent | neutral | unknown\n"
        "Rules:\n"
        "- problem_summary: 1-3 concise sentences describing the customer problem.\n"
        "- resolution_description: concise current state of resolution.\n"
        "- next_action: most relevant immediate support action.\n"
        "- language: detected conversation language code (for example fr, en, ar).\n"
        "- Use unknown when uncertain.\n"
        "- Base your answer only on the conversation below.\n\n"
        f"Customer: {customer_label}\n"
        f"Conversation status: {conversation_status}\n\n"
        "Conversation transcript:\n"
        f"{transcript}"
    )

    return [
        {"role": "system", "content": system},
        {"role": "user", "content": user_prompt},
    ]


@router.post("", response_model=ConversationResponse, status_code=status.HTTP_201_CREATED)
async def create_conversation(
    payload: ConversationCreate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Start a new conversation (any authenticated user)."""
    svc = ConversationService(db)
    return await svc.create_conversation(current_user.id, payload)
def _match_id(field_value, target: str) -> bool:
    """Compare IDs en ignorant les différences de format (UUID vs int string)."""
    if field_value is None:
        return False
    return str(field_value).strip() == target

@router.get("", response_model=ConversationListResponse)
async def list_conversations(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    status_filter: Optional[ConversationStatus] = Query(None, alias="status"),
    channel: Optional[ChannelType] = Query(None),
    include_total: bool = Query(True),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=200),
    agent_id: Optional[str] = Query(None, description="Filter by agent/assigned user ID (admin only)"),
    filter_user_id: Optional[str] = Query(None, alias="user_id", description="Filter by client user ID (admin only)"),
):
    """List conversations — clients see own, agents/admins see all."""
    svc = ConversationService(db)

    if current_user.role == UserRole.CLIENT:
        convos, total = await svc.list_conversations(
            user_id=current_user.id,
            status=status_filter,
            channel=channel,
            include_total=include_total,
            skip=skip,
            limit=limit,
        )
        return {"conversations": convos, "total": total}

    convos, total = await svc.list_conversations(
        user_id=None,
        status=status_filter,
        channel=channel,
        include_total=include_total,
        skip=0,
        limit=500,
    )

    if agent_id:
        agent_id_str = str(agent_id).strip()
        convos = [
            c for c in convos
            if _match_id(getattr(c, 'agent_id', None), agent_id_str)
            or _match_id(getattr(c, 'assigned_agent_id', None), agent_id_str)
            or _match_id(getattr(c, 'assigned_to', None), agent_id_str)
        ]

    if filter_user_id:
        uid_str = str(filter_user_id).strip()
        convos = [
            c for c in convos
            if _match_id(getattr(c, 'user_id', None), uid_str)
            or _match_id(getattr(c, 'contact_id', None), uid_str)
            or _match_id(getattr(c, 'customer_id', None), uid_str)
        ]

    total = len(convos)
    convos = convos[skip: skip + limit]

    return {"conversations": convos, "total": total}

@router.get(
    "/automation/snippets",
    response_model=ConversationSnippetListResponse,
)
async def list_conversation_snippets(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
    channel: Optional[ChannelType] = Query(None),
    include_inactive: bool = Query(False),
    skip: int = Query(0, ge=0),
    limit: int = Query(200, ge=1, le=500),
):
    if include_inactive and current_user.role != UserRole.ADMIN:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Only admins can list inactive snippets",
        )

    snippets, total = await ConversationSnippetService(db).list_snippets(
        channel=channel,
        include_inactive=include_inactive,
        skip=skip,
        limit=limit,
    )
    return {"snippets": snippets, "total": total}


@router.post(
    "/automation/snippets",
    response_model=ConversationSnippetResponse,
    status_code=status.HTTP_201_CREATED,
)
async def create_conversation_snippet(
    payload: ConversationSnippetCreate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_admin)],
):
    return await ConversationSnippetService(db).create_snippet(payload, actor_id=current_user.id)


@router.patch(
    "/automation/snippets/{snippet_id}",
    response_model=ConversationSnippetResponse,
)
async def update_conversation_snippet(
    snippet_id: uuid.UUID,
    payload: ConversationSnippetUpdate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_admin)],
):
    snippet = await ConversationSnippetService(db).update_snippet(
        snippet_id,
        payload,
        actor_id=current_user.id,
    )
    if not snippet:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Snippet not found")
    return snippet


@router.delete(
    "/automation/snippets/{snippet_id}",
    status_code=status.HTTP_204_NO_CONTENT,
)
async def delete_conversation_snippet(
    snippet_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_admin)],
):
    deleted = await ConversationSnippetService(db).delete_snippet(
        snippet_id,
        actor_id=current_user.id,
    )
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Snippet not found")


@router.get("/{conversation_id}", response_model=ConversationResponse)
async def get_conversation(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    # Clients can only see their own
    if current_user.role == UserRole.CLIENT and conv.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    return conv


@router.get(
    "/{conversation_id}/sla-predictor",
    response_model=ConversationSlaPredictorResponse,
)
async def get_conversation_sla_predictor(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    service = ConversationPlaybookService(db)
    try:
        return await service.get_predictor(
            conversation_id,
            event="predictor_poll",
            auto_apply_playbook=True,
        )
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc))


@router.post(
    "/{conversation_id}/sla-actions/escalate",
    response_model=ConversationSlaActionResponse,
)
async def escalate_conversation_from_sla(
    conversation_id: uuid.UUID,
    payload: ConversationSlaEscalateRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    service = ConversationPlaybookService(db)
    try:
        predictor, ticket = await service.escalate_now(
            conversation_id,
            actor_user_id=current_user.id,
            note=payload.note,
        )
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc))

    return ConversationSlaActionResponse(
        conversation_id=conversation_id,
        action="escalate",
        success=True,
        ticket_id=ticket.id,
        assigned_agent_id=ticket.assigned_agent_id,
        predictor=predictor,
    )


@router.post(
    "/{conversation_id}/sla-actions/assign",
    response_model=ConversationSlaActionResponse,
)
async def assign_conversation_from_sla(
    conversation_id: uuid.UUID,
    payload: ConversationSlaAssignRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    if payload.agent_id and current_user.role == UserRole.AGENT and payload.agent_id != current_user.id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Agents can only assign themselves from this quick action",
        )

    service = ConversationPlaybookService(db)
    try:
        predictor, ticket, assigned_agent_id = await service.assign_now(
            conversation_id,
            actor_user=current_user,
            agent_id=payload.agent_id,
        )
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc))

    return ConversationSlaActionResponse(
        conversation_id=conversation_id,
        action="assign",
        success=True,
        ticket_id=ticket.id,
        assigned_agent_id=assigned_agent_id,
        predictor=predictor,
    )


@router.post(
    "/{conversation_id}/sla-actions/snooze",
    response_model=ConversationSlaActionResponse,
)
async def snooze_conversation_sla(
    conversation_id: uuid.UUID,
    payload: ConversationSlaSnoozeRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    service = ConversationPlaybookService(db)
    try:
        predictor, snoozed_until = await service.snooze(
            conversation_id,
            actor_user_id=current_user.id,
            minutes=payload.minutes,
        )
    except ValueError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(exc))

    return ConversationSlaActionResponse(
        conversation_id=conversation_id,
        action="snooze",
        success=True,
        snoozed_until=snoozed_until,
        predictor=predictor,
    )


@router.get(
    "/{conversation_id}/ai-auto-reply",
    response_model=ConversationAutoReplyResponse,
)
async def get_conversation_auto_reply(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    if current_user.role == UserRole.CLIENT and conv.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

    return await _build_conversation_auto_reply_response(db, conv)


@router.put(
    "/{conversation_id}/ai-auto-reply",
    response_model=ConversationAutoReplyResponse,
)
async def set_conversation_auto_reply(
    conversation_id: uuid.UUID,
    payload: ConversationAutoReplyUpdate,
    background_tasks: BackgroundTasks,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_admin)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    conv.ai_auto_reply_enabled = payload.ai_auto_reply_enabled
    await db.flush()
    await db.refresh(conv)

    _schedule_playbook_evaluation(
        background_tasks,
        conversation_id=conversation_id,
        event="conversation_toggle_changed",
    )

    return await _build_conversation_auto_reply_response(db, conv)


@router.put(
    "/{conversation_id}/ai-auto-reply/pause",
    response_model=ConversationAutoReplyResponse,
)
async def set_conversation_auto_reply_pause(
    conversation_id: uuid.UUID,
    payload: ConversationAutoReplyPauseUpdate,
    background_tasks: BackgroundTasks,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    if payload.clear:
        conv.ai_auto_reply_paused_until = None
    else:
        provided_options = int(payload.minutes is not None) + int(payload.pause_until is not None)
        if provided_options != 1:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Provide exactly one of minutes or pause_until",
            )

        if payload.minutes is not None:
            paused_until = datetime.now(timezone.utc) + timedelta(minutes=payload.minutes)
        else:
            paused_until = payload.pause_until
            if paused_until is None:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail="pause_until is required when minutes is not provided",
                )
            if paused_until.tzinfo is None:
                paused_until = paused_until.replace(tzinfo=timezone.utc)

        conv.ai_auto_reply_paused_until = paused_until if paused_until > datetime.now(timezone.utc) else None

    await db.flush()
    await db.refresh(conv)

    _schedule_playbook_evaluation(
        background_tasks,
        conversation_id=conversation_id,
        event="conversation_pause_updated",
    )

    return await _build_conversation_auto_reply_response(db, conv)


@router.delete(
    "/{conversation_id}/ai-auto-reply/pause",
    response_model=ConversationAutoReplyResponse,
)
async def clear_conversation_auto_reply_pause(
    conversation_id: uuid.UUID,
    background_tasks: BackgroundTasks,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    conv.ai_auto_reply_paused_until = None
    await db.flush()
    await db.refresh(conv)

    _schedule_playbook_evaluation(
        background_tasks,
        conversation_id=conversation_id,
        event="conversation_pause_cleared",
    )

    return await _build_conversation_auto_reply_response(db, conv)


@router.get(
    "/{conversation_id}/agent-reply-suspensions/{agent_id}",
    response_model=ConversationAgentReplySuspensionResponse,
)
async def get_agent_reply_suspension(
    conversation_id: uuid.UUID,
    agent_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    if current_user.role not in {UserRole.ADMIN, UserRole.AGENT}:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

    if current_user.role == UserRole.AGENT and current_user.id != agent_id:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Agents can only view their own conversation reply suspension status",
        )

    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    agent = await svc.get_user(agent_id)
    if not agent or agent.role != UserRole.AGENT:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Agent not found")

    suspension_result = await db.execute(
        select(ConversationAgentReplySuspension)
        .where(
            ConversationAgentReplySuspension.conversation_id == conversation_id,
            ConversationAgentReplySuspension.agent_id == agent_id,
        )
        .limit(1)
    )
    suspension = suspension_result.scalar_one_or_none()

    if not suspension:
        return ConversationAgentReplySuspensionResponse(
            conversation_id=conversation_id,
            agent_id=agent_id,
            suspended=False,
            reason=None,
            suspended_by_id=None,
            updated_at=None,
        )

    return ConversationAgentReplySuspensionResponse(
        conversation_id=conversation_id,
        agent_id=agent_id,
        suspended=True,
        reason=suspension.reason,
        suspended_by_id=suspension.suspended_by_id,
        updated_at=suspension.updated_at,
    )


@router.put(
    "/{conversation_id}/agent-reply-suspensions/{agent_id}",
    response_model=ConversationAgentReplySuspensionResponse,
)
async def set_agent_reply_suspension(
    conversation_id: uuid.UUID,
    agent_id: uuid.UUID,
    payload: ConversationAgentReplySuspensionUpdate,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_admin)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    agent = await svc.get_user(agent_id)
    if not agent or agent.role != UserRole.AGENT:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Agent not found")

    reason = (payload.reason or "").strip() or None
    conversation_label = (conv.subject or "").strip() or f"Conversation {str(conversation_id)[:8]}"
    conversation_action_url = (
        f"/conversations?user={conv.user_id}&conversation={conversation_id}"
    )

    suspension_result = await db.execute(
        select(ConversationAgentReplySuspension)
        .where(
            ConversationAgentReplySuspension.conversation_id == conversation_id,
            ConversationAgentReplySuspension.agent_id == agent_id,
        )
        .limit(1)
    )
    suspension = suspension_result.scalar_one_or_none()
    had_suspension = suspension is not None

    if payload.suspended:
        if suspension:
            suspension.reason = reason
            suspension.suspended_by_id = current_user.id
        else:
            suspension = ConversationAgentReplySuspension(
                conversation_id=conversation_id,
                agent_id=agent_id,
                suspended_by_id=current_user.id,
                reason=reason,
            )
            db.add(suspension)

        await db.flush()
        await db.refresh(suspension)

        body = f'An admin suspended your replies for "{conversation_label}".'
        if reason:
            body = f"{body} Reason: {reason}"

        await NotificationService(db).create_notification(
            user_id=agent_id,
            type="conversation_reply_suspended",
            title="Reply suspended for this conversation",
            body=body,
            resource_type="conversation",
            resource_id=str(conversation_id),
            action_url=conversation_action_url,
            meta={
                "conversation_id": str(conversation_id),
                "agent_id": str(agent_id),
                "suspended": True,
                "suspended_by_id": str(current_user.id),
                "reason": reason,
            },
        )

        return ConversationAgentReplySuspensionResponse(
            conversation_id=conversation_id,
            agent_id=agent_id,
            suspended=True,
            reason=suspension.reason,
            suspended_by_id=suspension.suspended_by_id,
            updated_at=suspension.updated_at,
        )

    if suspension:
        await db.delete(suspension)
        await db.flush()

    if had_suspension:
        body = f'An admin restored your reply access for "{conversation_label}".'
        if reason:
            body = f"{body} Note: {reason}"

        await NotificationService(db).create_notification(
            user_id=agent_id,
            type="conversation_reply_unsuspended",
            title="Reply access restored",
            body=body,
            resource_type="conversation",
            resource_id=str(conversation_id),
            action_url=conversation_action_url,
            meta={
                "conversation_id": str(conversation_id),
                "agent_id": str(agent_id),
                "suspended": False,
                "suspended_by_id": str(current_user.id),
                "reason": reason,
            },
        )

    return ConversationAgentReplySuspensionResponse(
        conversation_id=conversation_id,
        agent_id=agent_id,
        suspended=False,
        reason=None,
        suspended_by_id=None,
        updated_at=None,
    )


@router.patch("/{conversation_id}", response_model=ConversationResponse)
async def update_conversation(
    conversation_id: uuid.UUID,
    payload: ConversationUpdate,
    background_tasks: BackgroundTasks,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Update conversation status — agents/admins."""
    svc = ConversationService(db)
    existing = await svc.get_conversation(conversation_id)
    if not existing:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if current_user.role == UserRole.CLIENT and existing.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    if current_user.role == UserRole.CLIENT and payload.status is not None:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Clients cannot update conversation status",
        )
    conv = await svc.update_conversation(conversation_id, payload)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    if payload.status is not None or payload.subject is not None:
        _schedule_playbook_evaluation(
            background_tasks,
            conversation_id=conversation_id,
            event="conversation_update",
        )

    return conv


# ── Messages ──────────────────────────────────────────

@router.delete("/{conversation_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_conversation(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if current_user.role == UserRole.CLIENT and conv.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

    deleted = await svc.delete_conversation(conversation_id)
    if not deleted:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")


@router.post("/{conversation_id}/messages", response_model=MessageResponse, status_code=status.HTTP_201_CREATED)
async def send_message(
    conversation_id: uuid.UUID,
    payload: MessageCreate,
    background_tasks: BackgroundTasks,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Send a message in a conversation."""
    _ensure_operator_can_reply(current_user)
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if current_user.role == UserRole.CLIENT and conv.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    if current_user.role == UserRole.CLIENT and payload.is_internal:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Clients cannot send internal messages",
        )
    await _ensure_agent_not_suspended_from_conversation(db, conversation_id, current_user)
    message = await svc.add_message(conversation_id, current_user.id, payload)

    if (
        payload.used_assisted_draft
        and not payload.is_internal
        and current_user.role in {UserRole.AGENT, UserRole.ADMIN}
    ):
        await _log_assisted_draft_event(
            db,
            event="accepted",
            conversation_id=conversation_id,
            channel=conv.channel,
            user_id=current_user.id,
            sent_message_id=message.id,
            assisted_draft_edited=payload.assisted_draft_edited,
            assisted_draft_generated_at=payload.assisted_draft_generated_at,
            sent_content=payload.content,
        )

    if (
        current_user.role == UserRole.CLIENT
        and not payload.is_internal
        and await _is_chat_auto_reply_allowed(db, conv)
    ):
        background_tasks.add_task(
            ConversationService.generate_support_reply_for_message,
            conversation_id=conversation_id,
            customer_id=current_user.id,
            latest_message_id=message.id,
        )
    if not payload.is_internal:
        playbook_event = "customer_message" if current_user.role == UserRole.CLIENT else "operator_message"
        _schedule_playbook_evaluation(
            background_tasks,
            conversation_id=conversation_id,
            event=playbook_event,
        )
    return message


@router.post(
    "/stream",
    status_code=status.HTTP_200_OK,
    summary="Send a client chat message and stream the AI reply",
)
async def stream_client_message(
    payload: ConversationStreamRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    if current_user.role != UserRole.CLIENT:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Only clients can use streamed chat replies",
        )

    svc = ConversationService(db)
    created_conversation = False

    if payload.conversation_id is not None:
        conversation = await svc.get_conversation(payload.conversation_id)
        if not conversation:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
        if conversation.user_id != current_user.id:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    else:
        conversation = await svc.create_conversation(
            current_user.id,
            ConversationCreate(subject=payload.subject or _build_conversation_subject(payload.content)),
        )
        created_conversation = True

    user_message = await svc.add_message(
        conversation.id,
        current_user.id,
        MessageCreate(content=payload.content, is_internal=False),
    )
    await db.commit()
    await db.refresh(conversation)
    await db.refresh(user_message)
    asyncio.create_task(
        ConversationPlaybookService.evaluate_playbooks_for_conversation(
            conversation.id,
            event="customer_stream_message",
        )
    )

    conversation_payload = ConversationResponse.model_validate(conversation).model_dump(mode="json")
    user_message_payload = MessageResponse.model_validate(user_message).model_dump(mode="json")

    async def event_stream():
        yield _sse_event(
            "meta",
            {
                "conversation": conversation_payload,
                "user_message": user_message_payload,
                "created_conversation": created_conversation,
            },
        )

        async with async_session_factory() as stream_db:
            stream_svc = ConversationService(stream_db)
            streamed_tokens: list[str] = []

            try:
                stream_conversation = await stream_svc.get_conversation(conversation.id)
                if not stream_conversation:
                    yield _sse_event("error", {"detail": "Conversation state changed before streaming began"})
                    return

                _, _, stream_auto_reply = await _evaluate_conversation_auto_reply(
                    stream_db,
                    stream_conversation,
                )
                if not stream_auto_reply.effective_enabled:
                    yield _sse_event("status", {"phase": "disabled"})
                    yield _sse_event(
                        "done",
                        {
                            "assistant_message": None,
                            "auto_reply_enabled": False,
                            "auto_reply_block_reason": stream_auto_reply.block_reason,
                            "auto_reply_paused_until": (
                                stream_auto_reply.paused_until.isoformat()
                                if stream_auto_reply.paused_until
                                else None
                            ),
                        },
                    )
                    return

                yield _sse_event("status", {"phase": "thinking"})

                customer = await stream_svc.get_user(current_user.id)
                latest_message = await stream_svc.get_message(user_message.id)
                if not customer or not latest_message:
                    yield _sse_event("error", {"detail": "Conversation state changed before streaming began"})
                    return

                request, attachment_context = await stream_svc.build_support_reply_request(
                    conversation=stream_conversation,
                    customer=customer,
                    latest_message=latest_message,
                )

                try:
                    async for token in ResponseGenerationService(stream_db).generate_stream(request):
                        if not token:
                            continue
                        streamed_tokens.append(token)
                        yield _sse_event("token", {"delta": token})
                except Exception:
                    # Fall back to the regular generation path if streaming fails before completion.
                    streamed_tokens.clear()

                raw_streamed_text = "".join(streamed_tokens).strip()
                if raw_streamed_text:
                    reply_text = format_response(
                        raw_streamed_text,
                        channel=request.channel,
                        language=request.language,
                    )
                else:
                    reply_text = await stream_svc._generate_reply_text(
                        request,
                        attachment_context=attachment_context,
                    )

                if not reply_text:
                    reply_text = stream_svc._default_support_reply(request.language)

                reply = await stream_svc.save_support_reply(
                    conversation=stream_conversation,
                    reply_text=reply_text,
                )
                await stream_db.commit()
                asyncio.create_task(
                    ConversationPlaybookService.evaluate_playbooks_for_conversation(
                        conversation.id,
                        event="support_stream_reply",
                    )
                )

                yield _sse_event(
                    "done",
                    {
                        "assistant_message": MessageResponse.model_validate(reply).model_dump(mode="json"),
                        "auto_reply_enabled": True,
                    },
                )
            except asyncio.CancelledError:
                await stream_db.rollback()
                raise
            except Exception:
                await stream_db.rollback()
                yield _sse_event("error", {"detail": "Reply generation failed"})

    return StreamingResponse(
        event_stream(),
        media_type="text/event-stream",
        headers={
            "Cache-Control": "no-cache",
            "Connection": "keep-alive",
            "X-Accel-Buffering": "no",
        },
    )


@router.post(
    "/{conversation_id}/messages/attachment",
    response_model=MessageResponse,
    status_code=status.HTTP_201_CREATED,
)
async def send_attachment_message(
    conversation_id: uuid.UUID,
    background_tasks: BackgroundTasks,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    file: UploadFile = File(..., description="Attached file, image, or recorded audio"),
    content: str = Form(""),
    is_internal: bool = Form(False),
):
    """Send a message with an attached file in a conversation."""
    _ensure_operator_can_reply(current_user)
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if current_user.role == UserRole.CLIENT and conv.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    if current_user.role == UserRole.CLIENT and is_internal:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Clients cannot send internal messages",
        )
    await _ensure_agent_not_suspended_from_conversation(db, conversation_id, current_user)

    stored = await _store_chat_attachment(conversation_id, file)
    payload = MessageCreate(
        content=_build_attachment_message_content(
            content=content,
            filename=stored["filename"],
            content_type=stored["content_type"],
        ),
        is_internal=is_internal,
    )
    message = await svc.add_message(
        conversation_id,
        current_user.id,
        payload,
        attachment_path=stored["path"],
        attachment_filename=stored["filename"],
        attachment_content_type=stored["content_type"],
        attachment_size=stored["size"],
    )
    if (
        current_user.role == UserRole.CLIENT
        and not is_internal
        and await _is_chat_auto_reply_allowed(db, conv)
    ):
        background_tasks.add_task(
            ConversationService.generate_support_reply_for_message,
            conversation_id=conversation_id,
            customer_id=current_user.id,
            latest_message_id=message.id,
        )
    if not is_internal:
        playbook_event = (
            "customer_attachment_message"
            if current_user.role == UserRole.CLIENT
            else "operator_attachment_message"
        )
        _schedule_playbook_evaluation(
            background_tasks,
            conversation_id=conversation_id,
            event=playbook_event,
        )
    return message


@router.get("/{conversation_id}/messages/{message_id}/attachment")
async def get_message_attachment(
    conversation_id: uuid.UUID,
    message_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
):
    """Download a message attachment with conversation-level access control."""
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if current_user.role == UserRole.CLIENT and conv.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")

    message = await svc.get_message(message_id)
    if not message or message.conversation_id != conversation_id or not message.attachment_path:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Attachment not found")

    attachment_path = Path(message.attachment_path)
    if not attachment_path.exists():
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Attachment file not found")

    return FileResponse(
        path=str(attachment_path),
        media_type=message.attachment_content_type or "application/octet-stream",
        filename=message.attachment_filename or attachment_path.name,
    )


@router.get("/{conversation_id}/messages", response_model=list[MessageResponse])
async def get_messages(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(get_current_user)],
    skip: int = Query(0, ge=0),
    limit: int = Query(100, ge=1, le=500),
):
    """Get messages for a conversation."""
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if current_user.role == UserRole.CLIENT and conv.user_id != current_user.id:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Access denied")
    return await svc.get_messages(
        conversation_id,
        skip=skip,
        limit=limit,
        include_internal=current_user.role != UserRole.CLIENT,
    )


@router.post(
    "/{conversation_id}/assisted-draft/jobs",
    response_model=ConversationAiJobQueuedResponse,
    status_code=status.HTTP_202_ACCEPTED,
)
async def queue_assisted_draft_job(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if conv.channel not in {ChannelType.CHAT, ChannelType.WHATSAPP}:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Assisted drafts are currently available for chat and WhatsApp conversations only",
        )

    latest_message_result = await db.execute(
        select(Message.id)
        .where(
            Message.conversation_id == conversation_id,
            Message.is_internal == False,
            Message.sender_id == conv.user_id,
        )
        .order_by(Message.created_at.desc())
        .limit(1)
    )
    if latest_message_result.scalar_one_or_none() is None:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No customer message is available to draft a reply",
        )

    from app.workers.tasks import generate_conversation_assisted_draft_job_task

    task = generate_conversation_assisted_draft_job_task.delay(
        conversation_id=str(conversation_id),
        requested_by_user_id=str(current_user.id),
    )
    await _store_conversation_ai_job_meta(
        redis,
        job_id=task.id,
        conversation_id=conversation_id,
        job_type="assisted_draft",
    )
    return ConversationAiJobQueuedResponse(
        job_id=task.id,
        job_type="assisted_draft",
    )


@router.get(
    "/{conversation_id}/assisted-draft/jobs/{job_id}",
    response_model=ConversationAssistedDraftJobStatusResponse,
)
async def get_assisted_draft_job_status(
    conversation_id: uuid.UUID,
    job_id: str,
    redis: Annotated[RedisService, Depends(get_redis)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    from app.workers.celery_app import celery_app

    async_result = celery_app.AsyncResult(job_id)
    status_value = _normalize_job_status(async_result.state)
    meta = await _load_conversation_ai_job_meta(redis, job_id=job_id)

    if meta:
        if (
            str(meta.get("conversation_id")) != str(conversation_id)
            or str(meta.get("job_type")) != "assisted_draft"
        ):
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")
    elif status_value in {"queued", "started"}:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")

    if status_value in {"queued", "started"}:
        return ConversationAssistedDraftJobStatusResponse(
            job_id=job_id,
            status=status_value,
        )

    if status_value == "succeeded":
        payload = async_result.result
        if not isinstance(payload, dict):
            return ConversationAssistedDraftJobStatusResponse(
                job_id=job_id,
                status="failed",
                error="Job completed with invalid payload",
            )

        if (
            str(payload.get("job_type")) != "assisted_draft"
            or str(payload.get("conversation_id")) != str(conversation_id)
        ):
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")

        try:
            assisted_draft = ConversationAssistedDraftResponse.model_validate(payload.get("result"))
        except Exception:
            return ConversationAssistedDraftJobStatusResponse(
                job_id=job_id,
                status="failed",
                error="Job completed with malformed assisted draft data",
            )

        return ConversationAssistedDraftJobStatusResponse(
            job_id=job_id,
            status="succeeded",
            assisted_draft=assisted_draft,
        )

    return ConversationAssistedDraftJobStatusResponse(
        job_id=job_id,
        status="failed",
        error=_job_error_message(async_result.result),
    )


@router.post(
    "/{conversation_id}/summary/jobs",
    response_model=ConversationAiJobQueuedResponse,
    status_code=status.HTTP_202_ACCEPTED,
)
async def queue_conversation_summary_job(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    redis: Annotated[RedisService, Depends(get_redis)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    max_messages: int = Query(120, ge=20, le=200),
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    from app.workers.tasks import generate_conversation_summary_job_task

    task = generate_conversation_summary_job_task.delay(
        conversation_id=str(conversation_id),
        max_messages=max_messages,
    )
    await _store_conversation_ai_job_meta(
        redis,
        job_id=task.id,
        conversation_id=conversation_id,
        job_type="summary",
    )
    return ConversationAiJobQueuedResponse(
        job_id=task.id,
        job_type="summary",
    )


@router.get(
    "/{conversation_id}/summary/jobs/{job_id}",
    response_model=ConversationSummaryJobStatusResponse,
)
async def get_conversation_summary_job_status(
    conversation_id: uuid.UUID,
    job_id: str,
    redis: Annotated[RedisService, Depends(get_redis)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    from app.workers.celery_app import celery_app

    async_result = celery_app.AsyncResult(job_id)
    status_value = _normalize_job_status(async_result.state)
    meta = await _load_conversation_ai_job_meta(redis, job_id=job_id)

    if meta:
        if (
            str(meta.get("conversation_id")) != str(conversation_id)
            or str(meta.get("job_type")) != "summary"
        ):
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")
    elif status_value in {"queued", "started"}:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")

    if status_value in {"queued", "started"}:
        return ConversationSummaryJobStatusResponse(
            job_id=job_id,
            status=status_value,
        )

    if status_value == "succeeded":
        payload = async_result.result
        if not isinstance(payload, dict):
            return ConversationSummaryJobStatusResponse(
                job_id=job_id,
                status="failed",
                error="Job completed with invalid payload",
            )

        if (
            str(payload.get("job_type")) != "summary"
            or str(payload.get("conversation_id")) != str(conversation_id)
        ):
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Job not found")

        try:
            summary = ConversationSummaryResponse.model_validate(payload.get("result"))
        except Exception:
            return ConversationSummaryJobStatusResponse(
                job_id=job_id,
                status="failed",
                error="Job completed with malformed summary data",
            )

        return ConversationSummaryJobStatusResponse(
            job_id=job_id,
            status="succeeded",
            summary=summary,
        )

    return ConversationSummaryJobStatusResponse(
        job_id=job_id,
        status="failed",
        error=_job_error_message(async_result.result),
    )


@router.post(
    "/{conversation_id}/assisted-draft",
    response_model=ConversationAssistedDraftResponse,
)
async def generate_assisted_draft(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")
    if conv.channel not in {ChannelType.CHAT, ChannelType.WHATSAPP}:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Assisted drafts are currently available for chat and WhatsApp conversations only",
        )

    latest_message_result = await db.execute(
        select(Message)
        .where(
            Message.conversation_id == conversation_id,
            Message.is_internal == False,
            Message.sender_id == conv.user_id,
        )
        .order_by(Message.created_at.desc())
        .limit(1)
    )
    latest_customer_message = latest_message_result.scalar_one_or_none()
    if not latest_customer_message:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No customer message is available to draft a reply",
        )

    customer = await svc.get_user(conv.user_id)
    if not customer:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation customer not found")

    request, attachment_context = await svc.build_support_reply_request(
        conversation=conv,
        customer=customer,
        latest_message=latest_customer_message,
    )
    try:
        draft = await asyncio.wait_for(
            svc._generate_reply_text(request, attachment_context=attachment_context),
            timeout=max(1, settings.CONVERSATION_ASSISTED_DRAFT_TIMEOUT_SECONDS),
        )
    except asyncio.TimeoutError:
        logger.warning(
            "Conversation assisted draft timed out for %s after %ss",
            conversation_id,
            settings.CONVERSATION_ASSISTED_DRAFT_TIMEOUT_SECONDS,
        )
        draft = svc._default_support_reply(request.language)

    if not draft:
        draft = svc._default_support_reply(request.language)

    await _log_assisted_draft_event(
        db,
        event="generated",
        conversation_id=conversation_id,
        channel=conv.channel,
        user_id=current_user.id,
        source_message_id=latest_customer_message.id,
    )

    return ConversationAssistedDraftResponse(
        conversation_id=conversation_id,
        source_message_id=latest_customer_message.id,
        draft=draft,
        language=request.language,
        generated_at=datetime.now(timezone.utc),
    )


@router.post("/{conversation_id}/summary", response_model=ConversationSummaryResponse)
async def summarize_conversation(
    conversation_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    max_messages: int = Query(120, ge=20, le=200),
):
    """Generate an AI summary of a conversation for operator handoff and ticketing."""
    svc = ConversationService(db)
    conv = await svc.get_conversation(conversation_id)
    if not conv:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Conversation not found")

    contact = await svc.get_user(conv.user_id)

    messages_result = await db.execute(
        select(Message)
        .where(
            Message.conversation_id == conversation_id,
            Message.is_internal == False,
        )
        .order_by(Message.created_at.desc())
        .limit(max_messages)
    )
    recent_messages_desc = list(messages_result.scalars().all())
    messages = list(reversed(recent_messages_desc))

    if not messages:
        return ConversationSummaryResponse(
            conversation_id=conversation_id,
            message_count=0,
            provider="none",
            model="none",
            problem_summary="No conversation messages are available yet.",
            resolution_state="unknown",
            resolution_description="No resolution state can be inferred without messages.",
            next_action="Wait for customer details or send a clarification message.",
            customer_sentiment="unknown",
            language=None,
            generated_at=datetime.now(timezone.utc),
        )

    transcript_lines: list[str] = []
    latest_customer_text = ""

    for msg in messages:
        role = "Customer" if msg.sender_id == conv.user_id else "Agent"
        content = " ".join((msg.content or "").split())
        if not content:
            continue
        if len(content) > 500:
            content = content[:500].rstrip() + "..."

        if role == "Customer":
            latest_customer_text = content

        timestamp = msg.created_at.isoformat(timespec="seconds") if msg.created_at else ""
        transcript_lines.append(f"{timestamp} | {role}: {content}")

    transcript = "\n".join(transcript_lines)
    if len(transcript) > 10_000:
        transcript = transcript[-10_000:]

    customer_label = (
        (contact.full_name if contact else "")
        or (contact.email if contact else "")
        or str(conv.user_id)
    )
    llm_messages = _build_conversation_summary_messages(
        customer_label=customer_label,
        conversation_status=conv.status.value,
        transcript=transcript,
    )

    attempts: list[str] = []
    last_error: Exception | None = None

    for provider_enum in _summary_provider_order():
        provider = get_provider(provider_enum)
        if not getattr(provider, "_is_configured", False):
            continue

        try:
            generated = await asyncio.wait_for(
                provider.generate(
                    messages=llm_messages,
                    temperature=0.2,
                    max_tokens=600,
                ),
                timeout=max(1, settings.CONVERSATION_SUMMARY_TIMEOUT_SECONDS),
            )
            parsed = _extract_json_object(str(generated.get("content", "")))

            problem_summary = str(
                parsed.get("problem_summary")
                or parsed.get("issue_summary")
                or ""
            ).strip()
            if not problem_summary:
                problem_summary = (
                    latest_customer_text
                    or "The customer reported an issue, but the exact problem is not fully clear yet."
                )

            resolution_description = str(
                parsed.get("resolution_description")
                or parsed.get("resolution_status")
                or ""
            ).strip() or "Resolution status is not clearly established yet."

            next_action = str(parsed.get("next_action") or "").strip() or (
                "Review the latest customer message and provide a concrete next troubleshooting or account action."
            )

            resolution_state = _normalize_choice(
                parsed.get("resolution_state"),
                _SUMMARY_ALLOWED_STATES,
                "unknown",
            )
            sentiment = _normalize_choice(
                parsed.get("customer_sentiment"),
                _SUMMARY_ALLOWED_SENTIMENTS,
                "unknown",
            )

            language = str(parsed.get("language") or "").strip() or None
            if language and len(language) > 16:
                language = language[:16]

            return ConversationSummaryResponse(
                conversation_id=conversation_id,
                message_count=len(messages),
                provider=provider_enum.value,
                model=str(generated.get("model") or "unknown"),
                problem_summary=problem_summary,
                resolution_state=resolution_state,
                resolution_description=resolution_description,
                next_action=next_action,
                customer_sentiment=sentiment,
                language=language,
                generated_at=datetime.now(timezone.utc),
            )
        except asyncio.TimeoutError as exc:
            attempts.append(f"{provider_enum.value}:TimeoutError")
            last_error = exc
        except Exception as exc:
            attempts.append(f"{provider_enum.value}:{exc.__class__.__name__}")
            last_error = exc

    if last_error:
        logger.warning(
            "Conversation summary generation failed for %s via %s",
            conversation_id,
            ",".join(attempts),
            exc_info=last_error,
        )
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="AI summary generation failed. Please try again.",
        )

    raise HTTPException(
        status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
        detail="No configured AI provider is available for conversation summary.",
    )


def _sanitize_attachment_name(filename: str | None) -> str:
    raw = Path(filename or "attachment").name
    cleaned = re.sub(r"[^A-Za-z0-9._-]+", "_", raw).strip("._")
    return cleaned or "attachment"


def _attachment_label(content_type: str) -> str:
    if content_type.startswith("image/"):
        return "Shared an image"
    if content_type.startswith("audio/"):
        return "Sent a voice message"
    return "Shared a file"


def _build_attachment_message_content(content: str, filename: str, content_type: str) -> str:
    stripped = content.strip()
    if stripped:
        return stripped
    return f"{_attachment_label(content_type)}: {filename}"


def _build_conversation_subject(content: str) -> str:
    compact = re.sub(r"\s+", " ", (content or "").strip())
    if not compact:
        return "New support chat"
    return compact[:72].rstrip() + ("..." if len(compact) > 72 else "")


def _sse_event(event: str, payload: dict) -> str:
    return f"event: {event}\ndata: {json.dumps(payload)}\n\n"


async def _store_chat_attachment(
    conversation_id: uuid.UUID,
    file: UploadFile,
) -> dict[str, str | int]:
    filename = _sanitize_attachment_name(file.filename)
    content_type = (file.content_type or "application/octet-stream").strip() or "application/octet-stream"
    try:
        payload = await file.read()
    finally:
        await file.close()
    if not payload:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Uploaded file is empty")
    if len(payload) > MAX_CHAT_ATTACHMENT_SIZE:
        raise HTTPException(
            status_code=status.HTTP_413_REQUEST_ENTITY_TOO_LARGE,
            detail=(
                "Attachment file too large. "
                f"Maximum size: {settings.MAX_CHAT_ATTACHMENT_SIZE_MB} MB"
            ),
        )

    target_dir = Path(settings.CHAT_ATTACHMENTS_DIR) / str(conversation_id)
    target_dir.mkdir(parents=True, exist_ok=True)
    stored_name = f"{uuid.uuid4().hex}_{filename}"
    target_path = target_dir / stored_name
    target_path.write_bytes(payload)

    return {
        "filename": filename,
        "content_type": content_type,
        "size": len(payload),
        "path": str(target_path),
    }
