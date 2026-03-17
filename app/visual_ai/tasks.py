"""
Celery tasks for the Visual AI module.

Background tasks for screenshot analysis and batch reprocessing.
Uses synchronous psycopg2 since Celery workers are synchronous.
"""

from __future__ import annotations

import logging
import uuid
import time
import json

import psycopg2
import psycopg2.extras
from celery import shared_task

from app.core.config import get_settings

logger = logging.getLogger(__name__)

# ── Sync DB URL for Celery workers ───────────────────────
_settings = get_settings()
SYNC_DB_URL = _settings.DATABASE_URL.replace(
    "postgresql+asyncpg://", "postgresql://"
)


def _get_sync_conn():
    """Create a synchronous psycopg2 connection."""
    return psycopg2.connect(SYNC_DB_URL)


def _run_sync_analysis(image_bytes: bytes, provider_name: str | None = None) -> dict:
    """
    Run visual analysis synchronously (for Celery worker context).
    Uses asyncio.run() to bridge the async provider interface.
    """
    import asyncio
    from app.visual_ai.providers import get_visual_provider

    provider = get_visual_provider(provider_name)
    loop = asyncio.new_event_loop()
    try:
        result = loop.run_until_complete(provider.full_analysis(image_bytes))
        return result.model_dump()
    finally:
        loop.close()


@shared_task(
    name="app.visual_ai.tasks.analyze_screenshot_task",
    bind=True,
    max_retries=3,
    default_retry_delay=30,
    queue="visual",
)
def analyze_screenshot_task(
    self,
    screenshot_id: str,
    provider_name: str | None = None,
):
    """
    Celery task: analyze a stored screenshot in the background.

    1. Read screenshot bytes from disk
    2. Run provider analysis
    3. Store VisualAnalysis record
    4. Return result summary
    """
    logger.info("Task: analyzing screenshot %s with provider=%s", screenshot_id, provider_name)

    try:
        conn = _get_sync_conn()
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)

        # 1. Fetch screenshot record
        cur.execute(
            "SELECT id, file_path, conversation_id FROM screenshots WHERE id = %s",
            (screenshot_id,),
        )
        screenshot = cur.fetchone()
        if not screenshot:
            logger.warning("Screenshot %s not found", screenshot_id)
            return {"status": "not_found", "screenshot_id": screenshot_id}

        # 2. Read image bytes
        from app.visual_ai.screenshot_store import read_screenshot
        image_bytes = read_screenshot(screenshot["file_path"])

        # 3. Run analysis
        start = time.perf_counter()
        result = _run_sync_analysis(image_bytes, provider_name)
        elapsed_ms = int((time.perf_counter() - start) * 1000)

        # 4. Store analysis record
        analysis_id = str(uuid.uuid4())
        ocr_data = result.get("ocr", {})
        ui_data = result.get("ui_analysis", {})
        embedding = result.get("embedding", [])

        cur.execute(
            """
            INSERT INTO visual_analyses
                (id, screenshot_id, provider, ocr_text, caption, elements, labels, regions,
                 embedding, confidence, processing_ms, raw_result, created_at, updated_at)
            VALUES
                (%s, %s, %s, %s, %s, %s, %s, %s,
                 %s, %s, %s, %s, NOW(), NOW())
            """,
            (
                analysis_id,
                screenshot_id,
                result.get("provider", provider_name or "unknown"),
                ocr_data.get("text"),
                ui_data.get("caption"),
                json.dumps(ui_data.get("elements", [])),
                json.dumps(ui_data.get("labels", [])),
                json.dumps(ui_data.get("regions", [])),
                str(embedding) if embedding else None,
                result.get("confidence"),
                elapsed_ms,
                json.dumps(result.get("raw_result")),
            ),
        )

        conn.commit()
        cur.close()
        conn.close()

        logger.info(
            "Task complete: screenshot=%s analysis=%s provider=%s in %dms",
            screenshot_id, analysis_id, result.get("provider"), elapsed_ms,
        )

        return {
            "status": "completed",
            "screenshot_id": screenshot_id,
            "analysis_id": analysis_id,
            "provider": result.get("provider"),
            "processing_ms": elapsed_ms,
            "ocr_length": len(ocr_data.get("text", "")),
            "element_count": len(ui_data.get("elements", [])),
        }

    except Exception as exc:
        logger.error("Task failed for screenshot %s: %s", screenshot_id, exc, exc_info=True)
        raise self.retry(exc=exc)


@shared_task(
    name="app.visual_ai.tasks.batch_reanalyze_task",
    bind=True,
    max_retries=1,
    queue="visual",
)
def batch_reanalyze_task(
    self,
    screenshot_ids: list[str],
    provider_name: str | None = None,
):
    """
    Celery task: reanalyze multiple screenshots in sequence.
    Useful for reprocessing after provider update or model change.
    """
    logger.info("Task: batch reanalyze %d screenshots", len(screenshot_ids))
    results = []

    for sid in screenshot_ids:
        try:
            result = analyze_screenshot_task.apply(
                args=[sid, provider_name],
            ).get(timeout=120)
            results.append(result)
        except Exception as e:
            logger.error("Batch reanalyze failed for %s: %s", sid, e)
            results.append({"status": "failed", "screenshot_id": sid, "error": str(e)})

    succeeded = sum(1 for r in results if r.get("status") == "completed")
    failed = len(results) - succeeded

    logger.info("Batch complete: %d succeeded, %d failed", succeeded, failed)
    return {
        "status": "completed",
        "total": len(screenshot_ids),
        "succeeded": succeeded,
        "failed": failed,
        "results": results,
    }
