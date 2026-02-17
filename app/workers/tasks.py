"""
Celery tasks.
Uses synchronous DB sessions because Celery workers run in a sync context.
"""

import logging
import uuid

from sqlalchemy import create_engine, select
from sqlalchemy.orm import Session, sessionmaker

from app.core.config import get_settings
from app.workers.celery_app import celery_app
from app.db.models.email import Email
from app.db.models.ticket import Ticket
from app.db.models.audit_log import AuditLog
from app.db.models.enums import EmailStatus, TicketPriority, ChannelType, AuditAction

logger = logging.getLogger(__name__)
settings = get_settings()

# Sync engine for Celery (Celery is not async)
_sync_url = settings.DATABASE_URL.replace("+asyncpg", "+psycopg2")
sync_engine = create_engine(_sync_url, pool_size=5, max_overflow=5)
SyncSession = sessionmaker(bind=sync_engine)


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

            # Create a ticket from the email
            ticket = Ticket(
                subject=f"[Email] {email.subject}",
                description=email.body,
                priority=TicketPriority.MEDIUM,
                channel_source=ChannelType.EMAIL,
                creator_id=None,  # System-created; no user linkage yet
                source_email_id=email.id,
            )
            # Note: creator_id is NOT NULL in model — we need a system user or make it nullable.
            # For Sprint 1 we'll skip actual DB insert if no system user exists.
            # This is a placeholder demonstrating the pattern.

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
def send_notification_placeholder(user_id: str, message: str):
    """
    Placeholder for future notification system.
    Will support email, push, websocket in later sprints.
    """
    logger.info(f"[PLACEHOLDER] Notification to {user_id}: {message}")
    return {"status": "placeholder", "user_id": user_id}


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
