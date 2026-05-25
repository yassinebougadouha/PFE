"""
Application configuration loaded from environment variables.
Uses pydantic-settings for validated, type-safe configuration.
"""

from functools import lru_cache
from typing import List

from pydantic import field_validator
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
        protected_namespaces=(),
        extra="ignore",
    )

    # ── App ───────────────────────────────────────────────
    APP_NAME: str = "AI Support Agent Backend"
    APP_VERSION: str = "0.1.0"
    DEBUG: bool = True
    ENVIRONMENT: str = "development"  # development | staging | production

    # ── API ───────────────────────────────────────────────
    API_V1_PREFIX: str = "/api/v1"
    CORS_ORIGINS: List[str] = ["*"]
    BACKEND_API_URL: str = "http://localhost:8600"  # URL for file serving, sent to frontend

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
    MAIL_MODE: str = "gmail"
    MAIL_SENDER_NAME: str = "Support"
    GMAIL_FROM_EMAIL: str = ""
    GMAIL_CLIENT_ID: str = ""
    GMAIL_CLIENT_SECRET: str = ""
    GMAIL_REFRESH_TOKEN: str = ""
    GMAIL_REDIRECT_URI: str = "http://localhost:8600/api/v1/gmail/callback"
    GMAIL_SCOPES: List[str] = ["https://www.googleapis.com/auth/gmail.readonly", "https://www.googleapis.com/auth/gmail.modify"]
    GMAIL_POLL_INTERVAL_SECONDS: int = 60
    SMTP_FROM_EMAIL: str = ""
    SMTP_HOST: str = "smtp.gmail.com"
    SMTP_PORT: int = 587
    SMTP_ENCRYPTION: str = "tls"
    SMTP_USERNAME: str = ""
    SMTP_PASSWORD: str = ""

    # ── WhatsApp ────────────────────────────────────────
    WHATSAPP_PROVIDER: str = "meta"  # "meta" (official Cloud API) | "bridge" (unofficial Web bridge)
    # Meta Cloud API settings
    WHATSAPP_PHONE_NUMBER_ID: str = ""
    WHATSAPP_ACCESS_TOKEN: str = ""
    WHATSAPP_VERIFY_TOKEN: str = "my-whatsapp-verify-token"
    WHATSAPP_API_VERSION: str = "v21.0"
    # Bridge settings (whatsapp-web.js via HTTP wrapper)
    WHATSAPP_BRIDGE_URL: str = "http://localhost:8602"  # URL of the bridge server
    WHATSAPP_BRIDGE_API_KEY: str = ""  # optional API key for bridge auth

    # ── Voice (STT / TTS) ────────────────────────────────
    WHISPER_MODEL: str = "tiny"  # still used by voice-agent pipeline helpers
    GEMINI_TRANSCRIPTION_MODEL: str = "gemini-2.5-flash"
    GEMINI_TRANSCRIPTION_INLINE_MAX_BYTES: int = 14 * 1024 * 1024
    GEMINI_IMAGE_ANALYSIS_MODEL: str = "gemini-2.5-flash"
    TTS_VOICE: str = "en-GB-RyanNeural"  # edge-tts voice name
    TTS_RATE: str = "+10%"  # speech rate adjustment
    TTS_PITCH: str = "-0Hz"  # pitch adjustment
    UPLOADS_DIR: str = "uploads"  # directory for temp audio files
    AUDIO_CLEANUP_DELAY_SECONDS: int = 300  # delete audio files after N seconds
    CHAT_ATTACHMENTS_DIR: str = "uploads/chat_attachments"  # persistent chat attachments
    MAX_CHAT_ATTACHMENT_SIZE_MB: int = 15  # max chat upload size in MB

    # ── RAG / Knowledge Base ─────────────────────────────
    RAG_EMBEDDING_MODEL: str = "all-MiniLM-L6-v2"
    USE_GEMINI_EMBEDDINGS: bool = False
    GEMINI_EMBEDDING_MODEL: str = "gemini-embedding-2-preview"
    GEMINI_EMBEDDING_DIMENSION: int = 384
    GEMINI_EMBEDDING_QUERY_TASK_TYPE: str = "RETRIEVAL_QUERY"
    GEMINI_EMBEDDING_DOCUMENT_TASK_TYPE: str = "RETRIEVAL_DOCUMENT"
    RAG_CHUNK_SIZE: int = 512
    RAG_CHUNK_OVERLAP: int = 64
    RAG_DEFAULT_TOP_K: int = 5
    RAG_MIN_SIMILARITY: float = 0.3
    RAG_DOCUMENTS_DIR: str = "app/rag/documents"

    # ── AI Response Providers ────────────────────────────
    OPENAI_API_KEY: str = ""
    ANTHROPIC_API_KEY: str = ""
    GEMINI_API_KEY: str = ""
    
    @property
    def current_gemini_key(self) -> str:
        """Rotate through comma-separated Gemini API keys to extend limits."""
        import random
        keys = [k.strip() for k in self.GEMINI_API_KEY.split(",") if k.strip()]
        return random.choice(keys) if keys else ""

    AI_RESPONSE_PROVIDER: str = "openai"       # openai | claude | gemini | local
    OPENAI_RESPONSE_MODEL: str = "gpt-4o-mini"
    ANTHROPIC_RESPONSE_MODEL: str = "claude-3-haiku-20240307"
    GEMINI_RESPONSE_MODEL: str = "gemini-2.5-flash-lite"
    AI_RESPONSE_TEMPERATURE: float = 0.3
    AI_RESPONSE_MAX_TOKENS: int = 1024
    CONVERSATION_SUMMARY_TIMEOUT_SECONDS: int = 25
    CONVERSATION_ASSISTED_DRAFT_TIMEOUT_SECONDS: int = 25
    CONVERSATION_AI_JOB_METADATA_TTL_SECONDS: int = 1800
    DASHBOARD_SUMMARY_CACHE_TTL_SECONDS: int = 30
    NOTIFICATIONS_UNREAD_COUNT_CACHE_TTL_SECONDS: int = 15

    # ── Local LLM (Ollama / llama.cpp / vLLM / LM Studio) ─
    LOCAL_LLM_BASE_URL: str = "http://localhost:11434/v1"   # OpenAI-compat endpoint
    LOCAL_LLM_MODEL: str = "llama3.2"                       # default model name
    LOCAL_LLM_API_KEY: str = ""                             # optional API key
    LOCAL_LLM_MODELS: str = ""                              # comma-separated available models

    # ── Visual AI ────────────────────────────────────────
    VISUAL_AI_PROVIDER: str = "local-basic"      # local-basic | local-advanced | google
    VISUAL_CLIP_MODEL: str = "clip-ViT-B-32"     # sentence-transformers CLIP model
    SCREENSHOT_DIR: str = "screenshots"           # directory for stored screenshots
    MAX_SCREENSHOT_SIZE_MB: int = 10              # max upload size in MB
    GAP_THRESHOLD_MINOR: float = 0.15
    GAP_THRESHOLD_SIGNIFICANT: float = 0.40
    GAP_THRESHOLD_CRITICAL: float = 0.70
    VISUAL_GUIDANCE_USE_LLM: bool = False          # enable LLM-enhanced guidance
    GOOGLE_CLOUD_PROJECT: str = ""                 # GCP project for Vertex AI
    VISUAL_SCREENSHARE_USE_GEMINI_EMBEDDINGS: bool = False
    VISUAL_SCREENSHARE_GEMINI_MODEL: str = "gemini-embedding-2-preview"
    VISUAL_SCREENSHARE_EMBEDDING_DIMENSION: int = 512
    VISUAL_SCREENSHARE_TARGET_FPS: float = 1.0
    VISUAL_SCREENSHARE_MAX_FRAMES: int = 120
    VISUAL_SCREENSHARE_MAX_VIDEO_MB: int = 50
    VISUAL_SCREENSHARE_MAX_VIDEO_DURATION_SECONDS: int = 300
    VISUAL_SCREENSHARE_FFMPEG_BIN: str = "ffmpeg"
    VISUAL_SCREENSHARE_FFPROBE_BIN: str = "ffprobe"

    # ── Voice Agents (LiveKit) ───────────────────────────
    LIVEKIT_API_KEY: str = "devkey"
    LIVEKIT_API_SECRET: str = "secret"
    LIVEKIT_URL: str = "ws://localhost:7880"
    GOOGLE_API_KEY: str = ""
    USE_REALTIME: bool = False
    VOICE_RECORDINGS_DIR: str = "recordings"

    # ── Internal Service-to-Service Auth ─────────────────
    INTERNAL_SERVICE_KEY: str = "change-me-internal-key"
    INTERNAL_API_BASE_URL: str = "http://api:8600"

    # ── GLPI Integration ─────────────────────────────────
    GLPI_API_URL: str = "http://localhost:8603/api/v1"  # Laravel proxy URL
    GLPI_ENABLED: bool = True
    GLPI_AUTO_SYNC: bool = True  # automatically sync tickets to GLPI
    LARAVEL_DATABASE_URL: str = "postgresql+asyncpg://sail:password@platform-glpi-main-pgsql-1:5432/laravel"
    GLPI_LIST_URL: str = "http://platform-glpi-main-laravel.test-1/api/v1/glpi/items/Ticket"

    # ── Channel Auto-Replies (RAG-powered) ───────────────
    EMAIL_AUTO_REPLY_ENABLED: bool = True
    WHATSAPP_AUTO_REPLY_ENABLED: bool = True
    AUTO_REPLY_TONE: str = "professional"
    AUTO_REPLY_TOP_K: int = 5

    # ── Logging ──────────────────────────────────────────
    LOG_LEVEL: str = "INFO"

    @field_validator("DEBUG", mode="before")
    @classmethod
    def _coerce_debug(cls, value):
        if isinstance(value, bool):
            return value
        if isinstance(value, (int, float)):
            return bool(value)
        if isinstance(value, str):
            normalized = value.strip().lower()
            if normalized in {"1", "true", "yes", "on", "debug", "dev", "development", "local"}:
                return True
            if normalized in {"0", "false", "no", "off", "release", "prod", "production", "staging"}:
                return False
        return value


@lru_cache()
def get_settings() -> Settings:
    """Cached singleton — avoids re-reading .env on each import."""
    return Settings()

settings = get_settings()
