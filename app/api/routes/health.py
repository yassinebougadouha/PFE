"""
Health / system routes.
"""

from fastapi import APIRouter

from app.core.config import get_settings
from app.schemas.common import HealthResponse

settings = get_settings()

router = APIRouter(tags=["System"])


@router.get("/health", response_model=HealthResponse)
async def health_check():
    return {
        "status": "healthy",
        "version": settings.APP_VERSION,
        "environment": settings.ENVIRONMENT,
    }
