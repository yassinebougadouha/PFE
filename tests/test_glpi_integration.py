"""
Tests for GLPI ticket integration.
"""

import uuid
from types import SimpleNamespace
from unittest.mock import AsyncMock, MagicMock, patch
import pytest

import app.services.ticket_service as ticket_service_module
from app.db.models.enums import TicketStatus, TicketPriority
from app.db.models.ticket import Ticket
from app.integrations.glpi_client import GlpiClient, GlpiClientError
from app.services.ticket_service import TicketService


pytestmark = pytest.mark.anyio


class TestGlpiClient:
    """Test GLPI client functionality."""

    async def test_create_ticket(self):
        """Test creating a ticket in GLPI."""
        client = GlpiClient()
        
        with patch.object(client, '_request', new_callable=AsyncMock) as mock_request:
            mock_request.return_value = {
                'id': 123,
                'glpi_ticket_id': 123,
                'name': 'Test Ticket',
                'status': 1,
            }
            
            result = await client.create_ticket(
                title='Test Ticket',
                description='Test description',
                priority=TicketPriority.HIGH,
            )
            
            assert result['id'] == 123
            mock_request.assert_called_once()
        
        await client.close()

    async def test_update_ticket(self):
        """Test updating a ticket in GLPI."""
        client = GlpiClient()
        
        with patch.object(client, '_request', new_callable=AsyncMock) as mock_request:
            mock_request.return_value = {
                'id': 123,
                'status': 2,
            }
            
            result = await client.update_ticket(
                123,
                status=TicketStatus.IN_PROGRESS,
                priority=TicketPriority.CRITICAL,
            )
            
            assert result['id'] == 123
            mock_request.assert_called_once()
        
        await client.close()

    async def test_get_ticket(self):
        """Test fetching a ticket from GLPI."""
        client = GlpiClient()
        
        with patch.object(client, '_request', new_callable=AsyncMock) as mock_request:
            mock_request.return_value = {
                'id': 123,
                'name': 'Fetched Ticket',
                'status': 2,
                'priority': 4,
            }
            
            result = await client.get_ticket(123)
            
            assert result['id'] == 123
            assert result['name'] == 'Fetched Ticket'
            mock_request.assert_called_once()
        
        await client.close()

    async def test_close_ticket(self):
        """Test closing a ticket in GLPI."""
        client = GlpiClient()
        
        with patch.object(client, '_request', new_callable=AsyncMock) as mock_request:
            mock_request.return_value = {
                'id': 123,
                'status': 6,  # closed
            }
            
            result = await client.close_ticket(123, 'Resolved by support')
            
            assert result['status'] == 6
            mock_request.assert_called_once()
        
        await client.close()

    async def test_add_followup(self):
        """Test adding a followup to a GLPI ticket."""
        client = GlpiClient()
        
        with patch.object(client, '_request', new_callable=AsyncMock) as mock_request:
            mock_request.return_value = {
                'id': 456,
                'ticket_id': 123,
                'content': 'Followup content',
            }
            
            result = await client.add_followup(123, 'Followup content')
            
            assert result['ticket_id'] == 123
            mock_request.assert_called_once()
        
        await client.close()

    async def test_delete_ticket(self):
        """Test deleting a ticket in GLPI."""
        client = GlpiClient()

        with patch.object(client, '_request', new_callable=AsyncMock) as mock_request:
            mock_request.return_value = True

            result = await client.delete_ticket(123)

            assert result is True
            mock_request.assert_called_once_with('DELETE', '/glpi/items/Ticket/123')

        await client.close()

    def test_status_mapping(self):
        """Test status mapping between FastAPI and GLPI."""
        assert GlpiClient.map_glpi_to_fastapi_status(1) == TicketStatus.OPEN
        assert GlpiClient.map_glpi_to_fastapi_status(2) == TicketStatus.IN_PROGRESS
        assert GlpiClient.map_glpi_to_fastapi_status(4) == TicketStatus.WAITING_ON_CUSTOMER
        assert GlpiClient.map_glpi_to_fastapi_status(5) == TicketStatus.RESOLVED
        assert GlpiClient.map_glpi_to_fastapi_status(6) == TicketStatus.CLOSED

    def test_priority_mapping(self):
        """Test priority mapping between FastAPI and GLPI."""
        assert GlpiClient.map_glpi_to_fastapi_priority(1) == TicketPriority.LOW
        assert GlpiClient.map_glpi_to_fastapi_priority(3) == TicketPriority.MEDIUM
        assert GlpiClient.map_glpi_to_fastapi_priority(4) == TicketPriority.HIGH
        assert GlpiClient.map_glpi_to_fastapi_priority(5) == TicketPriority.CRITICAL

    async def test_request_error_handling(self):
        """Test error handling in requests."""
        client = GlpiClient()
        
        with patch.object(client, '_request', new_callable=AsyncMock) as mock_request:
            mock_request.side_effect = GlpiClientError("Connection failed")
            
            with pytest.raises(GlpiClientError):
                await client.create_ticket(
                    title='Test',
                    description='Test',
                    priority=TicketPriority.MEDIUM,
                )
        
        await client.close()


class TestTicketServiceGlpiSync:
    """Test TicketService GLPI synchronization."""

    async def test_sync_to_glpi_creates_ticket(self, mock_db):
        """Test syncing a new ticket to GLPI."""
        svc = TicketService(mock_db)
        ticket = Ticket(
            id=uuid.uuid4(),
            subject='Test Ticket',
            description='Test',
            priority=TicketPriority.HIGH,
            status=TicketStatus.OPEN,
            creator_id=uuid.uuid4(),
            glpi_sync_status='pending',
        )
        
        with patch.object(GlpiClient, 'create_ticket', new_callable=AsyncMock) as mock_create:
            mock_create.return_value = {
                'id': 123,
                'glpi_ticket_id': 123,
            }
            with patch.object(GlpiClient, 'close', new_callable=AsyncMock):
                success = await svc.sync_to_glpi(ticket)
        
        assert success is True
        assert ticket.glpi_ticket_id == 123
        assert ticket.glpi_sync_status == 'synced'
        assert ticket.glpi_sync_error is None

    async def test_sync_to_glpi_updates_ticket(self, mock_db):
        """Test syncing an existing ticket to GLPI."""
        svc = TicketService(mock_db)
        ticket = Ticket(
            id=uuid.uuid4(),
            subject='Updated Ticket',
            description='Updated',
            priority=TicketPriority.MEDIUM,
            status=TicketStatus.IN_PROGRESS,
            creator_id=uuid.uuid4(),
            glpi_ticket_id=999,
            glpi_sync_status='synced',
        )
        
        with patch.object(GlpiClient, 'update_ticket', new_callable=AsyncMock) as mock_update:
            mock_update.return_value = {'id': 999}
            with patch.object(GlpiClient, 'close', new_callable=AsyncMock):
                success = await svc.sync_to_glpi(ticket)
        
        assert success is True
        assert ticket.glpi_sync_status == 'synced'
        mock_update.assert_called_once()

    async def test_sync_to_glpi_handles_error(self, mock_db):
        """Test error handling during GLPI sync."""
        svc = TicketService(mock_db)
        ticket = Ticket(
            id=uuid.uuid4(),
            subject='Test',
            description='Test',
            priority=TicketPriority.MEDIUM,
            status=TicketStatus.OPEN,
            creator_id=uuid.uuid4(),
        )
        
        with patch.object(GlpiClient, 'create_ticket', new_callable=AsyncMock) as mock_create:
            mock_create.side_effect = GlpiClientError("API error")
            with patch.object(GlpiClient, 'close', new_callable=AsyncMock):
                success = await svc.sync_to_glpi(ticket)
        
        assert success is False
        assert ticket.glpi_sync_status == 'failed'
        assert 'API error' in ticket.glpi_sync_error

    async def test_update_ticket_auto_syncs_to_glpi(self, mock_db, monkeypatch):
        """Test normal ticket edits trigger GLPI sync."""
        monkeypatch.setattr(ticket_service_module.settings, "GLPI_AUTO_SYNC", True)
        ticket = Ticket(
            id=uuid.uuid4(),
            subject='Original',
            description='Original',
            priority=TicketPriority.MEDIUM,
            status=TicketStatus.OPEN,
            creator_id=uuid.uuid4(),
            glpi_ticket_id=123,
        )
        svc = TicketService(mock_db)
        svc.get_ticket = AsyncMock(return_value=ticket)
        svc.sync_to_glpi = AsyncMock(return_value=True)

        result = await svc.update_ticket(
            ticket.id,
            ticket_service_module.TicketUpdate(subject="Updated", priority=TicketPriority.HIGH),
        )

        assert result is ticket
        assert ticket.subject == "Updated"
        assert ticket.priority == TicketPriority.HIGH
        svc.sync_to_glpi.assert_awaited_once_with(ticket)

    async def test_assign_agent_auto_syncs_to_glpi(self, mock_db, monkeypatch):
        """Test assignment actions trigger GLPI sync."""
        monkeypatch.setattr(ticket_service_module.settings, "GLPI_AUTO_SYNC", True)
        ticket = Ticket(
            id=uuid.uuid4(),
            subject='Needs help',
            description='Assign me',
            priority=TicketPriority.MEDIUM,
            status=TicketStatus.OPEN,
            creator_id=uuid.uuid4(),
            glpi_ticket_id=123,
        )
        assignee = SimpleNamespace(id=uuid.uuid4())
        mock_result = MagicMock()
        mock_result.scalar_one_or_none.return_value = assignee
        mock_db.execute = AsyncMock(return_value=mock_result)

        svc = TicketService(mock_db)
        svc.get_ticket = AsyncMock(return_value=ticket)
        svc.sync_to_glpi = AsyncMock(return_value=True)
        svc.notification_service.notify_assignment = AsyncMock()

        result = await svc.assign_agent(ticket.id, assignee.id)

        assert result is ticket
        assert ticket.assigned_agent_id == assignee.id
        assert ticket.status == TicketStatus.IN_PROGRESS
        svc.sync_to_glpi.assert_awaited_once_with(ticket)

    async def test_soft_delete_auto_deletes_from_glpi(self, mock_db, monkeypatch):
        """Test local soft deletes trigger GLPI delete sync."""
        monkeypatch.setattr(ticket_service_module.settings, "GLPI_AUTO_SYNC", True)
        ticket = Ticket(
            id=uuid.uuid4(),
            subject='Delete me',
            description='Delete me',
            priority=TicketPriority.MEDIUM,
            status=TicketStatus.OPEN,
            creator_id=uuid.uuid4(),
            glpi_ticket_id=123,
        )
        svc = TicketService(mock_db)
        svc.get_ticket = AsyncMock(return_value=ticket)
        svc.delete_from_glpi = AsyncMock(return_value=True)

        deleted = await svc.soft_delete(ticket.id)

        assert deleted is True
        assert ticket.is_deleted is True
        assert ticket.deleted_at is not None
        svc.delete_from_glpi.assert_awaited_once_with(ticket)

    async def test_sync_from_glpi_updates_ticket(self, mock_db):
        """Test syncing a ticket from GLPI."""
        ticket_id = uuid.uuid4()
        ticket = Ticket(
            id=ticket_id,
            subject='Original',
            description='Original',
            priority=TicketPriority.LOW,
            status=TicketStatus.OPEN,
            creator_id=uuid.uuid4(),
            glpi_ticket_id=456,
        )
        
        # Mock the database query
        mock_result = MagicMock()
        mock_result.scalar_one_or_none.return_value = ticket
        mock_db.execute = AsyncMock(return_value=mock_result)
        
        svc = TicketService(mock_db)
        
        with patch.object(GlpiClient, 'get_ticket', new_callable=AsyncMock) as mock_get:
            mock_get.return_value = {
                'id': 456,
                'name': 'Updated from GLPI',
                'content': 'Updated description',
                'status': 5,  # resolved
                'priority': 4,  # high
            }
            with patch.object(GlpiClient, 'close', new_callable=AsyncMock):
                result = await svc.sync_from_glpi(456)
        
        assert result is not None
        assert result.subject == 'Updated from GLPI'
        assert result.status == TicketStatus.RESOLVED
        assert result.priority == TicketPriority.HIGH


@pytest.fixture
def mock_db():
    """Create a mock async database session."""
    db = AsyncMock()
    db.flush = AsyncMock()
    db.refresh = AsyncMock()
    db.add = MagicMock()
    db.execute = AsyncMock()
    return db
