"""
Application entry point — assembles FastAPI app with all routes and middleware.
"""

from contextlib import asynccontextmanager
from pathlib import Path

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles

from app.core.config import get_settings
from app.utils.logging import setup_logging
from app.utils.middleware import TraceIDMiddleware, RequestLoggingMiddleware

settings = get_settings()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup / shutdown events."""
    setup_logging()
    yield


app = FastAPI(
    title=settings.APP_NAME,
    version=settings.APP_VERSION,
    docs_url="/docs",
    redoc_url="/redoc",
    openapi_url="/openapi.json",
    lifespan=lifespan,
)

# ── Middleware (order matters — outermost first) ─────────
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.CORS_ORIGINS,
    # Browsers reject wildcard origins when credentials are enabled.
    allow_credentials="*" not in settings.CORS_ORIGINS,
    allow_methods=["*"],
    allow_headers=["*"],
)
app.add_middleware(RequestLoggingMiddleware)
app.add_middleware(TraceIDMiddleware)

# ── Static Files ─────────────────────────────────────────
# Serve uploaded files (profile pictures, attachments, etc.)
uploads_dir = Path(settings.UPLOADS_DIR)
if uploads_dir.exists():
    app.mount("/uploads", StaticFiles(directory=str(uploads_dir)), name="uploads")

# ── Routes ──────────────────────────────────────────────
from app.api.routes import health, auth, users, conversations, tickets, emails, audit, gmail, voice, whatsapp, internal, voice_calls, voice_agents, settings as settings_routes, notifications as notifications_routes, dashboard as dashboard_routes  # noqa: E402
from app.decision_engine.routes import router as decision_engine_router  # noqa: E402
from app.rag.routes import router as rag_router  # noqa: E402
from app.rag.response_providers.routes import router as response_gen_router  # noqa: E402
from app.visual_ai.routes import router as visual_ai_router  # noqa: E402

app.include_router(health.router)
app.include_router(auth.router, prefix=settings.API_V1_PREFIX)
app.include_router(users.router, prefix=settings.API_V1_PREFIX)
app.include_router(conversations.router, prefix=settings.API_V1_PREFIX)
app.include_router(tickets.router, prefix=settings.API_V1_PREFIX)
app.include_router(emails.router, prefix=settings.API_V1_PREFIX)
app.include_router(audit.router, prefix=settings.API_V1_PREFIX)
app.include_router(gmail.router, prefix=settings.API_V1_PREFIX)
app.include_router(voice.router, prefix=settings.API_V1_PREFIX)
app.include_router(whatsapp.router, prefix=settings.API_V1_PREFIX)
app.include_router(decision_engine_router, prefix=settings.API_V1_PREFIX)
app.include_router(rag_router, prefix=settings.API_V1_PREFIX)
app.include_router(response_gen_router, prefix=settings.API_V1_PREFIX)
app.include_router(visual_ai_router, prefix=settings.API_V1_PREFIX)
app.include_router(internal.router, prefix=settings.API_V1_PREFIX)
app.include_router(voice_calls.router, prefix=settings.API_V1_PREFIX)
app.include_router(voice_agents.router, prefix=settings.API_V1_PREFIX)
app.include_router(settings_routes.router, prefix=settings.API_V1_PREFIX)
app.include_router(notifications_routes.router, prefix=settings.API_V1_PREFIX)
app.include_router(dashboard_routes.router, prefix=settings.API_V1_PREFIX)
