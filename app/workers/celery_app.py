"""
Celery application instance.
"""

from celery import Celery

from app.core.config import get_settings

settings = get_settings()

celery_app = Celery(
    "support_worker",
    broker=settings.CELERY_BROKER_URL,
    backend=settings.CELERY_RESULT_BACKEND,
)

celery_app.conf.update(
    task_serializer="json",
    accept_content=["json"],
    result_serializer="json",
    timezone="UTC",
    enable_utc=True,
    task_track_started=True,
    task_acks_late=True,
    worker_prefetch_multiplier=1,
    # Task routing for scaling specific queues
    task_routes={
        "app.workers.tasks.process_email_task": {"queue": "emails"},
        "app.workers.tasks.log_action_task": {"queue": "logging"},
        "app.workers.tasks.sync_gmail_for_user_task": {"queue": "gmail"},
        "app.workers.tasks.sync_all_gmail_accounts": {"queue": "gmail"},
        "app.workers.tasks.process_whatsapp_incoming_task": {"queue": "whatsapp"},
        "app.workers.tasks.record_whatsapp_outbound_task": {"queue": "whatsapp"},
        "app.decision_engine.tasks.analyze_ticket_task": {"queue": "decision"},
        "app.rag.tasks.index_article_task": {"queue": "rag"},
        "app.rag.tasks.reindex_all_articles_task": {"queue": "rag"},
        "app.visual_ai.tasks.analyze_screenshot_task": {"queue": "visual"},
        "app.visual_ai.tasks.batch_reanalyze_task": {"queue": "visual"},
    },
    # Celery Beat schedule — periodic tasks
    beat_schedule={
        "sync-all-gmail-accounts": {
            "task": "app.workers.tasks.sync_all_gmail_accounts",
            "schedule": settings.GMAIL_POLL_INTERVAL_SECONDS,
        },
    },
)

celery_app.autodiscover_tasks(["app.workers", "app.decision_engine", "app.rag", "app.visual_ai"])
