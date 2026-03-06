"""
Smart Routing Engine — routes tickets to the best-suited agent
based on skills, workload, and availability.

Routing algorithm:
  1. Find agents with matching skill category
  2. Filter out agents at max capacity
  3. Score agents: proficiency * (1 - workload_ratio)
  4. Select the best-scoring agent
  5. Fallback: round-robin among all active agents
"""

import uuid
import logging
from typing import Optional

from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.user import User
from app.db.models.ticket import Ticket
from app.db.models.enums import UserRole, UserStatus, TicketStatus
from app.decision_engine.enums import IntentCategory
from app.decision_engine.models import AgentSkill
from app.decision_engine.schemas import RoutingResult, RoutingResponse

logger = logging.getLogger(__name__)


async def find_best_agent(
    db: AsyncSession,
    ticket_id: uuid.UUID,
    category: IntentCategory,
) -> RoutingResponse:
    """
    Find the best agent to handle a ticket based on skill matching and workload.

    Args:
        db: Database session.
        ticket_id: The ticket to route.
        category: The classified intent category.

    Returns:
        RoutingResponse with selected agent and candidate list.
    """
    candidates: list[RoutingResult] = []

    # ── Step 1: Find agents with matching skill ──────────
    skill_query = (
        select(AgentSkill, User)
        .join(User, AgentSkill.agent_id == User.id)
        .where(
            AgentSkill.skill_category == category,
            User.role.in_([UserRole.AGENT, UserRole.ADMIN]),
            User.status == UserStatus.ACTIVE,
            User.is_deleted == False,
        )
        .order_by(AgentSkill.proficiency.desc())
    )
    result = await db.execute(skill_query)
    skill_agents = result.all()

    for skill, agent in skill_agents:
        # Count current open/in_progress tickets assigned to this agent
        workload_q = select(func.count(Ticket.id)).where(
            Ticket.assigned_agent_id == agent.id,
            Ticket.status.in_([TicketStatus.OPEN, TicketStatus.IN_PROGRESS]),
            Ticket.is_deleted == False,
        )
        workload_count = (await db.execute(workload_q)).scalar() or 0

        # Skip if at max capacity
        if workload_count >= skill.max_concurrent_tickets:
            logger.info(
                f"Agent {agent.email} at max capacity ({workload_count}/{skill.max_concurrent_tickets})"
            )
            continue

        # Score: proficiency weighted by available capacity
        workload_ratio = workload_count / skill.max_concurrent_tickets
        score = round(skill.proficiency * (1.0 - workload_ratio), 3)

        candidates.append(RoutingResult(
            agent_id=agent.id,
            agent_name=agent.full_name,
            agent_email=agent.email,
            skill_match_score=score,
            current_workload=workload_count,
            max_capacity=skill.max_concurrent_tickets,
            reasoning=(
                f"Skill match: {category.value} "
                f"(proficiency={skill.proficiency}, "
                f"workload={workload_count}/{skill.max_concurrent_tickets})"
            ),
        ))

    # Sort candidates by score descending
    candidates.sort(key=lambda c: c.skill_match_score, reverse=True)

    if candidates:
        return RoutingResponse(
            ticket_id=ticket_id,
            selected_agent=candidates[0],
            candidates=candidates,
            auto_assigned=False,
        )

    # ── Step 2: Fallback — any active agent with lowest workload ──
    logger.info(
        f"No skilled agent found for category {category.value}. "
        f"Falling back to least-loaded agent."
    )
    fallback_query = (
        select(User)
        .where(
            User.role.in_([UserRole.AGENT, UserRole.ADMIN]),
            User.status == UserStatus.ACTIVE,
            User.is_deleted == False,
        )
    )
    fallback_result = await db.execute(fallback_query)
    all_agents = fallback_result.scalars().all()

    fallback_candidates: list[RoutingResult] = []
    for agent in all_agents:
        workload_q = select(func.count(Ticket.id)).where(
            Ticket.assigned_agent_id == agent.id,
            Ticket.status.in_([TicketStatus.OPEN, TicketStatus.IN_PROGRESS]),
            Ticket.is_deleted == False,
        )
        workload_count = (await db.execute(workload_q)).scalar() or 0

        fallback_candidates.append(RoutingResult(
            agent_id=agent.id,
            agent_name=agent.full_name,
            agent_email=agent.email,
            skill_match_score=0.0,  # no skill match
            current_workload=workload_count,
            max_capacity=10,  # default capacity
            reasoning=f"Fallback routing (no skill match for {category.value})",
        ))

    # Sort by workload ascending (least loaded first)
    fallback_candidates.sort(key=lambda c: c.current_workload)

    if fallback_candidates:
        return RoutingResponse(
            ticket_id=ticket_id,
            selected_agent=fallback_candidates[0],
            candidates=fallback_candidates,
            auto_assigned=False,
        )

    # No agents available at all
    logger.warning("No agents available for routing")
    return RoutingResponse(
        ticket_id=ticket_id,
        selected_agent=None,
        candidates=[],
        auto_assigned=False,
    )
