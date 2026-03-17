"""
Embedding generation service for the RAG knowledge base.

Uses sentence-transformers to produce dense vector embeddings.
Model: all-MiniLM-L6-v2 (384 dimensions, fast, multilingual-capable).
"""

from __future__ import annotations

import logging
from typing import Optional

import numpy as np
from sentence_transformers import SentenceTransformer

from app.core.config import get_settings

logger = logging.getLogger(__name__)

# ── Singleton model cache ─────────────────────────────────
_model: Optional[SentenceTransformer] = None


def _get_model() -> SentenceTransformer:
    """Lazy-load the embedding model (singleton)."""
    global _model
    if _model is None:
        settings = get_settings()
        model_name = settings.RAG_EMBEDDING_MODEL
        logger.info("Loading embedding model: %s", model_name)
        _model = SentenceTransformer(model_name)
        logger.info(
            "Embedding model loaded — dimension=%d",
            _model.get_sentence_embedding_dimension(),
        )
    return _model


def get_embedding_dimension() -> int:
    """Return the embedding dimension of the configured model."""
    return _get_model().get_sentence_embedding_dimension()


def embed_text(text: str) -> list[float]:
    """Generate an embedding vector for a single text string."""
    model = _get_model()
    embedding = model.encode(text, normalize_embeddings=True)
    return embedding.tolist()


def embed_texts(texts: list[str], batch_size: int = 64) -> list[list[float]]:
    """
    Generate embedding vectors for multiple texts.

    Args:
        texts: List of text strings.
        batch_size: Batch size for encoding.

    Returns:
        List of embedding vectors (each a list of floats).
    """
    if not texts:
        return []

    model = _get_model()
    embeddings = model.encode(
        texts,
        batch_size=batch_size,
        normalize_embeddings=True,
        show_progress_bar=len(texts) > 100,
    )
    return [e.tolist() for e in embeddings]


def cosine_similarity(a: list[float], b: list[float]) -> float:
    """Compute cosine similarity between two vectors."""
    a_arr = np.array(a)
    b_arr = np.array(b)
    dot = np.dot(a_arr, b_arr)
    norm_a = np.linalg.norm(a_arr)
    norm_b = np.linalg.norm(b_arr)
    if norm_a == 0 or norm_b == 0:
        return 0.0
    return float(dot / (norm_a * norm_b))


def get_model_name() -> str:
    """Return the name of the configured embedding model."""
    return get_settings().RAG_EMBEDDING_MODEL
