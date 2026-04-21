"""
Client ticket AI helper schemas.
"""

import uuid
from typing import Optional

from pydantic import BaseModel, Field


class TicketClassifyRequest(BaseModel):
    title: str = Field(default="", max_length=500)
    description: str = Field(default="")


class TicketClassifyResponse(BaseModel):
    available: bool
    category: Optional[str] = None
    category_label: Optional[str] = None
    priority: Optional[int] = None
    priority_label: Optional[str] = None
    urgency: Optional[int] = None
    confidence: Optional[int] = None
    solutions: list[str] = Field(default_factory=list)


class TicketReformulateRequest(BaseModel):
    title: str = Field(default="", max_length=500)
    description: str = Field(default="")


class TicketReformulateResponse(BaseModel):
    available: bool
    reformulated: str


class SimilarTicketItem(BaseModel):
    id: Optional[uuid.UUID] = None
    title: str
    description: Optional[str] = None
    solution: Optional[str] = None
    source: str = "local"


class SimilarTicketsResponse(BaseModel):
    tickets: list[SimilarTicketItem]
