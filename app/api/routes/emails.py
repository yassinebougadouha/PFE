"""
Email routes — ingestion, retrieval, reply, and thread view.
"""

from datetime import datetime, timezone
import uuid
from typing import Annotated, Literal, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, Request, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.email import Email
from app.db.models.enums import AuditAction, EmailStatus
from app.api.deps import require_agent_or_admin
from app.schemas.email import (
    EmailAssistedDraftResponse,
    EmailBulkActionRequest,
    EmailBulkActionResponse,
    EmailComposeRequest,
    EmailComposeResponse,
    EmailDeliveryStatusResponse,
    EmailFlagUpdateRequest,
    EmailIngest,
    EmailListResponse,
    EmailReplyRequest,
    EmailReplyResponse,
    EmailResponse,
)
from app.rag.response_providers.enums import ResponseChannel, ResponseTone
from app.rag.response_providers.schemas import GenerateRequest
from app.rag.response_providers.service import ResponseGenerationService
from app.services.email_service import EmailService
from app.services.audit_service import AuditService
from app.services.gmail_service import GmailService
from app.services.runtime_mail_service import RuntimeMailService
from app.services.settings_service import SettingsService
from app.utils.mail_content import normalize_mail_like_text

router = APIRouter(prefix="/emails", tags=["Emails"])


def _ensure_utc(value: datetime) -> datetime:
    if value.tzinfo is None:
        return value.replace(tzinfo=timezone.utc)
    return value.astimezone(timezone.utc)


def _clip_email_text(value: str | None, *, max_chars: int) -> str:
    normalized = normalize_mail_like_text(value or "")
    if len(normalized) <= max_chars:
        return normalized
    return f"{normalized[: max_chars - 3].rstrip()}..."


def _build_email_assisted_draft_query(anchor_email: Email, thread_emails: list[Email]) -> str:
    subject = (anchor_email.subject or "(No subject)").strip() or "(No subject)"
    customer_message = _clip_email_text(anchor_email.body, max_chars=1600) or "(empty message)"

    context_lines: list[str] = []
    for email in thread_emails[-6:]:
        direction = "Operator" if email.is_outbound else "Customer"
        snippet = _clip_email_text(email.body, max_chars=320)
        if not snippet:
            continue
        context_lines.append(f"- {direction}: {snippet}")

    context_block = "\n".join(context_lines) if context_lines else "- No additional thread history"
    query = (
        "Draft an operator email reply to the customer message below.\n"
        f"Subject: {subject}\n"
        "Customer message:\n"
        f"{customer_message}\n\n"
        "Recent thread context:\n"
        f"{context_block}\n\n"
        "Instructions:\n"
        "- Keep it concise, professional, and solution-oriented.\n"
        "- Match the customer's language.\n"
        "- Provide concrete next steps when applicable.\n"
        "- Do not mention being an AI.\n"
    )
    return query[:5000]


async def _log_email_assisted_draft_event(
    db: AsyncSession,
    *,
    event: str,
    original_email: Email,
    user_id: uuid.UUID,
    assisted_draft_edited: bool | None = None,
    assisted_draft_generated_at: datetime | None = None,
    sent_content: str | None = None,
    trace_id: str | None = None,
) -> None:
    meta: dict[str, object] = {
        "event": event,
        "channel": "email",
        "source_email_id": str(original_email.id),
    }
    if original_email.gmail_thread_id:
        meta["thread_id"] = original_email.gmail_thread_id
    if sent_content:
        meta["sent_char_count"] = len(sent_content.strip())

    if event == "accepted":
        if assisted_draft_edited is not None:
            meta["assisted_draft_edited"] = bool(assisted_draft_edited)
        if assisted_draft_generated_at is not None:
            generated_at = _ensure_utc(assisted_draft_generated_at)
            meta["assisted_draft_generated_at"] = generated_at.isoformat()
            meta["assisted_draft_seconds_to_send"] = max(
                0,
                int((datetime.now(timezone.utc) - generated_at).total_seconds()),
            )

    audit = AuditService(db)
    await audit.log(
        action=AuditAction.REPLY,
        resource_type="assisted_draft",
        resource_id=str(original_email.id),
        user_id=user_id,
        description=f"Assisted draft {event} on email {original_email.id}",
        meta=meta,
        trace_id=trace_id,
    )


@router.get("", response_model=EmailListResponse)
async def list_emails(
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
    folder: Literal["inbox", "sent", "all"] = Query("inbox"),
    status_filter: Optional[EmailStatus] = Query(None, alias="status"),
    search: Optional[str] = Query(None),
    unread_only: bool = Query(False),
    starred_only: bool = Query(False),
    label: Optional[str] = Query(None),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=200),
):
    """Mailbox listing for in-app inbox/sent views."""
    svc = EmailService(db)
    emails, total = await svc.list_emails(
        folder=folder,
        status=status_filter,
        search=search,
        unread_only=unread_only,
        starred_only=starred_only,
        label=label,
        skip=skip,
        limit=limit,
    )
    return {"emails": emails, "total": total}


@router.post("/compose", response_model=EmailComposeResponse, status_code=status.HTTP_201_CREATED)
async def compose_email(
    payload: EmailComposeRequest,
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    """Queue a brand-new outbound email send using the active delivery mode."""
    from app.workers.tasks import send_new_email_task

    settings_service = SettingsService(db)
    mail_settings = await settings_service.get_all_settings()
    mail_mode = RuntimeMailService.normalize_mail_mode(mail_settings.get("mail_mode"))

    if mail_mode == "gmail":
        gmail_svc = GmailService(db)
        cred = await gmail_svc.get_credential(current_user.id)
        if not cred or not cred.is_active:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Gmail is not connected. Please authorize first.",
            )
    else:
        missing_fields = RuntimeMailService.validate_smtp_settings(mail_settings)
        if missing_fields:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"SMTP is not fully configured: {', '.join(missing_fields)}",
            )

    queued_id = uuid.uuid4()
    send_new_email_task.delay(
        user_id=str(current_user.id),
        recipient=str(payload.recipient),
        subject=payload.subject,
        body=payload.body,
        labels=payload.labels,
    )

    audit = AuditService(db)
    await audit.log(
        action=AuditAction.CREATE,
        resource_type="email",
        resource_id=str(queued_id),
        user_id=current_user.id,
        description=f"Outbound email queued to {payload.recipient}",
        trace_id=request.state.trace_id if hasattr(request.state, "trace_id") else None,
    )

    return {
        "id": queued_id,
        "recipient": str(payload.recipient),
        "subject": payload.subject,
        "body": payload.body,
        "gmail_message_id": None,
        "gmail_thread_id": None,
        "sent_at": datetime.now(timezone.utc),
    }


@router.get("/delivery-status", response_model=EmailDeliveryStatusResponse)
async def get_email_delivery_status(
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    """Return the active outbound mail mode and whether the current user can send mail."""
    settings_service = SettingsService(db)
    mail_settings = await settings_service.get_all_settings()
    mail_mode = RuntimeMailService.normalize_mail_mode(mail_settings.get("mail_mode"))

    gmail_svc = GmailService(db)
    cred = await gmail_svc.get_credential(current_user.id)
    gmail_connected = bool(cred and cred.is_active)

    smtp_missing_fields = RuntimeMailService.validate_smtp_settings(mail_settings)
    smtp_sender_email = RuntimeMailService.resolve_smtp_sender_email(mail_settings) or None
    smtp_ready = not smtp_missing_fields

    return {
        "mail_mode": mail_mode,
        "ready": gmail_connected if mail_mode == "gmail" else smtp_ready,
        "gmail_connected": gmail_connected,
        "gmail_address": cred.gmail_address if cred and cred.is_active else None,
        "gmail_last_synced": cred.updated_at if cred and cred.is_active else None,
        "smtp_ready": smtp_ready,
        "smtp_sender_email": smtp_sender_email,
        "smtp_missing_fields": smtp_missing_fields,
    }


@router.post("/ingest", response_model=EmailResponse, status_code=status.HTTP_201_CREATED)
async def ingest_email(
    payload: EmailIngest,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """
    Ingest an incoming email.
    Stored for async processing — Celery will convert it to a ticket.
    """
    svc = EmailService(db)
    email = await svc.ingest_email(payload)

    # Trigger Celery task (import here to avoid circular deps)
    from app.workers.tasks import process_email_task
    process_email_task.delay(str(email.id))

    return email


@router.post("/bulk-action", response_model=EmailBulkActionResponse)
async def bulk_action_emails(
    payload: EmailBulkActionRequest,
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    if payload.action in {"add_label", "remove_label"} and not (payload.label or "").strip():
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="label is required for add_label/remove_label actions",
        )

    svc = EmailService(db)
    updated = await svc.apply_bulk_action(
        email_ids=payload.email_ids,
        action=payload.action,
        label=payload.label,
    )

    audit = AuditService(db)
    await audit.log(
        action=AuditAction.UPDATE,
        resource_type="email",
        user_id=current_user.id,
        description=f"Bulk email action {payload.action} applied to {updated} rows",
        trace_id=request.state.trace_id if hasattr(request.state, "trace_id") else None,
    )

    return {"action": payload.action, "updated": updated}


@router.get("/{email_id}", response_model=EmailResponse)
async def get_email(
    email_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = EmailService(db)
    email = await svc.get_email(email_id)
    if not email:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Email not found")
    return email


@router.patch("/{email_id}/flags", response_model=EmailResponse)
async def update_email_flags(
    email_id: uuid.UUID,
    payload: EmailFlagUpdateRequest,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = EmailService(db)
    updated = await svc.update_flags(
        email_id,
        is_read=payload.is_read,
        is_starred=payload.is_starred,
        labels=payload.labels,
    )
    if not updated:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Email not found")
    return updated


@router.post("/{email_id}/assisted-draft", response_model=EmailAssistedDraftResponse)
async def generate_email_assisted_draft(
    email_id: uuid.UUID,
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    svc = EmailService(db)
    anchor = await svc.get_email(email_id)
    if not anchor:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Email not found")

    if anchor.gmail_thread_id:
        thread_result = await db.execute(
            select(Email)
            .where(Email.gmail_thread_id == anchor.gmail_thread_id)
            .order_by(Email.created_at.asc())
        )
        thread_emails = list(thread_result.scalars().all())
    else:
        thread_emails = [anchor]

    source_email = next((email for email in reversed(thread_emails) if not email.is_outbound), anchor)
    query = _build_email_assisted_draft_query(source_email, thread_emails)

    generator = ResponseGenerationService(db)
    try:
        generated = await generator.generate(
            GenerateRequest(
                query=query,
                channel=ResponseChannel.EMAIL,
                tone=ResponseTone.PROFESSIONAL,
                include_sources=False,
                max_tokens=700,
                temperature=0.2,
            )
        )
    except Exception as exc:
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="Unable to generate assisted draft for this email right now",
        ) from exc

    draft = (generated.response or "").strip()
    if not draft:
        raise HTTPException(
            status_code=status.HTTP_502_BAD_GATEWAY,
            detail="Assisted draft generation returned an empty response",
        )

    trace_id = request.state.trace_id if hasattr(request.state, "trace_id") else None
    await _log_email_assisted_draft_event(
        db,
        event="generated",
        original_email=source_email,
        user_id=current_user.id,
        trace_id=trace_id,
    )

    return {
        "original_email_id": source_email.id,
        "draft": draft,
        "language": None,
        "generated_at": generated.generated_at,
    }


@router.post(
    "/{email_id}/reply",
    response_model=EmailReplyResponse,
    status_code=status.HTTP_201_CREATED,
)
async def reply_to_email(
    email_id: uuid.UUID,
    payload: EmailReplyRequest,
    request: Request,
    db: Annotated[AsyncSession, Depends(get_db)],
    current_user: Annotated[User, Depends(require_agent_or_admin)],
):
    """
    Reply to an ingested email using the active delivery mode.
    The reply is recorded as an outbound email in the local mailbox.
    """
    # Verify original email exists
    svc = EmailService(db)
    original = await svc.get_email(email_id)
    if not original:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Email not found",
        )

    # Dispatch the send via Celery (fire-and-forget for non-blocking)
    from app.workers.tasks import send_email_reply_task
    send_email_reply_task.delay(
        user_id=str(current_user.id),
        original_email_id=str(email_id),
        reply_body=payload.body,
    )

    # Audit
    audit = AuditService(db)
    trace_id = request.state.trace_id if hasattr(request.state, "trace_id") else None
    await audit.log(
        action=AuditAction.REPLY,
        resource_type="email",
        resource_id=str(email_id),
        user_id=current_user.id,
        description=f"Reply queued to {original.sender_address}",
        meta={
            "channel": "email",
            "used_assisted_draft": bool(payload.used_assisted_draft),
        },
        trace_id=trace_id,
    )

    if payload.used_assisted_draft:
        await _log_email_assisted_draft_event(
            db,
            event="accepted",
            original_email=original,
            user_id=current_user.id,
            assisted_draft_edited=payload.assisted_draft_edited,
            assisted_draft_generated_at=payload.assisted_draft_generated_at,
            sent_content=payload.body,
            trace_id=trace_id,
        )

    return {
        "id": uuid.uuid4(),  # placeholder until Celery completes
        "original_email_id": email_id,
        "recipient": original.sender_address,
        "subject": f"Re: {original.subject}" if not original.subject.lower().startswith("re:") else original.subject,
        "body": payload.body,
        "gmail_message_id": None,
        "gmail_thread_id": original.gmail_thread_id,
        "sent_at": original.created_at,  # will be updated by worker
    }


@router.get("/{email_id}/thread", response_model=list[EmailResponse])
async def get_email_thread(
    email_id: uuid.UUID,
    db: Annotated[AsyncSession, Depends(get_db)],
    _: Annotated[User, Depends(require_agent_or_admin)],
):
    """
    Get the full email thread (original + all replies) for conversation context.
    Returns emails ordered by creation time.
    """
    # Get the anchor email
    result = await db.execute(select(Email).where(Email.id == email_id))
    anchor = result.scalar_one_or_none()

    if not anchor:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Email not found",
        )

    # If no thread ID, return just this email
    if not anchor.gmail_thread_id:
        return [anchor]

    # Fetch all emails in the same Gmail thread
    thread_result = await db.execute(
        select(Email)
        .where(Email.gmail_thread_id == anchor.gmail_thread_id)
        .order_by(Email.created_at.asc())
    )
    return list(thread_result.scalars().all())
