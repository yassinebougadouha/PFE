"""
Decision Engine Service — DB operations for decision logs and agent skills.
"""

import uuid
import logging
from typing import Optional

from sqlalchemy import select, func, and_
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.ticket import Ticket
from app.db.models.user import User
from app.db.models.enums import UserRole, UserStatus
from app.decision_engine.models import DecisionLog, AgentSkill
from app.decision_engine.enums import IntentCategory, DecisionOutcome
from app.decision_engine.schemas import (
    DecisionLogResponse,
    DecisionHistoryResponse,
    AgentSkillCreate,
    AgentSkillResponse,
    AgentSkillListResponse,
    DecisionStats,
)

logger = logging.getLogger(__name__)


class DecisionService:
    """Service for decision engine DB operations."""

    def __init__(self, db: AsyncSession):
        self.db = db

    # ── Decision Logs ────────────────────────────────────

    async def get_decision_history(
        self,
        ticket_id: Optional[uuid.UUID] = None,
        skip: int = 0,
        limit: int = 20,
    ) -> DecisionHistoryResponse:
        """Get decision logs, optionally filtered by ticket."""
        filters = []
        if ticket_id is not None:
            filters.append(DecisionLog.ticket_id == ticket_id)

        count_q = select(func.count(DecisionLog.id))
        if filters:
            count_q = count_q.where(and_(*filters))
        total = (await self.db.execute(count_q)).scalar() or 0

        query = select(DecisionLog)
        if filters:
            query = query.where(and_(*filters))

        query = (
            query
            .order_by(DecisionLog.created_at.desc())
            .offset(skip)
            .limit(limit)
        )
        result = await self.db.execute(query)
        decisions = [
            DecisionLogResponse.model_validate(d)
            for d in result.scalars().all()
        ]

        return DecisionHistoryResponse(
            ticket_id=ticket_id,
            decisions=decisions,
            total=total,
        )

    async def get_latest_decision(self, ticket_id: uuid.UUID) -> Optional[DecisionLog]:
        """Get the most recent decision for a ticket."""
        query = (
            select(DecisionLog)
            .where(DecisionLog.ticket_id == ticket_id)
            .order_by(DecisionLog.created_at.desc())
            .limit(1)
        )
        result = await self.db.execute(query)
        return result.scalar_one_or_none()

    # ── Agent Skills ─────────────────────────────────────

    async def create_agent_skill(self, payload: AgentSkillCreate) -> AgentSkill:
        """Create or update an agent skill."""
        # Check if skill already exists for this agent + category
        existing = await self.db.execute(
            select(AgentSkill).where(
                AgentSkill.agent_id == payload.agent_id,
                AgentSkill.skill_category == payload.skill_category,
            )
        )
        skill = existing.scalar_one_or_none()

        if skill:
            # Update existing
            skill.proficiency = payload.proficiency
            skill.max_concurrent_tickets = payload.max_concurrent_tickets
        else:
            # Verify agent exists and has correct role
            agent = await self.db.execute(
                select(User).where(
                    User.id == payload.agent_id,
                    User.role.in_([UserRole.AGENT, UserRole.ADMIN]),
                    User.is_deleted == False,
                )
            )
            if not agent.scalar_one_or_none():
                raise ValueError(f"Agent {payload.agent_id} not found or not an agent/admin")

            skill = AgentSkill(
                agent_id=payload.agent_id,
                skill_category=payload.skill_category,
                proficiency=payload.proficiency,
                max_concurrent_tickets=payload.max_concurrent_tickets,
            )
            self.db.add(skill)

        await self.db.flush()
        await self.db.refresh(skill)
        return skill

    async def list_agent_skills(
        self,
        agent_id: Optional[uuid.UUID] = None,
        category: Optional[IntentCategory] = None,
    ) -> AgentSkillListResponse:
        """List agent skills with optional filters."""
        query = select(AgentSkill)
        count_q = select(func.count(AgentSkill.id))

        if agent_id:
            query = query.where(AgentSkill.agent_id == agent_id)
            count_q = count_q.where(AgentSkill.agent_id == agent_id)
        if category:
            query = query.where(AgentSkill.skill_category == category)
            count_q = count_q.where(AgentSkill.skill_category == category)

        total = (await self.db.execute(count_q)).scalar() or 0
        result = await self.db.execute(query.order_by(AgentSkill.created_at.desc()))
        skills = [
            AgentSkillResponse.model_validate(s)
            for s in result.scalars().all()
        ]
        return AgentSkillListResponse(skills=skills, total=total)

    async def delete_agent_skill(self, skill_id: uuid.UUID) -> bool:
        """Delete an agent skill."""
        result = await self.db.execute(
            select(AgentSkill).where(AgentSkill.id == skill_id)
        )
        skill = result.scalar_one_or_none()
        if not skill:
            return False
        await self.db.delete(skill)
        await self.db.flush()
        return True

    # ── Dashboard Stats ──────────────────────────────────

    async def get_stats(self) -> DecisionStats:
        """Get decision engine statistics for the dashboard."""
        total = (await self.db.execute(
            select(func.count(DecisionLog.id))
        )).scalar() or 0

        if total == 0:
            return DecisionStats(
                total_decisions=0,
                auto_resolved=0,
                escalated=0,
                routed=0,
                clarification_needed=0,
                avg_confidence=0.0,
                avg_risk=0.0,
                decisions_by_category={},
                decisions_by_outcome={},
                escalation_rate=0.0,
            )

        # Counts by outcome
        auto_resolved = (await self.db.execute(
            select(func.count(DecisionLog.id)).where(
                DecisionLog.decision_outcome == DecisionOutcome.AUTO_RESOLVE
            )
        )).scalar() or 0

        escalated = (await self.db.execute(
            select(func.count(DecisionLog.id)).where(
                DecisionLog.decision_outcome == DecisionOutcome.ESCALATE_HUMAN
            )
        )).scalar() or 0

        routed = (await self.db.execute(
            select(func.count(DecisionLog.id)).where(
                DecisionLog.decision_outcome == DecisionOutcome.ROUTE_AGENT
            )
        )).scalar() or 0

        clarification = (await self.db.execute(
            select(func.count(DecisionLog.id)).where(
                DecisionLog.decision_outcome == DecisionOutcome.CLARIFY
            )
        )).scalar() or 0

        # Averages
        avg_confidence = (await self.db.execute(
            select(func.avg(DecisionLog.confidence_score))
        )).scalar() or 0.0

        avg_risk = (await self.db.execute(
            select(func.avg(DecisionLog.risk_score))
        )).scalar() or 0.0

        # By category
        cat_results = await self.db.execute(
            select(DecisionLog.intent_category, func.count(DecisionLog.id))
            .group_by(DecisionLog.intent_category)
        )
        decisions_by_category = {
            row[0].value: row[1] for row in cat_results.all()
        }

        # By outcome
        out_results = await self.db.execute(
            select(DecisionLog.decision_outcome, func.count(DecisionLog.id))
            .group_by(DecisionLog.decision_outcome)
        )
        decisions_by_outcome = {
            row[0].value: row[1] for row in out_results.all()
        }

        return DecisionStats(
            total_decisions=total,
            auto_resolved=auto_resolved,
            escalated=escalated,
            routed=routed,
            clarification_needed=clarification,
            avg_confidence=round(float(avg_confidence), 3),
            avg_risk=round(float(avg_risk), 3),
            decisions_by_category=decisions_by_category,
            decisions_by_outcome=decisions_by_outcome,
            escalation_rate=round(escalated / total, 3) if total > 0 else 0.0,
        )
