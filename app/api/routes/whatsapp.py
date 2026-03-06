"""
WhatsApp integration routes.

- GET  /whatsapp/webhook       → Meta webhook verification (hub.challenge)
- POST /whatsapp/webhook       → Receive incoming messages (Meta Cloud API)
- POST /whatsapp/bridge/webhook → Receive incoming messages (Web bridge)
- POST /whatsapp/send          → Send a message (agent/admin)
- POST /whatsapp/reply/{conversation_id} → Reply in WhatsApp conversation
- GET  /whatsapp/status        → Check provider status
- GET  /whatsapp/inbox         → List WhatsApp conversations with unread counts
- GET  /whatsapp/inbox/{conversation_id} → Get full conversation messages
- POST /whatsapp/inbox/{conversation_id}/read → Mark messages as read
"""

import uuid
from typing import Annotated, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, Request, status
from fastapi.responses import PlainTextResponse
from sqlalchemy import select, func, case, and_
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import selectinload

from app.db.session import get_db
from app.db.models.user import User
from app.db.models.conversation import Conversation, Message
from app.db.models.enums import ChannelType, ConversationStatus, UserRole
from app.api.deps import get_current_user, require_agent_or_admin
from app.schemas.whatsapp import (
    WhatsAppSendRequest,
    WhatsAppReplyRequest,
    WhatsAppSendResult,
    WhatsAppStatusResponse,
    WhatsAppInboxResponse,
    WhatsAppConversationInbox,
    WhatsAppConversationDetail,
    WhatsAppMessageItem,
    MarkReadRequest,
)
from app.services.whatsapp_service import (
    MetaCloudProvider,
    get_whatsapp_provider,
)
from app.services.audit_service import AuditService

router = APIRouter(prefix="/whatsapp", tags=["WhatsApp Integration"])


# ── Webhooks (no auth — called by Meta / bridge) ────────

@router.get("/webhook", response_class=PlainTextResponse)
async def verify_webhook(
    request: Request,
    hub_mode: str = Query(None, alias="hub.mode"),
    hub_verify_token: str = Query(None, alias="hub.verify_token"),
    hub_challenge: str = Query(None, alias="hub.challenge"),
):
    """
    Meta Cloud API webhook verification (GET request).
    Meta sends hub.mode, hub.verify_token, hub.challenge.
    We must return the challenge to confirm the subscription.
    """
    if not all([hub_mode, hub_verify_token, hub_challenge]):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Missing required verification parameters",
        )

    result = MetaCloudProvider.verify_webhook(hub_mode, hub_verify_token, hub_challenge)
    if result:
        return result

    raise HTTPException(
        status_code=status.HTTP_403_FORBIDDEN,
        detail="Verification failed",
    )


@router.post("/webhook")
async def meta_webhook(
    request: Request,
    db: AsyncSession = Depends(get_db),
):
    """
    Receive incoming WhatsApp messages from Meta Cloud API.
    Meta sends JSON POST with the message data.
    No authentication — Meta doesn't send our JWT.
    """
    payload = await request.json()

    messages = MetaCloudProvider.parse_webhook_payload(payload)
    if not messages:
        # Status updates, read receipts, etc. — acknowledge silently
        return {"status": "ok", "messages_processed": 0}

    results = []
    for msg in messages:
        # Fire Celery task for async processing
        from app.workers.tasks import process_whatsapp_incoming_task
        process_whatsapp_incoming_task.delay(
            from_number=msg["from_number"],
            body=msg["body"],
            sender_name=msg["sender_name"],
            message_id=msg["message_id"],
        )
        results.append({"from": msg["from_number"], "queued": True})

    return {"status": "ok", "messages_processed": len(results), "results": results}


@router.post("/bridge/webhook")
async def bridge_webhook(
    request: Request,
    db: AsyncSession = Depends(get_db),
):
    """
    Receive incoming WhatsApp messages from the Web bridge.
    Bridge sends a simpler JSON format.
    """
    try:
        payload = await request.json()
    except Exception:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Invalid or empty JSON body",
        )

    # Bridge format: { from: "XXXXXXXXX@c.us", body: "...", sender_name: "..." }
    from_raw = payload.get("from", payload.get("chatId", ""))
    body = payload.get("body", payload.get("message", ""))
    sender_name = payload.get("sender_name", payload.get("name", "Unknown"))

    if not from_raw or not body:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Missing 'from' or 'body' in webhook payload",
        )

    # Normalize phone number: strip @c.us, @s.whatsapp.net
    from_number = from_raw.split("@")[0]

    from app.workers.tasks import process_whatsapp_incoming_task
    process_whatsapp_incoming_task.delay(
        from_number=from_number,
        body=body,
        sender_name=sender_name,
        message_id=payload.get("id", payload.get("message_id")),
    )

    return {"status": "ok", "from": from_number, "queued": True}


# ── Send / Reply (authenticated) ─────────────────────────

@router.post("/send", response_model=WhatsAppSendResult)
async def send_message(
    data: WhatsAppSendRequest,
    db: AsyncSession = Depends(get_db),
    current_user: User = Depends(require_agent_or_admin),
):
    """Send a WhatsApp message to a phone number (agent/admin only)."""
    provider = get_whatsapp_provider()
    result = await provider.send_message(data.to_number, data.message)

    if result["success"]:
        # Record outbound as a Message in the conversation via Celery
        from app.workers.tasks import record_whatsapp_outbound_task
        record_whatsapp_outbound_task.delay(
            to_number=data.to_number,
            body=data.message,
            wa_message_id=result.get("message_id"),
            user_id=str(current_user.id),
        )

        audit = AuditService(db)
        await audit.log(
            action="WHATSAPP_OUT",
            resource_type="whatsapp_message",
            user_id=current_user.id,
            description=f"WhatsApp sent to {data.to_number} via {provider.provider_name}",
        )
        await db.commit()

    return {
        "success": result["success"],
        "message_id": result.get("message_id"),
        "provider": result["provider"],
        "error": result.get("error"),
    }


@router.post("/reply/{conversation_id}", response_model=WhatsAppSendResult)
async def reply_to_conversation(
    conversation_id: uuid.UUID,
    data: WhatsAppReplyRequest,
    db: AsyncSession = Depends(get_db),
    current_user: User = Depends(require_agent_or_admin),
):
    """
    Reply to a WhatsApp conversation — extracts the customer phone number
    from the conversation subject and sends the reply.
    """
    result = await db.execute(
        select(Conversation).where(Conversation.id == conversation_id)
    )
    conv = result.scalar_one_or_none()

    if not conv:
        raise HTTPException(status_code=404, detail="Conversation not found")

    if conv.channel != ChannelType.WHATSAPP:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="This conversation is not from WhatsApp",
        )

    # Extract phone number from the conversation user's phone_number field
    wa_user_result = await db.execute(
        select(User).where(User.id == conv.user_id)
    )
    wa_user = wa_user_result.scalar_one_or_none()

    phone_number = wa_user.phone_number if wa_user else None

    if not phone_number:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Cannot determine customer phone number from this conversation",
        )

    # Send via provider
    provider = get_whatsapp_provider()
    send_result = await provider.send_message(phone_number, data.message)

    if send_result["success"]:
        from app.workers.tasks import record_whatsapp_outbound_task
        record_whatsapp_outbound_task.delay(
            to_number=phone_number,
            body=data.message,
            wa_message_id=send_result.get("message_id"),
            user_id=str(current_user.id),
            conversation_id=str(conversation_id),
        )

        audit = AuditService(db)
        await audit.log(
            action="WHATSAPP_OUT",
            resource_type="conversation",
            resource_id=str(conversation_id),
            user_id=current_user.id,
            description=f"WhatsApp reply to {phone_number} in conversation {conversation_id}",
        )
        await db.commit()

    return {
        "success": send_result["success"],
        "message_id": send_result.get("message_id"),
        "provider": send_result["provider"],
        "error": send_result.get("error"),
    }


# ── Status ────────────────────────────────────────────────

@router.get("/status", response_model=WhatsAppStatusResponse)
async def whatsapp_status(
    current_user: User = Depends(get_current_user),
):
    """Check WhatsApp integration status and provider configuration."""
    provider = get_whatsapp_provider()
    provider_status = await provider.get_status()

    return {
        "provider": provider.provider_name,
        "configured": provider_status["configured"],
        "details": provider_status.get("details", {}),
    }


# ── Inbox: list conversations, read messages, mark read ──

@router.get("/inbox", response_model=WhatsAppInboxResponse)
async def list_whatsapp_inbox(
    db: AsyncSession = Depends(get_db),
    current_user: User = Depends(require_agent_or_admin),
    status_filter: Optional[ConversationStatus] = Query(None, alias="status"),
    unread_only: bool = Query(False, description="Only show conversations with unread messages"),
    skip: int = Query(0, ge=0),
    limit: int = Query(50, ge=1, le=200),
):
    """
    List all WhatsApp conversations with unread message counts.
    Agents/admins see all WhatsApp conversations.
    """
    # Subquery: count unread messages per conversation
    unread_sub = (
        select(
            Message.conversation_id,
            func.count(Message.id).label("unread_count"),
        )
        .where(Message.is_read == False)
        .group_by(Message.conversation_id)
        .subquery()
    )

    # Subquery: last message per conversation
    last_msg_sub = (
        select(
            Message.conversation_id,
            func.max(Message.created_at).label("last_msg_at"),
        )
        .group_by(Message.conversation_id)
        .subquery()
    )

    # Get the actual last message content
    last_content_sub = (
        select(
            Message.conversation_id,
            Message.content,
            Message.created_at,
        )
        .distinct(Message.conversation_id)
        .order_by(Message.conversation_id, Message.created_at.desc())
        .subquery()
    )

    # Main query
    query = (
        select(
            Conversation,
            func.coalesce(unread_sub.c.unread_count, 0).label("unread_count"),
            last_content_sub.c.content.label("last_message"),
            last_content_sub.c.created_at.label("last_message_at"),
        )
        .outerjoin(unread_sub, Conversation.id == unread_sub.c.conversation_id)
        .outerjoin(last_content_sub, Conversation.id == last_content_sub.c.conversation_id)
        .where(
            Conversation.channel == ChannelType.WHATSAPP,
            Conversation.is_deleted == False,
        )
    )

    if status_filter:
        query = query.where(Conversation.status == status_filter)

    if unread_only:
        query = query.where(func.coalesce(unread_sub.c.unread_count, 0) > 0)

    # Count total
    count_q = (
        select(func.count(Conversation.id))
        .where(
            Conversation.channel == ChannelType.WHATSAPP,
            Conversation.is_deleted == False,
        )
    )
    if status_filter:
        count_q = count_q.where(Conversation.status == status_filter)
    if unread_only:
        count_q = (
            select(func.count(Conversation.id))
            .select_from(Conversation)
            .outerjoin(unread_sub, Conversation.id == unread_sub.c.conversation_id)
            .where(
                Conversation.channel == ChannelType.WHATSAPP,
                Conversation.is_deleted == False,
                func.coalesce(unread_sub.c.unread_count, 0) > 0,
            )
        )

    total = (await db.execute(count_q)).scalar() or 0

    # Order by last message time (most recent first), then unread count
    query = query.order_by(
        last_content_sub.c.created_at.desc().nullslast(),
    ).offset(skip).limit(limit)

    result = await db.execute(query)
    rows = result.all()

    # Build response with user contact info
    conversations = []
    for row in rows:
        conv = row[0]
        unread = row[1]
        last_msg = row[2]
        last_msg_at = row[3]

        # Fetch contact info
        user_result = await db.execute(select(User).where(User.id == conv.user_id))
        user = user_result.scalar_one_or_none()

        conversations.append(
            WhatsAppConversationInbox(
                id=conv.id,
                user_id=conv.user_id,
                contact_name=user.full_name if user else None,
                contact_phone=user.phone_number if user else None,
                subject=conv.subject,
                status=conv.status.value,
                unread_count=unread,
                last_message=last_msg[:200] if last_msg else None,
                last_message_at=last_msg_at,
                created_at=conv.created_at,
                updated_at=conv.updated_at,
            )
        )

    return WhatsAppInboxResponse(conversations=conversations, total=total)


@router.get("/inbox/{conversation_id}", response_model=WhatsAppConversationDetail)
async def get_whatsapp_conversation(
    conversation_id: uuid.UUID,
    db: AsyncSession = Depends(get_db),
    current_user: User = Depends(require_agent_or_admin),
    skip: int = Query(0, ge=0),
    limit: int = Query(100, ge=1, le=500),
):
    """
    Get a WhatsApp conversation with all its messages.
    Returns sender info (name + phone) for each message.
    """
    result = await db.execute(
        select(Conversation).where(
            Conversation.id == conversation_id,
            Conversation.is_deleted == False,
        )
    )
    conv = result.scalar_one_or_none()

    if not conv:
        raise HTTPException(status_code=404, detail="Conversation not found")
    if conv.channel != ChannelType.WHATSAPP:
        raise HTTPException(status_code=400, detail="Not a WhatsApp conversation")

    # Fetch contact info
    user_result = await db.execute(select(User).where(User.id == conv.user_id))
    contact = user_result.scalar_one_or_none()

    # Fetch messages with sender info
    msg_query = (
        select(Message)
        .where(Message.conversation_id == conversation_id)
        .order_by(Message.created_at.asc())
        .offset(skip)
        .limit(limit)
    )
    msg_result = await db.execute(msg_query)
    messages_raw = list(msg_result.scalars().all())

    # Count total
    total_count = (
        await db.execute(
            select(func.count(Message.id)).where(
                Message.conversation_id == conversation_id
            )
        )
    ).scalar() or 0

    # Build message items with sender details
    sender_cache: dict[uuid.UUID, User] = {}
    message_items = []
    for msg in messages_raw:
        if msg.sender_id not in sender_cache:
            sr = await db.execute(select(User).where(User.id == msg.sender_id))
            sender_cache[msg.sender_id] = sr.scalar_one_or_none()
        sender = sender_cache[msg.sender_id]
        message_items.append(
            WhatsAppMessageItem(
                id=msg.id,
                conversation_id=msg.conversation_id,
                sender_id=msg.sender_id,
                sender_name=sender.full_name if sender else None,
                sender_phone=sender.phone_number if sender else None,
                content=msg.content,
                is_read=msg.is_read,
                created_at=msg.created_at,
            )
        )

    return WhatsAppConversationDetail(
        id=conv.id,
        user_id=conv.user_id,
        contact_name=contact.full_name if contact else None,
        contact_phone=contact.phone_number if contact else None,
        subject=conv.subject,
        status=conv.status.value,
        messages=message_items,
        total_messages=total_count,
    )


@router.post("/inbox/{conversation_id}/read")
async def mark_messages_read(
    conversation_id: uuid.UUID,
    data: MarkReadRequest = MarkReadRequest(),
    db: AsyncSession = Depends(get_db),
    current_user: User = Depends(require_agent_or_admin),
):
    """
    Mark messages as read in a WhatsApp conversation.
    If message_ids is null/empty, marks ALL unread messages in the conversation.
    """
    # Verify conversation exists
    conv_result = await db.execute(
        select(Conversation).where(
            Conversation.id == conversation_id,
            Conversation.is_deleted == False,
        )
    )
    conv = conv_result.scalar_one_or_none()
    if not conv:
        raise HTTPException(status_code=404, detail="Conversation not found")

    # Build update query
    from sqlalchemy import update

    if data.message_ids:
        # Mark specific messages
        stmt = (
            update(Message)
            .where(
                Message.conversation_id == conversation_id,
                Message.id.in_(data.message_ids),
                Message.is_read == False,
            )
            .values(is_read=True)
            .execution_options(synchronize_session=False)
        )
    else:
        # Mark all unread in this conversation
        stmt = (
            update(Message)
            .where(
                Message.conversation_id == conversation_id,
                Message.is_read == False,
            )
            .values(is_read=True)
            .execution_options(synchronize_session=False)
        )

    update_result = await db.execute(stmt)
    await db.flush()

    return {
        "status": "ok",
        "conversation_id": str(conversation_id),
        "messages_marked_read": update_result.rowcount,
    }
