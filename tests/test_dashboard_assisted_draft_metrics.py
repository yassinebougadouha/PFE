import asyncio
import uuid
from datetime import datetime, timedelta, timezone
from types import SimpleNamespace

from app.db.models.enums import ChannelType, UserRole
from app.services.dashboard_service import DashboardService


class DummyScalarResult:
    def __init__(self, rows):
        self._rows = rows

    def all(self):
        return self._rows


class DummyResult:
    def __init__(self, *, scalar_rows=None, rows=None):
        self._scalar_rows = scalar_rows or []
        self._rows = rows or []

    def scalars(self):
        return DummyScalarResult(self._scalar_rows)

    def all(self):
        return self._rows


class QueueSession:
    def __init__(self, results):
        self._results = list(results)

    async def execute(self, _query):
        if not self._results:
            raise AssertionError("No queued DB result available")
        return self._results.pop(0)


def _log(*, event: str, channel: str, at: datetime, user_id: uuid.UUID, resource_id: str, meta: dict | None = None):
    payload = {
        "event": event,
        "channel": channel,
    }
    if meta:
        payload.update(meta)
    return SimpleNamespace(
        meta=payload,
        created_at=at,
        user_id=user_id,
        resource_id=resource_id,
    )


def test_assisted_draft_metrics_roll_up_channels_latency_and_edits():
    now = datetime.now(timezone.utc)
    admin_id = uuid.uuid4()
    agent_one_id = uuid.uuid4()
    agent_two_id = uuid.uuid4()
    conv_chat = str(uuid.uuid4())
    conv_wa = str(uuid.uuid4())

    logs = [
        _log(
            event="generated",
            channel="chat",
            at=now - timedelta(days=3),
            user_id=agent_one_id,
            resource_id=conv_chat,
        ),
        _log(
            event="generated",
            channel="chat",
            at=now - timedelta(days=1, seconds=120),
            user_id=agent_one_id,
            resource_id=conv_chat,
        ),
        _log(
            event="accepted",
            channel="chat",
            at=now - timedelta(days=1, seconds=90),
            user_id=agent_one_id,
            resource_id=conv_chat,
            meta={
                "assisted_draft_seconds_to_send": 30,
                "assisted_draft_edited": False,
            },
        ),
        _log(
            event="generated",
            channel="whatsapp",
            at=now - timedelta(days=2, seconds=240),
            user_id=agent_two_id,
            resource_id=conv_wa,
        ),
        _log(
            event="accepted",
            channel="whatsapp",
            at=now - timedelta(days=2, seconds=120),
            user_id=agent_two_id,
            resource_id=conv_wa,
            meta={
                "assisted_draft_edited": True,
            },
        ),
        _log(
            event="generated",
            channel="email",
            at=now - timedelta(days=1, seconds=80),
            user_id=agent_one_id,
            resource_id=str(uuid.uuid4()),
        ),
        _log(
            event="accepted",
            channel="email",
            at=now - timedelta(days=1, seconds=35),
            user_id=agent_one_id,
            resource_id=str(uuid.uuid4()),
            meta={
                "assisted_draft_seconds_to_send": 45,
                "assisted_draft_edited": False,
            },
        ),
    ]

    sent_rows = [
        (ChannelType.CHAT, now - timedelta(days=3, minutes=5)),
        (ChannelType.CHAT, now - timedelta(days=1, minutes=1)),
        (ChannelType.WHATSAPP, now - timedelta(days=2, minutes=1)),
    ]
    email_sent_rows = [
        now - timedelta(days=1, minutes=2),
    ]

    users_rows = [
        (agent_one_id, "Agent One"),
        (agent_two_id, "Agent Two"),
    ]

    db = QueueSession(
        [
            DummyResult(scalar_rows=logs),
            DummyResult(rows=sent_rows),
            DummyResult(scalar_rows=email_sent_rows),
            DummyResult(rows=users_rows),
        ]
    )
    service = DashboardService(db)

    summary = asyncio.run(
        service._build_assisted_draft_performance(
            SimpleNamespace(id=admin_id, role=UserRole.ADMIN)
        )
    )

    assert summary.lookback_days == 30
    assert summary.total_generated == 4
    assert summary.total_accepted == 3
    assert summary.total_sent == 4
    assert summary.acceptance_rate == 0.75
    assert summary.assisted_share == 0.75
    assert summary.edited_rate == 0.333
    assert summary.edited_samples == 3
    assert summary.median_seconds_to_send == 45.0
    assert summary.latency_samples == 3

    by_channel = {item.channel: item for item in summary.channels}
    assert by_channel["chat"].generated == 2
    assert by_channel["chat"].accepted == 1
    assert by_channel["chat"].sent == 2
    assert by_channel["chat"].acceptance_rate == 0.5
    assert by_channel["chat"].assisted_share == 0.5
    assert by_channel["chat"].edited_rate == 0.0
    assert by_channel["chat"].median_seconds_to_send == 30.0

    assert by_channel["whatsapp"].generated == 1
    assert by_channel["whatsapp"].accepted == 1
    assert by_channel["whatsapp"].sent == 1
    assert by_channel["whatsapp"].acceptance_rate == 1.0
    assert by_channel["whatsapp"].assisted_share == 1.0
    assert by_channel["whatsapp"].edited_rate == 1.0
    assert by_channel["whatsapp"].median_seconds_to_send == 120.0

    assert by_channel["email"].generated == 1
    assert by_channel["email"].accepted == 1
    assert by_channel["email"].sent == 1
    assert by_channel["email"].acceptance_rate == 1.0
    assert by_channel["email"].assisted_share == 1.0
    assert by_channel["email"].edited_rate == 0.0
    assert by_channel["email"].median_seconds_to_send == 45.0

    assert len(summary.top_agents) == 2
    assert summary.top_agents[0].user_id == agent_one_id
    assert summary.top_agents[0].generated == 3
    assert summary.top_agents[0].accepted == 2


def test_assisted_draft_metrics_for_clients_are_empty_without_queries():
    service = DashboardService(QueueSession([]))
    summary = asyncio.run(
        service._build_assisted_draft_performance(
            SimpleNamespace(id=uuid.uuid4(), role=UserRole.CLIENT)
        )
    )

    assert summary.total_generated == 0
    assert summary.total_accepted == 0
    assert summary.total_sent == 0
    assert {item.channel for item in summary.channels} == {"chat", "whatsapp", "email"}
    assert summary.top_agents == []


def test_assisted_draft_metrics_respect_lookback_days():
    now = datetime.now(timezone.utc)
    agent_id = uuid.uuid4()

    all_logs = [
        _log(
            event="generated",
            channel="chat",
            at=now - timedelta(days=45),
            user_id=agent_id,
            resource_id="old-chat",
        ),
        _log(
            event="accepted",
            channel="chat",
            at=now - timedelta(days=45) + timedelta(seconds=60),
            user_id=agent_id,
            resource_id="old-chat",
        ),
        _log(
            event="generated",
            channel="chat",
            at=now - timedelta(days=5),
            user_id=agent_id,
            resource_id="new-chat",
        ),
        _log(
            event="accepted",
            channel="chat",
            at=now - timedelta(days=5) + timedelta(seconds=30),
            user_id=agent_id,
            resource_id="new-chat",
        ),
    ]
    logs_30 = [
        log
        for log in all_logs
        if log.created_at >= now - timedelta(days=30)
    ]

    sent_rows_30 = [
        (ChannelType.CHAT, now - timedelta(days=5, minutes=3)),
    ]
    email_sent_rows_30: list[datetime] = []

    service_30 = DashboardService(
        QueueSession(
            [
                DummyResult(scalar_rows=logs_30),
                DummyResult(rows=sent_rows_30),
                DummyResult(scalar_rows=email_sent_rows_30),
            ]
        )
    )
    summary_30 = asyncio.run(
        service_30._build_assisted_draft_performance(
            SimpleNamespace(id=agent_id, role=UserRole.AGENT),
            lookback_days=30,
        )
    )

    assert summary_30.lookback_days == 30
    assert summary_30.total_generated == 1
    assert summary_30.total_accepted == 1
    assert len(summary_30.daily) == 30

    sent_rows_90 = [
        (ChannelType.CHAT, now - timedelta(days=45, minutes=2)),
        (ChannelType.CHAT, now - timedelta(days=5, minutes=3)),
    ]
    email_sent_rows_90: list[datetime] = []

    service_90 = DashboardService(
        QueueSession(
            [
                DummyResult(scalar_rows=all_logs),
                DummyResult(rows=sent_rows_90),
                DummyResult(scalar_rows=email_sent_rows_90),
            ]
        )
    )
    summary_90 = asyncio.run(
        service_90._build_assisted_draft_performance(
            SimpleNamespace(id=agent_id, role=UserRole.AGENT),
            lookback_days=90,
        )
    )

    assert summary_90.lookback_days == 90
    assert summary_90.total_generated == 2
    assert summary_90.total_accepted == 2
    assert len(summary_90.daily) == 90
