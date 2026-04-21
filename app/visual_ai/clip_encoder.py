"""
Shared CLIP encoder — used by local-basic and local-advanced providers.

Uses sentence-transformers (already installed for RAG) with the CLIP model.
Produces 512-dimensional visual embeddings for similarity search.
"""

from __future__ import annotations

import io
import logging
from functools import lru_cache

from PIL import Image

logger = logging.getLogger(__name__)

# ── Model Singleton ───────────────────────────────────────


@lru_cache(maxsize=1)
def _get_clip_model(model_name: str = "clip-ViT-B-32"):
    """Load the CLIP model once and cache it."""
    from sentence_transformers import SentenceTransformer

    logger.info("Loading CLIP model: %s (this may take a moment on first run)", model_name)
    model = SentenceTransformer(model_name)
    embedding_dim = model.get_sentence_embedding_dimension()
    logger.info("CLIP model loaded, embedding dim=%s", embedding_dim if embedding_dim is not None else "unknown")
    return model


# ── Public API ────────────────────────────────────────────


def encode_image(image_bytes: bytes, model_name: str = "clip-ViT-B-32") -> list[float]:
    """
    Encode an image into a 512-dimensional CLIP embedding.

    Args:
        image_bytes: Raw image bytes (PNG, JPEG, etc.)
        model_name: CLIP model to use (default: clip-ViT-B-32)

    Returns:
        list[float] — 512-dimensional normalised embedding vector
    """
    model = _get_clip_model(model_name)
    img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    embedding = model.encode(img, normalize_embeddings=True)
    return embedding.tolist()


def encode_text(text: str, model_name: str = "clip-ViT-B-32") -> list[float]:
    """
    Encode a text description into CLIP text embedding space.

    Useful for text-to-image similarity search (e.g., find screenshots
    matching a description like "login page with error").
    """
    model = _get_clip_model(model_name)
    embedding = model.encode(text, normalize_embeddings=True)
    return embedding.tolist()


def cosine_similarity(a: list[float], b: list[float]) -> float:
    """Compute cosine similarity between two embedding vectors."""
    import numpy as np

    a_arr = np.array(a, dtype=np.float32)
    b_arr = np.array(b, dtype=np.float32)
    dot = np.dot(a_arr, b_arr)
    norm_a = np.linalg.norm(a_arr)
    norm_b = np.linalg.norm(b_arr)
    if norm_a == 0 or norm_b == 0:
        return 0.0
    return float(dot / (norm_a * norm_b))
