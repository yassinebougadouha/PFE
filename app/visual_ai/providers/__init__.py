"""
Provider registry — factory function that returns the configured provider.
"""

from __future__ import annotations

import logging
from functools import lru_cache

from app.visual_ai.providers.base import BaseVisualProvider
from app.visual_ai.enums import VisualAIProvider

logger = logging.getLogger(__name__)


@lru_cache(maxsize=4)
def get_visual_provider(provider: str | None = None) -> BaseVisualProvider:
    """
    Return the configured visual AI provider instance.

    Priority:
      1. Explicit *provider* argument
      2. VISUAL_AI_PROVIDER env var
      3. Default → gemini
    """
    from app.core.config import get_settings
    settings = get_settings()

    raw_name = provider or getattr(settings, "VISUAL_AI_PROVIDER", "gemini")
    name = str(raw_name).strip().lower()

    # Visual AI is now Gemini-only. Keep this guard to ignore stale
    # overrides/config values without breaking older clients.
    if name not in {VisualAIProvider.GEMINI.value, "gemini", "google"}:
        logger.warning(
            "Ignoring unsupported Visual AI provider override '%s'; forcing Gemini provider.",
            raw_name,
        )

    from app.visual_ai.providers.google_cloud import GoogleCloudProvider
    logger.info("Visual AI provider: gemini")
    return GoogleCloudProvider()
