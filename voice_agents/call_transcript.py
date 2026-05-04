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
import re
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

    Wire up ALL three handlers in server.py:
        session.on("user_input_transcribed",  collector.on_user_input_transcribed)
        session.on("agent_speech_committed",  collector.on_agent_speech_committed)
        session.on("conversation_item_added", collector.on_conversation_item_added)
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

    def _call_transcription_prompt(self) -> str:
        client_label = self._client_role_label()
        return (
            "You are transcribing a two-speaker customer support call.\n"
            "Return valid JSON only with this exact shape:\n"
            "{\n"
            '  "text": "full transcript with one utterance per line",\n'
            '  "language": "detected language",\n'
            '  "segments": []\n'
            "}\n\n"
            "Requirements:\n"
            "- Transcribe the spoken words accurately.\n"
            "- In text, put one utterance per line.\n"
            f'- Use speaker labels exactly "{client_label}:" and "ai:".\n'
            "- The AI assistant is the support agent voice. The client is the caller/customer voice.\n"
            "- If a speaker is uncertain, use the client label.\n"
            "- Preserve the spoken language; do not translate.\n"
            '- If there is no speech, return {"text":"","language":"unknown","segments":[]}.\n'
            "- If reliable timestamps are not available, return an empty segments array.\n"
            "- Do not wrap the JSON in markdown fences."
        )

    def _has_role_label(self, line: str) -> bool:
        return (
            line.lower().startswith("ai:")
            or line.lower().startswith(f"{self._client_role_label().lower()}:")
            or re.match(r"^client\s*\([^)]*\)\s*:", line, flags=re.IGNORECASE) is not None
        )

    def _normalize_call_transcript(self, transcript: str) -> str:
        """Normalize common speaker labels to the labels stored in call logs."""
        client_label = self._client_role_label()
        lines: list[str] = []

        for raw_line in transcript.strip().splitlines():
            line = raw_line.strip()
            if not line:
                continue

            line = re.sub(
                r"^(assistant|agent|support|ai)\s*:\s*",
                "ai: ",
                line,
                flags=re.IGNORECASE,
            )
            line = re.sub(
                r"^(customer|caller|user|human|client)\s*:\s*",
                f"{client_label}: ",
                line,
                flags=re.IGNORECASE,
            )

            if not self._has_role_label(line):
                line = f"{client_label}: {line}"

            lines.append(line)

        return "\n".join(lines)

    def _append_turn(self, role: str, text: str) -> None:
        cleaned = text.strip()
        if not cleaned:
            return

        # Some providers emit both intermediate and committed events for the same text.
        if self._turns and self._turns[-1] == (role, cleaned):
            return

        self._turns.append((role, cleaned))

    # ── Event handlers (register all three on the AgentSession) ─────────────

    def on_user_input_transcribed(self, ev) -> None:
        """Called when the user's speech is transcribed."""
        if hasattr(ev, "is_final") and not bool(ev.is_final):
            return

        text = ""
        if hasattr(ev, "transcript"):
            text = ev.transcript
        elif hasattr(ev, "text"):
            text = ev.text
        elif hasattr(ev, "alternatives") and ev.alternatives:
            alt = ev.alternatives[0]
            text = alt.text if hasattr(alt, "text") else str(alt)

        speaker_id = getattr(ev, "speaker_id", None)
        if speaker_id:
            self.set_client_id(str(speaker_id))

        if text and text.strip():
            self._append_turn(self._client_role_label(), text)
            logger.debug("Transcript turn [User]: %s", text.strip()[:80])

    def on_agent_speech_committed(self, ev) -> None:
        """Called when the agent's response is committed (spoken).

        Must be registered in server.py:
            session.on("agent_speech_committed", collector.on_agent_speech_committed)
        """
        text = ""
        if hasattr(ev, "content"):
            text = ev.content
        elif hasattr(ev, "text"):
            text = ev.text

        if text and text.strip():
            self._append_turn("ai", text)
            logger.debug("Transcript turn [AI/committed]: %s", text.strip()[:80])

    def on_conversation_item_added(self, ev) -> None:
        """Called when a conversation message is committed in AgentSession.

        Handles livekit-agents v1+ where item.content is a list of parts,
        not a plain string.
        """
        item = getattr(ev, "item", None)
        if item is None:
            return

        role = getattr(item, "role", None)
        if role not in {"assistant", "user"}:
            return

        text = None

        # livekit-agents v1+: content is a list of Part objects
        if hasattr(item, "text_content") and item.text_content:
            text = item.text_content
        elif hasattr(item, "content"):
            content = item.content
            if isinstance(content, str):
                text = content or None
            elif isinstance(content, list):
                parts: list[str] = []
                for part in content:
                    if isinstance(part, str) and part.strip():
                        parts.append(part.strip())
                    elif hasattr(part, "text") and part.text and part.text.strip():
                        parts.append(part.text.strip())
                text = " ".join(parts) or None

        if not text:
            return

        role_label = "ai" if role == "assistant" else self._client_role_label()
        self._append_turn(role_label, text)
        logger.debug(
            "Transcript turn [%s/conversation_item]: %s", role_label, text[:80]
        )

    # ── Finalization ─────────────────────────────────────────────────────────

    def get_raw_transcript(self) -> str:
        """Return the raw transcript without translation."""
        return "\n".join(f"{role}: {text}" for role, text in self._turns)

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
        """
        Run transcription on the saved recording.

        Priority:
          1. Gemini SDK  (primary — no backend import dependency)
          2. Whisper     (local fallback — no API key needed)
          3. Backend service (last resort — may not be importable in voice agent process)
        """
        # 1. Gemini SDK (preferred — self-contained, no app.services import)
        transcript = await self._transcribe_audio_file_with_genai_sdk(audio_file_path)
        if transcript:
            return transcript

        # 2. Local Whisper (free, no API key)
        transcript = await self._transcribe_audio_file_whisper(audio_file_path)
        if transcript:
            return transcript

        # 3. Backend service (last resort — will fail if app.services not importable)
        transcript = await self._transcribe_audio_file_with_backend_service(audio_file_path)
        if transcript:
            return transcript

        logger.error(
            "All transcription methods failed for %s. "
            "Check Gemini API key and that faster-whisper is installed.",
            audio_file_path,
        )
        return ""

    async def _transcribe_audio_file_with_backend_service(self, audio_file_path: str) -> str:
        """Use the backend transcription service (may fail in voice agent container)."""
        try:
            from app.services.transcription_service import save_upload_and_transcribe
        except Exception as exc:
            logger.debug(
                "Backend transcription service not importable (expected in voice agent process): %s",
                exc,
            )
            return ""

        try:
            path = Path(audio_file_path)
            mime_type = mimetypes.guess_type(path.name)[0] or "audio/wav"
            audio_bytes = await asyncio.to_thread(path.read_bytes)
            result = await save_upload_and_transcribe(
                audio_bytes,
                path.name,
                content_type=mime_type,
                prompt=self._call_transcription_prompt(),
            )
            transcript = self._normalize_call_transcript(str(result.get("text") or ""))
            if transcript:
                logger.info("Call audio transcribed with backend Gemini service.")
            return transcript
        except Exception as exc:
            logger.warning("Backend Gemini transcription failed: %s", exc)
            return ""

    async def _transcribe_audio_file_with_genai_sdk(self, audio_file_path: str) -> str:
        """Gemini SDK transcription — primary path, no backend import dependency."""
        settings = get_voice_settings()
        api_key = settings.current_gemini_key or settings.current_google_key

        if genai is None or genai_types is None:
            logger.debug("google-genai SDK not installed, skipping Gemini transcription.")
            return ""

        if not api_key:
            logger.warning("No Gemini/Google API key configured — skipping Gemini transcription.")
            return ""

        try:
            if self._genai_client is None:
                self._genai_client = genai.Client(api_key=api_key)

            transcription_model = (
                os.environ.get("GEMINI_TRANSCRIPTION_MODEL")
                or settings.gemini_model
                or "gemini-2.5-flash-lite"
            )
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
                return self._normalize_call_transcript(
                    (getattr(response, "text", "") or "").strip()
                )

            transcript = await asyncio.to_thread(_run_gemini_transcribe)
            if transcript:
                logger.info(
                    "Call audio transcribed with Gemini SDK (%d chars).", len(transcript)
                )
            else:
                logger.warning(
                    "Gemini SDK returned empty transcription for %s.", audio_file_path
                )
            return transcript

        except Exception as exc:
            logger.warning("Gemini SDK transcription failed: %s", exc)
            return ""

    async def _transcribe_audio_file_whisper(self, audio_file_path: str) -> str:
        """Fallback transcription using local faster-whisper."""
        if WhisperModel is None:
            logger.debug(
                "faster-whisper not installed; cannot transcribe with Whisper."
            )
            return ""

        model_size = os.environ.get("WHISPER_MODEL") or "tiny"

        def _run_transcribe() -> str:
            if self._whisper_model is None:
                logger.info(
                    "Loading faster-whisper model for post-call transcription: %s", model_size
                )
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

        transcript = await asyncio.to_thread(_run_transcribe)
        if transcript:
            logger.info(
                "Call audio transcribed with local Whisper (%d chars).", len(transcript)
            )
        else:
            logger.warning("Whisper returned empty transcription for %s.", audio_file_path)
        return transcript

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

            try:
                response = await asyncio.to_thread(
                    self._genai_client.models.generate_content,
                    model="gemini-2.5-flash-lite",
                    contents=prompt,
                )
                translated = (getattr(response, "text", "") or "").strip()
                if translated:
                    logger.info(
                        "Transcript translated to French (%d chars).", len(translated)
                    )
                    return translated
                logger.warning("Gemini returned empty translation — returning raw transcript.")
            except Exception as exc:
                logger.warning("Gemini translation request failed: %s", exc)

        # Compatibility fallback for older environments where google-genai is unavailable.
        legacy_genai = None
        if genai is None:
            try:
                import google.generativeai as legacy_genai  # Deprecated fallback.
            except ImportError:
                legacy_genai = None

        if legacy_genai is not None:
            try:
                if not self._gemini_configured:
                    legacy_genai.configure(api_key=api_key)
                    self._gemini_configured = True

                model = legacy_genai.GenerativeModel("gemini-2.5-flash-lite")
                response = await model.generate_content_async(prompt)
                translated = response.text.strip()
                logger.info(
                    "Transcript translated to French via legacy SDK (%d chars).", len(translated)
                )
                return translated
            except Exception as exc:
                logger.warning("Legacy Gemini translation failed: %s", exc)

        logger.warning("No Gemini SDK available or all translation attempts failed — returning raw transcript.")
        return transcript