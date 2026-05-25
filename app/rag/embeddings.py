"""
Embedding generation service for the RAG knowledge base.

Supports two backends:
    - Local sentence-transformers (default)
    - Gemini Embeddings API (toggle via USE_GEMINI_EMBEDDINGS)
"""

from __future__ import annotations

import logging
from typing import Optional

import httpx
import numpy as np
from sentence_transformers import SentenceTransformer

from app.core.config import get_settings

logger = logging.getLogger(__name__)
GEMINI_EMBEDDING_BASE_URL = "https://generativelanguage.googleapis.com/v1beta/models"

# ── Singleton model cache ─────────────────────────────────
_model: Optional[SentenceTransformer] = None


def _get_model() -> SentenceTransformer:
    global _model
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


def _use_gemini_embeddings() -> bool:
    return bool(get_settings().USE_GEMINI_EMBEDDINGS)


def _normalize_vector(vector: list[float]) -> list[float]:
    """Normalize vector to unit length to preserve cosine-similarity quality."""
    arr = np.array(vector, dtype=np.float32)
    norm = np.linalg.norm(arr)
    if norm == 0:
        return arr.tolist()
    return (arr / norm).tolist()


def _extract_embedding_values(item: dict) -> list[float]:
    """Handle Gemini response variants: {values:[...]} or {embedding:{values:[...]}}."""
    if "values" in item and isinstance(item["values"], list):
        return [float(v) for v in item["values"]]

    nested = item.get("embedding")
    if isinstance(nested, dict) and isinstance(nested.get("values"), list):
        return [float(v) for v in nested["values"]]

    raise RuntimeError("Unexpected Gemini embedding payload shape")


def _embed_with_gemini(texts: list[str], task_type: str) -> list[list[float]]:
    """Embed texts via Gemini API in one call."""
    settings = get_settings()
    api_key = (settings.current_gemini_key or "").strip()
    if not api_key:
        raise RuntimeError("USE_GEMINI_EMBEDDINGS=True but GEMINI_API_KEY is not configured")

    model_name = (settings.GEMINI_EMBEDDING_MODEL or "gemini-embedding-2-preview").strip()
    model_path = model_name if model_name.startswith("models/") else f"models/{model_name}"
    url = f"{GEMINI_EMBEDDING_BASE_URL}/{model_name}:embedContent?key={api_key}"

    payload = {
        "model": model_path,
        "contents": texts,
        "taskType": task_type,
        "outputDimensionality": int(settings.GEMINI_EMBEDDING_DIMENSION),
    }

    with httpx.Client(timeout=60) as client:
        resp = client.post(url, json=payload)
        resp.raise_for_status()
        data = resp.json()

    if isinstance(data.get("embeddings"), list):
        vectors = [_extract_embedding_values(item) for item in data["embeddings"]]
    elif isinstance(data.get("embedding"), dict):
        vectors = [_extract_embedding_values(data)]
    else:
        raise RuntimeError(f"Gemini returned no embeddings: {data}")

    if len(vectors) != len(texts):
        raise RuntimeError(
            f"Gemini embedding count mismatch: expected {len(texts)}, got {len(vectors)}"
        )

    return [_normalize_vector(v) for v in vectors]


def get_embedding_dimension() -> int:
    """Return the embedding dimension of the configured model."""
    if _use_gemini_embeddings():
        return int(get_settings().GEMINI_EMBEDDING_DIMENSION)
    return _get_model().get_sentence_embedding_dimension()


def embed_text(text: str) -> list[float]:
    """Generate an embedding vector for a single text string."""
    if _use_gemini_embeddings():
        task_type = get_settings().GEMINI_EMBEDDING_QUERY_TASK_TYPE
        return _embed_with_gemini([text], task_type=task_type)[0]

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

    if _use_gemini_embeddings():
        task_type = get_settings().GEMINI_EMBEDDING_DOCUMENT_TASK_TYPE
        return _embed_with_gemini(texts, task_type=task_type)

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
    settings = get_settings()
    if settings.USE_GEMINI_EMBEDDINGS:
        return f"gemini:{settings.GEMINI_EMBEDDING_MODEL}"
    return settings.RAG_EMBEDDING_MODEL
