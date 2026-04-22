"""
Visual AI module — screenshot analysis, gap detection, and adaptive guidance.

Providers:
  - gemini         : Google Cloud Vision + Gemini Vision + Gemini embeddings
"""

from app.visual_ai.routes import router as visual_ai_router

__all__ = ["visual_ai_router"]
