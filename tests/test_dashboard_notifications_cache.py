import asyncio
import json
import os
import uuid
from types import SimpleNamespace

os.environ["DEBUG"] = "true"

import app.api.routes.dashboard as dashboard_routes
import app.api.routes.notifications as notification_routes


class DummyRedis:
    def __init__(self):
        self.store: dict[str, str] = {}
        self.set_calls: list[dict] = []
        self.deleted: list[str] = []

    async def get(self, key: str):
        return self.store.get(key)

    async def set(self, key: str, value: str, ttl: int | None = None):
        self.store[key] = value
        self.set_calls.append({"key": key, "value": value, "ttl": ttl})

    async def delete(self, key: str):
        self.deleted.append(key)
        self.store.pop(key, None)


def test_dashboard_summary_returns_cached_value_without_service_call(monkeypatch):
    user_id = uuid.uuid4()
    redis = DummyRedis()
    redis.store[f"dashboard:summary:{user_id}:days:30"] = json.dumps({"cached": True})

    class FailDashboardService:
        def __init__(self, _db):
            return

        async def get_summary(self, *_args, **_kwargs):
            raise AssertionError("DashboardService should not be called when cache is available")

    monkeypatch.setattr(dashboard_routes, "DashboardService", FailDashboardService)
    monkeypatch.setattr(
        dashboard_routes.DashboardSummaryResponse,
        "model_validate",
        classmethod(lambda _cls, payload: {"cached_payload": payload}),
    )

    result = asyncio.run(
        dashboard_routes.get_dashboard_summary(
            db=object(),
            redis=redis,
            current_user=SimpleNamespace(id=user_id),
            assisted_draft_days=30,
        )
    )

    assert result == {"cached_payload": {"cached": True}}


def test_dashboard_summary_cache_miss_stores_summary(monkeypatch):
    user_id = uuid.uuid4()
    redis = DummyRedis()
    calls: dict[str, object] = {}

    class DummySummary:
        def model_dump(self, mode="json"):
            assert mode == "json"
            return {"summary": "fresh"}

    dummy_summary = DummySummary()

    class StubDashboardService:
        def __init__(self, db):
            calls["db"] = db

        async def get_summary(self, current_user, assisted_draft_days=30):
            calls["user_id"] = current_user.id
            calls["days"] = assisted_draft_days
            return dummy_summary

    monkeypatch.setattr(dashboard_routes, "DashboardService", StubDashboardService)

    result = asyncio.run(
        dashboard_routes.get_dashboard_summary(
            db=object(),
            redis=redis,
            current_user=SimpleNamespace(id=user_id),
            assisted_draft_days=90,
        )
    )

    assert result is dummy_summary
    assert calls["user_id"] == user_id
    assert calls["days"] == 90
    assert len(redis.set_calls) == 1
    assert redis.set_calls[0]["key"] == f"dashboard:summary:{user_id}:days:90"
    assert json.loads(redis.set_calls[0]["value"]) == {"summary": "fresh"}
    assert redis.set_calls[0]["ttl"] >= 5


def test_notifications_unread_count_uses_cached_value(monkeypatch):
    user_id = uuid.uuid4()
    redis = DummyRedis()
    redis.store[f"notifications:unread-count:{user_id}"] = "11"

    class FailNotificationService:
        def __init__(self, _db):
            return

        async def get_unread_count(self, _user_id):
            raise AssertionError("NotificationService should not be called when cache is available")

    monkeypatch.setattr(notification_routes, "NotificationService", FailNotificationService)

    result = asyncio.run(
        notification_routes.get_unread_count(
            db=object(),
            redis=redis,
            current_user=SimpleNamespace(id=user_id),
        )
    )

    assert result == {"unread_count": 11}


def test_notifications_unread_count_cache_miss_sets_value(monkeypatch):
    user_id = uuid.uuid4()
    redis = DummyRedis()

    class StubNotificationService:
        def __init__(self, _db):
            return

        async def get_unread_count(self, requested_user_id):
            assert requested_user_id == user_id
            return 7

    monkeypatch.setattr(notification_routes, "NotificationService", StubNotificationService)

    result = asyncio.run(
        notification_routes.get_unread_count(
            db=object(),
            redis=redis,
            current_user=SimpleNamespace(id=user_id),
        )
    )

    assert result == {"unread_count": 7}
    assert len(redis.set_calls) == 1
    assert redis.set_calls[0]["key"] == f"notifications:unread-count:{user_id}"
    assert redis.set_calls[0]["value"] == "7"
    assert redis.set_calls[0]["ttl"] >= 5


def test_mark_all_notifications_read_invalidates_unread_count_cache(monkeypatch):
    user_id = uuid.uuid4()
    redis = DummyRedis()
    cache_key = f"notifications:unread-count:{user_id}"
    redis.store[cache_key] = "5"

    class StubNotificationService:
        def __init__(self, _db):
            return

        async def mark_all_read(self, requested_user_id):
            assert requested_user_id == user_id
            return 3

    monkeypatch.setattr(notification_routes, "NotificationService", StubNotificationService)

    result = asyncio.run(
        notification_routes.mark_all_notifications_read(
            db=object(),
            redis=redis,
            current_user=SimpleNamespace(id=user_id),
        )
    )

    assert result == {"message": "Marked 3 notifications as read"}
    assert cache_key in redis.deleted
