"""
Celery tasks.
Uses synchronous DB sessions because Celery workers run in a sync context.
"""

import logging
import uuid
import asyncio
import re
import inspect
from datetime import datetime, timezone
from pathlib import Path
from types import SimpleNamespace

import httpx
from sqlalchemy import create_engine, select
from sqlalchemy.orm import Session, sessionmaker

from app.core.config import get_settings
from app.services.auto_reply_policy import (
    is_channel_auto_reply_enabled_sync,
    is_conversation_auto_reply_enabled_sync,
)
from app.services.auto_reply_guardrails import get_email_auto_reply_skip_reason
from app.utils.mail_content import normalize_email_subject, normalize_mail_like_text
from app.workers.celery_app import celery_app
from app.db.models.email import Email
from app.db.models.ticket import Ticket
from app.db.models.audit_log import AuditLog
from app.db.models.notification import Notification
from app.db.models.user import User
from app.db.models.gmail_credential import GmailCredential
from app.db.models.enums import EmailStatus, TicketPriority, ChannelType, AuditAction

logger = logging.getLogger(__name__)
settings = get_settings()

# Sync engine for Celery (Celery is not async)
_sync_url = settings.DATABASE_URL.replace("+asyncpg", "+psycopg2")
sync_engine = create_engine(_sync_url, pool_size=5, max_overflow=5)
SyncSession = sessionmaker(bind=sync_engine)


def _detect_language(text: str) -> str:
    """Heuristic language detection for auto-replies (fr/en/ar)."""
    sample = (text or "").strip().lower()
    if not sample:
        return "en"

    if re.search(r"[\u0600-\u06FF]", sample):
        return "ar"

    french_markers = (
        "bonjour", "merci", "caractere", "comment", "pourquoi", "quel", "quelle",
        "avec", "sans", "etre", "votre", "nous", "vous", "limite",
    )
    if any(m in sample for m in french_markers):
        return "fr"

    return "en"


def _fallback_auto_reply(channel: str, language: str = "en") -> str:
    """Safe fallback when upstream LLM generation is temporarily unavailable."""
    lang = (language or "en").lower()
    if channel == "WHATSAPP":
        if lang == "fr":
            return (
                "Merci pour votre message. Nous avons bien recu votre demande et "
                "notre equipe support la traite actuellement. Nous vous repondrons tres bientot."
            )
        return (
            "Thanks for your message. We received your request and our support team "
            "is reviewing it now. We will get back to you shortly."
        )

    if lang == "fr":
        return (
            "Bonjour,\n\nMerci pour votre message. Nous avons bien recu votre demande et "
            "notre equipe support est en train de la traiter. Nous reviendrons vers vous tres bientot."
        )

    return (
        "Hello,\n\nThank you for contacting support. We received your message and "
        "are currently reviewing your request. We will follow up with you as soon as possible."
    )


def _contextual_fallback_reply(query: str, channel: str) -> str | None:
    """Build a best-effort answer from retrieved RAG chunks when LLM generation is unavailable."""
    search_url = f"{settings.INTERNAL_API_BASE_URL.rstrip('/')}{settings.API_V1_PREFIX}/internal/rag/search"
    headers = {"X-Service-Key": settings.INTERNAL_SERVICE_KEY}
    payload = {
        "query": query[:2000],
        "top_k": 3,
        "include_content": True,
    }

    try:
        with httpx.Client(timeout=30) as client:
            resp = client.post(search_url, json=payload, headers=headers)
        if resp.status_code != 200:
            return None

        hits = (resp.json() or {}).get("hits", [])
        if not hits:
            return None

        best = _build_chatbot_fallback_answer(query=query, hits=hits, channel=channel)
        if not best:
            return None
        return best
    except Exception:
        logger.exception("Contextual fallback search failed")
        return None


def _build_chatbot_fallback_answer(query: str, hits: list[dict], channel: str) -> str | None:
    """Return a concise chatbot-like answer from retrieved hits."""
    content_blocks = [" ".join((h.get("chunk_content") or "").split()) for h in hits if h.get("chunk_content")]
    if not content_blocks:
        return None

    combined = " ".join(content_blocks)
    lowered_q = (query or "").lower()

    # Targeted extraction for common factual asks (e.g. SMS character limits).
    if "sms" in lowered_q and ("caract" in lowered_q or "character" in lowered_q or "max" in lowered_q):
        m = re.search(r"(\d{2,4})\s*(?:caract|character)", combined, flags=re.IGNORECASE)
        if m:
            value = m.group(1)
            if channel == "WHATSAPP":
                return f"Le nombre maximal est de {value} caracteres par SMS standard."
            return (
                "Bonjour,\n\n"
                f"Le nombre maximal est de {value} caracteres par SMS standard.\n"
                "Si le message depasse cette limite, il peut etre segmente en plusieurs SMS."
            )

    # Generic extraction: pick the most query-relevant sentence from hits.
    query_terms = [t for t in re.findall(r"[a-zA-Z0-9]+", lowered_q) if len(t) >= 4]
    query_terms = [t for t in query_terms if t not in {"comment", "please", "bonjour", "hello", "help", "avec", "pour", "where", "what", "quel", "quelle", "quand"}]

    sentences = re.split(r"(?<=[.!?])\s+", combined)
    best_sentence = ""
    best_score = -1
    for sentence in sentences:
        s = sentence.strip()
        if len(s) < 20:
            continue
        score = sum(1 for term in query_terms if term in s.lower())
        if score > best_score:
            best_score = score
            best_sentence = s

    if not best_sentence:
        best_sentence = content_blocks[0][:280]

    if channel == "WHATSAPP":
        return best_sentence[:420]

    return (
        "Bonjour,\n\n"
        f"{best_sentence[:520]}\n\n"
        "Si vous voulez, je peux vous donner la reponse en etapes simples."
    )


def _internal_generate_reply(query: str, channel: str) -> str | None:
    """Generate a channel-formatted RAG reply through the internal API endpoint."""
    if not query.strip():
        return None

    language = _detect_language(query)

    url = f"{settings.INTERNAL_API_BASE_URL.rstrip('/')}{settings.API_V1_PREFIX}/internal/rag/generate"
    payload = {
        "query": query[:5000],
        "channel": channel,
        "tone": settings.AUTO_REPLY_TONE,
        "top_k": settings.AUTO_REPLY_TOP_K,
        "language": language,
    }
    headers = {"X-Service-Key": settings.INTERNAL_SERVICE_KEY}

    try:
        with httpx.Client(timeout=45) as client:
            resp = client.post(url, json=payload, headers=headers)
        if resp.status_code != 200:
            logger.warning("Auto-reply generation failed (%s): %s", resp.status_code, resp.text[:200])
            return _fallback_auto_reply(channel, language=language)
        data = resp.json()
        response_text = (data.get("response") or "").strip()
        if response_text:
            return response_text
        return _fallback_auto_reply(channel, language=language)
    except Exception:
        logger.exception("Auto-reply generation request failed")
        return _fallback_auto_reply(channel, language=language)


def _get_or_create_system_user(db: Session) -> User:
    """Create a stable system user for channel-ingested tickets if needed."""
    from app.db.models.enums import UserRole, UserStatus

    system_email = "system.ingest@local"
    user = db.execute(select(User).where(User.email == system_email)).scalar_one_or_none()
    if user:
        return user

    user = User(
        email=system_email,
        full_name="System Ingest",
        hashed_password="!no_login",
        role=UserRole.CLIENT,
        status=UserStatus.ACTIVE,
    )
    db.add(user)
    db.flush()
    return user


@celery_app.task(name="app.workers.tasks.process_email_task", bind=True, max_retries=3)
def process_email_task(self, email_id: str):
    """
    Async processing: convert an ingested email into a ticket.
    """
    logger.info(f"Processing email {email_id}")
    with SyncSession() as db:
        try:
            email = db.execute(
                select(Email).where(Email.id == uuid.UUID(email_id))
            ).scalar_one_or_none()

            if not email:
                logger.error(f"Email {email_id} not found")
                return {"status": "error", "detail": "Email not found"}

            email.status = EmailStatus.PROCESSING
            db.flush()

            # Resolve ticket owner from mailbox credential when possible.
            recipient = (email.recipient_address or "").strip().lower()
            cred = None
            if recipient:
                cred = db.execute(
                    select(GmailCredential).where(
                        GmailCredential.gmail_address == recipient,
                        GmailCredential.is_active == True,
                    )
                ).scalar_one_or_none()

            creator_id = cred.user_id if cred else _get_or_create_system_user(db).id

            cleaned_subject = normalize_email_subject(email.subject)
            cleaned_body = normalize_mail_like_text(email.body) or "(empty)"
            if cleaned_subject != (email.subject or ""):
                email.subject = cleaned_subject
                db.flush()
            if cleaned_body != (email.body or ""):
                email.body = cleaned_body
                db.flush()

            # Create a ticket from the email
            ticket = Ticket(
                subject=f"[Email] {cleaned_subject}",
                description=cleaned_body,
                priority=TicketPriority.MEDIUM,
                channel_source=ChannelType.EMAIL,
                creator_id=creator_id,
                source_email_id=email.id,
            )
            db.add(ticket)

            skip_reason = get_email_auto_reply_skip_reason(
                email.sender_address,
                cleaned_subject,
                raw_headers=email.raw_headers,
                recipient=email.recipient_address,
                body=cleaned_body,
            )
            email_auto_reply_enabled = (
                settings.EMAIL_AUTO_REPLY_ENABLED
                and is_channel_auto_reply_enabled_sync(db, "email", default=True)
            )

            # Optional auto-reply via Gmail if mailbox credential is known.
            if email_auto_reply_enabled and cred and not skip_reason:
                from app.services.gmail_service import GmailSyncService

                generated = _internal_generate_reply(
                    query=f"Subject: {cleaned_subject}\n\nEmail body:\n{cleaned_body}",
                    channel="EMAIL",
                )
                if generated:
                    try:
                        GmailSyncService(db).send_reply(
                            user_id=cred.user_id,
                            original_email_id=email.id,
                            reply_body=generated,
                        )
                        email.status = EmailStatus.REPLIED
                    except Exception:
                        logger.exception("Failed to auto-reply to email %s", email.id)
            elif email_auto_reply_enabled and cred and skip_reason:
                logger.info(
                    "Skipping email auto-reply for %s (%s): %s",
                    email.id,
                    email.sender_address,
                    skip_reason,
                )

            if email.status != EmailStatus.REPLIED:
                email.status = EmailStatus.CONVERTED
            db.commit()

            logger.info(f"Email {email_id} converted to ticket")
            return {"status": "success", "email_id": email_id}

        except Exception as exc:
            db.rollback()
            email_obj = db.execute(
                select(Email).where(Email.id == uuid.UUID(email_id))
            ).scalar_one_or_none()
            if email_obj:
                email_obj.status = EmailStatus.FAILED
                db.commit()
            logger.exception(f"Failed to process email {email_id}")
            raise self.retry(exc=exc, countdown=60)


@celery_app.task(name="app.workers.tasks.log_action_task")
def log_action_task(
    action: str,
    resource_type: str,
    resource_id: str = None,
    user_id: str = None,
    description: str = None,
    meta: dict = None,
    trace_id: str = None,
    ip_address: str = None,
):
    """Background audit logging — fire-and-forget."""
    with SyncSession() as db:
        try:
            entry = AuditLog(
                action=AuditAction(action),
                resource_type=resource_type,
                resource_id=resource_id,
                user_id=uuid.UUID(user_id) if user_id else None,
                description=description,
                meta=meta,
                trace_id=trace_id,
                ip_address=ip_address,
            )
            db.add(entry)
            db.commit()
        except Exception:
            db.rollback()
            logger.exception("Failed to write audit log")


@celery_app.task(name="app.workers.tasks.send_notification_placeholder")
def send_notification_placeholder(
    user_id: str,
    message: str,
    title: str = "System notification",
    notification_type: str = "system",
):
    """
    Persist an in-app notification from the worker runtime.
    """
    with SyncSession() as db:
        try:
            notification = Notification(
                user_id=uuid.UUID(user_id),
                type=notification_type,
                title=title,
                body=message,
            )
            db.add(notification)
            db.commit()
            return {"status": "created", "user_id": user_id, "notification_id": str(notification.id)}
        except Exception:
            db.rollback()
            logger.exception("Failed to persist worker notification for %s", user_id)
            return {"status": "error", "user_id": user_id}


# ── Gmail sync tasks ────────────────────────────────────

@celery_app.task(name="app.workers.tasks.sync_gmail_for_user_task", bind=True, max_retries=2)
def sync_gmail_for_user_task(self, user_id: str):
    """Sync Gmail emails for a specific user."""
    from app.db.models.gmail_credential import GmailCredential
    from app.services.gmail_service import GmailSyncService

    logger.info(f"Syncing Gmail for user {user_id}")
    with SyncSession() as db:
        try:
            cred = db.execute(
                select(GmailCredential).where(
                    GmailCredential.user_id == uuid.UUID(user_id),
                    GmailCredential.is_active == True,
                )
            ).scalar_one_or_none()

            if not cred:
                logger.warning(f"No active Gmail credential for user {user_id}")
                return {"status": "skipped", "reason": "no_credential"}

            sync_svc = GmailSyncService(db)
            stats = sync_svc.sync_emails_for_credential(cred)
            db.commit()

            logger.info(f"Gmail sync for {user_id}: {stats}")
            return {"status": "success", **stats}

        except Exception as exc:
            db.rollback()
            logger.exception(f"Gmail sync failed for user {user_id}")
            raise self.retry(exc=exc, countdown=120)


@celery_app.task(name="app.workers.tasks.send_email_reply_task", bind=True, max_retries=2)
def send_email_reply_task(self, user_id: str, original_email_id: str, reply_body: str):
    """
    Send a reply to an ingested email via the user's connected Gmail.
    Runs synchronously in the Celery worker.
    """
    from app.services.gmail_service import GmailSyncService

    logger.info(f"Sending reply to email {original_email_id} for user {user_id}")
    with SyncSession() as db:
        try:
            sync_svc = GmailSyncService(db)
            reply_email = sync_svc.send_reply(
                user_id=uuid.UUID(user_id),
                original_email_id=uuid.UUID(original_email_id),
                reply_body=reply_body,
            )
            db.commit()

            logger.info(
                f"Reply sent: {reply_email.id} → {reply_email.recipient_address} "
                f"(Gmail ID: {reply_email.gmail_message_id})"
            )
            return {
                "status": "sent",
                "reply_email_id": str(reply_email.id),
                "gmail_message_id": reply_email.gmail_message_id,
            }

        except ValueError as exc:
            db.rollback()
            logger.error(f"Reply failed (validation): {exc}")
            return {"status": "error", "detail": str(exc)}

        except Exception as exc:
            db.rollback()
            logger.exception(f"Reply failed for email {original_email_id}")
            raise self.retry(exc=exc, countdown=30)


@celery_app.task(name="app.workers.tasks.send_new_email_task", bind=True, max_retries=2)
def send_new_email_task(
    self,
    user_id: str,
    recipient: str,
    subject: str,
    body: str,
    labels: list[str] | None = None,
):
    """Send a brand-new outbound email via the user's connected Gmail account."""
    from app.services.gmail_service import GmailSyncService

    logger.info(f"Sending new outbound email to {recipient} for user {user_id}")
    with SyncSession() as db:
        try:
            sync_svc = GmailSyncService(db)
            outbound = sync_svc.send_new_email(
                user_id=uuid.UUID(user_id),
                recipient=recipient,
                subject=subject,
                body=body,
                labels=labels or [],
            )
            db.commit()

            logger.info(
                f"New outbound email sent: {outbound.id} → {outbound.recipient_address} "
                f"(Gmail ID: {outbound.gmail_message_id})"
            )
            return {
                "status": "sent",
                "email_id": str(outbound.id),
                "gmail_message_id": outbound.gmail_message_id,
            }

        except ValueError as exc:
            db.rollback()
            logger.error(f"New outbound email failed (validation): {exc}")
            return {"status": "error", "detail": str(exc)}

        except Exception as exc:
            db.rollback()
            logger.exception(f"New outbound email failed for user {user_id}")
            raise self.retry(exc=exc, countdown=30)


@celery_app.task(name="app.workers.tasks.sync_all_gmail_accounts")
def sync_all_gmail_accounts():
    """
    Periodic task: sync all active Gmail accounts.
    Called by Celery Beat on the configured interval.
    """
    from app.db.models.gmail_credential import GmailCredential
    from app.services.gmail_service import GmailSyncService

    logger.info("Starting periodic Gmail sync for all accounts")
    with SyncSession() as db:
        try:
            sync_svc = GmailSyncService(db)
            credentials = sync_svc.get_all_active_credentials()

            total_stats = {"accounts": 0, "fetched": 0, "ingested": 0, "errors": 0}

            for cred in credentials:
                stats = sync_svc.sync_emails_for_credential(cred)
                total_stats["accounts"] += 1
                total_stats["fetched"] += stats["fetched"]
                total_stats["ingested"] += stats["ingested"]
                total_stats["errors"] += stats["errors"]

            db.commit()
            logger.info(f"Periodic Gmail sync complete: {total_stats}")
            return total_stats

        except Exception:
            db.rollback()
            logger.exception("Periodic Gmail sync failed")
            return {"status": "error"}


# ── WhatsApp tasks ────────────────────────────────────────

@celery_app.task(name="app.workers.tasks.process_whatsapp_incoming_task", bind=True, max_retries=3)
def process_whatsapp_incoming_task(
    self,
    from_number: str,
    body: str,
    sender_name: str = "Unknown",
    message_id: str | None = None,
    reply_target: str | None = None,
):
    """
    Process an incoming WhatsApp message: find-or-create User + Conversation,
    then add a Message — just like chat.
    Fired by the webhook endpoints.
    """
    from app.services.whatsapp_service import (
        WhatsAppSyncService,
        get_whatsapp_provider,
        normalize_whatsapp_number,
    )

    normalized_from = normalize_whatsapp_number(from_number)
    if not normalized_from:
        logger.warning("Skipping WhatsApp incoming with invalid sender: %s", from_number)
        return {"status": "skipped", "reason": "invalid_sender", "from": from_number}

    logger.info(f"Processing incoming WhatsApp message from {normalized_from}")
    with SyncSession() as db:
        try:
            svc = WhatsAppSyncService(db)
            conv, msg = svc.create_conversation_from_message(
                from_number=normalized_from,
                body=body,
                sender_name=sender_name,
                message_id=message_id,
            )
            whatsapp_auto_reply_enabled = (
                settings.WHATSAPP_AUTO_REPLY_ENABLED
                and is_channel_auto_reply_enabled_sync(db, "whatsapp", default=True)
                and is_conversation_auto_reply_enabled_sync(db, conv.id, default=True)
            )

            # Auto-reply through RAG + configured WhatsApp provider.
            if whatsapp_auto_reply_enabled:
                generated = _internal_generate_reply(
                    query=body,
                    channel="WHATSAPP",
                )
                if generated:
                    provider = get_whatsapp_provider()
                    target = reply_target or normalized_from
                    try:
                        send_result = asyncio.run(provider.send_message(target, generated))
                    except Exception:
                        logger.exception("Failed to dispatch WhatsApp auto-reply to %s", target)
                        send_result = {"success": False, "error": "dispatch_failed"}

                    if send_result.get("success"):
                        svc.record_outbound_message(
                            to_number=normalized_from,
                            body=generated,
                            wa_message_id=send_result.get("message_id"),
                            conversation_id=conv.id,
                        )
                    else:
                        logger.warning(
                            "WhatsApp auto-reply send failed for %s: %s",
                            normalized_from,
                            send_result.get("error", "unknown_error"),
                        )

            db.commit()

            logger.info(
                f"WhatsApp message processed: {normalized_from} → "
                f"conversation={conv.id}, message={msg.id}"
            )
            return {
                "status": "processed",
                "conversation_id": str(conv.id),
                "message_id": str(msg.id),
            }

        except Exception as exc:
            db.rollback()
            logger.exception(f"Failed to process WhatsApp message from {normalized_from}")
            raise self.retry(exc=exc, countdown=30)


@celery_app.task(name="app.workers.tasks.record_whatsapp_outbound_task")
def record_whatsapp_outbound_task(
    to_number: str,
    body: str,
    wa_message_id: str | None = None,
    user_id: str | None = None,
    conversation_id: str | None = None,
):
    """
    Record an outbound WhatsApp message as a Message in the conversation.
    Fired after a successful send_message().
    """
    from app.services.whatsapp_service import WhatsAppSyncService

    logger.info(f"Recording outbound WhatsApp message to {to_number}")
    with SyncSession() as db:
        try:
            svc = WhatsAppSyncService(db)
            msg = svc.record_outbound_message(
                to_number=to_number,
                body=body,
                wa_message_id=wa_message_id,
                user_id=uuid.UUID(user_id) if user_id else None,
                conversation_id=uuid.UUID(conversation_id) if conversation_id else None,
            )
            db.commit()
            if msg:
                logger.info(f"Outbound recorded: msg={msg.id} → {to_number}")
                return {"status": "recorded", "message_id": str(msg.id)}
            return {"status": "no_conversation_found"}

        except Exception:
            db.rollback()
            logger.exception(f"Failed to record outbound to {to_number}")
            return {"status": "error"}


def _serialize_task_result(payload):
    if hasattr(payload, "model_dump"):
        return payload.model_dump(mode="json")
    if isinstance(payload, dict):
        return payload
    return payload


async def _run_conversation_summary_job(
    *,
    conversation_id: str,
    max_messages: int,
):
    from app.api.routes.conversations import summarize_conversation
    from app.db.session import async_session_factory

    async with async_session_factory() as db:
        try:
            result = await summarize_conversation(
                conversation_id=uuid.UUID(conversation_id),
                db=db,
                _=SimpleNamespace(id=uuid.uuid4()),
                max_messages=max_messages,
            )
            await db.commit()
        except Exception:
            await db.rollback()
            raise

    return {
        "job_type": "summary",
        "conversation_id": conversation_id,
        "result": _serialize_task_result(result),
    }


@celery_app.task(name="app.workers.tasks.generate_conversation_summary_job_task")
def generate_conversation_summary_job_task(
    conversation_id: str,
    max_messages: int = 120,
):
    logger.info(
        "Starting conversation summary job conversation_id=%s max_messages=%s",
        conversation_id,
        max_messages,
    )
    return asyncio.run(
        _run_conversation_summary_job(
            conversation_id=conversation_id,
            max_messages=max_messages,
        )
    )


async def _run_conversation_assisted_draft_job(
    *,
    conversation_id: str,
    requested_by_user_id: str,
):
    from app.api.routes.conversations import generate_assisted_draft
    from app.db.session import async_session_factory

    async with async_session_factory() as db:
        try:
            result = await generate_assisted_draft(
                conversation_id=uuid.UUID(conversation_id),
                db=db,
                current_user=SimpleNamespace(id=uuid.UUID(requested_by_user_id)),
            )
            await db.commit()
        except Exception:
            await db.rollback()
            raise

    return {
        "job_type": "assisted_draft",
        "conversation_id": conversation_id,
        "result": _serialize_task_result(result),
    }


@celery_app.task(name="app.workers.tasks.generate_conversation_assisted_draft_job_task")
def generate_conversation_assisted_draft_job_task(
    conversation_id: str,
    requested_by_user_id: str,
):
    logger.info(
        "Starting conversation assisted draft job conversation_id=%s requested_by=%s",
        conversation_id,
        requested_by_user_id,
    )
    return asyncio.run(
        _run_conversation_assisted_draft_job(
            conversation_id=conversation_id,
            requested_by_user_id=requested_by_user_id,
        )
    )


def get_worker_tasks_runtime_marker() -> str:
    """
    Return a concise marker for the worker task module currently loaded.
    This makes stale Celery containers obvious in startup logs.
    """
    file_path = Path(__file__).resolve()
    stat = file_path.stat()
    modified_at = datetime.fromtimestamp(stat.st_mtime, timezone.utc).isoformat()
    try:
        supports_reply_target = "reply_target" in inspect.signature(
            process_whatsapp_incoming_task.run
        ).parameters
    except Exception:
        supports_reply_target = "unknown"

    return (
        f"{file_path} "
        f"mtime={modified_at} "
        f"size={stat.st_size} "
        f"reply_target_supported={supports_reply_target}"
    )
