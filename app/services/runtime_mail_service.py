"""
Runtime email delivery based on persisted admin settings.
"""

import asyncio
import base64
import logging
import smtplib
from email.message import EmailMessage
from email.utils import formataddr
from typing import Optional

from google.auth.transport.requests import Request as GoogleAuthRequest
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build
from sqlalchemy.ext.asyncio import AsyncSession

from app.services.settings_service import SettingsService

logger = logging.getLogger(__name__)

GMAIL_SEND_SCOPE = ["https://www.googleapis.com/auth/gmail.send"]
GMAIL_TOKEN_URI = "https://oauth2.googleapis.com/token"


class RuntimeMailService:
    def __init__(self, db: AsyncSession):
        self.db = db
        self.settings_service = SettingsService(db)

    async def send_email(
        self,
        *,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str] = None,
    ) -> bool:
        settings = await self.settings_service.get_all_settings()
        mode = str(settings["mail_mode"]).lower()

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
    ) -> EmailMessage:
        message = EmailMessage()
        message["From"] = formataddr((sender_name, sender_email))
        message["To"] = to_address
        message["Subject"] = subject
        message.set_content(text_body)
        if html_body:
            message.add_alternative(html_body, subtype="html")
        return message

    def _send_via_smtp(
        self,
        settings: dict,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str],
    ) -> bool:
        sender_email = str(settings.get("smtp_from_email") or settings.get("smtp_username") or "").strip()
        sender_name = str(settings.get("smtp_from_name") or settings.get("app_name") or "Support").strip()
        host = str(settings.get("smtp_host") or "").strip()
        username = str(settings.get("smtp_username") or "").strip()
        password = str(settings.get("smtp_password") or "")
        encryption = str(settings.get("smtp_encryption") or "tls").lower()
        port = int(settings.get("smtp_port") or 587)

        if not sender_email or not host:
            logger.info("SMTP delivery skipped: sender or host is missing")
            return False

        message = self._build_message(
            sender_name=sender_name,
            sender_email=sender_email,
            to_address=to_address,
            subject=subject,
            text_body=text_body,
            html_body=html_body,
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
            return True
        except Exception:
            logger.exception("SMTP delivery failed")
            return False

    def _send_via_gmail(
        self,
        settings: dict,
        to_address: str,
        subject: str,
        text_body: str,
        html_body: Optional[str],
    ) -> bool:
        sender_email = str(settings.get("gmail_from_email") or "").strip()
        client_id = str(settings.get("gmail_client_id") or "").strip()
        client_secret = str(settings.get("gmail_client_secret") or "").strip()
        refresh_token = str(settings.get("gmail_refresh_token") or "").strip()
        sender_name = str(settings.get("smtp_from_name") or settings.get("app_name") or "Support").strip()

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
