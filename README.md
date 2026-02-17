# AI Support Agent Backend — Sprint 1

**Decision-Centric Intelligent Customer Support Platform**

Production-ready backend foundation built with FastAPI, PostgreSQL, Redis, Celery, and Docker.

---

## Architecture Overview

```
app/
├── main.py                    # FastAPI app assembly
├── core/
│   ├── config.py              # Pydantic-settings configuration
│   ├── security.py            # JWT + bcrypt password hashing
│   └── dependencies.py        # Shared dependency factories
├── db/
│   ├── base.py                # SQLAlchemy declarative base + mixins
│   ├── session.py             # Async session factory
│   └── models/
│       ├── enums.py           # Shared enumerations
│       ├── user.py            # User model (roles, status, soft-delete)
│       ├── conversation.py    # Conversation + Message models
│       ├── ticket.py          # Ticket model (priority, escalation flag)
│       ├── email.py           # Email ingestion model
│       └── audit_log.py       # Audit trail (traceability)
├── schemas/                   # Pydantic request/response models
├── services/                  # Business logic layer
│   ├── user_service.py
│   ├── auth_service.py
│   ├── redis_service.py
│   ├── conversation_service.py
│   ├── ticket_service.py
│   ├── email_service.py
│   └── audit_service.py
├── api/
│   ├── deps.py                # Auth deps, RBAC (RoleChecker)
│   └── routes/
│       ├── health.py
│       ├── auth.py            # Register, login, refresh, logout
│       ├── users.py           # CRUD, role-protected
│       ├── conversations.py   # Chat module
│       ├── tickets.py         # Ticketing module
│       ├── emails.py          # Email ingestion
│       └── audit.py           # Admin-only audit logs
├── workers/
│   ├── celery_app.py          # Celery configuration
│   └── tasks.py               # Email processing, background logging
└── utils/
    ├── middleware.py           # TraceID + request logging
    └── logging.py             # Structured logging setup
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | FastAPI |
| Database | PostgreSQL 16 |
| ORM | SQLAlchemy 2.0 (async) |
| Migrations | Alembic |
| Cache | Redis 7 |
| Task Queue | Celery 5 |
| Auth | JWT (access 15min + refresh 7d) |
| Password | bcrypt via passlib |
| Containers | Docker + Docker Compose |

## Quick Start

### Option 1: Docker (recommended)

```bash
# Copy environment variables
cp .env.example .env

# Start all services
docker compose up --build

# API available at http://localhost:8000
# Swagger docs at http://localhost:8000/docs
# Flower (Celery monitoring) at http://localhost:5555
```

### Option 2: Local development

```bash
# 1. Create virtual environment
python -m venv .venv
.venv\Scripts\activate  # Windows
# source .venv/bin/activate  # Linux/Mac

# 2. Install dependencies
pip install -r requirements.txt

# 3. Start PostgreSQL and Redis (Docker or local)
docker compose up postgres redis -d

# 4. Copy env file
cp .env.example .env

# 5. Run migrations
alembic upgrade head

# 6. Start the API
uvicorn app.main:app --reload

# 7. Start Celery worker (separate terminal)
celery -A app.workers.celery_app worker --loglevel=info -Q emails,logging,celery
```

### Generate first migration

```bash
alembic revision --autogenerate -m "initial_schema"
alembic upgrade head
```

## API Endpoints

### Auth (`/api/v1/auth`)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/register` | Create account |
| POST | `/login` | Get JWT tokens |
| POST | `/refresh` | Refresh access token |
| POST | `/logout` | Blacklist tokens |

### Users (`/api/v1/users`)
| Method | Path | Access | Description |
|--------|------|--------|-------------|
| GET | `/me` | Any | Current user profile |
| GET | `/` | Agent/Admin | List users |
| GET | `/{id}` | Agent/Admin | Get user |
| PATCH | `/{id}` | Admin | Update user |
| DELETE | `/{id}` | Admin | Soft-delete user |

### Conversations (`/api/v1/conversations`)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/` | Start conversation |
| GET | `/` | List conversations |
| GET | `/{id}` | Get conversation |
| PATCH | `/{id}` | Update status |
| POST | `/{id}/messages` | Send message |
| GET | `/{id}/messages` | Get messages |

### Tickets (`/api/v1/tickets`)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/` | Create ticket |
| GET | `/` | List tickets |
| GET | `/{id}` | Get ticket |
| PATCH | `/{id}` | Update ticket |
| POST | `/{id}/assign/{agent_id}` | Assign to agent |
| DELETE | `/{id}` | Soft-delete |

### Emails (`/api/v1/emails`)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/ingest` | Ingest email (triggers Celery) |
| GET | `/{id}` | Get email |

### Audit (`/api/v1/audit`)
| Method | Path | Access | Description |
|--------|------|--------|-------------|
| GET | `/` | Admin | List audit logs |

## Security

- **JWT**: Access tokens (15 min) + refresh tokens (7 days)
- **Password hashing**: bcrypt
- **Token blacklist**: Redis-backed (logout invalidation)
- **RBAC**: `CLIENT`, `AGENT`, `ADMIN` — enforced per-route via `RoleChecker`
- **Trace ID**: Every request tagged for end-to-end tracing
- **Audit logs**: All significant actions stored in DB

## Design Decisions

1. **Service Layer pattern** — business logic isolated from routes and models
2. **Soft delete** — data never physically removed, supporting audit trails
3. **UUID primary keys** — no sequential ID leakage
4. **Async everything** — SQLAlchemy 2.0 async + asyncpg for non-blocking DB
5. **Celery for heavy tasks** — email processing runs in background workers
6. **Redis multi-DB** — separate DB numbers for cache, blacklist, and Celery broker
7. **Escalation flag placeholder** — Ticket model ready for Sprint 2 decision engine
8. **Channel-agnostic conversations** — `ChannelType` enum supports future call transcripts

## Future-Ready (Sprint 2+)

The architecture explicitly supports:
- **Decision engine** integration (confidence/risk scoring on conversations)
- **Human-in-the-loop** escalation workflows
- **Visual AI** module (screenshot analysis)
- **Admin dashboard** metrics (audit logs + ticket analytics)
- **Microservice extraction** (workers already isolated)


A FastAPI backend application.

## Setup

1. Create a virtual environment:
```bash
python -m venv venv
```

2. Activate the virtual environment:
```bash
# Windows
venv\Scripts\activate

# Linux/Mac
source venv/bin/activate
```

3. Install dependencies:
```bash
pip install -r requirements.txt
```

## Running the Application

```bash
uvicorn main:app --reload
```

The API will be available at `http://localhost:8000`

## API Documentation

Once running, visit:
- Swagger UI: `http://localhost:8000/docs`
- ReDoc: `http://localhost:8000/redoc`
