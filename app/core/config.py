"""
Application configuration loaded from environment variables.
Uses pydantic-settings for validated, type-safe configuration.
"""

from functools import lru_cache
from typing import List

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """
    Central configuration — all values come from .env or environment variables.
    Defaults are development-friendly; override in production.
    """

    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        case_sensitive=False,
    )

    # ── App ───────────────────────────────────────────────
    APP_NAME: str = "AI Support Agent Backend"
    APP_VERSION: str = "0.1.0"
    DEBUG: bool = True
    ENVIRONMENT: str = "development"  # development | staging | production

    # ── API ───────────────────────────────────────────────
    API_V1_PREFIX: str = "/api/v1"
    CORS_ORIGINS: List[str] = ["*"]

    # ── Database ─────────────────────────────────────────
    DATABASE_URL: str = "postgresql+asyncpg://postgres:postgres@localhost:5432/support_db"
    DB_ECHO: bool = False
    DB_POOL_SIZE: int = 20
    DB_MAX_OVERFLOW: int = 10

    # ── Redis ────────────────────────────────────────────
    REDIS_URL: str = "redis://localhost:6379/0"
    REDIS_TOKEN_BLACKLIST_DB: int = 1

    # ── JWT ──────────────────────────────────────────────
    JWT_SECRET_KEY: str = "CHANGE-ME-IN-PRODUCTION-USE-OPENSSL-RAND"
    JWT_ALGORITHM: str = "HS256"
    JWT_ACCESS_TOKEN_EXPIRE_MINUTES: int = 15
    JWT_REFRESH_TOKEN_EXPIRE_DAYS: int = 7

    # ── Celery ───────────────────────────────────────────
    CELERY_BROKER_URL: str = "redis://localhost:6379/2"
    CELERY_RESULT_BACKEND: str = "redis://localhost:6379/3"

    # ── Gmail OAuth2 ─────────────────────────────────────
    GMAIL_CLIENT_ID: str = ""
    GMAIL_CLIENT_SECRET: str = ""
    GMAIL_REDIRECT_URI: str = "http://localhost:8000/api/v1/gmail/callback"
    GMAIL_SCOPES: List[str] = ["https://www.googleapis.com/auth/gmail.readonly", "https://www.googleapis.com/auth/gmail.modify"]
    GMAIL_POLL_INTERVAL_SECONDS: int = 60

    # ── WhatsApp ────────────────────────────────────────
    WHATSAPP_PROVIDER: str = "meta"  # "meta" (official Cloud API) | "bridge" (unofficial Web bridge)
    # Meta Cloud API settings
    WHATSAPP_PHONE_NUMBER_ID: str = ""
    WHATSAPP_ACCESS_TOKEN: str = ""
    WHATSAPP_VERIFY_TOKEN: str = "my-whatsapp-verify-token"
    WHATSAPP_API_VERSION: str = "v21.0"
    # Bridge settings (whatsapp-web.js via HTTP wrapper)
    WHATSAPP_BRIDGE_URL: str = "http://localhost:3000"  # URL of the bridge server
    WHATSAPP_BRIDGE_API_KEY: str = ""  # optional API key for bridge auth

    # ── Voice (STT / TTS) ────────────────────────────────
    WHISPER_MODEL: str = "tiny"  # tiny | base | small | medium | large
    TTS_VOICE: str = "en-GB-RyanNeural"  # edge-tts voice name
    TTS_RATE: str = "+10%"  # speech rate adjustment
    TTS_PITCH: str = "-0Hz"  # pitch adjustment
    UPLOADS_DIR: str = "uploads"  # directory for temp audio files
    AUDIO_CLEANUP_DELAY_SECONDS: int = 300  # delete audio files after N seconds

    # ── Logging ──────────────────────────────────────────
    LOG_LEVEL: str = "INFO"


@lru_cache()
def get_settings() -> Settings:
    """Cached singleton — avoids re-reading .env on each import."""
    return Settings()
