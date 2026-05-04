"""
Runtime email delivery based on persisted admin settings.
"""

import asyncio
import base64
import logging
import smtplib
from dataclasses import dataclass, field
from email.message import EmailMessage
from email.utils import formataddr, make_msgid
from typing import Optional

from google.auth.transport.requests import Request as GoogleAuthRequest
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.services.settings_service import SettingsService

logger = logging.getLogger(__name__)

GMAIL_SEND_SCOPE = ["https://www.googleapis.com/auth/gmail.send"]
GMAIL_TOKEN_URI = "https://oauth2.googleapis.com/token"
runtime_settings = get_settings()


@dataclass(slots=True)
class SmtpDeliveryResult:
    ok: bool
    sender_email: str
    message_id: Optional[str] = None
    headers: dict[str, str] = field(default_factory=dict)
    error: Optional[str] = None


class RuntimeMailService:
    def __init__(self, db: AsyncSession):
        self.db = db
        self.settings_service = SettingsService(db)

    @staticmethod
    def normalize_mail_mode(value: object) -> str:
        normalized = str(value or runtime_settings.MAIL_MODE or "gmail").strip().lower()
        return "smtp" if normalized == "smtp" else "gmail"

    @staticmethod
    def resolve_sender_name(settings: dict) -> str:
        return str(
            settings.get("smtp_from_name")
            or runtime_settings.MAIL_SENDER_NAME
            or settings.get("app_name")
            or "Support"
        ).strip()

    @staticmethod
    def resolve_smtp_sender_email(settings: dict) -> str:
        return str(
            settings.get("smtp_from_email")
            or runtime_settings.SMTP_FROM_EMAIL
            or settings.get("support_email")
            or settings.get("smtp_username")
            or runtime_settings.SMTP_USERNAME
            or ""
        ).strip()

    @staticmethod
    def resolve_gmail_sender_email(settings: dict) -> str:
        return str(
            settings.get("gmail_from_email")
            or runtime_settings.GMAIL_FROM_EMAIL
            or settings.get("support_email")
            or ""
        ).strip()

    @classmethod
    def get_smtp_config(cls, settings: dict) -> dict[str, object]:
        encryption = str(
            settings.get("smtp_encryption") or runtime_settings.SMTP_ENCRYPTION or "tls"
        ).strip().lower()
        if encryption not in {"tls", "ssl", "none"}:
            encryption = "tls"

        try:
            port = int(settings.get("smtp_port") or runtime_settings.SMTP_PORT or 587)
        except (TypeError, ValueError):
            port = 587

        return {
            "sender_email": cls.resolve_smtp_sender_email(settings),
            "sender_name": cls.resolve_sender_name(settings),
            "host": str(settings.get("smtp_host") or runtime_settings.SMTP_HOST or "").strip(),
            "port": port,
            "username": str(
                settings.get("smtp_username") or runtime_settings.SMTP_USERNAME or ""
            ).strip(),
            "password": str(settings.get("smtp_password") or runtime_settings.SMTP_PASSWORD or ""),
            "encryption": encryption,
        }

    @classmethod
    def validate_smtp_settings(cls, settings: dict) -> list[str]:
        config = cls.get_smtp_config(settings)
        missing: list[str] = []

        if not config["sender_email"]:
            missing.append("smtp_from_email")
        if not config["host"]:
            missing.append("smtp_host")
        if int(config["port"]) <= 0:
            missing.append("smtp_port")
        if config["username"] and not config["password"]:
            missing.append("smtp_password")

        return missing

    async def send_email(
        self,
        *,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str] = None,
    ) -> bool:
        settings = await self.settings_service.get_all_settings()
        mode = self.normalize_mail_mode(settings.get("mail_mode"))

        if mode == "smtp":
            return await asyncio.to_thread(
                self._send_via_smtp,
                settings,
                to_address,
                subject,
                text_body,
                html_body,
            )

        if mode == "gmail":
            return await asyncio.to_thread(
                self._send_via_gmail,
                settings,
                to_address,
                subject,
                text_body,
                html_body,
            )

        logger.warning("Unknown mail mode configured: %s", mode)
        return False

    @staticmethod
    def _build_message(
        *,
        sender_name: str,
        sender_email: str,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str] = None,
        extra_headers: Optional[dict[str, str]] = None,
    ) -> EmailMessage:
        message = EmailMessage()
        message["From"] = formataddr((sender_name, sender_email))
        message["To"] = to_address
        message["Subject"] = subject
        for header_name, header_value in (extra_headers or {}).items():
            if header_value:
                message[header_name] = header_value
        message.set_content(text_body)
        if html_body:
            message.add_alternative(html_body, subtype="html")
        return message

    @classmethod
    def send_via_smtp_with_settings(
        cls,
        settings: dict,
        *,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str] = None,
        in_reply_to: Optional[str] = None,
        references: Optional[list[str]] = None,
    ) -> SmtpDeliveryResult:
        config = cls.get_smtp_config(settings)
        sender_email = str(config["sender_email"])
        sender_name = str(config["sender_name"])
        host = str(config["host"])
        port = int(config["port"])
        username = str(config["username"])
        password = str(config["password"])
        encryption = str(config["encryption"])

        missing = cls.validate_smtp_settings(settings)
        if missing:
            logger.info("SMTP delivery skipped: missing settings %s", ", ".join(missing))
            return SmtpDeliveryResult(
                ok=False,
                sender_email=sender_email,
                error=f"Missing SMTP settings: {', '.join(missing)}",
            )

        message_id = make_msgid(
            domain=sender_email.split("@", 1)[1] if "@" in sender_email else None
        )
        extra_headers = {"Message-ID": message_id}
        if in_reply_to:
            extra_headers["In-Reply-To"] = in_reply_to
        if references:
            normalized_refs = [ref.strip() for ref in references if ref and ref.strip()]
            if normalized_refs:
                extra_headers["References"] = " ".join(normalized_refs)

        message = cls._build_message(
            sender_name=sender_name,
            sender_email=sender_email,
            to_address=to_address,
            subject=subject,
            text_body=text_body,
            html_body=html_body,
            extra_headers=extra_headers,
        )

        try:
            if encryption == "ssl":
                with smtplib.SMTP_SSL(host, port, timeout=20) as smtp:
                    if username:
                        smtp.login(username, password)
                    smtp.send_message(message)
            else:
                with smtplib.SMTP(host, port, timeout=20) as smtp:
                    if encryption == "tls":
                        smtp.starttls()
                    if username:
                        smtp.login(username, password)
                    smtp.send_message(message)
            return SmtpDeliveryResult(
                ok=True,
                sender_email=sender_email,
                message_id=message_id,
                headers={key: str(value) for key, value in message.items()},
            )
        except Exception as exc:
            logger.exception("SMTP delivery failed")
            return SmtpDeliveryResult(
                ok=False,
                sender_email=sender_email,
                message_id=message_id,
                headers={key: str(value) for key, value in message.items()},
                error=str(exc),
            )

    def _send_via_smtp(
        self,
        settings: dict,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str],
    ) -> bool:
        return self.send_via_smtp_with_settings(
            settings,
            to_address=to_address,
            subject=subject,
            text_body=text_body,
            html_body=html_body,
        ).ok

    def _send_via_gmail(
        self,
        settings: dict,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str],
    ) -> bool:
        sender_email = self.resolve_gmail_sender_email(settings)
        client_id = str(settings.get("gmail_client_id") or runtime_settings.GMAIL_CLIENT_ID or "").strip()
        client_secret = str(
            settings.get("gmail_client_secret") or runtime_settings.GMAIL_CLIENT_SECRET or ""
        ).strip()
        refresh_token = str(
            settings.get("gmail_refresh_token") or runtime_settings.GMAIL_REFRESH_TOKEN or ""
        ).strip()
        sender_name = self.resolve_sender_name(settings)

        if not (sender_email and client_id and client_secret and refresh_token):
            logger.info("Gmail delivery skipped: OAuth settings are incomplete")
            return False

        try:
            credentials = Credentials(
                token=None,
                refresh_token=refresh_token,
                token_uri=GMAIL_TOKEN_URI,
                client_id=client_id,
                client_secret=client_secret,
                scopes=GMAIL_SEND_SCOPE,
            )
            credentials.refresh(GoogleAuthRequest())
            gmail = build("gmail", "v1", credentials=credentials, cache_discovery=False)
            message = self._build_message(
                sender_name=sender_name,
                sender_email=sender_email,
                to_address=to_address,
                subject=subject,
                text_body=text_body,
                html_body=html_body,
            )
            raw = base64.urlsafe_b64encode(message.as_bytes()).decode("utf-8")
            gmail.users().messages().send(userId="me", body={"raw": raw}).execute()
            return True
        except Exception:
            logger.exception("Gmail delivery failed")
            return False
