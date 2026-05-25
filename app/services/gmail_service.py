"""
Gmail service — OAuth2 flow, email fetching, sending replies, and ingestion.
Handles the full lifecycle: authorize → fetch → ingest → reply → ticket creation.
"""

import base64
import json
import logging
import os
import re
import uuid
from email.mime.text import MIMEText
from typing import Optional

import httpx
from google.auth.exceptions import RefreshError
from google.auth.transport.requests import Request as GoogleAuthRequest
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import Flow
from googleapiclient.discovery import build

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.services.auto_reply_policy import is_channel_auto_reply_enabled_sync
from app.services.auto_reply_guardrails import get_email_auto_reply_skip_reason
from app.db.models.gmail_credential import GmailCredential
from app.db.models.email import Email
from app.db.models.ticket import Ticket
from app.db.models.enums import EmailStatus, TicketPriority, ChannelType
from app.utils.mail_content import normalize_email_subject, normalize_mail_like_text

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
        self.created_ticket_ids: list[uuid.UUID] = []

    def get_all_active_credentials(self) -> list[GmailCredential]:
        result = self.db.execute(
            select(GmailCredential).where(GmailCredential.is_active == True)
        )
        return list(result.scalars().all())

    @staticmethod
    def _is_invalid_grant_error(exc: RefreshError) -> bool:
        """Detect revoked/expired OAuth grants that require user re-authentication."""
        details = str(exc).lower()
        return "invalid_grant" in details

    def _deactivate_credential_for_reauth(self, cred: GmailCredential, reason: str) -> None:
        """Disable unusable credentials so periodic sync does not fail repeatedly."""
        cred.is_active = False
        self.db.flush()
        logger.warning(
            "Disabled Gmail credential for user %s (%s). User must reconnect Gmail.",
            cred.user_id,
            reason,
        )

    @staticmethod
    def _extract_sender_display_name(sender: str) -> str | None:
        """Extract display name from sender header like 'Jane Doe <jane@example.com>'."""
        raw = (sender or "").strip()
        if not raw:
            return None

        m = re.match(r"\s*\"?([^\"<]+?)\"?\s*<[^>]+>", raw)
        if m:
            name = m.group(1).strip()
            if name and "@" not in name:
                return name

        # Fallback to local-part when only an email address is present.
        if "@" in raw:
            local = raw.split("@", 1)[0].strip().strip('"')
            local = re.sub(r"[._-]+", " ", local).strip()
            if local:
                return local.title()

        return None

    @staticmethod
    def _normalize_labels(labels: list[str]) -> list[str]:
        normalized: list[str] = []
        seen = set()
        for raw in labels:
            label = (raw or "").strip().lower()
            if not label:
                continue
            if len(label) > 64:
                label = label[:64]
            if label in seen:
                continue
            seen.add(label)
            normalized.append(label)
        return normalized

    @classmethod
    def _mailbox_state_from_gmail_labels(cls, label_ids: list[str]) -> tuple[bool, bool, list[str]]:
        labels = cls._normalize_labels(label_ids)
        is_read = "unread" not in labels
        is_starred = "starred" in labels
        visible_labels = [label for label in labels if label not in {"unread", "starred"}]
        return is_read, is_starred, visible_labels

    def _generate_auto_reply(self, subject: str, body: str, sender: str | None = None) -> str | None:
        """Generate an EMAIL-formatted response via internal RAG endpoint."""
        query = f"Subject: {subject}\n\nEmail body:\n{body}".strip()
        if not query:
            return None

        language = self._detect_language(query)

        base_url = (settings.INTERNAL_API_BASE_URL or os.getenv("INTERNAL_API_BASE_URL") or "http://api:8600").rstrip("/")
        url = f"{base_url}{settings.API_V1_PREFIX}/internal/rag/generate"
        headers = {"X-Service-Key": settings.INTERNAL_SERVICE_KEY}
        payload = {
            "query": query[:5000],
            "channel": "EMAIL",
            "tone": settings.AUTO_REPLY_TONE,
            "top_k": settings.AUTO_REPLY_TOP_K,
            "customer_name": self._extract_sender_display_name(sender or ""),
            "language": language,
        }

        try:
            with httpx.Client(timeout=45) as client:
                resp = client.post(url, json=payload, headers=headers)
            if resp.status_code != 200:
                logger.warning("Gmail auto-reply generation failed (%s): %s", resp.status_code, resp.text[:200])
                return self._fallback_email_reply(language)
            text = (resp.json().get("response") or "").strip()
            if text:
                return text
            return self._fallback_email_reply(language)
        except Exception:
            logger.exception("Gmail auto-reply generation request failed")
            return self._fallback_email_reply(language)

    @staticmethod
    def _detect_language(text: str) -> str:
        """Heuristic language detection for incoming email content."""
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

    @staticmethod
    def _fallback_email_reply(language: str) -> str:
        lang = (language or "en").lower()
        if lang == "fr":
            return (
                "Bonjour,\n\nMerci pour votre email. Nous avons bien recu votre message et "
                "notre equipe support est en train de traiter votre demande. Nous reviendrons "
                "vers vous tres bientot avec plus de details."
            )
        if lang == "ar":
            return (
                "Hello,\n\nThank you for your email. We received your message and our support team "
                "is reviewing your request now. We will follow up with more details shortly."
            )
        return (
            "Hello,\n\nThank you for your email. We received your message and "
            "our support team is currently reviewing your request. We will reply "
            "with more details as soon as possible."
        )

    def _contextual_fallback_reply(self, query: str) -> str | None:
        """Build a best-effort email answer from RAG search hits when LLM is unavailable."""
        base_url = (settings.INTERNAL_API_BASE_URL or os.getenv("INTERNAL_API_BASE_URL") or "http://api:8600").rstrip("/")
        url = f"{base_url}{settings.API_V1_PREFIX}/internal/rag/search"
        headers = {"X-Service-Key": settings.INTERNAL_SERVICE_KEY}
        payload = {
            "query": query[:2000],
            "top_k": 3,
            "include_content": True,
        }

        try:
            with httpx.Client(timeout=30) as client:
                resp = client.post(url, json=payload, headers=headers)
            if resp.status_code != 200:
                return None

            hits = (resp.json() or {}).get("hits", [])
            if not hits:
                return None

            return self._build_chatbot_email_answer(query=query, hits=hits)
        except Exception:
            logger.exception("Gmail contextual fallback search failed")
            return None

    def _build_chatbot_email_answer(self, query: str, hits: list[dict]) -> str | None:
        """Return concise direct-answer email text from retrieved hits."""
        content_blocks = [" ".join((h.get("chunk_content") or "").split()) for h in hits if h.get("chunk_content")]
        if not content_blocks:
            return None

        combined = " ".join(content_blocks)
        lowered_q = (query or "").lower()

        if "sms" in lowered_q and ("caract" in lowered_q or "character" in lowered_q or "max" in lowered_q):
            m = re.search(r"(\d{2,4})\s*(?:caract|character)", combined, flags=re.IGNORECASE)
            if m:
                value = m.group(1)
                return (
                    "Bonjour,\n\n"
                    f"Le nombre maximal est de {value} caracteres par SMS standard.\n"
                    "Au-dela, le message peut etre segmente en plusieurs SMS."
                )

        query_terms = [t for t in re.findall(r"[a-zA-Z0-9]+", lowered_q) if len(t) >= 4]
        query_terms = [t for t in query_terms if t not in {"comment", "bonjour", "hello", "please", "where", "what", "quel", "quelle", "help", "avec", "pour"}]

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
            best_sentence = content_blocks[0][:520]

        return (
            "Bonjour,\n\n"
            f"{best_sentence[:520]}\n\n"
            "Si vous voulez, je peux vous donner la reponse en etapes simples."
        )

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

        except RefreshError as exc:
            stats["errors"] += 1
            if self._is_invalid_grant_error(exc):
                self._deactivate_credential_for_reauth(cred, "invalid_grant")
            else:
                logger.exception(f"Failed to sync Gmail for user {cred.user_id}")

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
        raw_subject = headers.get("subject", "(No Subject)")
        sender = headers.get("from", "unknown@unknown.com")
        recipient = headers.get("to", cred.gmail_address)

        # Extract body
        raw_body = self._extract_body(msg["payload"])
        subject = normalize_email_subject(raw_subject)
        body = normalize_mail_like_text(raw_body) or "(empty)"

        # Build raw headers string
        raw_headers = json.dumps(
            {h["name"]: h["value"] for h in msg["payload"].get("headers", [])},
            indent=2,
        )

        # Gmail identifiers for threading
        gmail_msg_id = msg.get("id", "")
        gmail_thread_id = msg.get("threadId", "")
        label_ids = msg.get("labelIds", [])
        is_read, is_starred, labels = self._mailbox_state_from_gmail_labels(label_ids)

        # Check for duplicate by gmail_message_id
        existing = self.db.execute(
            select(Email).where(Email.gmail_message_id == gmail_msg_id)
        ).scalar_one_or_none()

        if existing:
            existing.is_read = is_read
            existing.is_starred = is_starred
            existing.labels = labels
            self.db.flush()
            logger.debug(f"Skipping duplicate Gmail message {gmail_msg_id}")
            try:
                service.users().messages().modify(
                    userId="me",
                    id=message_id,
                    body={"removeLabelIds": ["UNREAD"]},
                ).execute()
            except Exception:
                pass
            return

        # Ingest email
        email = Email(
            sender_address=sender[:320],
            recipient_address=recipient[:320],
            subject=subject[:500],
            body=body,
            raw_headers=raw_headers,
            gmail_message_id=gmail_msg_id,
            gmail_thread_id=gmail_thread_id,
            is_outbound=False,
            is_read=is_read,
            is_starred=is_starred,
            labels=labels,
            status=EmailStatus.RECEIVED,
        )
        self.db.add(email)
        self.db.flush()

        # Auto-create ticket from email
        ticket = Ticket(
            subject=f"[Gmail] {subject[:480]}",
            description=body,
            priority=TicketPriority.MEDIUM,
            channel_source=ChannelType.EMAIL,
            creator_id=cred.user_id,
            source_email_id=email.id,
        )
        self.db.add(ticket)
        email.status = EmailStatus.CONVERTED
        self.db.flush()
        self.created_ticket_ids.append(ticket.id)

        skip_reason = get_email_auto_reply_skip_reason(
            sender,
            subject,
            raw_headers=raw_headers,
            recipient=recipient,
            body=body,
        )
        email_auto_reply_enabled = (
            settings.EMAIL_AUTO_REPLY_ENABLED
            and is_channel_auto_reply_enabled_sync(self.db, "email", default=True)
        )

        # Auto-reply newly ingested messages via Gmail using RAG-generated content.
        if email_auto_reply_enabled and not skip_reason:
            generated = self._generate_auto_reply(subject=subject, body=body, sender=sender)
            if generated:
                try:
                    self.send_reply(
                        user_id=cred.user_id,
                        original_email_id=email.id,
                        reply_body=generated,
                    )
                    email.status = EmailStatus.REPLIED
                    self.db.flush()
                except Exception:
                    logger.exception("Failed to auto-reply for Gmail message %s", gmail_msg_id)
        elif email_auto_reply_enabled and skip_reason:
            logger.info(
                "Skipping Gmail auto-reply for message %s (%s): %s",
                gmail_msg_id,
                sender,
                skip_reason,
            )

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
        try:
            creds = credentials_from_model(cred)
            creds = refresh_credentials_if_needed(creds)

            if creds.token != cred.access_token:
                cred.access_token = creds.token
                self.db.flush()

            gmail_service = build("gmail", "v1", credentials=creds)

            # Always use the authenticated account address as sender identity.
            # A stale/mismatched stored gmail_address can trigger verification warnings.
            profile = gmail_service.users().getProfile(userId="me").execute()
            authenticated_email = (profile.get("emailAddress") or cred.gmail_address or "").strip().lower()
            if not authenticated_email:
                raise ValueError("Could not resolve authenticated Gmail sender address")

            if cred.gmail_address != authenticated_email:
                cred.gmail_address = authenticated_email
                self.db.flush()
        except RefreshError as exc:
            if self._is_invalid_grant_error(exc):
                self._deactivate_credential_for_reauth(cred, "invalid_grant")
                raise ValueError(
                    "Gmail authorization expired or was revoked. Please reconnect Gmail and try again."
                ) from exc
            raise

        # Build the MIME reply
        reply_subject = original.subject
        if not reply_subject.lower().startswith("re:"):
            reply_subject = f"Re: {reply_subject}"

        message = MIMEText(reply_body)
        message["to"] = original.sender_address
        message["from"] = authenticated_email
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
            sender_address=authenticated_email[:320],
            recipient_address=original.sender_address[:320],
            subject=reply_subject[:500],
            body=reply_body,
            gmail_message_id=sent_msg_id,
            gmail_thread_id=sent_thread_id,
            is_outbound=True,
            is_read=True,
            is_starred=False,
            labels=["sent"],
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

    def send_new_email(
        self,
        user_id: uuid.UUID,
        recipient: str,
        subject: str,
        body: str,
        labels: Optional[list[str]] = None,
    ) -> Email:
        """Send a brand-new outbound email and persist it in the local mailbox."""
        cred = self.db.execute(
            select(GmailCredential).where(
                GmailCredential.user_id == user_id,
                GmailCredential.is_active == True,
            )
        ).scalar_one_or_none()

        if not cred:
            raise ValueError("No active Gmail connection for this user")

        try:
            creds = credentials_from_model(cred)
            creds = refresh_credentials_if_needed(creds)

            if creds.token != cred.access_token:
                cred.access_token = creds.token
                self.db.flush()

            gmail_service = build("gmail", "v1", credentials=creds)

            profile = gmail_service.users().getProfile(userId="me").execute()
            authenticated_email = (profile.get("emailAddress") or cred.gmail_address or "").strip().lower()
            if not authenticated_email:
                raise ValueError("Could not resolve authenticated Gmail sender address")

            if cred.gmail_address != authenticated_email:
                cred.gmail_address = authenticated_email
                self.db.flush()
        except RefreshError as exc:
            if self._is_invalid_grant_error(exc):
                self._deactivate_credential_for_reauth(cred, "invalid_grant")
                raise ValueError(
                    "Gmail authorization expired or was revoked. Please reconnect Gmail and try again."
                ) from exc
            raise

        outbound_subject = (subject or "").strip() or "(No Subject)"
        message = MIMEText(body)
        message["to"] = recipient
        message["from"] = authenticated_email
        message["subject"] = outbound_subject

        raw = base64.urlsafe_b64encode(message.as_bytes()).decode("utf-8")
        sent = gmail_service.users().messages().send(
            userId="me",
            body={"raw": raw},
        ).execute()

        sent_msg_id = sent.get("id", "")
        sent_thread_id = sent.get("threadId", "")
        outbound_labels = self._normalize_labels(["sent", *(labels or [])])

        outbound_email = Email(
            sender_address=authenticated_email[:320],
            recipient_address=recipient[:320],
            subject=outbound_subject[:500],
            body=body,
            gmail_message_id=sent_msg_id,
            gmail_thread_id=sent_thread_id,
            is_outbound=True,
            is_read=True,
            is_starred=False,
            labels=outbound_labels,
            status=EmailStatus.REPLIED,
            replied_by_id=user_id,
        )
        self.db.add(outbound_email)
        self.db.flush()

        logger.info(f"New email sent to {recipient} — Gmail ID: {sent_msg_id}")
        return outbound_email
