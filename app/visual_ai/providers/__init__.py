"""
Provider registry — factory function that returns the configured provider.
"""

from __future__ import annotations

import logging
from functools import lru_cache

from app.visual_ai.providers.base import BaseVisualProvider
from app.visual_ai.enums import VisualAIProvider

logger = logging.getLogger(__name__)


@lru_cache(maxsize=1)
def get_visual_provider(provider: str | None = None) -> BaseVisualProvider:
    """
    Return the configured visual AI provider instance.

    Priority:
      1. Explicit *provider* argument
      2. VISUAL_AI_PROVIDER env var
      3. Default → local-basic
    """
    from app.core.config import get_settings
    settings = get_settings()

    name = provider or getattr(settings, "VISUAL_AI_PROVIDER", "local-basic")

    if name == VisualAIProvider.LOCAL_ADVANCED or name == "local-advanced":
        from app.visual_ai.providers.local_advanced import LocalAdvancedProvider
        logger.info("Visual AI provider: local-advanced (Florence-2 + CLIP)")
        return LocalAdvancedProvider()

    if name == VisualAIProvider.GOOGLE or name == "google":
        from app.visual_ai.providers.google_cloud import GoogleCloudProvider
        logger.info("Visual AI provider: google (Cloud Vision + Vertex AI + Gemini)")
        return GoogleCloudProvider()

    # Default: local-basic
    from app.visual_ai.providers.local_basic import LocalBasicProvider
    logger.info("Visual AI provider: local-basic (Tesseract + CLIP + rules)")
    return LocalBasicProvider()
