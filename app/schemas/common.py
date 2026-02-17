"""
Shared / generic schemas.
"""

from pydantic import BaseModel


class HealthResponse(BaseModel):
    status: str
    version: str
    environment: str


class MessageOut(BaseModel):
    message: str
