"""
Gmail service — OAuth2 flow, email fetching, sending replies, and ingestion.
Handles the full lifecycle: authorize → fetch → ingest → reply → ticket creation.
"""

import base64
import json
import logging
import uuid
from email.mime.text import MIMEText
from typing import Optional

from google.auth.transport.requests import Request as GoogleAuthRequest
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import Flow
from googleapiclient.discovery import build

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.db.models.gmail_credential import GmailCredential
from app.db.models.email import Email
from app.db.models.ticket import Ticket
from app.db.models.enums import EmailStatus, TicketPriority, ChannelType

logger = logging.getLogger(__name__)
settings = get_settings()


# ── OAuth2 Flow ──────────────────────────────────────────

def build_oauth_flow(state: Optional[str] = None) -> Flow:
    """Build a Google OAuth2 flow from app config."""
    client_config = {
        "web": {
            "client_id": settings.GMAIL_CLIENT_ID,
            "client_secret": settings.GMAIL_CLIENT_SECRET,
            "auth_uri": "https://accounts.google.com/o/oauth2/auth",
            "token_uri": "https://oauth2.googleapis.com/token",
            "redirect_uris": [settings.GMAIL_REDIRECT_URI],
        }
    }
    flow = Flow.from_client_config(
        client_config,
        scopes=settings.GMAIL_SCOPES,
        state=state,
    )
    flow.redirect_uri = settings.GMAIL_REDIRECT_URI
    return flow


def get_authorization_url() -> tuple[str, str]:
    """Generate the Google OAuth2 consent URL."""
    flow = build_oauth_flow()
    url, state = flow.authorization_url(
        access_type="offline",
        include_granted_scopes="true",
        prompt="consent",
    )
    return url, state


def exchange_code_for_tokens(code: str, state: str) -> Credentials:
    """Exchange the authorization code for OAuth2 credentials."""
    flow = build_oauth_flow(state=state)
    flow.fetch_token(code=code)
    return flow.credentials


# ── Credentials helpers ──────────────────────────────────

def credentials_from_model(cred: GmailCredential) -> Credentials:
    """Reconstruct google Credentials from our DB model."""
    return Credentials(
        token=cred.access_token,
        refresh_token=cred.refresh_token,
        token_uri=cred.token_uri,
        client_id=settings.GMAIL_CLIENT_ID,
        client_secret=settings.GMAIL_CLIENT_SECRET,
        scopes=json.loads(cred.scopes),
    )


def refresh_credentials_if_needed(creds: Credentials) -> Credentials:
    """Refresh expired credentials using the refresh token."""
    if creds.expired and creds.refresh_token:
        creds.refresh(GoogleAuthRequest())
    return creds


# ── Async service (for API routes) ───────────────────────

class GmailService:
    """Async service for Gmail credential management."""

    def __init__(self, db: AsyncSession):
        self.db = db

    async def get_credential(self, user_id: uuid.UUID) -> Optional[GmailCredential]:
        result = await self.db.execute(
            select(GmailCredential).where(GmailCredential.user_id == user_id)
        )
        return result.scalar_one_or_none()

    async def save_credential(
        self,
        user_id: uuid.UUID,
        gmail_address: str,
        credentials: Credentials,
    ) -> GmailCredential:
        """Save or update OAuth2 tokens for a user."""
        existing = await self.get_credential(user_id)

        if existing:
            existing.gmail_address = gmail_address
            existing.access_token = credentials.token
            existing.refresh_token = credentials.refresh_token or existing.refresh_token
            existing.token_uri = credentials.token_uri
            existing.scopes = json.dumps(list(credentials.scopes or []))
            existing.is_active = True
            await self.db.flush()
            await self.db.refresh(existing)
            return existing

        cred = GmailCredential(
            user_id=user_id,
            gmail_address=gmail_address,
            access_token=credentials.token,
            refresh_token=credentials.refresh_token,
            token_uri=credentials.token_uri,
            scopes=json.dumps(list(credentials.scopes or [])),
            is_active=True,
        )
        self.db.add(cred)
        await self.db.flush()
        await self.db.refresh(cred)
        return cred

    async def disconnect(self, user_id: uuid.UUID) -> bool:
        cred = await self.get_credential(user_id)
        if not cred:
            return False
        cred.is_active = False
        await self.db.flush()
        return True


# ── Sync service (for Celery tasks) ──────────────────────

class GmailSyncService:
    """
    Synchronous service for Celery workers.
    Fetches unread emails from Gmail and ingests them into the platform.
    """

    def __init__(self, db: Session):
        self.db = db

    def get_all_active_credentials(self) -> list[GmailCredential]:
        result = self.db.execute(
            select(GmailCredential).where(GmailCredential.is_active == True)
        )
        return list(result.scalars().all())

    def sync_emails_for_credential(self, cred: GmailCredential) -> dict:
        """Fetch new unread emails from Gmail and ingest them."""
        stats = {"fetched": 0, "ingested": 0, "errors": 0}

        try:
            creds = credentials_from_model(cred)
            creds = refresh_credentials_if_needed(creds)

            # Persist refreshed token
            if creds.token != cred.access_token:
                cred.access_token = creds.token
                self.db.flush()

            service = build("gmail", "v1", credentials=creds)

            # Fetch unread messages
            results = service.users().messages().list(
                userId="me",
                q="is:unread",
                maxResults=20,
            ).execute()

            messages = results.get("messages", [])
            stats["fetched"] = len(messages)

            for msg_ref in messages:
                try:
                    self._process_message(service, cred, msg_ref["id"])
                    stats["ingested"] += 1
                except Exception:
                    stats["errors"] += 1
                    logger.exception(f"Failed to process Gmail message {msg_ref['id']}")

            # Update history ID for incremental sync
            profile = service.users().getProfile(userId="me").execute()
            cred.last_history_id = str(profile.get("historyId", ""))
            self.db.flush()

        except Exception:
            stats["errors"] += 1
            logger.exception(f"Failed to sync Gmail for user {cred.user_id}")

        return stats

    def _process_message(self, service, cred: GmailCredential, message_id: str):
        """Fetch a single message, ingest it, and mark as read."""
        msg = service.users().messages().get(
            userId="me",
            id=message_id,
            format="full",
        ).execute()

        headers = {h["name"].lower(): h["value"] for h in msg["payload"].get("headers", [])}
        subject = headers.get("subject", "(No Subject)")
        sender = headers.get("from", "unknown@unknown.com")
        recipient = headers.get("to", cred.gmail_address)

        # Extract body
        body = self._extract_body(msg["payload"])

        # Build raw headers string
        raw_headers = json.dumps(
            {h["name"]: h["value"] for h in msg["payload"].get("headers", [])},
            indent=2,
        )

        # Gmail identifiers for threading
        gmail_msg_id = msg.get("id", "")
        gmail_thread_id = msg.get("threadId", "")

        # Check for duplicate by gmail_message_id
        existing = self.db.execute(
            select(Email).where(Email.gmail_message_id == gmail_msg_id)
        ).scalar_one_or_none()

        if existing:
            logger.debug(f"Skipping duplicate Gmail message {gmail_msg_id}")
            return

        # Ingest email
        email = Email(
            sender_address=sender[:320],
            recipient_address=recipient[:320],
            subject=subject[:500],
            body=body or "(empty)",
            raw_headers=raw_headers,
            gmail_message_id=gmail_msg_id,
            gmail_thread_id=gmail_thread_id,
            is_outbound=False,
            status=EmailStatus.RECEIVED,
        )
        self.db.add(email)
        self.db.flush()

        # Auto-create ticket from email
        ticket = Ticket(
            subject=f"[Gmail] {subject[:480]}",
            description=body or "(empty)",
            priority=TicketPriority.MEDIUM,
            channel_source=ChannelType.EMAIL,
            creator_id=cred.user_id,
            source_email_id=email.id,
        )
        self.db.add(ticket)
        email.status = EmailStatus.CONVERTED
        self.db.flush()

        # Mark as read in Gmail
        service.users().messages().modify(
            userId="me",
            id=message_id,
            body={"removeLabelIds": ["UNREAD"]},
        ).execute()

        logger.info(f"Ingested Gmail message: {subject[:80]}")

    @staticmethod
    def _extract_body(payload: dict) -> str:
        """Recursively extract the text/plain body from a Gmail message payload."""
        if payload.get("mimeType") == "text/plain" and payload.get("body", {}).get("data"):
            return base64.urlsafe_b64decode(payload["body"]["data"]).decode("utf-8", errors="replace")

        # Multipart — recurse into parts
        for part in payload.get("parts", []):
            body = GmailSyncService._extract_body(part)
            if body:
                return body

        # Fallback: try HTML
        if payload.get("mimeType") == "text/html" and payload.get("body", {}).get("data"):
            return base64.urlsafe_b64decode(payload["body"]["data"]).decode("utf-8", errors="replace")

        return ""

    # ── Reply / Send ────────────────────────────────────

    def send_reply(
        self,
        user_id: uuid.UUID,
        original_email_id: uuid.UUID,
        reply_body: str,
    ) -> Email:
        """
        Send a reply to an ingested email via Gmail API.
        Creates an outbound Email record and sends through the user's Gmail.
        """
        # Load the original email
        original = self.db.execute(
            select(Email).where(Email.id == original_email_id)
        ).scalar_one_or_none()

        if not original:
            raise ValueError(f"Original email {original_email_id} not found")

        # Load user's Gmail credential
        cred = self.db.execute(
            select(GmailCredential).where(
                GmailCredential.user_id == user_id,
                GmailCredential.is_active == True,
            )
        ).scalar_one_or_none()

        if not cred:
            raise ValueError("No active Gmail connection for this user")

        # Build Google credentials and Gmail service
        creds = credentials_from_model(cred)
        creds = refresh_credentials_if_needed(creds)

        if creds.token != cred.access_token:
            cred.access_token = creds.token
            self.db.flush()

        gmail_service = build("gmail", "v1", credentials=creds)

        # Build the MIME reply
        reply_subject = original.subject
        if not reply_subject.lower().startswith("re:"):
            reply_subject = f"Re: {reply_subject}"

        message = MIMEText(reply_body)
        message["to"] = original.sender_address
        message["from"] = cred.gmail_address
        message["subject"] = reply_subject

        # Thread the reply: set In-Reply-To and References headers
        if original.raw_headers:
            try:
                orig_headers = json.loads(original.raw_headers)
                rfc_message_id = orig_headers.get("Message-ID") or orig_headers.get("Message-Id", "")
                if rfc_message_id:
                    message["In-Reply-To"] = rfc_message_id
                    message["References"] = rfc_message_id
            except (json.JSONDecodeError, AttributeError):
                pass

        # Encode and send via Gmail API
        raw = base64.urlsafe_b64encode(message.as_bytes()).decode("utf-8")
        send_body = {"raw": raw}

        # Thread the reply in Gmail using threadId
        if original.gmail_thread_id:
            send_body["threadId"] = original.gmail_thread_id

        sent = gmail_service.users().messages().send(
            userId="me",
            body=send_body,
        ).execute()

        sent_msg_id = sent.get("id", "")
        sent_thread_id = sent.get("threadId", original.gmail_thread_id)

        # Record the outbound email
        reply_email = Email(
            sender_address=cred.gmail_address[:320],
            recipient_address=original.sender_address[:320],
            subject=reply_subject[:500],
            body=reply_body,
            gmail_message_id=sent_msg_id,
            gmail_thread_id=sent_thread_id,
            is_outbound=True,
            in_reply_to_id=original.id,
            replied_by_id=user_id,
            status=EmailStatus.REPLIED,
        )
        self.db.add(reply_email)

        # Update the original email status
        if original.status != EmailStatus.REPLIED:
            original.status = EmailStatus.REPLIED

        self.db.flush()

        logger.info(f"Reply sent to {original.sender_address} — Gmail ID: {sent_msg_id}")
        return reply_email
