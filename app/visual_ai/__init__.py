"""
Visual AI module — screenshot analysis, gap detection, and adaptive guidance.

Providers:
  - local-basic    : Tesseract OCR + CLIP embeddings + rule-based UI detection
  - local-advanced : Florence-2 (OCR, caption, OD, regions) + CLIP embeddings
  - google         : Google Cloud Vision + Gemini Vision + CLIP/Vertex AI embeddings
"""

from app.visual_ai.routes import router as visual_ai_router

__all__ = ["visual_ai_router"]
