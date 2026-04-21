"""
Development launcher for Celery worker/beat with backend file watching.

It keeps Celery containers aligned with live backend code in the same way the
API container stays current through bind mounts and auto-reload.
"""

from __future__ import annotations

import os
import signal
import subprocess
import sys
from pathlib import Path

from watchfiles import watch

ROOT = Path(__file__).resolve().parents[1]
WATCH_ROOTS = [
    ROOT / "app",
    ROOT / "alembic",
    ROOT / "scripts",
    ROOT / "main.py",
    ROOT / ".env",
    ROOT / "docker-compose.yml",
    ROOT / "requirements.txt",
]
WATCH_SUFFIXES = {".py", ".env", ".yml", ".yaml", ".ini", ".toml"}
WATCH_NAMES = {"Dockerfile", "docker-compose.yml", "requirements.txt", ".env", "main.py"}
IGNORED_PARTS = {
    ".git",
    ".pytest_cache",
    ".venv",
    "__pycache__",
    "frontend",
    "node_modules",
    "recordings",
    "screenshots",
    "uploads",
}
ROLE_COMMANDS = {
    "worker": [
        "celery",
        "-A",
        "app.workers.celery_app",
        "worker",
        "--loglevel=info",
        "-Q",
        "emails,logging,gmail,whatsapp,decision,rag,celery",
    ],
    "beat": [
        "celery",
        "-A",
        "app.workers.celery_app",
        "beat",
        "--loglevel=info",
    ],
}


def _normalize_path(path: str) -> Path:
    try:
        return Path(path).resolve()
    except OSError:
        return Path(path)


def _is_interesting(path: str) -> bool:
    normalized = _normalize_path(path)
    if any(part in IGNORED_PARTS for part in normalized.parts):
        return False
    return normalized.name in WATCH_NAMES or normalized.suffix in WATCH_SUFFIXES


def _start_process(command: list[str]) -> subprocess.Popen[bytes]:
    print(f"[dev_celery] starting: {' '.join(command)}", flush=True)
    return subprocess.Popen(command, cwd=ROOT)


def _stop_process(process: subprocess.Popen[bytes] | None) -> None:
    if process is None or process.poll() is not None:
        return

    process.terminate()
    try:
        process.wait(timeout=15)
    except subprocess.TimeoutExpired:
        process.kill()
        process.wait(timeout=5)


def _run(role: str) -> int:
    command = ROLE_COMMANDS[role]
    process = _start_process(command)
    stop_requested = False

    def _handle_shutdown(signum, _frame):
        nonlocal stop_requested
        print(f"[dev_celery] received signal {signum}, stopping {role}", flush=True)
        stop_requested = True
        _stop_process(process)

    signal.signal(signal.SIGINT, _handle_shutdown)
    signal.signal(signal.SIGTERM, _handle_shutdown)

    watch_paths = [str(path) for path in WATCH_ROOTS if path.exists()]

    for changes in watch(
        *watch_paths,
        recursive=True,
        debounce=750,
        step=1000,
        yield_on_timeout=True,
    ):
        if stop_requested:
            break

        if process.poll() is not None:
            print(f"[dev_celery] {role} exited with code {process.returncode}", flush=True)
            return int(process.returncode or 0)

        interesting = sorted(
            str(_normalize_path(changed_path).relative_to(ROOT))
            for _change, changed_path in changes
            if _is_interesting(changed_path)
        )
        if not interesting:
            continue

        print(
            f"[dev_celery] detected backend changes for {role}: {', '.join(interesting[:5])}",
            flush=True,
        )
        _stop_process(process)
        process = _start_process(command)

    _stop_process(process)
    return int(process.returncode or 0)


def main() -> int:
    if len(sys.argv) != 2 or sys.argv[1] not in ROLE_COMMANDS:
        valid = ", ".join(sorted(ROLE_COMMANDS))
        print(f"Usage: python scripts/dev_celery.py <{valid}>", file=sys.stderr)
        return 2

    os.chdir(ROOT)
    return _run(sys.argv[1])


if __name__ == "__main__":
    raise SystemExit(main())
