"""
GLPI Client — Integration with GLPI REST API for ticket synchronization.
Communicates with GLPI instance via the Laravel proxy controller.
"""

import logging
from typing import Optional, Any
from datetime import datetime

import httpx

from app.core.config import settings
from app.db.models.enums import TicketStatus, TicketPriority

logger = logging.getLogger(__name__)


# ══════════════════════════════════════════════════════════════════════════════
# STATUS & PRIORITY MAPPING
# ══════════════════════════════════════════════════════════════════════════════

# Map FastAPI ticket status to GLPI status codes
# GLPI Ticket statuses: 1=new, 2=assigned, 3=planned, 4=waiting, 5=solved, 6=closed
FASTAPI_TO_GLPI_STATUS = {
    TicketStatus.OPEN: 1,           # new
    TicketStatus.IN_PROGRESS: 2,    # assigned
    TicketStatus.WAITING_ON_CUSTOMER: 4,  # waiting
    TicketStatus.ESCALATED: 2,      # assigned (escalated)
    TicketStatus.RESOLVED: 5,       # solved
    TicketStatus.CLOSED: 6,         # closed
}

GLPI_TO_FASTAPI_STATUS = {
    1: TicketStatus.OPEN,
    2: TicketStatus.IN_PROGRESS,
    3: TicketStatus.IN_PROGRESS,    # planned -> in_progress
    4: TicketStatus.WAITING_ON_CUSTOMER,
    5: TicketStatus.RESOLVED,
    6: TicketStatus.CLOSED,
}

# Map FastAPI priority to GLPI urgency (1-5: very low to critical)
FASTAPI_TO_GLPI_PRIORITY = {
    TicketPriority.LOW: 1,
    TicketPriority.MEDIUM: 3,
    TicketPriority.HIGH: 4,
    TicketPriority.CRITICAL: 5,
}

GLPI_TO_FASTAPI_PRIORITY = {
    1: TicketPriority.LOW,
    2: TicketPriority.LOW,
    3: TicketPriority.MEDIUM,
    4: TicketPriority.HIGH,
    5: TicketPriority.CRITICAL,
}


# ══════════════════════════════════════════════════════════════════════════════
# GLPI CLIENT
# ══════════════════════════════════════════════════════════════════════════════

class GlpiClient:
    """
    GLPI Integration Client.
    
    Communicates with GLPI via Laravel SupportApiProxyController.
    Handles ticket creation, updates, and synchronization.
    """

    def __init__(self, base_url: Optional[str] = None):
        """
        Initialize GLPI client.
        
        Args:
            base_url: GLPI API proxy base URL (defaults to settings.GLPI_API_URL)
        """
        self.base_url = (base_url or settings.GLPI_API_URL).rstrip('/')
        self.client = httpx.AsyncClient(timeout=30.0)
        self._sync_client: Optional[httpx.Client] = None

    @property
    def sync_client(self) -> httpx.Client:
        """Get or create synchronous HTTP client."""
        if self._sync_client is None:
            self._sync_client = httpx.Client(timeout=30.0)
        return self._sync_client

    async def close(self):
        """Close the HTTP clients."""
        await self.client.aclose()
        if self._sync_client:
            self._sync_client.close()

    def close_sync(self):
        """Close the synchronous HTTP client."""
        if self._sync_client:
            self._sync_client.close()

    async def _request(
        self,
        method: str,
        endpoint: str,
        **kwargs
    ) -> dict[str, Any]:
        """
        Make HTTP request to GLPI proxy.
        
        Args:
            method: HTTP method (GET, POST, PUT, DELETE)
            endpoint: API endpoint (e.g., '/glpi/items/Ticket')
            **kwargs: Additional request arguments
            
        Returns:
            Response JSON data
            
        Raises:
            GlpiClientError: If request fails
        """
        url = f"{self.base_url}{endpoint}"
        
        try:
            response = await self.client.request(method, url, **kwargs)
            response.raise_for_status()
            data = response.json()
            
            if not data.get('success', True) and 'error' in data:
                raise GlpiClientError(data['error'])
                
            return data.get('data', data)
        except httpx.HTTPError as e:
            logger.error(f"GLPI request failed: {method} {url} - {e}")
            raise GlpiClientError(f"GLPI API error: {str(e)}") from e

    def _request_sync(
        self,
        method: str,
        endpoint: str,
        **kwargs
    ) -> dict[str, Any]:
        """Synchronous version of _request."""
        url = f"{self.base_url}{endpoint}"
        
        try:
            response = self.sync_client.request(method, url, **kwargs)
            response.raise_for_status()
            data = response.json()
            
            if not data.get('success', True) and 'error' in data:
                raise GlpiClientError(data['error'])
                
            return data.get('data', data)
        except httpx.HTTPError as e:
            logger.error(f"GLPI sync request failed: {method} {url} - {e}")
            raise GlpiClientError(f"GLPI API error: {str(e)}") from e

    async def create_ticket(
        self,
        title: str,
        description: str,
        priority: TicketPriority,
        category_id: Optional[int] = None,
        requester_id: Optional[int] = None,
    ) -> dict[str, Any]:
        """
        Create a new ticket in GLPI.
        """
        payload = {
            'input': {
                'name': title,
                'content': description,
                'priority': FASTAPI_TO_GLPI_PRIORITY.get(priority, 3),
                'itilcategories_id': category_id,
                '_users_id_requester': requester_id,
            }
        }
        result = await self._request('POST', '/glpi/items/Ticket', json=payload)
        # Laravel's GlpiApiController returns {'success': true, 'data': {...}}
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        logger.info(f"Created GLPI ticket with ID: {result.get('id')}")
        return result

    def create_ticket_sync(
        self,
        title: str,
        description: str,
        priority: TicketPriority,
        category_id: Optional[int] = None,
        requester_id: Optional[int] = None,
    ) -> dict[str, Any]:
        """Synchronous version of create_ticket."""
        payload = {
            'input': {
                'name': title,
                'content': description,
                'priority': FASTAPI_TO_GLPI_PRIORITY.get(priority, 3),
                'itilcategories_id': category_id,
                '_users_id_requester': requester_id,
            }
        }
        result = self._request_sync('POST', '/glpi/items/Ticket', json=payload)
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return result

    async def update_ticket(
        self,
        glpi_ticket_id: int,
        **updates
    ) -> dict[str, Any]:
        """
        Update an existing ticket in GLPI.
        """
        payload = {}
        if 'status' in updates:
            payload['status'] = FASTAPI_TO_GLPI_STATUS.get(updates['status'], 2)
        if 'priority' in updates:
            payload['priority'] = FASTAPI_TO_GLPI_PRIORITY.get(updates['priority'], 3)
        if 'title' in updates:
            payload['name'] = updates['title']
        if 'description' in updates:
            payload['content'] = updates['description']
        if 'assigned_agent_id' in updates and updates['assigned_agent_id'] is not None:
            payload['_users_id_assign'] = updates['assigned_agent_id']
        if 'resolution_note' in updates and updates['resolution_note'] is not None:
            payload['solution'] = updates['resolution_note']
            
        result = await self._request(
            'PUT',
            f'/glpi/items/Ticket/{glpi_ticket_id}',
            json={'input': payload}
        )
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return result

    def update_ticket_sync(
        self,
        glpi_ticket_id: int,
        **updates
    ) -> dict[str, Any]:
        """Synchronous version of update_ticket."""
        payload = {}
        if 'status' in updates:
            payload['status'] = FASTAPI_TO_GLPI_STATUS.get(updates['status'], 2)
        if 'priority' in updates:
            payload['priority'] = FASTAPI_TO_GLPI_PRIORITY.get(updates['priority'], 3)
        if 'title' in updates:
            payload['name'] = updates['title']
        if 'description' in updates:
            payload['content'] = updates['description']
        if 'assigned_agent_id' in updates and updates['assigned_agent_id'] is not None:
            payload['_users_id_assign'] = updates['assigned_agent_id']
        if 'resolution_note' in updates and updates['resolution_note'] is not None:
            payload['solution'] = updates['resolution_note']
            
        result = self._request_sync('PUT', f'/glpi/items/Ticket/{glpi_ticket_id}', json={'input': payload})
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return result

    async def delete_ticket(self, glpi_ticket_id: int) -> bool:
        """
        Delete an existing ticket in GLPI.
        """
        result = await self._request(
            'DELETE',
            f'/glpi/items/Ticket/{glpi_ticket_id}',
        )
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return bool(result if result is not None else True)

    async def get_ticket(self, glpi_ticket_id: int) -> dict[str, Any]:
        """
        Fetch ticket from GLPI.
        
        Args:
            glpi_ticket_id: GLPI ticket ID
            
        Returns:
            GLPI ticket data
            
        Raises:
            GlpiClientError: If fetch fails
        """
        logger.info(f"Fetching GLPI ticket {glpi_ticket_id}")
        result = await self._request(
            'GET',
            f'/glpi/items/Ticket/{glpi_ticket_id}'
        )
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return result

    async def add_followup(
        self,
        glpi_ticket_id: int,
        content: str,
        is_private: bool = False,
    ) -> dict[str, Any]:
        """
        Add a followup (comment) to a ticket in GLPI.
        """
        payload = {
            'input': {
                'items_id': glpi_ticket_id,
                'itemtype': 'Ticket',
                'content': content,
                'is_private': 1 if is_private else 0,
            }
        }
        result = await self._request('POST', '/glpi/items/ITILFollowup', json=payload)
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return result

    def add_followup_sync(
        self,
        glpi_ticket_id: int,
        content: str,
        is_private: bool = False,
    ) -> dict[str, Any]:
        """Synchronous version of add_followup."""
        payload = {
            'input': {
                'items_id': glpi_ticket_id,
                'itemtype': 'Ticket',
                'content': content,
                'is_private': 1 if is_private else 0,
            }
        }
        result = self._request_sync('POST', '/glpi/items/ITILFollowup', json=payload)
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return result

    async def close_ticket(
        self,
        glpi_ticket_id: int,
        solution: str,
    ) -> dict[str, Any]:
        """
        Close ticket in GLPI with solution.
        
        Args:
            glpi_ticket_id: GLPI ticket ID
            solution: Solution/resolution text
            
        Returns:
            Updated GLPI ticket data
            
        Raises:
            GlpiClientError: If closure fails
        """
        payload = {
            'input': {
                'status': 6,  # closed
                'solution': solution,
            }
        }
        
        logger.info(f"Closing GLPI ticket {glpi_ticket_id}")
        result = await self._request(
            'PUT',
            f'/glpi/items/Ticket/{glpi_ticket_id}',
            json=payload
        )
        if isinstance(result, dict) and 'data' in result:
            result = result['data']
        return result

    @staticmethod
    def map_glpi_to_fastapi_status(glpi_status: int) -> TicketStatus:
        """Map GLPI status code to FastAPI TicketStatus."""
        return GLPI_TO_FASTAPI_STATUS.get(glpi_status, TicketStatus.OPEN)

    @staticmethod
    def map_glpi_to_fastapi_priority(glpi_priority: int) -> TicketPriority:
        """Map GLPI priority code to FastAPI TicketPriority."""
        return GLPI_TO_FASTAPI_PRIORITY.get(glpi_priority, TicketPriority.MEDIUM)


class GlpiClientError(Exception):
    """GLPI client error."""
    pass
