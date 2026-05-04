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

from typing import Any

from livekit.agents import Agent
from livekit.agents.job import get_job_context
from livekit.agents.llm import function_tool
from livekit import api

from voice_agents import rag_bridge
from voice_agents import shared_state


class GenericAgent(Agent):
    """Base voice agent with auto-greet, RAG tools, and conversation teardown."""

    # ── lifecycle ────────────────────────────────────────

    async def on_enter(self):
        """Automatically greet the user when the agent enters the session."""
        self.session.generate_reply()

    def _get_current_room_name(self) -> str:
        """Resolve the active LiveKit room name across session/runtime variants."""
        room = getattr(self.session, "room", None)
        room_name = getattr(room, "name", None) if room is not None else None
        if isinstance(room_name, str) and room_name.strip():
            return room_name.strip()

        try:
            job_ctx = get_job_context()
        except Exception:
            return ""

        job_room = getattr(job_ctx, "room", None)
        job_room_name = getattr(job_room, "name", None) if job_room is not None else None
        if isinstance(job_room_name, str) and job_room_name.strip():
            return job_room_name.strip()

        return ""

    async def _get_live_screen_context(self) -> tuple[str | None, dict[str, Any] | None]:
        """Load live screen-analysis context for the current support-call room."""
        room_name = self._get_current_room_name()
        if not room_name:
            return "I cannot access the live screen context right now because the room is unavailable.", None

        context = await rag_bridge.get_support_call_screen_context(room_name)
        if not context or not context.get("has_context"):
            return (
                "I don't have any live screen analysis yet. "
                "Please keep screen sharing on and ask again in a few seconds."
            ), None

        return None, context

    @staticmethod
    def _format_screen_packets(context: dict[str, Any], max_events: int = 4) -> str:
        """Create a compact packet summary that can be fed to generation tools."""
        latest_analysis = (context.get("latest_analysis_text") or "").strip()
        latest_caption = (context.get("latest_caption") or "").strip()
        latest_hints = context.get("latest_hints") or []
        latest_capture_mode = context.get("latest_capture_mode")
        latest_frame_number = context.get("latest_frame_number")
        age_seconds = float(context.get("age_seconds") or 0.0)

        lines: list[str] = []
        if latest_analysis:
            lines.append(f"Latest screen analysis: {latest_analysis}")

        if latest_caption and latest_caption.lower() not in latest_analysis.lower():
            lines.append(f"Detected caption: {latest_caption}")

        if latest_hints:
            lines.append("Action hints: " + " ".join(latest_hints[:3]))

        if latest_capture_mode and latest_frame_number:
            lines.append(f"Source: {latest_capture_mode} #{latest_frame_number}")

        recent_events = context.get("recent_events") or []
        for index, event in enumerate(recent_events[:max_events], 1):
            if not isinstance(event, dict):
                continue

            event_analysis = (event.get("analysis_text") or "").strip()
            if not event_analysis:
                continue

            event_capture_mode = event.get("capture_mode") or "packet"
            event_frame_number = event.get("frame_number")
            packet_name = (
                f"{event_capture_mode} #{event_frame_number}"
                if event_frame_number
                else str(event_capture_mode)
            )
            lines.append(f"Recent packet {index} ({packet_name}): {event_analysis}")

        if age_seconds >= 45:
            lines.append(
                f"Note: this visual context is about {int(age_seconds)} seconds old, so ask the user to keep sharing for an updated read."
            )

        return "\n".join(lines).strip()

    @staticmethod
    def _select_screen_hint(hints: list[Any]) -> str:
        for hint in hints:
            normalized = str(hint or "").strip()
            lowered = normalized.lower()
            if not normalized:
                continue
            if lowered.startswith("processed "):
                continue
            if lowered.startswith("average ui transition score:"):
                continue
            return normalized
        return ""

    @classmethod
    def _build_direct_screen_answer(cls, context: dict[str, Any]) -> str:
        latest_analysis = (context.get("latest_analysis_text") or "").strip()
        latest_caption = (context.get("latest_caption") or "").strip()
        latest_hints = context.get("latest_hints") or []
        age_seconds = float(context.get("age_seconds") or 0.0)

        parts: list[str] = []
        if latest_analysis:
            parts.append(latest_analysis.rstrip("."))
        elif latest_caption:
            parts.append(f"I can currently see {latest_caption}".rstrip("."))
        else:
            packet_context = cls._format_screen_packets(context, max_events=2)
            if packet_context:
                parts.append(packet_context.replace("\n", " ").strip().rstrip("."))

        hint = cls._select_screen_hint(latest_hints)
        if hint:
            normalized_parts = " ".join(parts).lower()
            if hint.lower() not in normalized_parts:
                parts.append(hint.rstrip("."))

        if not parts:
            return (
                "I have a live shared-screen packet, but it does not contain enough readable detail yet. "
                "Please keep sharing for another moment and ask again."
            )

        response = "Based on the latest shared-screen packet, " + ". ".join(parts).strip() + "."
        if age_seconds >= 45:
            response += " This packet is a little old, so keeping the screen share active will help me refresh it."
        return response

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

    @function_tool
    async def get_live_screen_analysis(self):
        """
        Get the latest shared-screen analysis for the current support call.

        Call this tool whenever the user asks what you can see on their screen,
        asks for guidance based on the shared UI, or references current visuals.
        """
        context_error, context = await self._get_live_screen_context()
        if context_error:
            return context_error

        packet_context = self._format_screen_packets(context, max_events=3)
        if not packet_context:
            return (
                "I have a screen-sharing context packet, but it did not include readable details. "
                "Please keep sharing and I will try again."
            )

        return packet_context

    @function_tool
    async def generate_screen_answer(self, question: str):
        """
        Generate an answer using live shared-screen packets as context.

        Use this tool when the user asks what you currently see on their screen
        or asks for guidance based on visible UI/actions.

        Args:
            question: The user's screen-related question
        """
        normalized_question = question.strip() or "What is on my shared screen right now?"

        context_error, context = await self._get_live_screen_context()
        if context_error:
            return context_error

        packet_context = self._format_screen_packets(context, max_events=6)
        if not packet_context:
            return (
                "I received screen packets, but they did not include enough readable details yet. "
                "Please keep screen sharing on and ask again in a few seconds."
            )

        prompt = (
            "Answer the user using ONLY this live shared-screen packet context. "
            "If something is missing, say exactly what is missing.\n\n"
            f"User question: {normalized_question}\n\n"
            f"Live shared-screen packets:\n{packet_context}"
        )

        response = await rag_bridge.generate_rag_response(
            query=prompt,
            channel="VOICE",
            tone="friendly",
        )
        if response:
            return response

        return self._build_direct_screen_answer(context)

    @function_tool
    async def escalate_to_human_agent(self, reason: str):
        """
        Escalate the conversation to a human support agent.
        
        Call this tool ONLY when:
        1. The user explicitly asks to speak to a human or real person.
        2. You cannot resolve a complex issue and need human intervention.
        3. The user is extremely frustrated or angry.
        
        Args:
            reason: A short summary of why the user needs human support.
        """
        # 1. Update the shared state for this room
        room_name = self._get_current_room_name() or "unknown"
        state = shared_state.get_session_state(room_name)
        state["escalated"] = True
        state["escalation_reason"] = reason

        escalation_result = await rag_bridge.escalate_voice_call(
            room_name=room_name,
            reason=reason,
        )
        if escalation_result and escalation_result.get("ticket_id"):
            state["escalation_ticket_id"] = str(escalation_result["ticket_id"])
            state["escalation_dispatched_at"] = escalation_result.get("created_at")

        # 2. Tell the user we're escalating and end the call
        self.session.interrupt()
        await self.session.generate_reply(
            instructions=(
                f"Inform the user politely that you are transferring them to a human agent because: {reason}. "
                "Say goodbye and end the conversation."
            )
        )

        try:
            job_ctx = get_job_context()
            await job_ctx.api.room.delete_room(
                api.DeleteRoomRequest(room=job_ctx.room.name)
            )
        except Exception:
            pass

        if escalation_result and escalation_result.get("ticket_id"):
            return f"Escalating to human agent. Ticket {escalation_result['ticket_id']} created."
        return "Escalating to human agent."

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
        
        return "Ending conversation."
