# AI Support Agent Backend

**Decision-Centric Intelligent Customer Support Platform**

Production-ready backend built with FastAPI, PostgreSQL, Redis, Celery, and Docker — featuring a full AI Decision Engine with intent classification, risk scoring, rule-based decision logic, smart agent routing, and human-in-the-loop escalation.

---

## Architecture Overview

```
├── main.py                           # Root entry point (re-exports app)
├── docker-compose.yml                # 7-service orchestration
├── Dockerfile                        # Multi-stage Python container
├── requirements.txt                  # Python dependencies
├── alembic/                          # Database migrations (9 revisions)
├── whatsapp-bridge/                  # Node.js WhatsApp Web bridge
│
└── app/
    ├── main.py                       # FastAPI app assembly (11 routers)
    ├── __init__.py
    │
    ├── core/
    │   ├── config.py                 # Pydantic-settings configuration
    │   ├── security.py               # JWT + bcrypt password hashing
    │   └── dependencies.py           # Shared dependency factories
    │
    ├── db/
    │   ├── base.py                   # SQLAlchemy declarative base + mixins
    │   ├── session.py                # Async session factory
    │   └── models/
    │       ├── enums.py              # Shared enumerations
    │       ├── user.py               # User model (roles, status, soft-delete)
    │       ├── conversation.py       # Conversation + Message models
    │       ├── ticket.py             # Ticket model (priority, escalation)
    │       ├── email.py              # Email ingestion model
    │       ├── gmail_credential.py   # Gmail OAuth2 credentials
    │       └── audit_log.py          # Audit trail (traceability)
    │
    ├── schemas/                      # Pydantic request/response models
    │   ├── auth.py, user.py          # Auth & user schemas
    │   ├── conversation.py, ticket.py, email.py
    │   ├── gmail.py, voice.py, whatsapp.py
    │   └── audit.py, common.py
    │
    ├── services/                     # Business logic layer
    │   ├── user_service.py
    │   ├── auth_service.py
    │   ├── redis_service.py
    │   ├── conversation_service.py
    │   ├── ticket_service.py
    │   ├── email_service.py
    │   ├── gmail_service.py          # Gmail OAuth2 + sync
    │   ├── transcription_service.py  # Whisper STT
    │   ├── tts_service.py            # Edge-TTS synthesis
    │   ├── whatsapp_service.py       # WhatsApp dual-provider
    │   └── audit_service.py
    │
    ├── api/
    │   ├── deps.py                   # Auth deps, RBAC (RoleChecker)
    │   └── routes/
    │       ├── health.py             # Health check
    │       ├── auth.py               # Register, login, refresh, logout
    │       ├── users.py              # CRUD, role-protected
    │       ├── conversations.py      # Chat module
    │       ├── tickets.py            # Ticketing module
    │       ├── emails.py             # Email ingestion
    │       ├── gmail.py              # Gmail OAuth2 integration
    │       ├── voice.py              # Speech-to-text + TTS
    │       ├── whatsapp.py           # WhatsApp messaging
    │       └── audit.py              # Admin-only audit logs
    │
    ├── decision_engine/              # Sprint 2 — AI Decision Engine
    │   ├── enums.py                  # IntentCategory, DecisionOutcome, RiskLevel
    │   ├── classifier.py             # Keyword + regex intent classification
    │   ├── scorer.py                 # 9-factor risk assessment
    │   ├── rules.py                  # Priority-ordered decision rules
    │   ├── decision_engine.py        # 9-step orchestrator pipeline
    │   ├── router_engine.py          # Skill-based agent routing
    │   ├── response_suggester.py     # Template-based response suggestions
    │   ├── escalation.py             # HITL escalation package builder
    │   ├── service.py                # DB operations (history, skills, stats)
    │   ├── routes.py                 # 10 API endpoints
    │   ├── tasks.py                  # Celery async analysis task
    │   ├── models.py                 # DecisionLog + AgentSkill models
    │   └── schemas.py                # Request/response schemas
    │
    ├── workers/
    │   ├── celery_app.py             # Celery config (6 queues, beat schedule)
    │   └── tasks.py                  # Email, Gmail, WhatsApp, logging tasks
    │
    └── utils/
        ├── middleware.py             # TraceID + request logging
        └── logging.py               # Structured logging setup
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | FastAPI 0.115.0 |
| Database | PostgreSQL 16 (alpine) |
| ORM | SQLAlchemy 2.0.35 (async + asyncpg) |
| Migrations | Alembic 1.13.3 |
| Cache / Blacklist | Redis 7 (alpine) |
| Task Queue | Celery 5.4.0 (6 queues) |
| Auth | JWT (access 15 min + refresh 7 days) |
| Password | bcrypt via passlib |
| Gmail | Google API Client + OAuth2 |
| Voice STT | faster-whisper 1.1.1 |
| Voice TTS | edge-tts 7.2.7 |
| WhatsApp | Meta Cloud API + Baileys Web Bridge |
| Containers | Docker + Docker Compose (7 services) |

## Docker Services

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| `api` | Dockerfile (Python) | **8000** | FastAPI app + Alembic migrations |
| `postgres` | postgres:16-alpine | 5432 | Primary database (`support_db`) |
| `redis` | redis:7-alpine | 6379 | Cache (DB 0), Celery broker (DB 2), results (DB 3) |
| `celery_worker` | Dockerfile (Python) | — | Background tasks (6 queues) |
| `celery_beat` | Dockerfile (Python) | — | Periodic scheduler (Gmail sync) |
| `flower` | Dockerfile (Python) | **5555** | Celery monitoring dashboard |
| `whatsapp_bridge` | whatsapp-bridge/Dockerfile (Node.js) | **3000** | Unofficial WhatsApp Web bridge |

## Quick Start

### Option 1: Docker (recommended)

```bash
# Copy environment variables
cp .env.example .env

# Start all 7 services
docker compose up --build -d

# API:     http://localhost:8000
# Swagger: http://localhost:8000/docs
# ReDoc:   http://localhost:8000/redoc
# Flower:  http://localhost:5555

# API container runs in stable mode by default (no Uvicorn reload).
# Optional hot reload in Docker:
# API_RELOAD=true docker compose up --build -d api
```

### Option 2: Local development

```bash
# 1. Create virtual environment
python -m venv .venv
.venv\Scripts\activate          # Windows
# source .venv/bin/activate     # Linux/Mac

# 2. Install dependencies
pip install -r requirements.txt

# 3. Start PostgreSQL and Redis
docker compose up postgres redis -d

# 4. Copy env file
cp .env.example .env

# 5. Run migrations
alembic upgrade head

# 6. Start the API
uvicorn app.main:app --reload

# 7. Start Celery worker (separate terminal)
celery -A app.workers.celery_app worker --loglevel=info -Q emails,logging,gmail,whatsapp,decision,celery

# 8. Start Celery beat (separate terminal)
celery -A app.workers.celery_app beat --loglevel=info
```

## API Endpoints (57+ routes)

## Notification Deep-Link Conventions

Use `action_url` in notifications to deep-link users directly into the right workspace context.

- Conversations deep link: `/conversations?user=<user_id>&conversation=<conversation_id>`
- WhatsApp deep link: `/whatsapp?conversation=<conversation_id>`

Notes:

- `conversation` should be the exact backend conversation UUID.
- Keep links relative (start with `/`) so the frontend router can open them in-app.
- When both list filters and `conversation` are present, UI should prioritize selecting the exact conversation.

### Health (`/`)
| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Service health check |

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

### Gmail (`/api/v1/gmail`)
| Method | Path | Description |
|--------|------|-------------|
| GET | `/authorize` | Generate Google OAuth2 authorization URL |
| GET | `/callback` | OAuth2 callback (exchange code for tokens) |
| GET | `/status` | Check Gmail connection status |
| POST | `/sync` | Manually trigger Gmail sync |
| DELETE | `/disconnect` | Deactivate Gmail integration |

### Voice (`/api/v1/voice`)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/transcribe` | Transcribe audio file (preview, no DB) |
| POST | `/message` | Transcribe audio → create Email + Ticket |
| POST | `/synthesize` | Text-to-speech → MP3 URL |
| GET | `/audio/{filename}` | Serve generated TTS audio |
| GET | `/voices` | List available TTS voices |

### WhatsApp (`/api/v1/whatsapp`)
| Method | Path | Access | Description |
|--------|------|--------|-------------|
| GET | `/webhook` | Public | Meta webhook verification |
| POST | `/webhook` | Public | Receive messages (Meta Cloud API) |
| POST | `/bridge/webhook` | Public | Receive messages (Web bridge) |
| POST | `/send` | Agent/Admin | Send message to phone number |
| POST | `/reply/{conversation_id}` | Agent/Admin | Reply in conversation |
| GET | `/status` | User | Provider status & config |
| GET | `/inbox` | Agent/Admin | List WhatsApp conversations |
| GET | `/inbox/{conversation_id}` | Agent/Admin | Get conversation messages |
| POST | `/inbox/{conversation_id}/read` | Agent/Admin | Mark messages as read |

### Decision Engine (`/api/v1/decision-engine`)
| Method | Path | Access | Description |
|--------|------|--------|-------------|
| POST | `/analyze` | Agent/Admin | Full AI analysis on a ticket |
| POST | `/analyze-text` | Agent/Admin | Analyze free text (preview, no DB) |
| GET | `/decisions/{ticket_id}` | Agent/Admin | Decision history for a ticket |
| POST | `/route/{ticket_id}` | Agent/Admin | Find best agent for ticket |
| GET | `/suggestions/{ticket_id}` | Agent/Admin | AI response suggestions |
| POST | `/escalate/{ticket_id}` | Agent/Admin | Generate escalation package |
| POST | `/agent-skills` | Admin | Create/update agent skill |
| GET | `/agent-skills` | Agent/Admin | List agent skills |
| DELETE | `/agent-skills/{skill_id}` | Admin | Delete agent skill |
| GET | `/stats` | Agent/Admin | Dashboard statistics |

### Audit (`/api/v1/audit`)
| Method | Path | Access | Description |
|--------|------|--------|-------------|
| GET | `/` | Admin | List audit logs |

## Decision Engine — How It Works

The AI Decision Engine is a rule-based, non-generative system that analyzes customer support tickets through a **9-step pipeline**:

```
┌─────────────────────────────────────────────────────────┐
│                    TICKET INPUT                         │
│         (subject + body + priority + metadata)          │
└──────────────────────┬──────────────────────────────────┘
                       │
                       ▼
              ┌────────────────┐
              │  1. CLASSIFY   │  Keyword + regex → IntentCategory
              │     INTENT     │  (TECHNICAL, BILLING, SECURITY, etc.)
              └───────┬────────┘
                      │
                      ▼
              ┌────────────────┐
              │  2. SCORE      │  9-factor weighted risk assessment
              │     RISK       │  → risk_score (0.0–1.0)
              └───────┬────────┘
                      │
                      ▼
              ┌────────────────┐
              │  3. APPLY      │  8 priority-ordered rules + fallback
              │     RULES      │  → DecisionOutcome
              └───────┬────────┘
                      │
                      ▼
         ┌────────────┼────────────────┐
         ▼            ▼                ▼
   AUTO_RESOLVE   CLARIFY       ESCALATE_TO_HUMAN
   SUGGEST_       MONITOR_      (HITL package)
   RESPONSE       AND_LEARN
         │            │                │
         ▼            ▼                ▼
  ┌────────────┐ ┌──────────┐  ┌──────────────┐
  │ 4.SUGGEST  │ │ 5.ROUTE  │  │ 6.ESCALATE   │
  │  RESPONSE  │ │  AGENT   │  │  (build pkg) │
  └────────────┘ └──────────┘  └──────────────┘
         │            │                │
         └────────────┼────────────────┘
                      ▼
              ┌────────────────┐
              │  7. BUILD      │  Human-readable reasoning
              │     REASON     │
              └───────┬────────┘
                      │
                      ▼
              ┌────────────────┐
              │  8. PERSIST    │  Save DecisionLog to DB
              │     TO DB      │
              └───────┬────────┘
                      │
                      ▼
              ┌────────────────┐
              │  9. AUTO       │  Auto-close / auto-assign
              │     ACTIONS    │  based on outcome
              └────────────────┘
```

### Decision Matrix

| Confidence | Risk | Outcome |
|-----------|------|---------|
| HIGH | LOW/MEDIUM | `AUTO_RESOLVE` |
| HIGH | HIGH/CRITICAL | `ESCALATE_TO_HUMAN` |
| MEDIUM | LOW | `SUGGEST_RESPONSE` |
| MEDIUM | MEDIUM | `MONITOR_AND_LEARN` |
| LOW | Any | `ESCALATE_TO_HUMAN` |
| Any | CRITICAL | `ESCALATE_TO_HUMAN` |

### Intent Categories

`TECHNICAL` · `BILLING` · `ACCOUNT` · `GENERAL_INQUIRY` · `COMPLAINT` · `SECURITY` · `FEATURE_REQUEST` · `OTHER`

Bilingual keyword support (French + English) with regex pattern matching.

### Agent Routing

Skill-based routing with workload balancing:
- Score = `proficiency × (1 − workload_ratio)`
- Fallback to least-loaded agent if no skill match

## Celery Task Queues

| Queue | Tasks |
|-------|-------|
| `emails` | `process_email_task` |
| `logging` | `log_action_task` |
| `gmail` | `sync_gmail_for_user_task`, `sync_all_gmail_accounts` |
| `whatsapp` | `process_whatsapp_incoming_task`, `record_whatsapp_outbound_task` |
| `decision` | `analyze_ticket_task` |
| `celery` | Default queue |

**Beat Schedule:** Periodic Gmail sync at configurable interval.

## Security

- **JWT**: Access tokens (15 min) + refresh tokens (7 days)
- **Password hashing**: bcrypt
- **Token blacklist**: Redis-backed (logout invalidation)
- **RBAC**: `CLIENT`, `AGENT`, `ADMIN` — enforced per-route via `RoleChecker`
- **Trace ID**: Every request tagged for end-to-end tracing
- **Audit logs**: All significant actions stored in DB
- **CORS**: Configurable allowed origins

## Design Decisions

1. **Service Layer pattern** — business logic isolated from routes and models
2. **Soft delete** — data never physically removed, supporting audit trails
3. **UUID primary keys** — no sequential ID leakage
4. **Async everything** — SQLAlchemy 2.0 async + asyncpg for non-blocking DB
5. **Celery for heavy tasks** — email processing, Gmail sync, WhatsApp, AI analysis
6. **Redis multi-DB** — separate DB numbers for blacklist (0), broker (2), results (3)
7. **Channel-agnostic conversations** — `ChannelType` enum supports chat, email, WhatsApp, voice
8. **Non-generative AI** — Decision engine uses explicit rules (no LLM hallucination risk)
9. **Dual WhatsApp providers** — Meta Cloud API (official) + Baileys Web bridge (unofficial)
10. **Decision traceability** — every AI decision logged with full reasoning chain

## Project Status

- **Sprint 1** — Backend foundation, auth, multi-channel (chat, email, tickets, Gmail, voice, WhatsApp)
- **Sprint 2** — AI Decision Engine (classification, scoring, rules, routing, escalation, HITL)
- **Sprint 3** — Visual AI module (planned)
- **Sprint 4** — Frontend + full integration (planned)

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
