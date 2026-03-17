"""
Voice Agent Server — entry point for the LiveKit agents process.

Supports multiple LLM providers (Gemini, OpenAI) via AI_RESPONSE_PROVIDER env var,
and connects to the backend's RAG knowledge base for dynamic knowledge retrieval.

Run with:
    python -m voice_agents.server dev          # development (console mode)
    python -m voice_agents.server start        # production (connects to LiveKit)

Or via the convenience script:
    python run_voice_agents.py dev
"""

from __future__ import annotations

import logging

from livekit import agents
from livekit.agents import AgentServer, AgentSession
from livekit.plugins import silero

from voice_agents.config import get_voice_settings
from voice_agents.llm_factory import make_stt
from voice_agents.agents import StarterAgent
from voice_agents import rag_bridge

logger = logging.getLogger(__name__)

settings = get_voice_settings()

# ── Create the LiveKit Agent Server ──────────────────────
server = AgentServer()


@server.rtc_session()
async def voice_session(ctx: agents.JobContext):
    """
    Called for every new LiveKit room / voice session.
    Starts the StarterAgent (Tom) who greets the user
    and routes to specialist agents as needed.

    The LLM provider is set by AI_RESPONSE_PROVIDER:
      - "gemini" → Google Gemini LLM + Google STT/TTS
      - "openai" → OpenAI GPT LLM + OpenAI Whisper STT + OpenAI TTS
    """
    provider = settings.ai_provider
    logger.info(
        "Starting voice session — provider=%s, realtime=%s, backend=%s",
        provider, settings.use_realtime, settings.backend_api_url,
    )

    if settings.use_realtime:
        # Realtime mode: Gemini handles STT + LLM + TTS in one model
        session = AgentSession()
    else:
        # Pipeline mode: separate STT → LLM → TTS (provider-aware)
        session = AgentSession(
            stt=make_stt(),
            vad=silero.VAD.load(),
        )

    await session.start(
        room=ctx.room,
        agent=StarterAgent(),
    )


def main():
    """CLI entry point."""
    logger.info(
        "Voice Agent Server — provider=%s, backend=%s",
        settings.ai_provider, settings.backend_api_url,
    )
    agents.cli.run_app(server)


if __name__ == "__main__":
    main()
