"""
Local STT plugin for LiveKit — uses faster-whisper (CTranslate2).

Runs Whisper locally on CPU, no API keys needed.
Same engine used by the backend's /api/v1/voice/transcribe endpoint.
"""

from __future__ import annotations

import logging
import time
import uuid

import numpy as np
from faster_whisper import WhisperModel
from livekit import rtc
from livekit.agents import stt
from livekit.agents.stt.stt import NOT_GIVEN, NotGivenOr, APIConnectOptions
from livekit.agents.utils import is_given

logger = logging.getLogger(__name__)

# ── Whisper model singleton ──────────────────────────────

_whisper_model: WhisperModel | None = None


def _get_model(model_size: str = "tiny") -> WhisperModel:
    """Load and cache the faster-whisper model (one per process)."""
    global _whisper_model
    if _whisper_model is None:
        logger.info("Loading faster-whisper model: %s (this may take a moment)", model_size)
        _whisper_model = WhisperModel(model_size, device="cpu", compute_type="int8")
        logger.info("faster-whisper model loaded successfully")
    return _whisper_model


# ── LiveKit STT implementation ───────────────────────────


class LocalWhisperSTT(stt.STT):
    """
    LiveKit-compatible STT that transcribes audio locally via faster-whisper.

    No cloud API calls, no API keys, completely free.
    """

    def __init__(
        self,
        *,
        model_size: str = "tiny",
        language: str | None = None,
        detect_language: bool = True,
        beam_size: int = 5,
    ) -> None:
        super().__init__(
            capabilities=stt.STTCapabilities(
                streaming=False,
                interim_results=False,
            )
        )
        self._model_size = model_size
        self._language = language
        self._detect_language = detect_language
        self._beam_size = beam_size

    @property
    def model(self) -> str:
        return f"faster-whisper-{self._model_size}"

    @property
    def provider(self) -> str:
        return "local-whisper"

    def prewarm(self) -> None:
        """Pre-load the Whisper model so the first transcription is fast."""
        _get_model(self._model_size)

    async def _recognize_impl(
        self,
        buffer: stt.AudioBuffer,
        *,
        language: NotGivenOr[str] = NOT_GIVEN,
        conn_options: APIConnectOptions,
    ) -> stt.SpeechEvent:
        """
        Transcribe an audio buffer using the local faster-whisper model.

        Steps:
          1. Combine LiveKit audio frames → single AudioFrame
          2. Convert int16 PCM → float32 numpy array
          3. Run faster-whisper transcribe()
          4. Return a SpeechEvent with the transcript
        """
        try:
            # 1. Combine frames into a single frame
            combined = rtc.combine_audio_frames(buffer)

            # 2. Convert int16 PCM bytes → float32 numpy array (faster-whisper expects this)
            pcm_int16 = np.frombuffer(combined.data, dtype=np.int16)
            audio_float32 = pcm_int16.astype(np.float32) / 32768.0

            # If stereo, convert to mono by averaging channels
            if combined.num_channels > 1:
                audio_float32 = audio_float32.reshape(-1, combined.num_channels).mean(axis=1)

            # 3. Determine language
            lang = None
            if is_given(language):
                lang = language
            elif self._language:
                lang = self._language

            # 4. Transcribe with faster-whisper
            model = _get_model(self._model_size)

            start = time.perf_counter()
            segments_gen, info = model.transcribe(
                audio_float32,
                language=lang,
                beam_size=self._beam_size,
                vad_filter=True,  # filter out non-speech segments
            )

            # Collect all segments
            text_parts = []
            for seg in segments_gen:
                text_parts.append(seg.text.strip())

            full_text = " ".join(text_parts)
            elapsed = time.perf_counter() - start

            detected_lang = info.language or lang or "en"
            logger.debug(
                "Local Whisper STT: '%s' (lang=%s, %.2fs)",
                full_text[:80],
                detected_lang,
                elapsed,
            )

            return stt.SpeechEvent(
                type=stt.SpeechEventType.FINAL_TRANSCRIPT,
                request_id=uuid.uuid4().hex,
                alternatives=[
                    stt.SpeechData(
                        text=full_text,
                        language=detected_lang,
                        confidence=info.language_probability if info.language_probability else 0.9,
                    )
                ],
            )

        except Exception as e:
            logger.exception("Local Whisper STT failed: %s", e)
            raise

    async def aclose(self) -> None:
        pass
