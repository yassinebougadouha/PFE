"""
Local TTS plugin for LiveKit — uses edge-tts (Microsoft Edge neural voices).

Free, no API keys, high-quality neural voices.
Same engine used by the backend's /api/v1/voice/synthesize endpoint.
"""

from __future__ import annotations

import logging
import uuid
from dataclasses import dataclass

import edge_tts
from livekit.agents import tts
from livekit.agents.tts.tts import DEFAULT_API_CONNECT_OPTIONS

logger = logging.getLogger(__name__)

# edge-tts outputs MP3 at 24 kHz mono
_SAMPLE_RATE = 24000
_NUM_CHANNELS = 1


# ── Voice name mapping: Google voice IDs → edge-tts voice names ──

VOICE_MAP: dict[str, str] = {
    "Puck": "en-US-GuyNeural",        # Tom (StarterAgent) — male
    "Charon": "en-US-ChristopherNeural",  # Mike (SupportAgent) — male
    "Aoede": "en-US-JennyNeural",     # Jessica (BookingAgent) — female
    "Kore": "en-US-AriaNeural",       # Ameni (FAQAgent) — female
}

DEFAULT_VOICE = "en-GB-RyanNeural"


@dataclass
class _EdgeTTSOptions:
    voice: str
    rate: str
    pitch: str


class EdgeTTS(tts.TTS):
    """
    LiveKit-compatible TTS that synthesizes audio locally via edge-tts.

    Uses Microsoft Edge's neural TTS voices — free, no API keys needed.
    Outputs MP3 audio which LiveKit's AudioEmitter decodes automatically.
    """

    def __init__(
        self,
        *,
        voice: str = DEFAULT_VOICE,
        rate: str = "+0%",
        pitch: str = "+0Hz",
    ) -> None:
        super().__init__(
            capabilities=tts.TTSCapabilities(streaming=False),
            sample_rate=_SAMPLE_RATE,
            num_channels=_NUM_CHANNELS,
        )
        self._opts = _EdgeTTSOptions(voice=voice, rate=rate, pitch=pitch)

    @property
    def model(self) -> str:
        return "edge-tts"

    @property
    def provider(self) -> str:
        return "microsoft-edge"

    def synthesize(
        self,
        text: str,
        *,
        conn_options: tts.APIConnectOptions = DEFAULT_API_CONNECT_OPTIONS,
    ) -> _EdgeChunkedStream:
        return _EdgeChunkedStream(
            tts_instance=self,
            input_text=text,
            conn_options=conn_options,
            opts=self._opts,
        )

    async def aclose(self) -> None:
        pass


class _EdgeChunkedStream(tts.ChunkedStream):
    """Streams MP3 chunks from edge-tts into the LiveKit audio pipeline."""

    def __init__(
        self,
        *,
        tts_instance: EdgeTTS,
        input_text: str,
        conn_options: tts.APIConnectOptions,
        opts: _EdgeTTSOptions,
    ) -> None:
        super().__init__(
            tts=tts_instance,
            input_text=input_text,
            conn_options=conn_options,
        )
        self._opts = opts

    async def _run(self, output_emitter: tts.AudioEmitter) -> None:
        """
        Stream audio from edge-tts and push MP3 chunks to the emitter.

        edge-tts yields dict chunks:
          {"type": "audio", "data": bytes}  — MP3 audio data
          {"type": "SentenceBoundary", ...} — text boundary metadata (ignored)
        """
        request_id = uuid.uuid4().hex

        communicate = edge_tts.Communicate(
            self.input_text,
            self._opts.voice,
            rate=self._opts.rate,
            pitch=self._opts.pitch,
        )

        output_emitter.initialize(
            request_id=request_id,
            sample_rate=_SAMPLE_RATE,
            num_channels=_NUM_CHANNELS,
            mime_type="audio/mpeg",  # MP3 — AudioEmitter will decode
        )

        chunk_count = 0
        try:
            async for chunk in communicate.stream():
                if chunk["type"] == "audio":
                    data = chunk["data"]
                    if data:
                        output_emitter.push(data)
                        chunk_count += 1

            output_emitter.flush()

            logger.debug(
                "Edge TTS: synthesized %d MP3 chunks for '%s' (voice=%s)",
                chunk_count,
                self.input_text[:60],
                self._opts.voice,
            )

        except Exception as e:
            logger.exception("Edge TTS synthesis failed: %s", e)
            raise
