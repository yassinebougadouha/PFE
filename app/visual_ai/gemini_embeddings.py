"""
Gemini embedding helpers for Visual AI screenshare workflows.

This module focuses on image embeddings (single image per call).
"""

from __future__ import annotations

import base64
from typing import Optional

import httpx
import numpy as np

from app.core.config import get_settings

GEMINI_EMBEDDING_BASE_URL = "https://generativelanguage.googleapis.com/v1beta/models"


def _normalize_vector(vector: list[float]) -> list[float]:
	arr = np.array(vector, dtype=np.float32)
	norm = np.linalg.norm(arr)
	if norm == 0:
		return arr.tolist()
	return (arr / norm).tolist()


def _extract_values(data: dict) -> list[float]:
	embedding = data.get("embedding")
	if isinstance(embedding, dict) and isinstance(embedding.get("values"), list):
		return [float(v) for v in embedding["values"]]

	if isinstance(data.get("values"), list):
		return [float(v) for v in data["values"]]

	raise RuntimeError("Unexpected Gemini embedding response shape")


def embed_image_with_gemini(
	image_bytes: bytes,
	*,
	mime_type: str = "image/png",
	model_name: Optional[str] = None,
	output_dimensionality: Optional[int] = None,
) -> list[float]:
	"""Embed one image using Gemini embedding API."""
	settings = get_settings()
	api_key = (settings.current_gemini_key or "").strip()
	if not api_key:
		raise RuntimeError("GEMINI_API_KEY is required for Gemini screenshare embeddings")

	selected_model = (model_name or settings.VISUAL_SCREENSHARE_GEMINI_MODEL).strip()
	model_path = selected_model if selected_model.startswith("models/") else f"models/{selected_model}"
	dim = int(output_dimensionality or settings.VISUAL_SCREENSHARE_EMBEDDING_DIMENSION)
	timeout_seconds = max(
		1.0,
		float(getattr(settings, "VISUAL_SCREENSHARE_GEMINI_EMBED_TIMEOUT_SECONDS", 12.0)),
	)
	b64 = base64.b64encode(image_bytes).decode("utf-8")

	payload = {
		"model": model_path,
		"content": {
			"parts": [
				{"inline_data": {"mime_type": mime_type, "data": b64}},
			]
		},
		"outputDimensionality": dim,
	}

	url = f"{GEMINI_EMBEDDING_BASE_URL}/{selected_model}:embedContent?key={api_key}"
	with httpx.Client(timeout=httpx.Timeout(timeout_seconds)) as client:
		resp = client.post(url, json=payload)
		resp.raise_for_status()
		data = resp.json()

	return _normalize_vector(_extract_values(data))
