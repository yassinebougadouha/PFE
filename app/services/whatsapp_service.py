"""
WhatsApp service — dual-provider: Meta Cloud API (official) + Web bridge (unofficial).

Provider selection is controlled by WHATSAPP_PROVIDER env var:
    - "meta"   → Official Meta Cloud API (production-ready, needs Business account)
    - "bridge" → Unofficial whatsapp-web.js HTTP bridge (free, no approval, can get banned)

Both share the same interface: send_message(), parse_incoming(), get_status().
"""

import logging
import uuid
import re
from abc import ABC, abstractmethod
from datetime import datetime
from typing import Optional

import httpx
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.config import get_settings
from app.db.models.conversation import Conversation, Message
from app.db.models.user import User
from app.db.models.audit_log import AuditLog
from app.db.models.enums import (
    AuditAction,
    ChannelType,
    ConversationStatus,
    UserRole,
    UserStatus,
)

logger = logging.getLogger(__name__)
settings = get_settings()


def normalize_whatsapp_number(raw: str | None) -> str | None:
    """Normalize WhatsApp identifiers into a stable numeric phone key.

    Accepts formats like:
    - 21611122233
    - +21611122233
    - 21611122233@c.us
    - 21611122233@s.whatsapp.net
    - 21611122233:12@s.whatsapp.net
    """
    if not raw:
        return None

    value = str(raw).strip().lower()
    if not value:
        return None

    # Remove WhatsApp JID suffix and any device segment.
    value = value.split("@")[0]
    value = value.split(":")[0]

    digits = re.sub(r"\D", "", value)
    if not digits:
        return None

    # E.164 max length is 15 digits; too-short IDs are also invalid.
    if len(digits) < 8 or len(digits) > 15:
        return None

    return digits


# ── Abstract base ────────────────────────────────────────

class WhatsAppProvider(ABC):
    """Common interface for all WhatsApp providers."""

    @abstractmethod
    async def send_message(self, to: str, message: str) -> dict:
        """
        Send a text message to a WhatsApp number.

        Args:
            to: Recipient phone number (with country code, no '+').
            message: Text body.

        Returns:
            dict with keys: success, message_id, provider
        """
        ...

    @abstractmethod
    async def get_status(self) -> dict:
        """Return provider health / config status."""
        ...

    @property
    @abstractmethod
    def provider_name(self) -> str:
        ...


# ══════════════════════════════════════════════════════════
# Provider 1: Meta Cloud API (official)
# ══════════════════════════════════════════════════════════

class MetaCloudProvider(WhatsAppProvider):
    """
    Official WhatsApp Business Cloud API via Meta.
    Docs: https://developers.facebook.com/docs/whatsapp/cloud-api
    """

    def __init__(self):
        self.phone_number_id = settings.WHATSAPP_PHONE_NUMBER_ID
        self.access_token = settings.WHATSAPP_ACCESS_TOKEN
        self.api_version = settings.WHATSAPP_API_VERSION
        self.base_url = f"https://graph.facebook.com/{self.api_version}/{self.phone_number_id}"

    @property
    def provider_name(self) -> str:
        return "meta"

    async def send_message(self, to: str, message: str) -> dict:
        url = f"{self.base_url}/messages"
        headers = {
            "Authorization": f"Bearer {self.access_token}",
            "Content-Type": "application/json",
        }
        payload = {
            "messaging_product": "whatsapp",
            "to": to,
            "type": "text",
            "text": {"body": message},
        }

        async with httpx.AsyncClient(timeout=30) as client:
            resp = await client.post(url, json=payload, headers=headers)

        if resp.status_code == 200:
            data = resp.json()
            msg_id = data.get("messages", [{}])[0].get("id", "unknown")
            logger.info(f"[Meta] Message sent to {to}: {msg_id}")
            return {"success": True, "message_id": msg_id, "provider": "meta"}
        else:
            logger.error(f"[Meta] Send failed ({resp.status_code}): {resp.text}")
            return {
                "success": False,
                "message_id": None,
                "provider": "meta",
                "error": resp.text,
            }

    async def get_status(self) -> dict:
        configured = bool(self.phone_number_id and self.access_token)
        return {
            "provider": "meta",
            "configured": configured,
            "details": {
                "phone_number_id": self.phone_number_id[:8] + "..." if self.phone_number_id else "",
                "api_version": self.api_version,
                "has_access_token": bool(self.access_token),
            },
        }

    # ── Meta webhook helpers ──────────────────────────────

    @staticmethod
    def verify_webhook(mode: str, token: str, challenge: str) -> str | None:
        """Verify Meta webhook subscription. Returns challenge if valid."""
        if mode == "subscribe" and token == settings.WHATSAPP_VERIFY_TOKEN:
            logger.info("Meta webhook verified")
            return challenge
        logger.warning(f"Meta webhook verification failed: mode={mode}")
        return None

    @staticmethod
    def parse_webhook_payload(payload: dict) -> list[dict]:
        """
        Extract incoming text messages from Meta webhook payload.
        Returns list of dicts: [{from_number, body, message_id, sender_name, timestamp}]
        """
        messages = []
        for entry in payload.get("entry", []):
            for change in entry.get("changes", []):
                value = change.get("value", {})
                contacts = {c["wa_id"]: c["profile"]["name"] for c in value.get("contacts", [])}
                for msg in value.get("messages", []):
                    if msg.get("type") == "text" and msg.get("text"):
                        messages.append({
                            "from_number": msg["from"],
                            "body": msg["text"]["body"],
                            "message_id": msg["id"],
                            "sender_name": contacts.get(msg["from"], "Unknown"),
                            "timestamp": msg.get("timestamp"),
                        })
        return messages


# ══════════════════════════════════════════════════════════
# Provider 2: WhatsApp Web Bridge (unofficial)
# ══════════════════════════════════════════════════════════

class WebBridgeProvider(WhatsAppProvider):
    """
    Unofficial WhatsApp integration via a whatsapp-web.js HTTP bridge.

    Expects a separate Node.js service running whatsapp-web.js with a REST API:
        POST /send   → { chatId: "XXXX@c.us", message: "..." }
        GET  /status → { connected: true/false, phone: "..." }

    Popular bridges:
        - https://github.com/nicnocquee/wa-gateway
        - https://github.com/nicnocquee/whatsapp-http-api
        - Or any custom wrapper around whatsapp-web.js / Baileys
    """

    def __init__(self):
        self.bridge_url = settings.WHATSAPP_BRIDGE_URL.rstrip("/")
        self.api_key = settings.WHATSAPP_BRIDGE_API_KEY

    @property
    def provider_name(self) -> str:
        return "bridge"

    def _headers(self) -> dict:
        h = {"Content-Type": "application/json"}
        if self.api_key:
            h["Authorization"] = f"Bearer {self.api_key}"
        return h

    async def send_message(self, to: str, message: str) -> dict:
        raw_to = (to or "").strip()
        if "@" in raw_to:
            local, domain = raw_to.split("@", 1)
            local = local.split(":", 1)[0]
            domain = domain.strip().lower()

            if domain in {"c.us", "s.whatsapp.net", "lid"} and local:
                # Preserve the inbound JID domain to avoid misrouting non-phone chats.
                chat_id = f"{local}@{domain}"
            else:
                logger.warning("[Bridge] Unsupported recipient JID: %s", to)
                return {
                    "success": False,
                    "message_id": None,
                    "provider": "bridge",
                    "error": "unsupported_recipient_jid",
                }
        else:
            normalized_to = normalize_whatsapp_number(raw_to)
            if not normalized_to:
                logger.warning("[Bridge] Invalid recipient number: %s", to)
                return {
                    "success": False,
                    "message_id": None,
                    "provider": "bridge",
                    "error": "invalid_recipient_number",
                }
            # Default for plain phone input from UI/API send endpoints.
            chat_id = f"{normalized_to}@c.us"
        url = f"{self.bridge_url}/send"
        payload = {"chatId": chat_id, "message": message}

        try:
            async with httpx.AsyncClient(timeout=30) as client:
                resp = await client.post(url, json=payload, headers=self._headers())

            if resp.status_code in (200, 201):
                data = resp.json()
                msg_id = data.get("id", data.get("message_id", "bridge_msg"))
                logger.info(f"[Bridge] Message sent to {to}: {msg_id}")
                return {"success": True, "message_id": str(msg_id), "provider": "bridge"}
            else:
                logger.error(f"[Bridge] Send failed ({resp.status_code}): {resp.text}")
                return {
                    "success": False,
                    "message_id": None,
                    "provider": "bridge",
                    "error": resp.text,
                }
        except httpx.ConnectError:
            logger.error(f"[Bridge] Cannot connect to bridge at {self.bridge_url}")
            return {
                "success": False,
                "message_id": None,
                "provider": "bridge",
                "error": f"Bridge unreachable at {self.bridge_url}",
            }

    async def get_status(self) -> dict:
        configured = bool(self.bridge_url)
        connected = False
        details = {"bridge_url": self.bridge_url}

        try:
            async with httpx.AsyncClient(timeout=10) as client:
                resp = await client.get(f"{self.bridge_url}/status", headers=self._headers())
            if resp.status_code == 200:
                data = resp.json()
                connected = data.get("connected", False)
                details["phone"] = data.get("phone", "unknown")
                details["connected"] = connected
        except Exception:
            details["connected"] = False
            details["error"] = "Bridge unreachable"

        return {
            "provider": "bridge",
            "configured": configured and connected,
            "details": details,
        }


# ══════════════════════════════════════════════════════════
# Factory + sync service (for Celery tasks)
# ══════════════════════════════════════════════════════════

def get_whatsapp_provider() -> WhatsAppProvider:
    """Factory: return the configured WhatsApp provider."""
    provider = settings.WHATSAPP_PROVIDER.lower()
    if provider == "bridge":
        return WebBridgeProvider()
    return MetaCloudProvider()


class WhatsAppSyncService:
    """
    Synchronous DB operations for WhatsApp — used by Celery tasks.
    Creates Conversation + Message from incoming messages (same as chat).
    """

    def __init__(self, db: Session):
        self.db = db

    def _find_or_create_whatsapp_user(
        self, phone_number: str, display_name: str = "Unknown",
    ) -> User:
        """
        Resolve a WhatsApp sender to an existing User:
          1. Check if any user already has this phone_number → use them
          2. Otherwise, create a new CLIENT user with this phone_number
        No more fake 'whatsapp_xxx@wa.local' emails — real phone lookup.
        """
        normalized_phone = normalize_whatsapp_number(phone_number)
        if not normalized_phone:
            raise ValueError(f"Invalid WhatsApp phone number: {phone_number}")

        # 1. Look up by canonical phone_number column
        user = self.db.execute(
            select(User).where(User.phone_number == normalized_phone)
        ).scalar_one_or_none()

        if user:
            # Update display name if it was generic
            if display_name != "Unknown" and (
                user.full_name.startswith("WhatsApp ") or user.full_name == "Unknown"
            ):
                user.full_name = display_name
                self.db.flush()
            logger.info(f"WhatsApp matched existing user: {user.id} ({user.email}) by phone {normalized_phone}")
            return user

        # 2. Create a new client user with a placeholder email
        wa_email = f"wa_{normalized_phone}@whatsapp.local"
        user = User(
            email=wa_email,
            full_name=display_name if display_name != "Unknown" else f"WhatsApp {normalized_phone}",
            hashed_password="!wa_no_login",  # cannot login via password
            phone_number=normalized_phone,
            role=UserRole.CLIENT,
            status=UserStatus.ACTIVE,
        )
        self.db.add(user)
        self.db.flush()
        logger.info(f"Created WhatsApp user: {user.id} (phone={normalized_phone})")
        return user

    def _find_or_create_conversation(
        self, user_id: uuid.UUID, phone_number: str,
    ) -> Conversation:
        """
        Find the latest OPEN WhatsApp conversation for this user,
        or create a new one.
        """
        conv = self.db.execute(
            select(Conversation).where(
                Conversation.user_id == user_id,
                Conversation.channel == ChannelType.WHATSAPP,
                Conversation.status == ConversationStatus.OPEN,
                Conversation.is_deleted == False,
            ).order_by(Conversation.updated_at.desc()).limit(1)
        ).scalar_one_or_none()

        if conv:
            return conv

        conv = Conversation(
            user_id=user_id,
            channel=ChannelType.WHATSAPP,
            status=ConversationStatus.OPEN,
            subject=f"WhatsApp — {phone_number}",
        )
        self.db.add(conv)
        self.db.flush()
        logger.info(f"Created WhatsApp conversation: {conv.id} for user {user_id}")
        return conv

    def _get_or_create_support_sender(self) -> User:
        """Return a stable support sender account for automated outbound messages."""
        support_email = "whatsapp.support@local"
        support_user = self.db.execute(
            select(User).where(User.email == support_email)
        ).scalar_one_or_none()

        if support_user:
            return support_user

        support_user = User(
            email=support_email,
            full_name="WhatsApp Support",
            hashed_password="!wa_no_login",
            role=UserRole.AGENT,
            status=UserStatus.ACTIVE,
            can_reply_conversations=True,
            can_reply_whatsapp=True,
        )
        self.db.add(support_user)
        self.db.flush()
        logger.info("Created WhatsApp support sender user: %s", support_user.id)
        return support_user

    def create_conversation_from_message(
        self,
        from_number: str,
        body: str,
        sender_name: str = "Unknown",
        message_id: str | None = None,
    ) -> tuple:
        """
        Ingest an incoming WhatsApp message: find-or-create a User +
        Conversation, then add a Message — exactly like chat.
        Returns (conversation, message).
        """
        normalized_from = normalize_whatsapp_number(from_number)
        if not normalized_from:
            raise ValueError(f"Invalid WhatsApp sender number: {from_number}")

        # 1. Resolve user
        user = self._find_or_create_whatsapp_user(normalized_from, sender_name)

        # 2. Resolve conversation (reuse open one or create new)
        conv = self._find_or_create_conversation(user.id, normalized_from)

        # 3. Add message
        msg = Message(
            conversation_id=conv.id,
            sender_id=user.id,
            content=body,
            is_internal=False,
        )
        self.db.add(msg)
        self.db.flush()

        # 4. Audit
        audit = AuditLog(
            action=AuditAction.WHATSAPP_IN,
            resource_type="conversation",
            resource_id=str(conv.id),
            user_id=user.id,
            description=f"WhatsApp message from {from_number} ({sender_name}) → conversation {conv.id} ({len(body)} chars)",
        )
        self.db.add(audit)
        self.db.flush()

        logger.info(
            f"WhatsApp message ingested: {normalized_from} → conv={conv.id}, msg={msg.id}"
        )
        return conv, msg

    def record_outbound_message(
        self,
        to_number: str,
        body: str,
        wa_message_id: str | None = None,
        user_id: uuid.UUID | None = None,
        conversation_id: uuid.UUID | None = None,
    ) -> Message:
        """
        Record an outbound WhatsApp message as a Message in the conversation.
        """
        normalized_to = normalize_whatsapp_number(to_number)
        if not normalized_to:
            logger.warning("Invalid WhatsApp recipient number for outbound record: %s", to_number)
            return None

        # Find the conversation to attach to
        conv = None
        if conversation_id:
            conv = self.db.execute(
                select(Conversation).where(Conversation.id == conversation_id)
            ).scalar_one_or_none()

        if not conv:
            # Try to find by the recipient's phone_number → open WhatsApp conversation
            wa_user = self.db.execute(
                select(User).where(User.phone_number == normalized_to)
            ).scalar_one_or_none()
            if wa_user:
                conv = self.db.execute(
                    select(Conversation).where(
                        Conversation.user_id == wa_user.id,
                        Conversation.channel == ChannelType.WHATSAPP,
                        Conversation.status == ConversationStatus.OPEN,
                        Conversation.is_deleted == False,
                    ).order_by(Conversation.updated_at.desc()).limit(1)
                ).scalar_one_or_none()

        if not conv:
            logger.warning(f"No conversation found for outbound to {normalized_to}, skipping message record")
            return None

        sender_id = user_id if user_id else self._get_or_create_support_sender().id

        msg = Message(
            conversation_id=conv.id,
            sender_id=sender_id,
            content=body,
            is_internal=False,
        )
        self.db.add(msg)
        self.db.flush()

        audit = AuditLog(
            action=AuditAction.WHATSAPP_OUT,
            resource_type="conversation",
            resource_id=str(conv.id),
            user_id=user_id,
            description=f"WhatsApp reply to {to_number} ({len(body)} chars)",
        )
        self.db.add(audit)
        self.db.flush()

        logger.info(f"WhatsApp outbound recorded: msg={msg.id} in conv={conv.id} → {normalized_to}")
        return msg
