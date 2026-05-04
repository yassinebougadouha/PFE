"""
RAG Bridge — connects voice agents to the backend's RAG knowledge base
and response providers via the internal API.

Uses httpx to call the backend's /api/v1/internal/rag/* endpoints,
authenticated with a shared service key (INTERNAL_SERVICE_KEY).

Provides two main functions:
  - search_knowledge_base()  → retrieve relevant KB chunks
  - generate_rag_response()  → full RAG + LLM generation

Both functions degrade gracefully if the backend API is unreachable.
"""

from __future__ import annotations

import logging
from typing import Any, Optional

import httpx

from voice_agents.config import get_voice_settings

logger = logging.getLogger(__name__)

# ── Module-level HTTP client (reused across calls) ────────
_client: Optional[httpx.AsyncClient] = None


def _get_client() -> httpx.AsyncClient:
    """Lazy-create a reusable async HTTP client."""
    global _client
    if _client is None or _client.is_closed:
        _client = httpx.AsyncClient(timeout=30.0)
    return _client


def _headers() -> dict[str, str]:
    """Build headers with the internal service key."""
    settings = get_voice_settings()
    return {
        "X-Service-Key": settings.internal_service_key,
        "Content-Type": "application/json",
    }


def _base_url() -> str:
    """Return the backend API base URL."""
    settings = get_voice_settings()
    return settings.backend_api_url.rstrip("/")


# ═══════════════════════════════════════════════════════════
#  RAG Knowledge Base Search
# ═══════════════════════════════════════════════════════════

async def search_knowledge_base(
    query: str,
    top_k: int = 5,
    category: Optional[str] = None,
    min_similarity: float = 0.3,
) -> list[dict]:
    """
    Search the RAG knowledge base for chunks relevant to the query.

    Returns a list of dicts:
        [{"article_title": "...", "chunk_content": "...", "similarity": 0.85}, ...]

    Falls back to an empty list if the backend is unreachable.
    """
    url = f"{_base_url()}/api/v1/internal/rag/search"
    payload = {
        "query": query,
        "top_k": top_k,
        "min_similarity": min_similarity,
        "include_content": True,
    }
    if category:
        payload["category"] = category

    try:
        client = _get_client()
        resp = await client.post(url, json=payload, headers=_headers())
        resp.raise_for_status()
        data = resp.json()

        hits = data.get("hits", [])
        results = [
            {
                "article_title": h.get("article_title", "Unknown"),
                "chunk_content": h.get("chunk_content", ""),
                "similarity": h.get("similarity", 0.0),
                "article_category": h.get("article_category", ""),
            }
            for h in hits
        ]
        logger.info(
            "RAG search: query=%r → %d results (top_k=%d)",
            query[:60], len(results), top_k,
        )
        return results

    except httpx.ConnectError:
        logger.warning("RAG bridge: backend unreachable at %s", _base_url())
        return []
    except httpx.HTTPStatusError as exc:
        logger.warning("RAG bridge: HTTP %d — %s", exc.response.status_code, exc.response.text[:200])
        return []
    except Exception as exc:
        logger.warning("RAG bridge: unexpected error — %s", exc)
        return []


# ═══════════════════════════════════════════════════════════
#  RAG + LLM Response Generation
# ═══════════════════════════════════════════════════════════

async def generate_rag_response(
    query: str,
    channel: str = "VOICE",
    provider: Optional[str] = None,
    tone: str = "friendly",
    category: Optional[str] = None,
    language: Optional[str] = None,
    top_k: int = 5,
    conversation_history: Optional[list[dict]] = None,
    customer_name: Optional[str] = None,
    agent_name: Optional[str] = None,
) -> Optional[str]:
    """
    Generate a complete response using the backend's RAG + LLM pipeline.

    This calls the backend's response generation service which:
      1. Retrieves relevant KB context
      2. Calls the configured LLM provider (OpenAI / Claude / Gemini)
      3. Formats the output for the VOICE channel

    Returns the generated response text, or None if unavailable.
    """
    url = f"{_base_url()}/api/v1/internal/rag/generate"
    payload = {
        "query": query,
        "channel": channel,
        "tone": tone,
        "top_k": top_k,
        "include_sources": False,
        "conversation_history": conversation_history or [],
    }
    if provider:
        payload["provider"] = provider
    if category:
        payload["category"] = category
    if language:
        payload["language"] = language
    if customer_name:
        payload["customer_name"] = customer_name
    if agent_name:
        payload["agent_name"] = agent_name

    try:
        client = _get_client()
        resp = await client.post(url, json=payload, headers=_headers())
        resp.raise_for_status()
        data = resp.json()

        response_text = data.get("response", "")
        provider_used = data.get("provider", "unknown")
        model_used = data.get("model_used", "unknown")

        logger.info(
            "RAG generate: query=%r → provider=%s model=%s (%d chars)",
            query[:60], provider_used, model_used, len(response_text),
        )
        return response_text

    except httpx.ConnectError:
        logger.warning("RAG bridge: backend unreachable at %s", _base_url())
        return None
    except httpx.HTTPStatusError as exc:
        logger.warning("RAG bridge: HTTP %d — %s", exc.response.status_code, exc.response.text[:200])
        return None
    except Exception as exc:
        logger.warning("RAG bridge: unexpected error — %s", exc)
        return None


# ═══════════════════════════════════════════════════════════
#  Support-call live screen context
# ═══════════════════════════════════════════════════════════

async def get_support_call_screen_context(room_name: str) -> Optional[dict[str, Any]]:
    """Fetch latest live screen-analysis context for a support-call room."""
    normalized_room = room_name.strip()
    if not normalized_room:
        return None

    url = f"{_base_url()}/api/v1/internal/support-call-screen-context/{normalized_room}"

    try:
        client = _get_client()
        resp = await client.get(url, headers=_headers())
        resp.raise_for_status()
        data = resp.json()
        logger.debug(
            "Support-call screen context: room=%s has_context=%s age=%.2fs",
            normalized_room,
            bool(data.get("has_context")),
            float(data.get("age_seconds") or 0.0),
        )
        return data

    except httpx.ConnectError:
        logger.warning("Support-call context bridge: backend unreachable at %s", _base_url())
        return None
    except httpx.HTTPStatusError as exc:
        logger.warning(
            "Support-call context bridge: HTTP %d — %s",
            exc.response.status_code,
            exc.response.text[:200],
        )
        return None
    except Exception as exc:
        logger.warning("Support-call context bridge: unexpected error — %s", exc)
        return None


async def clear_support_call_screen_context(room_name: str) -> bool:
    """Clear cached live screen-analysis context for a support-call room."""
    normalized_room = room_name.strip()
    if not normalized_room:
        return False

    url = f"{_base_url()}/api/v1/internal/support-call-screen-context/{normalized_room}"

    try:
        client = _get_client()
        resp = await client.delete(url, headers=_headers())
        resp.raise_for_status()
        data = resp.json()
        return bool(data.get("cleared"))
    except Exception:
        return False


# ═══════════════════════════════════════════════════════════
#  Voice escalation handoff
# ═══════════════════════════════════════════════════════════

async def escalate_voice_call(
    *,
    room_name: str,
    reason: str,
    transcript: str | None = None,
    audio_file_path: str | None = None,
) -> Optional[dict[str, Any]]:
    """Create a human-escalation ticket for the current voice call immediately."""
    normalized_room = room_name.strip()
    normalized_reason = reason.strip()
    if not normalized_room or not normalized_reason:
        return None

    url = f"{_base_url()}/api/v1/internal/voice/escalations"
    payload: dict[str, Any] = {
        "room_name": normalized_room,
        "reason": normalized_reason,
    }
    if transcript:
        payload["transcript"] = transcript
    if audio_file_path:
        payload["audio_file_path"] = audio_file_path

    try:
        client = _get_client()
        resp = await client.post(url, json=payload, headers=_headers())
        resp.raise_for_status()
        data = resp.json()
        logger.info(
            "Voice escalation created: room=%s ticket=%s",
            normalized_room,
            data.get("ticket_id"),
        )
        return data
    except httpx.ConnectError:
        logger.warning("Voice escalation bridge: backend unreachable at %s", _base_url())
        return None
    except httpx.HTTPStatusError as exc:
        logger.warning("Voice escalation bridge: HTTP %d — %s", exc.response.status_code, exc.response.text[:200])
        return None
    except Exception as exc:
        logger.warning("Voice escalation bridge: unexpected error — %s", exc)
        return None


# ═══════════════════════════════════════════════════════════
#  Helpers
# ═══════════════════════════════════════════════════════════

def format_rag_context(chunks: list[dict]) -> str:
    """
    Format RAG search results into a text block suitable for LLM context injection.

    Used by voice agents to include KB context in their prompts.
    """
    if not chunks:
        return "No knowledge base results found."

    parts: list[str] = []
    for i, chunk in enumerate(chunks, 1):
        title = chunk.get("article_title", "Unknown")
        content = chunk.get("chunk_content", "")
        similarity = chunk.get("similarity", 0.0)
        parts.append(f"[Source {i}: {title} (relevance: {similarity:.0%})]\n{content}")

    return "\n\n".join(parts)


async def close():
    """Close the HTTP client. Call during shutdown."""
    global _client
    if _client and not _client.is_closed:
        await _client.aclose()
        _client = None
