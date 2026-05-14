"""Pytest configuration for repository-wide imports.

Ensures the project root is available on sys.path so tests can import the
FastAPI app package when pytest is executed inside the Docker container.
"""

from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))
