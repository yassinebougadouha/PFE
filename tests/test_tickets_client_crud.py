import asyncio
import os
import uuid
from types import SimpleNamespace

os.environ["DEBUG"] = "true"

from fastapi import HTTPException

from app.api.routes.tickets import delete_ticket, update_ticket
from app.db.models.enums import TicketPriority, TicketStatus, UserRole
from app.schemas.ticket import TicketUpdate
from app.services.ticket_service import TicketService


def test_client_can_update_own_ticket(monkeypatch):
    ticket_id = uuid.uuid4()
    user_id = uuid.uuid4()
    captured: dict[str, object] = {}

    async def fake_get_ticket(self, requested_id):
        assert requested_id == ticket_id
        return SimpleNamespace(id=ticket_id, creator_id=user_id)

    async def fake_update_ticket(self, requested_id, payload):
        captured["payload"] = payload
        return SimpleNamespace(id=requested_id, creator_id=user_id)

    monkeypatch.setattr(TicketService, "get_ticket", fake_get_ticket)
    monkeypatch.setattr(TicketService, "update_ticket", fake_update_ticket)

    result = asyncio.run(
        update_ticket(
            ticket_id=ticket_id,
            payload=TicketUpdate(
                subject="Updated ticket",
                description="Updated details",
                priority=TicketPriority.HIGH,
            ),
            db=object(),
            current_user=SimpleNamespace(id=user_id, role=UserRole.CLIENT),
        )
    )

    assert result.id == ticket_id
    payload = captured["payload"]
    assert payload.subject == "Updated ticket"
    assert payload.description == "Updated details"
    assert payload.status is None
    assert payload.priority == TicketPriority.HIGH


def test_client_cannot_update_ticket_assignment(monkeypatch):
    ticket_id = uuid.uuid4()
    user_id = uuid.uuid4()

    async def fake_get_ticket(self, requested_id):
        assert requested_id == ticket_id
        return SimpleNamespace(id=ticket_id, creator_id=user_id)

    monkeypatch.setattr(TicketService, "get_ticket", fake_get_ticket)

    try:
        asyncio.run(
            update_ticket(
                ticket_id=ticket_id,
                payload=TicketUpdate(assigned_agent_id=uuid.uuid4()),
                db=object(),
                current_user=SimpleNamespace(id=user_id, role=UserRole.CLIENT),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 403
        assert exc.detail == "Clients cannot update assignment or escalation settings"
    else:
        raise AssertionError("Expected client assignment update to be rejected")


def test_client_can_delete_own_ticket(monkeypatch):
    ticket_id = uuid.uuid4()
    user_id = uuid.uuid4()
    captured: dict[str, object] = {}

    async def fake_get_ticket(self, requested_id):
        assert requested_id == ticket_id
        return SimpleNamespace(id=ticket_id, creator_id=user_id)

    async def fake_soft_delete(self, requested_id):
        captured["deleted_ticket_id"] = requested_id
        return True

    monkeypatch.setattr(TicketService, "get_ticket", fake_get_ticket)
    monkeypatch.setattr(TicketService, "soft_delete", fake_soft_delete)

    result = asyncio.run(
        delete_ticket(
            ticket_id=ticket_id,
            db=object(),
            current_user=SimpleNamespace(id=user_id, role=UserRole.CLIENT),
        )
    )

    assert result == {"message": "Ticket deleted"}
    assert captured["deleted_ticket_id"] == ticket_id


def test_client_cannot_delete_someone_elses_ticket(monkeypatch):
    ticket_id = uuid.uuid4()
    owner_id = uuid.uuid4()
    other_user_id = uuid.uuid4()

    async def fake_get_ticket(self, requested_id):
        assert requested_id == ticket_id
        return SimpleNamespace(id=ticket_id, creator_id=owner_id)

    monkeypatch.setattr(TicketService, "get_ticket", fake_get_ticket)

    try:
        asyncio.run(
            delete_ticket(
                ticket_id=ticket_id,
                db=object(),
                current_user=SimpleNamespace(id=other_user_id, role=UserRole.CLIENT),
            )
        )
    except HTTPException as exc:
        assert exc.status_code == 403
        assert exc.detail == "Access denied"
    else:
        raise AssertionError("Expected client delete on another ticket to be rejected")
