"""
LLM / TTS / STT factory helpers — multi-provider support.

Supports:
  - Google Gemini  (livekit-plugins-google)  — default LLM
  - OpenAI GPT     (livekit-plugins-openai)  — LLM via AI_RESPONSE_PROVIDER=openai
  - Local Whisper  (faster-whisper)          — default STT (free, no API key)
  - Edge TTS       (edge-tts)               — default TTS (free, no API key)

Abstracts the choice between Gemini Realtime API (single model, low latency)
and the Pipeline approach (STT → LLM → TTS), as well as provider selection.
"""

from __future__ import annotations

import logging
import os

from voice_agents.config import get_voice_settings
from voice_agents.local_stt import LocalWhisperSTT
from voice_agents.local_tts import EdgeTTS, VOICE_MAP as _EDGE_VOICE_MAP

from livekit.plugins import google

# ── Optional OpenAI plugin (installed via livekit-agents[openai]) ──
try:
    from livekit.plugins import openai as openai_plugin
    _openai_available = True
except ImportError:
    _openai_available = False

logger = logging.getLogger(__name__)


# ═══════════════════════════════════════════════════════════
#  Voice name mapping: Google voices → OpenAI voices
# ═══════════════════════════════════════════════════════════

_OPENAI_VOICE_MAP: dict[str, str] = {
    "Puck": "alloy",      # Tom (StarterAgent)
    "Charon": "echo",     # Mike (SupportAgent)
    "Aoede": "nova",      # Jessica (BookingAgent)
    "Kore": "shimmer",    # Ameni (FAQAgent)
}


# ═══════════════════════════════════════════════════════════
#  LLM factory
# ═══════════════════════════════════════════════════════════

def make_llm(voice: str):
    """
    Return an LLM instance for the configured provider.

    In realtime mode, always uses Google Gemini Realtime (single model).
    In pipeline mode, selects based on AI_RESPONSE_PROVIDER setting:
      - "gemini" → google.LLM  (uses Gemini API key — free tier OK)
      - "openai" → openai.LLM

    Args:
        voice: Google voice ID (e.g. "Puck", "Charon", "Aoede", "Kore").
               Used only in realtime mode.
    """
    settings = get_voice_settings()

    if settings.use_realtime:
        # Realtime mode: always Google (Gemini handles STT+LLM+TTS in one)
        api_key = settings.current_gemini_key or settings.current_google_key or None
        return google.beta.realtime.RealtimeModel(voice=voice, api_key=api_key)

    provider = settings.ai_provider.lower()

    if provider == "openai" and _openai_available:
        logger.info("LLM factory: using OpenAI (%s)", settings.openai_model)
        return openai_plugin.LLM(model=settings.openai_model)

    # Default: Google Gemini (works with free Gemini API key)
    logger.info("LLM factory: using Google Gemini (%s)", settings.gemini_model)
    api_key = settings.current_gemini_key or settings.current_google_key or None
    return google.LLM(model=settings.gemini_model, api_key=api_key)


# ═══════════════════════════════════════════════════════════
#  TTS factory
# ═══════════════════════════════════════════════════════════

def make_tts(voice: str):
    """
    Return a TTS instance for the configured provider.

    In realtime mode returns None (Gemini handles TTS internally).
    In pipeline mode, priority:
      1. "openai" provider + key available  → openai.TTS (cloud, paid)
      2. Google Cloud credentials available → google.TTS (cloud, paid)
      3. Default: EdgeTTS (free, no API key, Microsoft neural voices)

    Args:
        voice: Google voice ID (e.g. "Puck"). Mapped to edge-tts voice names.
    """
    settings = get_voice_settings()

    if settings.use_realtime:
        return None  # realtime model handles TTS internally

    provider = settings.ai_provider.lower()

    # ── Explicit OpenAI provider ─────────────────────────
    if provider == "openai" and _openai_available and settings.openai_api_key:
        openai_voice = _OPENAI_VOICE_MAP.get(voice, "alloy")
        logger.info("TTS factory: using OpenAI TTS (voice=%s)", openai_voice)
        return openai_plugin.TTS(voice=openai_voice)

    # ── Google Cloud TTS (requires service-account credentials) ──
    google_creds_file = os.environ.get("GOOGLE_APPLICATION_CREDENTIALS", "")
    if google_creds_file and os.path.isfile(google_creds_file):
        logger.info("TTS factory: using Google Cloud TTS (voice=%s)", voice)
        return google.TTS(voice_name=voice)

    # ── Default: Edge TTS (free, no API key) ─────────────
    edge_voice = _EDGE_VOICE_MAP.get(voice, "en-GB-RyanNeural")
    logger.info("TTS factory: using Edge TTS — free, no API key (voice=%s)", edge_voice)
    return EdgeTTS(voice=edge_voice)


# ═══════════════════════════════════════════════════════════
#  STT factory
# ═══════════════════════════════════════════════════════════

def make_stt():
    """
    Return an STT instance for the configured provider.

    Priority:
      1. "openai" provider + key available        → openai.STT (Whisper, cloud)
      2. Google Cloud credentials available        → google.STT (cloud)
      3. Default: LocalWhisperSTT (faster-whisper, free, runs locally on CPU)

    Note: google.STT requires Google Cloud service-account credentials.
    A simple Gemini API key is NOT sufficient for Google Cloud Speech-to-Text.
    """
    settings = get_voice_settings()
    provider = settings.ai_provider.lower()

    # ── Explicit OpenAI provider ─────────────────────────
    if provider == "openai" and _openai_available and settings.openai_api_key:
        logger.info("STT factory: using OpenAI Whisper (provider=openai)")
        return openai_plugin.STT()

    # ── Google Cloud STT (requires service-account credentials) ──
    google_creds_file = os.environ.get("GOOGLE_APPLICATION_CREDENTIALS", "")
    if google_creds_file and os.path.isfile(google_creds_file):
        logger.info("STT factory: using Google Cloud STT (credentials from ADC)")
        return google.STT(detect_language=True)

    # ── Default: Local Whisper STT (free, no API key) ────
    whisper_model = os.environ.get("WHISPER_MODEL", "tiny")
    logger.info(
        "STT factory: using local faster-whisper — free, no API key (model=%s)",
        whisper_model,
    )
    return LocalWhisperSTT(model_size=whisper_model)

