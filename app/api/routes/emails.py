"""
Email routes — ingestion, retrieval, reply, and thread view.
"""

import uuid
from typing import Annotated

from fastapi import APIRouter, Depends, HTTPException, Request, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.email import Email
from app.db.models.enums import AuditAction
from app.api.deps import require_agent_or_admin, get_current_user
from app.schemas.email import EmailIngest, EmailResponse, EmailReplyRequest, EmailReplyResponse
from app.schemas.common import MessageOut
from app.services.email_service import EmailService
from app.services.audit_service import AuditService

router = APIRouter(prefix="/emails", tags=["Emails"])


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
    Reply to an ingested email via the connected Gmail account.
    The reply is threaded in Gmail and recorded as an outbound email.
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
    task = send_email_reply_task.delay(
        user_id=str(current_user.id),
        original_email_id=str(email_id),
        reply_body=payload.body,
    )

    # Audit
    audit = AuditService(db)
    await audit.log(
        action=AuditAction.REPLY,
        resource_type="email",
        resource_id=str(email_id),
        user_id=current_user.id,
        description=f"Reply queued to {original.sender_address}",
        trace_id=request.state.trace_id if hasattr(request.state, "trace_id") else None,
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
