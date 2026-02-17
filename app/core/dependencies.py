"""
Shared FastAPI dependency factories.
"""

from app.core.config import Settings, get_settings


def get_app_settings() -> Settings:
    """Injectable dependency for settings."""
    return get_settings()
