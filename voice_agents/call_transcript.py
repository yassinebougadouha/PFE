"""
Call Transcript Collector — captures every turn of a voice call and
translates the full transcript to French using Google Gemini.

Usage:
    collector = CallTranscriptCollector(session)
    # ... session runs ...
    french_transcript = await collector.finalize()
"""

from __future__ import annotations

import asyncio
import logging
import mimetypes
import os
from dataclasses import dataclass, field
from pathlib import Path
from typing import List, Tuple

try:
    from google import genai
    from google.genai import types as genai_types
except ImportError:
    genai = None
    genai_types = None

try:
    from faster_whisper import WhisperModel
except ImportError:
    WhisperModel = None

from voice_agents.config import get_voice_settings

logger = logging.getLogger(__name__)


@dataclass
class CallTranscriptCollector:
    """
    Listens to AgentSession events and accumulates transcript turns.

    Turns are stored as (role, text) pairs:
        role = "ai" or "client (<id>)"
    """

    _turns: List[Tuple[str, str]] = field(default_factory=list, init=False)
    _gemini_configured: bool = field(default=False, init=False)
    _genai_client: object | None = field(default=None, init=False)
    _whisper_model: object | None = field(default=None, init=False)
    _client_id: str | None = field(default=None, init=False)

    def set_client_id(self, client_id: str | None) -> None:
        normalized = (client_id or "").strip()
        if normalized:
            self._client_id = normalized

    def _client_role_label(self, client_id: str | None = None) -> str:
        resolved = (client_id or self._client_id or "unknown").strip() or "unknown"
        return f"client ({resolved})"

    def _append_turn(self, role: str, text: str) -> None:
        cleaned = text.strip()
        if not cleaned:
            return

        # Some providers emit both intermediate and committed events for the same text.
        if self._turns and self._turns[-1] == (role, cleaned):
            return

        self._turns.append((role, cleaned))

    # ── Event handlers (register on the AgentSession) ────────

    def on_user_input_transcribed(self, ev) -> None:
        """Called when the user's speech is transcribed."""
        if hasattr(ev, "is_final") and not bool(ev.is_final):
            return

        text = ""
        # Handle different event attribute patterns
        if hasattr(ev, "transcript"):
            text = ev.transcript
        elif hasattr(ev, "text"):
            text = ev.text
        elif hasattr(ev, "alternatives") and ev.alternatives:
            text = ev.alternatives[0].text if hasattr(ev.alternatives[0], "text") else str(ev.alternatives[0])

        speaker_id = getattr(ev, "speaker_id", None)
        if speaker_id:
            self.set_client_id(str(speaker_id))

        if text and text.strip():
            self._append_turn(self._client_role_label(), text)
            logger.debug("Transcript turn [User]: %s", text.strip()[:80])

    def on_agent_speech_committed(self, ev) -> None:
        """Called when the agent's response is committed (spoken)."""
        text = ""
        if hasattr(ev, "content"):
            text = ev.content
        elif hasattr(ev, "text"):
            text = ev.text

        if text and text.strip():
            self._append_turn("ai", text)
            logger.debug("Transcript turn [AI]: %s", text.strip()[:80])

    def on_conversation_item_added(self, ev) -> None:
        """Called when a conversation message is committed in AgentSession."""
        item = getattr(ev, "item", None)
        if item is None:
            return

        role = getattr(item, "role", None)
        if role not in {"assistant", "user"}:
            return

        text = None
        if hasattr(item, "text_content"):
            text = item.text_content
        elif hasattr(item, "content"):
            content = getattr(item, "content")
            if isinstance(content, str):
                text = content

        if not text:
            return

        role_label = "ai" if role == "assistant" else self._client_role_label()
        self._append_turn(role_label, text)
        logger.debug("Transcript turn [%s/Conversation]: %s", role_label, text.strip()[:80])

    # ── Finalization ─────────────────────────────────────────

    def get_raw_transcript(self) -> str:
        """Return the raw transcript without translation."""
        lines: list[str] = []
        for role, text in self._turns:
            lines.append(f"{role}: {text}")
        return "\n".join(lines)

    async def finalize(self) -> str:
        """
        Assemble the full transcript and translate to French via Gemini.

        Returns the French transcript string formatted as:
            ai: Bonjour, je suis Tom…
            client (abc123): Je voudrais un rendez-vous…
        """
        if not self._turns:
            logger.info("No transcript turns to finalize.")
            return ""

        raw_transcript = self.get_raw_transcript()
        logger.info(
            "Finalizing transcript: %d turns, %d chars",
            len(self._turns), len(raw_transcript),
        )

        # Translate to French using Gemini
        try:
            french_transcript = await self._translate_to_french(raw_transcript)
            return french_transcript
        except Exception as exc:
            logger.warning(
                "Translation to French failed, returning raw transcript: %s", exc
            )
            return raw_transcript

    async def finalize_from_audio(self, audio_file_path: str) -> str:
        """
        Build transcript from a recorded WAV file, then translate to French.

        Returns translated transcript when possible, otherwise raw transcript.
        """
        raw_transcript = await self._transcribe_audio_file(audio_file_path)
        if not raw_transcript.strip():
            logger.info("Audio transcription produced no text for: %s", audio_file_path)
            return ""

        logger.info(
            "Audio transcription complete: %d chars from %s",
            len(raw_transcript),
            Path(audio_file_path).name,
        )

        try:
            return await self._translate_to_french(raw_transcript)
        except Exception as exc:
            logger.warning(
                "Translation to French failed, returning raw transcript: %s", exc
            )
            return raw_transcript

    async def _transcribe_audio_file(self, audio_file_path: str) -> str:
        """Run Gemini transcription on the saved recording, with Whisper fallback."""
        settings = get_voice_settings()
        api_key = settings.current_gemini_key or settings.current_google_key

        if genai is not None and genai_types is not None and api_key:
            try:
                if self._genai_client is None:
                    self._genai_client = genai.Client(api_key=api_key)

                transcription_model = os.environ.get("GEMINI_TRANSCRIPTION_MODEL") or settings.gemini_model or "gemini-2.5-flash-lite"
                client_label = self._client_role_label()
                mime_type = mimetypes.guess_type(audio_file_path)[0] or "audio/wav"

                def _run_gemini_transcribe() -> str:
                    with open(audio_file_path, "rb") as audio_file:
                        audio_bytes = audio_file.read()

                    prompt = (
                        "Transcribe this support call audio.\n"
                        "Return only transcript lines (no markdown, no explanations).\n"
                        "Use one utterance per line.\n"
                        f"Use speaker labels exactly as: '{client_label}:' and 'ai:'.\n"
                        "If speaker is uncertain, use the client label.\n"
                        "Keep spoken language as-is (do not translate)."
                    )

                    response = self._genai_client.models.generate_content(
                        model=transcription_model,
                        contents=[
                            prompt,
                            genai_types.Part.from_bytes(data=audio_bytes, mime_type=mime_type),
                        ],
                    )
                    return (getattr(response, "text", "") or "").strip()

                transcript = await asyncio.to_thread(_run_gemini_transcribe)
                if transcript:
                    return transcript
            except Exception as exc:
                logger.warning("Gemini transcription failed, falling back to Whisper: %s", exc)

        return await self._transcribe_audio_file_whisper(audio_file_path)

    async def _transcribe_audio_file_whisper(self, audio_file_path: str) -> str:
        """Fallback transcription using local faster-whisper."""
        if WhisperModel is None:
            logger.warning("faster-whisper not installed; cannot transcribe audio recording.")
            return ""

        model_size = os.environ.get("WHISPER_MODEL") or "tiny"

        def _run_transcribe() -> str:
            if self._whisper_model is None:
                logger.info("Loading faster-whisper model for post-call transcription: %s", model_size)
                self._whisper_model = WhisperModel(model_size, device="cpu", compute_type="int8")

            segments, _info = self._whisper_model.transcribe(
                audio_file_path,
                vad_filter=True,
                beam_size=5,
            )
            parts = [seg.text.strip() for seg in segments if seg.text and seg.text.strip()]
            joined = " ".join(parts)
            if not joined:
                return ""

            return f"{self._client_role_label()}: {joined}"

        # Keep blocking model inference off the event loop.
        return await asyncio.to_thread(_run_transcribe)

    async def _translate_to_french(self, transcript: str) -> str:
        """Use Google Gemini to translate the transcript to French."""
        settings = get_voice_settings()
        api_key = settings.current_gemini_key or settings.current_google_key

        if not api_key:
            logger.warning("No Gemini API key — skipping translation, returning raw.")
            return transcript

        prompt = f"""Translate the following voice call transcript entirely to French.
Rules:
- Keep label prefixes exactly as they appear in the transcript (for example "ai:" or "client (abc123):").
- If a line is already in French, keep it as-is.
- If a line is in Arabic, Tunisian Derja, English, or any other language, translate it to natural French.
- Preserve the meaning and tone of each line.
- Return ONLY the translated transcript, nothing else.

Transcript:
{transcript}"""

        # Preferred SDK: google-genai
        if genai is not None:
            if self._genai_client is None:
                self._genai_client = genai.Client(api_key=api_key)

            response = await asyncio.to_thread(
                self._genai_client.models.generate_content,
                model="gemini-2.5-flash-lite",
                contents=prompt,
            )
            translated = (getattr(response, "text", "") or "").strip()
            if translated:
                logger.info("Transcript translated to French (%d chars).", len(translated))
                return translated

        # Compatibility fallback for older environments where google-genai is unavailable.
        legacy_genai = None
        if genai is None:
            try:
                import google.generativeai as legacy_genai  # Deprecated fallback.
            except ImportError:
                legacy_genai = None

        if legacy_genai is not None:
            if not self._gemini_configured:
                legacy_genai.configure(api_key=api_key)
                self._gemini_configured = True

            model = legacy_genai.GenerativeModel("gemini-2.5-flash-lite")
            response = await model.generate_content_async(prompt)
            translated = response.text.strip()
            logger.info("Transcript translated to French (%d chars).", len(translated))
            return translated

        logger.warning("No Gemini SDK is available, returning raw transcript.")
        return transcript
