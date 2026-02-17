"""
Middleware: trace ID injection, request logging, audit logging.
"""

import time
import uuid
import logging

from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import Response

logger = logging.getLogger("app.middleware")


class TraceIDMiddleware(BaseHTTPMiddleware):
    """
    Injects a unique trace_id into every request for distributed tracing.
    If the client sends X-Trace-ID, it is reused; otherwise a new UUID is generated.
    The trace_id is also returned in the response headers.
    """

    async def dispatch(self, request: Request, call_next):
        trace_id = request.headers.get("X-Trace-ID", str(uuid.uuid4()))
        request.state.trace_id = trace_id

        response: Response = await call_next(request)
        response.headers["X-Trace-ID"] = trace_id
        return response


class RequestLoggingMiddleware(BaseHTTPMiddleware):
    """
    Logs every request with method, path, status code, and duration.
    """

    async def dispatch(self, request: Request, call_next):
        start = time.perf_counter()
        response: Response = await call_next(request)
        duration_ms = round((time.perf_counter() - start) * 1000, 2)

        trace_id = getattr(request.state, "trace_id", "-")
        logger.info(
            f"[{trace_id}] {request.method} {request.url.path} "
            f"→ {response.status_code} ({duration_ms}ms)"
        )
        return response
