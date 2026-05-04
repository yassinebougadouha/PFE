"""
Call Audio Recorder — captures all audio from a LiveKit room and
saves it as a single WAV file on disk.

Listens to audio tracks from the room, collects PCM frames from both
participants (user + agent), and mixes them into a mono WAV.

Usage:
    recorder = CallAudioRecorder(room)
    # ... room runs ...
    file_path = await recorder.finalize("room-name")
"""

from __future__ import annotations

import asyncio
import io
import logging
import os
import struct
import time
import wave
from datetime import datetime, timezone
from pathlib import Path

import numpy as np
from livekit import rtc

from voice_agents.config import get_voice_settings

logger = logging.getLogger(__name__)

# Target sample rate for WAV output
TARGET_SAMPLE_RATE = 16000
TARGET_CHANNELS = 1


class CallAudioRecorder:
    """
    Records all audio tracks in a LiveKit room and mixes them
    into a single mono WAV file.
    """

    def __init__(self) -> None:
        self._participant_buffers: dict[str, list[np.ndarray]] = {}
        self._participant_start_samples: dict[str, int] = {}
        self._captured_track_ids: set[str] = set()
        self._lock = asyncio.Lock()
        self._active_streams: list[asyncio.Task] = []
        self._recording = True
        self._start_time: float = time.time()

    # ── Track subscription handler ────────────────────────

    def on_track_subscribed(
        self,
        track: rtc.Track,
        publication: rtc.RemoteTrackPublication,
        participant: rtc.RemoteParticipant,
    ) -> None:
        """
        Called when a new track is subscribed in the room.
        If it's an audio track, start capturing frames.
        """
        if track.kind != rtc.TrackKind.KIND_AUDIO:
            return

        logger.info(
            "Recording audio track from participant %s (track %s)",
            participant.identity,
            getattr(track, "sid", "unknown"),
        )

        self._start_capture(track, f"remote:{participant.identity}")

    def on_local_track_published(self, track: rtc.Track) -> None:
        """Called when the local agent publishes a track in the room."""
        if track.kind != rtc.TrackKind.KIND_AUDIO:
            return

        logger.info("Recording local agent audio track %s", getattr(track, "sid", "unknown"))
        self._start_capture(track, "local:agent")

    def on_local_track_subscribed(self, track: rtc.Track) -> None:
        """Compatibility hook when local track subscription events fire."""
        self.on_local_track_published(track)

    def capture_existing_tracks(self, room: rtc.Room) -> None:
        """
        Attach to audio tracks that were already published before event handlers
        fired. LiveKit agent jobs often join a room after the caller's mic track
        already exists, and the agent output track is usually published during
        session startup.
        """
        if room is None:
            return

        try:
            remote_participants = getattr(room, "remote_participants", {}) or {}
            participants = (
                remote_participants.values()
                if hasattr(remote_participants, "values")
                else remote_participants
            )
            for participant in participants:
                identity = getattr(participant, "identity", "unknown")
                self._capture_publications_for_participant(
                    participant,
                    f"remote:{identity}",
                )
        except Exception as exc:
            logger.debug("Failed to inspect existing remote tracks: %s", exc)

        try:
            local_participant = getattr(room, "local_participant", None)
            if local_participant is not None:
                self._capture_publications_for_participant(local_participant, "local:agent")
        except Exception as exc:
            logger.debug("Failed to inspect existing local tracks: %s", exc)

    def _capture_publications_for_participant(self, participant, source_id: str) -> None:
        publications = getattr(participant, "track_publications", None) or {}
        values = publications.values() if hasattr(publications, "values") else publications

        for publication in values:
            track = getattr(publication, "track", None)
            kind = getattr(track, "kind", None) or getattr(publication, "kind", None)
            if kind != rtc.TrackKind.KIND_AUDIO or track is None:
                continue

            logger.info(
                "Recording existing audio track from %s (track %s)",
                source_id,
                getattr(track, "sid", "unknown"),
            )
            self._start_capture(track, source_id)

    def _start_capture(self, track: rtc.Track, source_id: str) -> None:
        """Start capture for a track once; duplicate events are ignored."""
        track_id = getattr(track, "sid", None) or f"track-{id(track)}"
        if track_id in self._captured_track_ids:
            return

        self._captured_track_ids.add(track_id)
        task = asyncio.create_task(self._capture_audio_stream(track, source_id))
        self._active_streams.append(task)

    async def _capture_audio_stream(
        self, track: rtc.Track, participant_id: str
    ) -> None:
        """Read audio frames from a track and append to the buffer."""
        try:
            audio_stream = rtc.AudioStream(
                track,
                sample_rate=TARGET_SAMPLE_RATE,
                num_channels=TARGET_CHANNELS,
            )
            async for event in audio_stream:
                if not self._recording:
                    break

                frame = event.frame
                # Convert int16 PCM to numpy array
                pcm_int16 = np.frombuffer(frame.data, dtype=np.int16)
                audio_float = pcm_int16.astype(np.float32)

                # If stereo, convert to mono
                if frame.num_channels > 1:
                    audio_float = audio_float.reshape(-1, frame.num_channels).mean(axis=1)

                # Resample if needed (simple decimation/interpolation)
                if frame.sample_rate != TARGET_SAMPLE_RATE:
                    ratio = TARGET_SAMPLE_RATE / frame.sample_rate
                    new_length = int(len(audio_float) * ratio)
                    indices = np.linspace(0, len(audio_float) - 1, new_length)
                    audio_float = np.interp(indices, np.arange(len(audio_float)), audio_float)

                async with self._lock:
                    if participant_id not in self._participant_start_samples:
                        start_offset = max(int((time.time() - self._start_time) * TARGET_SAMPLE_RATE), 0)
                        self._participant_start_samples[participant_id] = start_offset

                    self._participant_buffers.setdefault(participant_id, []).append(audio_float)

        except Exception as exc:
            logger.warning(
                "Audio capture stopped for %s: %s", participant_id, exc
            )

    # ── Finalization ─────────────────────────────────────────

    async def finalize(self, room_name: str) -> str | None:
        """
        Stop recording, mix all audio, and save to a WAV file.

        Returns the absolute path to the WAV file, or None if no audio.
        """
        self._recording = False

        # Wait for active streams to finish (with timeout)
        if self._active_streams:
            done, pending = await asyncio.wait(self._active_streams, timeout=3.0)
            if pending:
                logger.warning(
                    "Timed out waiting for %d audio capture task(s); saving buffered audio.",
                    len(pending),
                )
                for task in pending:
                    task.cancel()

                await asyncio.gather(*pending, return_exceptions=True)
            if done:
                await asyncio.gather(*done, return_exceptions=True)

        async with self._lock:
            if not self._participant_buffers:
                logger.info("No audio frames captured — skipping WAV save.")
                return None

            participant_timelines: list[np.ndarray] = []
            participant_labels: list[str] = []
            for participant_id, chunks in self._participant_buffers.items():
                if not chunks:
                    continue

                timeline = np.concatenate(chunks)
                start_offset = self._participant_start_samples.get(participant_id, 0)
                if start_offset > 0:
                    timeline = np.pad(timeline, (start_offset, 0), mode="constant")

                participant_timelines.append(timeline)
                participant_labels.append(participant_id)

            if not participant_timelines:
                logger.info("Audio streams were opened but no frames were captured — skipping WAV save.")
                return None

            max_length = max(len(timeline) for timeline in participant_timelines)
            mixed_audio = np.zeros(max_length, dtype=np.float32)
            for timeline in participant_timelines:
                mixed_audio[: len(timeline)] += timeline

            # Normalize by participant count to avoid clipping after summing.
            all_audio = mixed_audio / float(len(participant_timelines))

        # Convert back to int16 for WAV
        all_audio = np.clip(all_audio, -32768, 32767)
        pcm_int16 = all_audio.astype(np.int16)

        # Determine output path
        settings = get_voice_settings()
        recordings_dir = Path(settings.voice_recordings_dir)
        recordings_dir.mkdir(parents=True, exist_ok=True)

        timestamp = datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S")
        safe_room = room_name.replace("/", "_").replace("\\", "_").replace(" ", "_")
        filename = f"{safe_room}_{timestamp}.wav"
        file_path = recordings_dir / filename

        # Write WAV file
        with wave.open(str(file_path), "wb") as wf:
            wf.setnchannels(TARGET_CHANNELS)
            wf.setsampwidth(2)  # 16-bit
            wf.setframerate(TARGET_SAMPLE_RATE)
            wf.writeframes(pcm_int16.tobytes())

        duration = len(pcm_int16) / TARGET_SAMPLE_RATE
        logger.info(
            "Audio saved: %s (%.1f seconds, %.1f MB, sources=%s)",
            file_path,
            duration,
            file_path.stat().st_size / (1024 * 1024),
            ",".join(participant_labels),
        )

        return str(file_path.resolve())

    def get_duration(self) -> float:
        """Return elapsed recording time in seconds."""
        return time.time() - self._start_time
