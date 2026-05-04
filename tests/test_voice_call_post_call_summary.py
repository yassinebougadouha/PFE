import asyncio
import uuid
from datetime import datetime, timezone
from types import SimpleNamespace

from fastapi import HTTPException

from app.api.routes.voice_calls import generate_post_call_summary, link_voice_call_ticket
from app.db.models.enums import ChannelType, TicketPriority, UserRole
from app.schemas.voice_call import (
    VoiceCallActionItem,
    VoiceCallPostCallSummaryRequest,
    VoiceCallPostCallSummaryResponse,
    VoiceCallTicketLinkRequest,
)
from app.services.ticket_service import TicketService
from app.services.voice_call_post_call_service import VoiceCallPostCallService
from app.services import voice_call_service as voice_call_service_module
from app.services.voice_call_service import VoiceCallService


def _mock_call(call_id: uuid.UUID):
    now = datetime.now(timezone.utc)
    return SimpleNamespace(
        id=call_id,
        room_name="support-call-client-1",
        transcript="Customer cannot complete checkout due to payment error.",
        duration_seconds=180.0,
        started_at=now,
        ended_at=now,
    )


def test_generate_post_call_summary_route_returns_service_payload(monkeypatch):
    call_id = uuid.uuid4()

    async def fake_get_call(self, requested_id, enrich_transcript=True):
        assert requested_id == call_id
        assert enrich_transcript is True
        return _mock_call(call_id)

    async def fake_summarize_call(self, call, max_transcript_chars=12000):
        assert call.id == call_id
        assert max_transcript_chars == 9000
        return VoiceCallPostCallSummaryResponse(
            call_id=call_id,
            room_name=call.room_name,
            provider="fallback",
            model="deterministic-v1",
            summary="Customer reports payment flow failure.",
            customer_issue="Payment fails at checkout.",
            resolution_status="in_progress",
            follow_up_recommendation="Validate payment gateway response and retry.",
            action_items=[
                VoiceCallActionItem(title="Verify gateway logs", owner="agent", priority="high"),
            ],
            ticket_subject_suggestion="Checkout payment failure",
            ticket_description_suggestion="Detailed follow-up notes",
            generated_at=datetime.now(timezone.utc),
        )

    monkeypatch.setattr(VoiceCallService, "get_call", fake_get_call)
    monkeypatch.setattr(VoiceCallPostCallService, "summarize_call", fake_summarize_call)

    response = asyncio.run(
        generate_post_call_summary(
            call_id=call_id,
            db=object(),
            _=SimpleNamespace(id=uuid.uuid4(), role=UserRole.AGENT),
            payload=VoiceCallPostCallSummaryRequest(max_transcript_chars=9000),
        )
    )

    assert response.call_id == call_id
    assert response.summary
    assert response.action_items[0].priority == "high"


def test_voice_call_list_backfills_even_when_existing_logs_are_present(monkeypatch):
    call_id = uuid.uuid4()
    events: list[str] = []
    call = _mock_call(call_id)

    class CountResult:
        def scalar(self):
            return 2

    class ItemsResult:
        def scalars(self):
            return self

        def all(self):
            return [call]

    class FakeDb:
        def __init__(self):
            self.results = [CountResult(), ItemsResult()]

        async def execute(self, _statement):
            return self.results.pop(0)

    async def fake_backfill(self):
        events.append("backfill")
        return 1

    async def fake_enrich_missing_durations(self):
        events.append("enrich")

    monkeypatch.setattr(VoiceCallService, "_backfill_from_recordings", fake_backfill)
    monkeypatch.setattr(VoiceCallService, "_enrich_missing_durations", fake_enrich_missing_durations)

    items, total = asyncio.run(VoiceCallService(FakeDb()).list_calls())

    assert items == [call]
    assert total == 2
    assert events == ["backfill", "enrich"]


def test_voice_call_recording_path_resolves_container_path_to_configured_dir(monkeypatch, tmp_path):
    recording = tmp_path / "support-call-client-1_20260502_101500.wav"
    recording.write_bytes(b"fake wav")
    monkeypatch.setattr(
        voice_call_service_module.settings,
        "VOICE_RECORDINGS_DIR",
        str(tmp_path),
        raising=False,
    )

    resolved = VoiceCallService._resolve_recording_path(f"/app/recordings/{recording.name}")

    assert resolved == recording


def test_link_voice_call_to_existing_ticket(monkeypatch):
    call_id = uuid.uuid4()
    ticket_id = uuid.uuid4()
    actor_id = uuid.uuid4()
    captured: dict[str, object] = {}

    async def fake_get_call(self, requested_id, enrich_transcript=False):
        assert requested_id == call_id
        return _mock_call(call_id)

    async def fake_get_ticket(self, requested_id):
        assert requested_id == ticket_id
        return SimpleNamespace(id=ticket_id, subject="Existing ticket", assigned_agent_id=None)

    async def fake_update_ticket(self, requested_id, payload):
        captured["payload"] = payload
        return SimpleNamespace(id=requested_id, subject="Existing ticket", assigned_agent_id=None)

    monkeypatch.setattr(VoiceCallService, "get_call", fake_get_call)
    monkeypatch.setattr(TicketService, "get_ticket", fake_get_ticket)
    monkeypatch.setattr(TicketService, "update_ticket", fake_update_ticket)

    response = asyncio.run(
        link_voice_call_ticket(
            call_id=call_id,
            payload=VoiceCallTicketLinkRequest(ticket_id=ticket_id),
            db=object(),
            current_user=SimpleNamespace(id=actor_id, role=UserRole.AGENT),
        )
    )

    assert response.link_type == "attached"
    assert response.ticket_id == ticket_id
    assert captured["payload"].source_voice_call_id == call_id


def test_link_voice_call_creates_ticket_with_summary_fallback(monkeypatch):
    call_id = uuid.uuid4()
    actor_id = uuid.uuid4()
    created_ticket_id = uuid.uuid4()
    captured: dict[str, object] = {}

    async def fake_get_call(self, requested_id, enrich_transcript=False):
        assert requested_id == call_id
        return _mock_call(call_id)

    async def fake_summarize_call(self, call, max_transcript_chars=12000):
        return VoiceCallPostCallSummaryResponse(
            call_id=call.id,
            room_name=call.room_name,
            provider="fallback",
            model="deterministic-v1",
            summary="Summary text",
            customer_issue="Checkout payment failure",
            resolution_status="unknown",
            follow_up_recommendation="Follow up with payment team",
            action_items=[
                VoiceCallActionItem(title="Collect payment logs", owner="agent", priority="high"),
            ],
            ticket_subject_suggestion="Payment issue from support call",
            ticket_description_suggestion="Create ticket from post-call summary",
            generated_at=datetime.now(timezone.utc),
        )

    async def fake_create_ticket(self, creator_id, payload):
        captured["creator_id"] = creator_id
        captured["payload"] = payload
        return SimpleNamespace(id=created_ticket_id, subject=payload.subject)

    monkeypatch.setattr(VoiceCallService, "get_call", fake_get_call)
    monkeypatch.setattr(VoiceCallPostCallService, "summarize_call", fake_summarize_call)
    monkeypatch.setattr(TicketService, "create_ticket", fake_create_ticket)

    response = asyncio.run(
        link_voice_call_ticket(
            call_id=call_id,
            payload=VoiceCallTicketLinkRequest(),
            db=object(),
            current_user=SimpleNamespace(id=actor_id, role=UserRole.AGENT),
        )
    )

    assert response.link_type == "created"
    assert response.ticket_id == created_ticket_id
    assert captured["creator_id"] == actor_id
    assert captured["payload"].subject == "Payment issue from support call"
    assert captured["payload"].description == "Create ticket from post-call summary"
    assert captured["payload"].channel_source == ChannelType.CALL_TRANSCRIPT
    assert captured["payload"].priority == TicketPriority.MEDIUM
    assert captured["payload"].source_voice_call_id == call_id


def test_link_voice_call_forbidden_for_foreign_agent_ticket(monkeypatch):
    call_id = uuid.uuid4()
    ticket_id = uuid.uuid4()

    async def fake_get_call(self, requested_id, enrich_transcript=False):
        return _mock_call(call_id)

    async def fake_get_ticket(self, requested_id):
        return SimpleNamespace(
            id=ticket_id,
            subject="Existing ticket",
            assigned_agent_id=uuid.uuid4(),
        )

    monkeypatch.setattr(VoiceCallService, "get_call", fake_get_call)
    monkeypatch.setattr(TicketService, "get_ticket", fake_get_ticket)

    try:
        asyncio.run(
            link_voice_call_ticket(
                call_id=call_id,
                payload=VoiceCallTicketLinkRequest(ticket_id=ticket_id),
                db=object(),
                current_user=SimpleNamespace(id=uuid.uuid4(), role=UserRole.AGENT),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 403
        assert exc.detail == "Access denied"
    else:
        raise AssertionError("Expected linking a foreign assigned ticket to be forbidden")
