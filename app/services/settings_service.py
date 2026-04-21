"""
Persistent application settings service.
"""

from typing import Any

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.setting import Setting


DEFAULT_SETTINGS: dict[str, Any] = {
    "app_name": "AI Support Agent",
    "support_email": "support@example.com",
    "description": "AI-powered support operations workspace.",
    "locale": "en",
    "timezone": "UTC",
    "primary_color": "#1f3a6f",
    "secondary_color": "#2f6f9f",
    "theme_mode": "light",
    "ticket_label": "Ticket",
    "auto_assignment": False,
    "auto_assignment_method": "Round-robin",
    "allow_client_close": True,
    "sla_critical_hours": 4,
    "sla_high_hours": 8,
    "sla_medium_hours": 24,
    "sla_low_hours": 48,
    "min_password_length": 8,
    "session_timeout": 120,
    "max_login_attempts": 5,
    "password_complexity": True,
    "allow_registration": True,
    "require_email_verification": False,
    "two_factor_auth": False,
    "require_admin_profile_completion": False,
    "mail_mode": "gmail",
    "gmail_from_email": "",
    "gmail_client_id": "",
    "gmail_client_secret": "",
    "gmail_refresh_token": "",
    "smtp_from_name": "Support",
    "smtp_from_email": "",
    "smtp_host": "smtp.gmail.com",
    "smtp_port": 587,
    "smtp_encryption": "tls",
    "smtp_username": "",
    "smtp_password": "",
    "notify_new_ticket": True,
    "notify_status_change": True,
    "notify_new_comment": True,
    "notify_assigned": True,
    "notify_overdue": True,
    "notify_resolved": True,
    "ai_auto_reply_chat_enabled": True,
    "ai_auto_reply_whatsapp_enabled": True,
    "ai_auto_reply_email_enabled": True,
    "conversation_sla_autopilot_enabled": True,
    "conversation_sla_auto_escalate_minutes_before_breach": 15,
    "conversation_sla_auto_assign_enabled": True,
    "conversation_sla_auto_assign_minutes_before_breach": 10,
    "conversation_sla_autopilot_respect_snooze": True,
    "decision_confidence_high_threshold": 0.7,
    "decision_confidence_medium_threshold": 0.4,
    "decision_risk_critical_threshold": 0.7,
    "decision_risk_high_threshold": 0.5,
    "decision_risk_medium_threshold": 0.3,
    "decision_low_confidence_risk_boost": 0.08,
    "decision_medium_confidence_risk_boost": 0.03,
    "decision_enforce_security_escalation": True,
    "decision_enforce_critical_escalation": True,
    "decision_low_confidence_general_suggest": True,
}

SECTION_KEYS: dict[str, tuple[str, ...]] = {
    "general": (
        "app_name",
        "support_email",
        "description",
        "locale",
        "timezone",
    ),
    "branding": (
        "primary_color",
        "secondary_color",
        "theme_mode",
        "ticket_label",
    ),
    "tickets": (
        "auto_assignment",
        "auto_assignment_method",
        "allow_client_close",
        "sla_critical_hours",
        "sla_high_hours",
        "sla_medium_hours",
        "sla_low_hours",
    ),
    "security": (
        "min_password_length",
        "session_timeout",
        "max_login_attempts",
        "password_complexity",
        "allow_registration",
        "require_email_verification",
        "two_factor_auth",
        "require_admin_profile_completion",
    ),
    "notifications": (
        "mail_mode",
        "gmail_from_email",
        "gmail_client_id",
        "gmail_client_secret",
        "gmail_refresh_token",
        "smtp_from_name",
        "smtp_from_email",
        "smtp_host",
        "smtp_port",
        "smtp_encryption",
        "smtp_username",
        "smtp_password",
        "notify_new_ticket",
        "notify_status_change",
        "notify_new_comment",
        "notify_assigned",
        "notify_overdue",
        "notify_resolved",
    ),
    "automation": (
        "ai_auto_reply_chat_enabled",
        "ai_auto_reply_whatsapp_enabled",
        "ai_auto_reply_email_enabled",
        "conversation_sla_autopilot_enabled",
        "conversation_sla_auto_escalate_minutes_before_breach",
        "conversation_sla_auto_assign_enabled",
        "conversation_sla_auto_assign_minutes_before_breach",
        "conversation_sla_autopilot_respect_snooze",
    ),
    "decision_engine": (
        "decision_confidence_high_threshold",
        "decision_confidence_medium_threshold",
        "decision_risk_critical_threshold",
        "decision_risk_high_threshold",
        "decision_risk_medium_threshold",
        "decision_low_confidence_risk_boost",
        "decision_medium_confidence_risk_boost",
        "decision_enforce_security_escalation",
        "decision_enforce_critical_escalation",
        "decision_low_confidence_general_suggest",
    ),
}

SECRET_KEYS = {
    "gmail_client_secret",
    "gmail_refresh_token",
    "smtp_password",
}


class SettingsService:
    def __init__(self, db: AsyncSession):
        self.db = db

    async def get_all_settings(self) -> dict[str, Any]:
        result = await self.db.execute(select(Setting))
        settings = dict(DEFAULT_SETTINGS)
        for row in result.scalars().all():
            settings[row.key] = row.value
        return settings

    async def get_section(self, section: str) -> dict[str, Any]:
        if section not in SECTION_KEYS:
            raise ValueError(f"Unknown settings section: {section}")
        settings = await self.get_all_settings()
        return {key: settings[key] for key in SECTION_KEYS[section]}

    async def update_section(self, section: str, values: dict[str, Any]) -> dict[str, Any]:
        if section not in SECTION_KEYS:
            raise ValueError(f"Unknown settings section: {section}")

        allowed_keys = set(SECTION_KEYS[section])
        unknown = sorted(set(values) - allowed_keys)
        if unknown:
            raise ValueError(f"Unsupported settings keys for {section}: {', '.join(unknown)}")

        existing_rows = await self.db.execute(
            select(Setting).where(Setting.key.in_(allowed_keys))
        )
        existing_by_key = {row.key: row for row in existing_rows.scalars().all()}

        for key, value in values.items():
            row = existing_by_key.get(key)
            if row:
                row.section = section
                row.value = value
                row.is_secret = key in SECRET_KEYS
                continue

            self.db.add(
                Setting(
                    section=section,
                    key=key,
                    value=value,
                    is_secret=key in SECRET_KEYS,
                )
            )

        await self.db.flush()
        return await self.get_all_settings()

    async def get_value(self, key: str) -> Any:
        settings = await self.get_all_settings()
        return settings.get(key)

    async def get_bool(self, key: str) -> bool:
        value = await self.get_value(key)
        if isinstance(value, bool):
            return value
        if isinstance(value, str):
            normalized = value.strip().lower()
            if normalized in {"true", "1", "yes", "on"}:
                return True
            if normalized in {"false", "0", "no", "off"}:
                return False
        return bool(value)

    async def get_int(self, key: str) -> int:
        value = await self.get_value(key)
        if value is None:
            return 0
        return int(value)

    async def get_str(self, key: str) -> str:
        value = await self.get_value(key)
        return "" if value is None else str(value)
