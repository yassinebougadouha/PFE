"""
Voice Agents — LiveKit-based multi-agent voice AI system with RAG integration.

A real-time voice assistant powered by LiveKit Agents SDK with multi-provider
LLM support (Google Gemini, OpenAI GPT) and RAG knowledge base retrieval.

Features multiple specialised agents that hand off conversations:

  - StarterAgent (Tom)    → main greeter & router
  - SupportAgent (Mike)   → technical issue resolution (RAG-powered)
  - BookingAgent (Jessica) → appointment scheduling
  - FAQAgent (Ameni)      → RAG knowledge base specialist

LLM Providers (configurable via AI_RESPONSE_PROVIDER):
  - "gemini" → Google Gemini LLM + Google STT/TTS (default)
  - "openai" → OpenAI GPT LLM + Whisper STT + OpenAI TTS

RAG Integration:
  - Agents can search the knowledge base via search_knowledge_base() tool
  - Agents can generate RAG-augmented answers via generate_answer() tool
  - Connects to the backend API via internal service key auth

Supports two modes:
  - Pipeline: STT → LLM → TTS — reliable, free-tier friendly
  - Realtime: Gemini Realtime API — lower latency, single model handles voice I/O

Multilingual: English, French, Modern Standard Arabic, Tunisian Derja.
"""
