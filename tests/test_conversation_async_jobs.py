import asyncio
import json
import os
import uuid
from datetime import datetime, timezone
from types import SimpleNamespace

from fastapi import HTTPException

os.environ["DEBUG"] = "true"

from app.api.routes.conversations import (
    get_assisted_draft_job_status,
    get_conversation_summary_job_status,
    queue_assisted_draft_job,
    queue_conversation_summary_job,
)
from app.db.models.enums import ChannelType
from app.services.conversation_service import ConversationService
from app.workers.celery_app import celery_app


class DummyScalarResult:
    def __init__(self, value):
        self._value = value

    def scalar_one_or_none(self):
        return self._value


class DummyDb:
    def __init__(self, latest_message_id=None):
        self.latest_message_id = latest_message_id

    async def execute(self, _query):
        return DummyScalarResult(self.latest_message_id)


class DummyRedis:
    def __init__(self):
        self.store: dict[str, str] = {}
        self.set_calls: list[dict] = []

    async def get(self, key: str):
        return self.store.get(key)

    async def set(self, key: str, value: str, ttl: int | None = None):
        self.store[key] = value
        self.set_calls.append({"key": key, "value": value, "ttl": ttl})

    async def delete(self, key: str):
        self.store.pop(key, None)


class DummyTaskInvoker:
    def __init__(self, task_id: str):
        self.task_id = task_id
        self.calls: list[dict] = []

    def delay(self, **kwargs):
        self.calls.append(kwargs)
        return SimpleNamespace(id=self.task_id)


class DummyAsyncResult:
    def __init__(self, state: str, result):
        self.state = state
        self.result = result


def _conversation(conversation_id: uuid.UUID, channel: ChannelType = ChannelType.CHAT):
    return SimpleNamespace(
        id=conversation_id,
        user_id=uuid.uuid4(),
        channel=channel,
    )


def test_queue_assisted_draft_job_enqueues_and_stores_metadata(monkeypatch):
    conversation_id = uuid.uuid4()
    current_user_id = uuid.uuid4()
    redis = DummyRedis()

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return _conversation(conversation_id, ChannelType.CHAT)

    task_invoker = DummyTaskInvoker(task_id="job-assisted-1")

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(
        "app.workers.tasks.generate_conversation_assisted_draft_job_task",
        task_invoker,
    )

    result = asyncio.run(
        queue_assisted_draft_job(
            conversation_id=conversation_id,
            db=DummyDb(latest_message_id=uuid.uuid4()),
            redis=redis,
            current_user=SimpleNamespace(id=current_user_id),
        )
    )

    assert result.job_id == "job-assisted-1"
    assert result.job_type == "assisted_draft"
    assert task_invoker.calls == [
        {
            "conversation_id": str(conversation_id),
            "requested_by_user_id": str(current_user_id),
        }
    ]

    assert len(redis.set_calls) == 1
    cached_payload = json.loads(redis.set_calls[0]["value"])
    assert cached_payload == {
        "conversation_id": str(conversation_id),
        "job_type": "assisted_draft",
    }


def test_queue_conversation_summary_job_enqueues_and_stores_metadata(monkeypatch):
    conversation_id = uuid.uuid4()
    redis = DummyRedis()

    async def fake_get_conversation(self, requested_id):
        assert requested_id == conversation_id
        return _conversation(conversation_id, ChannelType.WHATSAPP)

    task_invoker = DummyTaskInvoker(task_id="job-summary-1")

    monkeypatch.setattr(ConversationService, "get_conversation", fake_get_conversation)
    monkeypatch.setattr(
        "app.workers.tasks.generate_conversation_summary_job_task",
        task_invoker,
    )

    result = asyncio.run(
        queue_conversation_summary_job(
            conversation_id=conversation_id,
            db=DummyDb(),
            redis=redis,
            _=SimpleNamespace(id=uuid.uuid4()),
            max_messages=90,
        )
    )

    assert result.job_id == "job-summary-1"
    assert result.job_type == "summary"
    assert task_invoker.calls == [
        {
            "conversation_id": str(conversation_id),
            "max_messages": 90,
        }
    ]

    assert len(redis.set_calls) == 1
    cached_payload = json.loads(redis.set_calls[0]["value"])
    assert cached_payload == {
        "conversation_id": str(conversation_id),
        "job_type": "summary",
    }


def test_get_assisted_draft_job_status_succeeded_returns_payload(monkeypatch):
    conversation_id = uuid.uuid4()
    job_id = "job-assisted-status-1"
    redis = DummyRedis()
    redis.store[f"conversation:ai-job:{job_id}"] = json.dumps(
        {
            "conversation_id": str(conversation_id),
            "job_type": "assisted_draft",
        }
    )

    payload = {
        "job_type": "assisted_draft",
        "conversation_id": str(conversation_id),
        "result": {
            "conversation_id": str(conversation_id),
            "source_message_id": str(uuid.uuid4()),
            "draft": "Suggested operator response",
            "language": "en",
            "generated_at": datetime.now(timezone.utc).isoformat(),
        },
    }

    monkeypatch.setattr(
        celery_app,
        "AsyncResult",
        lambda _job_id: DummyAsyncResult(state="SUCCESS", result=payload),
    )

    result = asyncio.run(
        get_assisted_draft_job_status(
            conversation_id=conversation_id,
            job_id=job_id,
            redis=redis,
            _=SimpleNamespace(id=uuid.uuid4()),
        )
    )

    assert result.status == "succeeded"
    assert result.assisted_draft is not None
    assert result.assisted_draft.conversation_id == conversation_id
    assert result.assisted_draft.draft == "Suggested operator response"


def test_get_conversation_summary_job_status_failed_returns_error(monkeypatch):
    conversation_id = uuid.uuid4()
    job_id = "job-summary-status-1"
    redis = DummyRedis()
    redis.store[f"conversation:ai-job:{job_id}"] = json.dumps(
        {
            "conversation_id": str(conversation_id),
            "job_type": "summary",
        }
    )

    monkeypatch.setattr(
        celery_app,
        "AsyncResult",
        lambda _job_id: DummyAsyncResult(state="FAILURE", result=RuntimeError("summary exploded")),
    )

    result = asyncio.run(
        get_conversation_summary_job_status(
            conversation_id=conversation_id,
            job_id=job_id,
            redis=redis,
            _=SimpleNamespace(id=uuid.uuid4()),
        )
    )

    assert result.status == "failed"
    assert result.error is not None
    assert "summary exploded" in result.error


def test_get_conversation_summary_job_status_rejects_mismatched_meta(monkeypatch):
    conversation_id = uuid.uuid4()
    other_conversation_id = uuid.uuid4()
    job_id = "job-summary-status-2"
    redis = DummyRedis()
    redis.store[f"conversation:ai-job:{job_id}"] = json.dumps(
        {
            "conversation_id": str(other_conversation_id),
            "job_type": "summary",
        }
    )

    monkeypatch.setattr(
        celery_app,
        "AsyncResult",
        lambda _job_id: DummyAsyncResult(state="PENDING", result=None),
    )

    try:
        asyncio.run(
            get_conversation_summary_job_status(
                conversation_id=conversation_id,
                job_id=job_id,
                redis=redis,
                _=SimpleNamespace(id=uuid.uuid4()),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 404
        assert exc.detail == "Job not found"
    else:
        raise AssertionError("Expected mismatched job metadata to be rejected")
