# GLPI Ticket Integration Documentation

## Overview

This document describes the GLPI ticket synchronization system that enables bidirectional ticket syncing between the local FastAPI backend and an external GLPI ticketing system.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                    FastAPI Backend                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────┐         ┌──────────────────────────┐  │
│  │  API Routes         │         │  TicketService           │  │
│  ├─────────────────────┤         ├──────────────────────────┤  │
│  │ POST /sync          │────────▶│ sync_to_glpi()           │  │
│  │ GET /glpi/status    │         │ sync_from_glpi()         │  │
│  │ POST /glpi/sync     │         │ create_ticket()          │  │
│  └─────────────────────┘         └──────────────────────────┘  │
│                                            │                     │
│                                            ▼                     │
│                                  ┌──────────────────┐            │
│                                  │  GlpiClient      │            │
│                                  ├──────────────────┤            │
│                                  │ create_ticket()  │            │
│                                  │ update_ticket()  │            │
│                                  │ get_ticket()     │            │
│                                  │ close_ticket()   │            │
│                                  │ add_followup()   │            │
│                                  └──────────────────┘            │
│                                            │                     │
│  ┌─────────────────────┐                   │                     │
│  │  Ticket Model       │                   │                     │
│  ├─────────────────────┤                   │                     │
│  │ glpi_ticket_id      │                   │                     │
│  │ glpi_sync_status    │                   │                     │
│  │ glpi_sync_error     │                   │                     │
│  └─────────────────────┘                   │                     │
│                                            │                     │
│  PostgreSQL Database              ◀────────┘                     │
└─────────────────────────────────────────────────────────────────┘
                                    │
                                    │ HTTP/REST
                                    ▼
                    ┌──────────────────────────────┐
                    │     GLPI REST API            │
                    ├──────────────────────────────┤
                    │ POST   /Ticket               │
                    │ PUT    /Ticket/{id}          │
                    │ GET    /Ticket/{id}          │
                    │ POST   /TicketFollowup       │
                    └──────────────────────────────┘
                                    │
                                    ▼
                    ┌──────────────────────────────┐
                    │   GLPI Instance              │
                    │   (External Ticketing)       │
                    └──────────────────────────────┘
```

## Key Features

### 1. **Bidirectional Synchronization**
- **To GLPI**: Tickets created/updated locally are automatically synced to GLPI
- **From GLPI**: Tickets can be manually pulled from GLPI to update local records

### 2. **Status & Priority Mapping**

#### Status Mapping
| FastAPI Status | GLPI Code | Description |
|---|---|---|
| OPEN | 1 | New ticket |
| IN_PROGRESS | 2 | Assigned to agent |
| WAITING_ON_CUSTOMER | 4 | Waiting for customer response |
| RESOLVED | 5 | Solution provided |
| CLOSED | 6 | Closed |

#### Priority Mapping
| FastAPI Priority | GLPI Code | Description |
|---|---|---|
| LOW | 1 | Low priority |
| MEDIUM | 3 | Medium priority |
| HIGH | 4 | High priority |
| CRITICAL | 5 | Critical/Urgent |

### 3. **Sync Status Tracking**
Each ticket maintains sync state:
- `pending` - Waiting to sync to GLPI
- `synced` - Successfully synced with GLPI
- `failed` - Sync failed (error in `glpi_sync_error`)
- `skipped` - GLPI sync disabled

### 4. **Configuration**

Environment variables in `app/core/config.py`:

```python
GLPI_API_URL = "http://localhost:8001/api/v1"  # GLPI REST API endpoint
GLPI_ENABLED = True                            # Enable/disable GLPI integration
GLPI_AUTO_SYNC = True                          # Auto-sync on ticket creation
GLPI_API_KEY = "your-glpi-api-key"            # Authentication token
```

## Database Schema

### New Ticket Fields

```python
glpi_ticket_id: int | None          # GLPI ticket ID (maps to external system)
glpi_sync_status: str               # Current sync status (pending|synced|failed|skipped)
glpi_sync_error: str | None         # Error message if sync failed
```

### Indexes for Performance

```sql
CREATE INDEX ix_tickets_glpi_id ON tickets(glpi_ticket_id);
CREATE INDEX ix_tickets_glpi_sync_status ON tickets(glpi_sync_status);
CREATE INDEX ix_tickets_glpi_sync ON tickets(glpi_ticket_id, glpi_sync_status);
```

## API Endpoints

### 1. Manual Sync to GLPI

**Endpoint**: `POST /api/v1/tickets/{ticket_id}/glpi/sync`

**Authentication**: Admin only

**Request**: None

**Response**:
```json
{
  "success": true,
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "glpi_ticket_id": 123,
  "sync_status": "synced",
  "message": "Ticket synced to GLPI",
  "error": null
}
```

### 2. Fetch from GLPI

**Endpoint**: `POST /api/v1/tickets/glpi/{glpi_ticket_id}/sync`

**Authentication**: Admin only

**Request**: None

**Response**:
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "subject": "Updated from GLPI",
  "description": "Latest description from GLPI",
  "status": "IN_PROGRESS",
  "priority": "HIGH",
  "glpi_ticket_id": 123,
  "glpi_sync_status": "synced",
  "created_at": "2024-05-10T10:30:00Z",
  "updated_at": "2024-05-10T11:45:00Z"
}
```

### 3. Check Sync Status

**Endpoint**: `GET /api/v1/tickets/{ticket_id}/glpi/status`

**Authentication**: Required (clients see only their own tickets)

**Response**:
```json
{
  "ticket_id": "550e8400-e29b-41d4-a716-446655440000",
  "glpi_ticket_id": 123,
  "sync_status": "synced",
  "sync_error": null,
  "synced_at": "2024-05-10T11:45:00Z"
}
```

## Service Layer

### TicketService Methods

#### `sync_to_glpi(ticket: Ticket) -> bool`

Syncs a local ticket to GLPI. If ticket doesn't have `glpi_ticket_id`, creates new ticket in GLPI. Otherwise, updates existing GLPI ticket.

```python
# Create new ticket in GLPI
ticket = Ticket(subject="New Issue", ...)
success = await ticket_service.sync_to_glpi(ticket)
if success:
    print(f"GLPI ticket ID: {ticket.glpi_ticket_id}")

# Update existing ticket in GLPI
ticket.subject = "Updated title"
success = await ticket_service.sync_to_glpi(ticket)
```

#### `sync_from_glpi(glpi_ticket_id: int) -> Optional[Ticket]`

Fetches ticket from GLPI and updates local database.

```python
ticket = await ticket_service.sync_from_glpi(glpi_ticket_id=123)
if ticket:
    print(f"Local ticket: {ticket.id}")
    print(f"Status: {ticket.status}")
else:
    print("Ticket not found")
```

## Auto-Sync on Ticket Creation

When `GLPI_AUTO_SYNC=True`, new tickets are automatically synced:

```python
# In TicketService.create_ticket()
ticket = Ticket(...)
await db.flush()

decision = await analyze_ticket(...)

# Auto-sync to GLPI if enabled
if settings.GLPI_AUTO_SYNC:
    await self.sync_to_glpi(ticket)
```

## Auto-Sync on Status Update

When ticket status changes, it's automatically synced to GLPI:

```python
# In TicketService.update_ticket_status()
ticket.status = new_status
await db.flush()

# Sync status update to GLPI
if settings.GLPI_AUTO_SYNC:
    await self.sync_to_glpi(ticket)
```

## Error Handling

### Graceful Degradation

If GLPI is unavailable or sync fails:

1. Ticket is created locally (not blocked by GLPI issues)
2. `glpi_sync_status` is set to `"failed"`
3. Error details stored in `glpi_sync_error`
4. Admin can retry manually via API

```python
try:
    await glpi_client.create_ticket(...)
except GlpiClientError as e:
    ticket.glpi_sync_status = "failed"
    ticket.glpi_sync_error = str(e)
    logger.error(f"GLPI sync failed: {e}")
```

## Testing

### Unit Tests

Location: `tests/test_glpi_integration.py`

Coverage:
- GlpiClient CRUD operations
- Status/priority mapping (bidirectional)
- Error handling and exceptions
- Async client lifecycle

Run:
```bash
pytest tests/test_glpi_integration.py -v
```

### Integration Tests (E2E)

Location: `tests/test_glpi_integration_e2e.py`

Workflow:
1. Create ticket locally
2. Verify sync to GLPI
3. Update ticket status
4. Manually sync from GLPI
5. Verify all fields

Run:
```bash
# Requires running backend and GLPI instance
python tests/test_glpi_integration_e2e.py
```

## Database Migration

Migration: `alembic/versions/t0u1v2w3x4y_add_glpi_ticket_fields.py`

Creates:
- `glpi_ticket_id` (Integer, nullable, indexed)
- `glpi_sync_status` (VARCHAR(20), default 'pending', indexed)
- `glpi_sync_error` (Text, nullable)
- Combined index on (glpi_ticket_id, glpi_sync_status)

Apply migration:
```bash
alembic upgrade head
```

Revert migration:
```bash
alembic downgrade -1
```

## Implementation Checklist

- [x] GlpiClient async HTTP client with CRUD operations
- [x] Database schema extension with GLPI fields
- [x] Status/priority mapping enums and converters
- [x] TicketService integration (sync_to_glpi, sync_from_glpi)
- [x] Auto-sync on ticket creation and status update
- [x] API endpoints for manual sync operations
- [x] Database migration
- [x] Unit tests for GlpiClient
- [x] Integration tests for full workflow
- [x] Error handling and graceful degradation
- [x] Documentation

## Troubleshooting

### Ticket not syncing to GLPI

1. Check configuration:
   ```python
   from app.core.config import settings
   print(f"GLPI_ENABLED: {settings.GLPI_ENABLED}")
   print(f"GLPI_AUTO_SYNC: {settings.GLPI_AUTO_SYNC}")
   ```

2. Check GLPI API connectivity:
   ```python
   from app.integrations.glpi_client import GlpiClient
   client = GlpiClient()
   ticket = await client.get_ticket(123)
   ```

3. Check ticket sync status:
   ```
   GET /api/v1/tickets/{ticket_id}/glpi/status
   ```
   Look for error in `sync_error` field

### GLPI API errors

Check logs for `GlpiClientError` details:
```python
import logging
logging.getLogger("app.integrations.glpi_client").setLevel(logging.DEBUG)
```

### Status mapping issues

Verify mapping constants:
```python
from app.integrations.glpi_client import FASTAPI_TO_GLPI_STATUS, GLPI_TO_FASTAPI_STATUS
print(FASTAPI_TO_GLPI_STATUS)
print(GLPI_TO_FASTAPI_STATUS)
```

## Performance Considerations

- **Indexes**: Queries by `glpi_ticket_id` and `glpi_sync_status` use database indexes
- **Async Operations**: All GLPI operations are async/await to prevent blocking
- **Batch Sync**: For bulk syncing, consider pagination
- **Caching**: GLPI ticket lookups not cached; consider Redis if high volume

## Security

- GLPI API key stored in environment variables (never in code)
- API endpoints require authentication (Admin/Agent roles)
- Clients can only view sync status of their own tickets
- No GLPI credentials exposed in logs or error messages

## Future Enhancements

1. **Webhook Integration**: Listen for GLPI ticket changes in real-time
2. **Batch Sync**: Sync multiple tickets in single request
3. **Conflict Resolution**: Handle simultaneous updates to same ticket
4. **Attachments**: Sync ticket attachments bidirectionally
5. **Custom Fields**: Map GLPI custom fields to local ticket attributes
6. **Scheduled Sync**: Background job for periodic re-sync

## References

- GLPI REST API: https://glpi.org/doc/current/en/
- FastAPI: https://fastapi.tiangolo.com/
- SQLAlchemy: https://docs.sqlalchemy.org/
- Alembic: https://alembic.sqlalchemy.org/
