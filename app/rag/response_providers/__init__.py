"""
Response Providers — AI-powered response generation for the RAG knowledge base.

Supports multiple LLM backends (OpenAI/ChatGPT, Anthropic/Claude, Google/Gemini)
with automatic channel-aware formatting for all input channels:
  - CHAT       → concise, conversational
  - EMAIL      → formal, structured with greeting/sign-off
  - WHATSAPP   → short, mobile-friendly with emojis
  - VOICE      → natural spoken language (no markdown/links)
  - TICKET     → detailed, reference-rich for agents
"""

from app.rag.response_providers.enums import AIProvider, ResponseChannel, ResponseTone
from app.rag.response_providers.service import ResponseGenerationService, get_provider

__all__ = [
    "AIProvider",
    "ResponseChannel",
    "ResponseTone",
    "ResponseGenerationService",
    "get_provider",
]
