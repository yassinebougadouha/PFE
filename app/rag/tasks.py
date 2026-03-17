"""
Celery tasks for the RAG knowledge base module.

Background tasks for article indexing and bulk reindexing.
"""

from __future__ import annotations

import logging
import uuid

import psycopg2
import psycopg2.extras
from celery import shared_task

from app.core.config import get_settings
from app.rag.chunker import chunk_text
from app.rag.embeddings import embed_texts

logger = logging.getLogger(__name__)

# ── Convert async DB URL to sync for Celery worker ───────
_settings = get_settings()
SYNC_DB_URL = _settings.DATABASE_URL.replace(
    "postgresql+asyncpg://", "postgresql://"
)


def _get_sync_conn():
    """Create a synchronous psycopg2 connection for Celery tasks."""
    return psycopg2.connect(SYNC_DB_URL)


@shared_task(
    name="app.rag.tasks.index_article_task",
    bind=True,
    max_retries=3,
    default_retry_delay=30,
    queue="rag",
)
def index_article_task(self, article_id: str, chunk_size: int = 512, chunk_overlap: int = 64):
    """
    Celery task: index a single article (chunk + embed).

    Uses sync psycopg2 since Celery workers are synchronous.
    """
    article_uuid = uuid.UUID(article_id)
    logger.info("Task: indexing article %s", article_uuid)

    try:
        conn = _get_sync_conn()
        cur = conn.cursor(cursor_factory=psycopg2.extras.RealDictCursor)

        # 1. Fetch article content
        cur.execute(
            "SELECT id, content FROM knowledge_articles WHERE id = %s AND is_deleted = FALSE",
            (str(article_uuid),),
        )
        article = cur.fetchone()
        if not article:
            logger.warning("Article %s not found or deleted", article_uuid)
            return {"status": "not_found", "article_id": article_id}

        content = article["content"]

        # 2. Delete existing chunks
        cur.execute(
            "DELETE FROM article_chunks WHERE article_id = %s",
            (str(article_uuid),),
        )

        # 3. Chunk the content
        chunks = chunk_text(content, chunk_size=chunk_size, chunk_overlap=chunk_overlap)
        if not chunks:
            cur.execute(
                "UPDATE knowledge_articles SET is_indexed = FALSE, chunk_count = 0, total_tokens = 0 WHERE id = %s",
                (str(article_uuid),),
            )
            conn.commit()
            cur.close()
            conn.close()
            return {"status": "empty", "article_id": article_id, "chunks": 0}

        # 4. Generate embeddings in batch
        chunk_texts = [c.content for c in chunks]
        embeddings = embed_texts(chunk_texts)

        # 5. Insert chunks with embeddings
        total_tokens = 0
        for chunk, embedding in zip(chunks, embeddings):
            chunk_id = str(uuid.uuid4())
            embedding_str = "[" + ",".join(str(v) for v in embedding) + "]"
            cur.execute(
                """
                INSERT INTO article_chunks
                    (id, article_id, chunk_index, content, token_count, embedding, status, created_at, updated_at)
                VALUES
                    (%s, %s, %s, %s, %s, %s::vector, 'INDEXED', NOW(), NOW())
                """,
                (chunk_id, str(article_uuid), chunk.index, chunk.content, chunk.token_count, embedding_str),
            )
            total_tokens += chunk.token_count

        # 6. Update article metadata
        cur.execute(
            "UPDATE knowledge_articles SET is_indexed = TRUE, chunk_count = %s, total_tokens = %s WHERE id = %s",
            (len(chunks), total_tokens, str(article_uuid)),
        )

        conn.commit()
        cur.close()
        conn.close()

        logger.info(
            "Task completed: article %s → %d chunks, %d tokens",
            article_uuid, len(chunks), total_tokens,
        )

        return {
            "status": "indexed",
            "article_id": article_id,
            "chunks": len(chunks),
            "tokens": total_tokens,
        }

    except Exception as exc:
        logger.error("Task failed for article %s: %s", article_id, exc)
        raise self.retry(exc=exc)


@shared_task(
    name="app.rag.tasks.reindex_all_articles_task",
    bind=True,
    max_retries=1,
    queue="rag",
)
def reindex_all_articles_task(self, article_ids: list[str]):
    """
    Celery task: reindex multiple articles sequentially.
    Dispatches individual index_article_task for each article.
    """
    logger.info("Task: reindexing %d articles", len(article_ids))

    results = []
    for aid in article_ids:
        try:
            result = index_article_task(aid)
            results.append(result)
        except Exception as exc:
            logger.error("Failed to reindex article %s: %s", aid, exc)
            results.append({"status": "failed", "article_id": aid, "error": str(exc)})

    success = sum(1 for r in results if r.get("status") == "indexed")
    failed = len(results) - success

    logger.info("Reindex complete: %d success, %d failed", success, failed)

    return {
        "total": len(article_ids),
        "success": success,
        "failed": failed,
        "results": results,
    }
