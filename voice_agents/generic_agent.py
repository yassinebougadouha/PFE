"""
GenericAgent — base class for all voice agents.

Provides:
  - Auto-greeting on agent entry (on_enter)
  - search_knowledge_base tool (RAG retrieval from backend KB)
  - generate_answer tool (full RAG + LLM generation from backend)
  - end_conversation tool (says goodbye, tears down the LiveKit room)

All specialised agents (Starter, Support, Booking, FAQ) inherit from this.
"""

from __future__ import annotations

from livekit.agents import Agent
from livekit.agents.job import get_job_context
from livekit.agents.llm import function_tool
from livekit import api

from voice_agents import rag_bridge


class GenericAgent(Agent):
    """Base voice agent with auto-greet, RAG tools, and conversation teardown."""

    # ── lifecycle ────────────────────────────────────────

    async def on_enter(self):
        """Automatically greet the user when the agent enters the session."""
        self.session.generate_reply()

    # ── RAG tools ────────────────────────────────────────

    @function_tool
    async def search_knowledge_base(self, query: str):
        """
        Search the knowledge base for information about TunisieSMS services,
        SMS marketing, SMS API, pricing, technical documentation, or any
        company-related topic.

        Call this tool when the user asks a question that might be answered
        by the knowledge base. Returns relevant articles and documentation.

        Args:
            query: The search query — what the user is asking about
        """
        chunks = await rag_bridge.search_knowledge_base(
            query=query,
            top_k=5,
        )

        if not chunks:
            return "No relevant information found in the knowledge base. Please answer based on your general knowledge or suggest the user contact TunisieSMS directly."

        return rag_bridge.format_rag_context(chunks)

    @function_tool
    async def generate_answer(self, question: str):
        """
        Generate a detailed answer using the RAG knowledge base and AI providers.

        Call this tool when the user needs a comprehensive, well-sourced answer
        about TunisieSMS services, technical details, or complex questions that
        benefit from knowledge base context.

        Args:
            question: The user's question to answer
        """
        response = await rag_bridge.generate_rag_response(
            query=question,
            channel="VOICE",
            tone="friendly",
        )

        if response is None:
            return "I couldn't generate a detailed answer right now. Let me try to help based on what I know."

        return response

    # ── conversation tools ───────────────────────────────

    @function_tool
    async def end_conversation(self):
        """Call this function when the user wants to end the conversation."""
        # Interrupt any ongoing generation, then say goodbye
        self.session.interrupt()

        await self.session.generate_reply(
            instructions="say goodbye"
        )

        # Tear down the LiveKit room (no-op in console/dev mode)
        try:
            job_ctx = get_job_context()
            await job_ctx.api.room.delete_room(
                api.DeleteRoomRequest(room=job_ctx.room.name)
            )
        except Exception:
            pass
