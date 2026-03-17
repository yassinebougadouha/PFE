"""
Screenshot filesystem store — save, retrieve, and delete screenshot images.
"""

from __future__ import annotations

import logging
import os
import uuid
from datetime import datetime, timezone
from pathlib import Path

from app.core.config import get_settings

logger = logging.getLogger(__name__)


def _get_screenshot_dir() -> Path:
    """Return the screenshot storage root, creating it if needed."""
    settings = get_settings()
    base = getattr(settings, "SCREENSHOT_DIR", "screenshots")
    path = Path(base)
    path.mkdir(parents=True, exist_ok=True)
    return path


def save_screenshot(
    image_bytes: bytes,
    filename: str,
    conversation_id: uuid.UUID | None = None,
    mime_type: str = "image/png",
) -> tuple[str, int]:
    """
    Save a screenshot to the filesystem.

    Returns:
        (file_path, file_size)
    """
    root = _get_screenshot_dir()

    # organise into sub-folders by conversation
    if conversation_id:
        folder = root / str(conversation_id)
    else:
        folder = root / "_unlinked"
    folder.mkdir(parents=True, exist_ok=True)

    # build unique filename
    ts = datetime.now(timezone.utc).strftime("%Y%m%d_%H%M%S")
    ext = _mime_to_ext(mime_type)
    safe_name = f"{ts}_{uuid.uuid4().hex[:8]}{ext}"
    file_path = folder / safe_name

    file_path.write_bytes(image_bytes)
    size = len(image_bytes)

    logger.info("Screenshot saved: %s (%d bytes)", file_path, size)
    return str(file_path), size


def read_screenshot(file_path: str) -> bytes:
    """Read screenshot bytes from the filesystem."""
    path = Path(file_path)
    if not path.exists():
        raise FileNotFoundError(f"Screenshot not found: {file_path}")
    return path.read_bytes()


def delete_screenshot(file_path: str) -> bool:
    """Delete a screenshot file. Returns True if deleted."""
    path = Path(file_path)
    if path.exists():
        path.unlink()
        logger.info("Screenshot deleted: %s", file_path)
        return True
    return False


def _mime_to_ext(mime_type: str) -> str:
    """Convert MIME type to file extension."""
    mapping = {
        "image/png": ".png",
        "image/jpeg": ".jpg",
        "image/jpg": ".jpg",
        "image/webp": ".webp",
        "image/gif": ".gif",
        "image/bmp": ".bmp",
    }
    return mapping.get(mime_type, ".png")
