"""
Timeline Tracker — manages ordered UI state records per conversation.

Tracks the sequence of UI states a user goes through during a support
conversation, enabling:
  - Step-by-step journey replay
  - Gap detection at each transition
  - Progress tracking through expected workflows
"""

from __future__ import annotations

import logging
import uuid
from typing import Optional, Sequence

from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession

from app.visual_ai.models import UIState, VisualAnalysis, Screenshot
from app.visual_ai.enums import GapSeverity
from app.visual_ai.schemas import (
    UIStateResponse, TimelineResponse, GapResult,
)

logger = logging.getLogger(__name__)


async def get_next_sequence_num(db: AsyncSession, conversation_id: uuid.UUID) -> int:
    """Get the next sequence number for a conversation's timeline."""
    result = await db.execute(
        select(func.coalesce(func.max(UIState.sequence_num), -1))
        .where(UIState.conversation_id == conversation_id)
    )
    current_max = result.scalar_one()
    return current_max + 1


async def add_state(
    db: AsyncSession,
    *,
    conversation_id: uuid.UUID,
    analysis_id: Optional[uuid.UUID] = None,
    screenshot_id: Optional[uuid.UUID] = None,
    state_label: Optional[str] = None,
    state_data: Optional[dict] = None,
    embedding: Optional[list[float]] = None,
    gap_result: Optional[GapResult] = None,
) -> UIState:
    """
    Add a new UI state entry to a conversation's timeline.

    Automatically assigns the next sequence number and stores
    any gap detection results.
    """
    seq = await get_next_sequence_num(db, conversation_id)

    gap_detected = False
    gap_severity = None
    gap_details = None

    if gap_result and gap_result.severity != GapSeverity.NO_GAP:
        gap_detected = True
        gap_severity = gap_result.severity
        gap_details = gap_result.model_dump()

    ui_state = UIState(
        conversation_id=conversation_id,
        analysis_id=analysis_id,
        screenshot_id=screenshot_id,
        state_label=state_label,
        state_data=state_data or {},
        embedding=embedding,
        sequence_num=seq,
        gap_detected=gap_detected,
        gap_severity=gap_severity,
        gap_details=gap_details,
    )

    db.add(ui_state)
    await db.flush()
    await db.refresh(ui_state)
    return ui_state


async def get_timeline(
    db: AsyncSession,
    conversation_id: uuid.UUID,
    *,
    limit: int = 100,
    offset: int = 0,
) -> TimelineResponse:
    """
    Retrieve the full UI timeline for a conversation, ordered by sequence.
    """
    # Get states ordered by sequence
    result = await db.execute(
        select(UIState)
        .where(UIState.conversation_id == conversation_id)
        .order_by(UIState.sequence_num.asc())
        .offset(offset)
        .limit(limit)
    )
    states = list(result.scalars().all())

    # Count total and gaps
    count_result = await db.execute(
        select(func.count(UIState.id))
        .where(UIState.conversation_id == conversation_id)
    )
    total = count_result.scalar_one()

    gap_count_result = await db.execute(
        select(func.count(UIState.id))
        .where(
            UIState.conversation_id == conversation_id,
            UIState.gap_detected == True,  # noqa: E712
        )
    )
    gaps = gap_count_result.scalar_one()

    state_responses = [
        UIStateResponse(
            id=s.id,
            conversation_id=s.conversation_id,
            screenshot_id=s.screenshot_id,
            analysis_id=s.analysis_id,
            state_label=s.state_label,
            sequence_num=s.sequence_num,
            gap_detected=s.gap_detected,
            gap_severity=s.gap_severity,
            gap_details=s.gap_details,
            created_at=s.created_at,
        )
        for s in states
    ]

    return TimelineResponse(
        conversation_id=conversation_id,
        states=state_responses,
        total_states=total,
        gaps_detected=gaps,
    )


async def get_latest_state(
    db: AsyncSession,
    conversation_id: uuid.UUID,
) -> Optional[UIState]:
    """Get the most recent UI state for a conversation."""
    result = await db.execute(
        select(UIState)
        .where(UIState.conversation_id == conversation_id)
        .order_by(UIState.sequence_num.desc())
        .limit(1)
    )
    return result.scalar_one_or_none()


async def get_state_by_id(
    db: AsyncSession,
    state_id: uuid.UUID,
) -> Optional[UIState]:
    """Get a UI state by its ID."""
    result = await db.execute(
        select(UIState).where(UIState.id == state_id)
    )
    return result.scalar_one_or_none()
