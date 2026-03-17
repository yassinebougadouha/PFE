"""
Enumerations for the response providers module.
"""

import enum


class AIProvider(str, enum.Enum):
    """Supported LLM provider backends."""
    OPENAI = "openai"          # GPT-4o, GPT-4o-mini, GPT-3.5-turbo
    CLAUDE = "claude"          # Claude 3.5 Sonnet, Claude 3 Haiku
    GEMINI = "gemini"          # Gemini 1.5 Pro, Gemini 1.5 Flash
    LOCAL = "local"            # Ollama, llama.cpp, vLLM, LM Studio, LocalAI


class ResponseChannel(str, enum.Enum):
    """
    Output channel that determines response formatting.
    Maps to the existing ChannelType enum but is specific to response style.
    """
    CHAT = "CHAT"
    EMAIL = "EMAIL"
    WHATSAPP = "WHATSAPP"
    VOICE = "VOICE"
    TICKET = "TICKET"


class ResponseTone(str, enum.Enum):
    """Tone presets for response generation."""
    PROFESSIONAL = "professional"
    FRIENDLY = "friendly"
    EMPATHETIC = "empathetic"
    CONCISE = "concise"
    TECHNICAL = "technical"
