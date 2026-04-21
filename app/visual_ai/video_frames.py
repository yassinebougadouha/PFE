"""
Video frame extraction helpers for Visual AI screenshare assistance.

Extracts low-FPS PNG frames from a single uploaded video using ffmpeg/ffprobe.
"""

from __future__ import annotations

import tempfile
import subprocess
import shutil
from pathlib import Path

from app.core.config import get_settings


def _binary_available(cmd: str) -> bool:
    if not cmd:
        return False
    if shutil.which(cmd):
        return True
    return Path(cmd).exists()


def _parse_fps(raw: str) -> float:
    value = (raw or "").strip()
    if not value:
        raise ValueError("Could not detect source FPS from ffprobe output")

    if "/" in value:
        num_s, den_s = value.split("/", 1)
        num = float(num_s)
        den = float(den_s)
        if den == 0:
            raise ValueError("Invalid ffprobe FPS denominator=0")
        fps = num / den
    else:
        fps = float(value)

    if fps <= 0:
        raise ValueError(f"Invalid source FPS: {fps}")
    return fps


def _parse_duration(raw: str) -> float:
    value = (raw or "").strip()
    if not value:
        raise ValueError("Could not detect video duration from ffprobe output")

    duration = float(value)
    if duration <= 0:
        raise ValueError(f"Invalid video duration: {duration}")
    return duration


def _guess_extension(mime_type: str) -> str:
    mt = (mime_type or "").lower()
    if mt == "video/mp4":
        return ".mp4"
    if mt == "video/webm":
        return ".webm"
    if mt in {"video/quicktime", "video/mov"}:
        return ".mov"
    if mt in {"video/x-matroska", "video/mkv"}:
        return ".mkv"
    return ".bin"


def extract_frames_from_video_bytes(
    video_bytes: bytes,
    *,
    mime_type: str,
    target_fps: float,
    max_frames: int,
    max_duration_seconds: float | None = None,
) -> tuple[list[tuple[bytes, str]], float]:
    """
    Extract PNG frames sampled at target_fps from uploaded video bytes.

    Returns:
        (frames, source_fps) where frames is [(frame_bytes, "image/png"), ...]
    """
    if not video_bytes:
        raise ValueError("Empty video payload")
    if target_fps <= 0:
        raise ValueError("target_fps must be positive")
    if max_frames <= 0:
        raise ValueError("max_frames must be positive")

    settings = get_settings()
    ffmpeg_bin = settings.VISUAL_SCREENSHARE_FFMPEG_BIN
    ffprobe_bin = settings.VISUAL_SCREENSHARE_FFPROBE_BIN

    if not _binary_available(ffmpeg_bin):
        raise ValueError(f"ffmpeg binary not found: {ffmpeg_bin}")
    if not _binary_available(ffprobe_bin):
        raise ValueError(f"ffprobe binary not found: {ffprobe_bin}")

    with tempfile.TemporaryDirectory(prefix="visual_screenshare_") as tmp:
        tmp_path = Path(tmp)
        input_path = tmp_path / f"input{_guess_extension(mime_type)}"
        input_path.write_bytes(video_bytes)

        probe_cmd = [
            ffprobe_bin,
            "-v",
            "error",
            "-select_streams",
            "v:0",
            "-show_entries",
            "stream=r_frame_rate",
            "-of",
            "default=nokey=1:noprint_wrappers=1",
            str(input_path),
        ]
        probe = subprocess.run(probe_cmd, capture_output=True, text=True, check=False)
        if probe.returncode != 0:
            raise ValueError(f"ffprobe failed: {probe.stderr.strip() or probe.stdout.strip()}")

        source_fps = _parse_fps(probe.stdout)

        duration_cmd = [
            ffprobe_bin,
            "-v",
            "error",
            "-show_entries",
            "format=duration",
            "-of",
            "default=nokey=1:noprint_wrappers=1",
            str(input_path),
        ]
        duration_probe = subprocess.run(duration_cmd, capture_output=True, text=True, check=False)
        if duration_probe.returncode != 0:
            raise ValueError(
                f"ffprobe duration check failed: {duration_probe.stderr.strip() or duration_probe.stdout.strip()}"
            )

        duration_seconds = _parse_duration(duration_probe.stdout)
        if max_duration_seconds is not None and duration_seconds > max_duration_seconds:
            raise ValueError(
                f"Video duration {duration_seconds:.2f}s exceeds max allowed {max_duration_seconds:.2f}s"
            )

        out_pattern = str(tmp_path / "frame_%06d.png")
        ffmpeg_cmd = [
            ffmpeg_bin,
            "-hide_banner",
            "-loglevel",
            "error",
            "-i",
            str(input_path),
            "-vf",
            f"fps={target_fps}",
            "-frames:v",
            str(max_frames),
            out_pattern,
        ]
        proc = subprocess.run(ffmpeg_cmd, capture_output=True, text=True, check=False)
        if proc.returncode != 0:
            raise ValueError(f"ffmpeg frame extraction failed: {proc.stderr.strip() or proc.stdout.strip()}")

        frame_files = sorted(tmp_path.glob("frame_*.png"))
        if not frame_files:
            raise ValueError("No frames extracted from uploaded video")

        frames = [(p.read_bytes(), "image/png") for p in frame_files]
        return frames, source_fps
