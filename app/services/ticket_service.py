"""
Ticket service â€” CRUD, assignment, auto-routing, and status transitions.
"""

import uuid
from datetime import datetime, timezone
from typing import Optional
import logging

from sqlalchemy import func, or_, select, text
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.db.models.enums import ChannelType, TicketPriority, TicketStatus, UserRole, UserStatus
from app.db.models.ticket import Ticket
from app.db.models.user import User
from app.db.session import laravel_session_factory
from app.decision_engine.decision_engine import analyze_ticket
from app.decision_engine.enums import DecisionOutcome
from app.decision_engine.classifier import classify_text
from app.decision_engine.models import AgentSkill
from app.integrations.glpi_client import GlpiClient, GlpiClientError
from app.schemas.ticket import GlpiTicketIngestRequest, TicketCreate, TicketStatusUpdate, TicketUpdate
from app.services.ticket_notification_service import TicketNotificationService

logger = logging.getLogger(__name__)
settings = get_settings()

OPEN_WORKLOAD_STATUSES = {
    TicketStatus.OPEN,
    TicketStatus.IN_PROGRESS,
    TicketStatus.WAITING_ON_CUSTOMER,
    TicketStatus.ESCALATED,
}


class TicketService:

    def __init__(self, db: AsyncSession):
        self.db = db
        self.notification_service = TicketNotificationService(db)

    async def create_ticket(self, creator_id: uuid.UUID, payload: TicketCreate) -> Ticket:
        ticket = Ticket(
            subject=payload.subject,
            description=payload.description,
            priority=payload.priority,
            channel_source=payload.channel_source,
            conversation_id=payload.conversation_id,
            source_voice_call_id=payload.source_voice_call_id,
            creator_id=creator_id,
            glpi_sync_status="pending" if settings.GLPI_AUTO_SYNC else "skipped",
        )
        self.db.add(ticket)
        await self.db.flush()

        decision = await analyze_ticket(
            db=self.db,
            ticket=ticket,
            auto_assign=True,
            auto_update_priority=True,
        )

        await self.db.flush()
        await self.db.refresh(ticket)

        # Auto-sync to GLPI if enabled
        if settings.GLPI_AUTO_SYNC:
            await self.sync_to_glpi(ticket, escalation_summary=decision.escalation_summary)

        if decision.decision_outcome != DecisionOutcome.AUTO_RESOLVE:
            await self.notification_service.notify_new_ticket(ticket)
        if ticket.assigned_agent_id:
            await self.notification_service.notify_assignment(ticket, ticket.assigned_agent_id)
        await self._notify_creator_decision_action(ticket, decision)

        return ticket

    async def ingest_glpi_ticket(self, creator_id: uuid.UUID, payload: GlpiTicketIngestRequest) -> tuple[Ticket, object]:
        result = await self.db.execute(
            select(Ticket).where(
                Ticket.glpi_ticket_id == payload.glpi_ticket_id,
                Ticket.is_deleted == False,
            )
        )
        ticket = result.scalar_one_or_none()
        if ticket:
            ticket.subject = payload.subject
            ticket.description = payload.description
            ticket.priority = payload.priority
            ticket.channel_source = payload.channel_source
            ticket.creator_id = creator_id
            ticket.glpi_ticket_id = payload.glpi_ticket_id
            ticket.glpi_sync_status = "synced"
            ticket.glpi_sync_error = None
        else:
            ticket = Ticket(
                subject=payload.subject,
                description=payload.description,
                priority=payload.priority,
                channel_source=payload.channel_source,
                creator_id=creator_id,
                glpi_ticket_id=payload.glpi_ticket_id,
                glpi_sync_status="synced",
                glpi_sync_error=None,
            )
            self.db.add(ticket)

        await self.db.flush()

        decision = await analyze_ticket(
            db=self.db,
            ticket=ticket,
            auto_assign=True,
            auto_update_priority=True,
        )

        await self.db.flush()
        await self.db.refresh(ticket)

        # Sync any auto-actions back to GLPI
        if settings.GLPI_AUTO_SYNC:
            await self.sync_to_glpi(ticket, escalation_summary=decision.escalation_summary)

        return ticket, decision

    async def _notify_creator_decision_action(self, ticket: Ticket, decision) -> None:
        first_suggestion = decision.response_suggestions[0] if decision.response_suggestions else None

        if decision.decision_outcome == DecisionOutcome.AUTO_RESOLVE:
            await self.notification_service.notification_service.create_notification(
                user_id=ticket.creator_id,
                type="ticket_auto_resolved",
                title=f"Ticket resolved: {ticket.subject[:60]}",
                body=first_suggestion or "Your ticket was resolved automatically by the support decision engine.",
                resource_type="ticket",
                resource_id=str(ticket.id),
                action_url=f"/tickets/{ticket.id}",
                meta={
                    "ticket_id": str(ticket.id),
                    "decision_outcome": decision.decision_outcome.value,
                    "confidence": decision.confidence_score,
                    "risk": decision.risk_score,
                },
            )
        elif decision.decision_outcome == DecisionOutcome.CLARIFY:
            await self.notification_service.notification_service.create_notification(
                user_id=ticket.creator_id,
                type="ticket_clarification_requested",
                title=f"More information needed: {ticket.subject[:60]}",
                body=first_suggestion or "Please add more details so support can handle your ticket accurately.",
                resource_type="ticket",
                resource_id=str(ticket.id),
                action_url=f"/tickets/{ticket.id}",
                meta={
                    "ticket_id": str(ticket.id),
                    "decision_outcome": decision.decision_outcome.value,
                    "confidence": decision.confidence_score,
                    "risk": decision.risk_score,
                },
            )

    async def get_ticket(self, ticket_id: uuid.UUID) -> Optional[Ticket]:
        result = await self.db.execute(
            select(Ticket).where(Ticket.id == ticket_id, Ticket.is_deleted == False)
        )
        return result.scalar_one_or_none()

    async def list_tickets(
        self,
        creator_id: Optional[uuid.UUID] = None,
        assigned_agent_id: Optional[uuid.UUID] = None,
        include_unassigned: bool = False,
        status: Optional[TicketStatus] = None,
        priority: Optional[TicketPriority] = None,
        include_total: bool = True,
        skip: int = 0,
        limit: int = 50,
    ) -> tuple[list[Ticket], int]:
        query = select(Ticket).where(Ticket.is_deleted == False)
        count_q = select(func.count(Ticket.id)).where(Ticket.is_deleted == False)

        if creator_id:
            query = query.where(Ticket.creator_id == creator_id)
            count_q = count_q.where(Ticket.creator_id == creator_id)
        if assigned_agent_id:
            if include_unassigned:
                query = query.where(
                    or_(
                        Ticket.assigned_agent_id == assigned_agent_id,
                        Ticket.assigned_agent_id == None,
                    )
                )
                count_q = count_q.where(
                    or_(
                        Ticket.assigned_agent_id == assigned_agent_id,
                        Ticket.assigned_agent_id == None,
                    )
                )
            else:
                query = query.where(Ticket.assigned_agent_id == assigned_agent_id)
                count_q = count_q.where(Ticket.assigned_agent_id == assigned_agent_id)
        if status:
            query = query.where(Ticket.status == status)
            count_q = count_q.where(Ticket.status == status)
        if priority:
            query = query.where(Ticket.priority == priority)
            count_q = count_q.where(Ticket.priority == priority)

        total = ((await self.db.execute(count_q)).scalar() or 0) if include_total else 0
        result = await self.db.execute(
            query.offset(skip).limit(limit).order_by(Ticket.created_at.desc())
        )
        return list(result.scalars().all()), total

    async def count_by_status(
        self,
        creator_id: Optional[uuid.UUID] = None,
        assigned_agent_id: Optional[uuid.UUID] = None,
        include_unassigned: bool = False,
        priority: Optional[TicketPriority] = None,
    ) -> dict[TicketStatus, int]:
        query = select(Ticket.status, func.count(Ticket.id)).where(Ticket.is_deleted == False)

        if creator_id:
            query = query.where(Ticket.creator_id == creator_id)
        if assigned_agent_id:
            if include_unassigned:
                query = query.where(
                    or_(
                        Ticket.assigned_agent_id == assigned_agent_id,
                        Ticket.assigned_agent_id == None,
                    )
                )
            else:
                query = query.where(Ticket.assigned_agent_id == assigned_agent_id)
        if priority:
            query = query.where(Ticket.priority == priority)

        result = await self.db.execute(query.group_by(Ticket.status))
        counts: dict[TicketStatus, int] = {}
        for status_value, count in result.all():
            if status_value is None:
                continue
            counts[status_value] = int(count or 0)
        return counts

    async def update_ticket(self, ticket_id: uuid.UUID, payload: TicketUpdate) -> Optional[Ticket]:
        ticket = await self.get_ticket(ticket_id)
        if not ticket:
            return None
        payload_data = payload.model_dump(exclude_unset=True)
        for field, value in payload_data.items():
            setattr(ticket, field, value)
        await self.db.flush()
        await self.db.refresh(ticket)
        if payload_data and settings.GLPI_AUTO_SYNC:
            await self.sync_to_glpi(ticket)
        return ticket

    async def assign_agent(self, ticket_id: uuid.UUID, agent_id: uuid.UUID) -> Optional[Ticket]:
        ticket = await self.get_ticket(ticket_id)
        if not ticket:
            return None

        result = await self.db.execute(
            select(User).where(
                User.id == agent_id,
                User.role.in_([UserRole.AGENT, UserRole.ADMIN]),
                User.status == UserStatus.ACTIVE,
                User.is_deleted == False,
            )
        )
        assignee = result.scalar_one_or_none()
        if not assignee:
            raise ValueError("Assignee not found or inactive.")

        ticket.assigned_agent_id = agent_id
        if ticket.status == TicketStatus.OPEN:
            ticket.status = TicketStatus.IN_PROGRESS
        await self.db.flush()
        await self.db.refresh(ticket)
        if settings.GLPI_AUTO_SYNC:
            await self.sync_to_glpi(ticket)
        await self.notification_service.notify_assignment(ticket, assignee.id)
        return ticket

    async def select_auto_assignee(self, *, ticket: Ticket, method: str) -> Optional[User]:
        """Resolve an assignee using configured auto-assignment strategies."""
        return await self._select_auto_assignee(ticket=ticket, method=method)

    async def update_ticket_status(
        self,
        ticket_id: uuid.UUID,
        payload: TicketStatusUpdate,
        *,
        actor: User,
    ) -> Optional[Ticket]:
        ticket = await self.get_ticket(ticket_id)
        if not ticket:
            return None

        previous_status = ticket.status
        note = (payload.resolution_note or "").strip() or None
        if payload.status in {TicketStatus.RESOLVED, TicketStatus.CLOSED} and not note:
            raise ValueError("A resolution note is required when resolving or closing a ticket.")

        ticket.status = payload.status
        if payload.status in {TicketStatus.RESOLVED, TicketStatus.CLOSED}:
            ticket.resolution_note = note
            ticket.resolved_at = datetime.now(timezone.utc)
            ticket.solved_by_id = actor.id
            if ticket.assigned_agent_id is None and actor.role in {UserRole.AGENT, UserRole.ADMIN}:
                ticket.assigned_agent_id = actor.id
        else:
            if note:
                ticket.resolution_note = note
            if payload.status in OPEN_WORKLOAD_STATUSES:
                ticket.resolved_at = None
                ticket.solved_by_id = None

        await self.db.flush()
        await self.db.refresh(ticket)
        
        # Sync status update to GLPI if enabled
        if settings.GLPI_AUTO_SYNC:
            await self.sync_to_glpi(ticket)
        
        await self.notification_service.notify_status_change(
            ticket=ticket,
            previous_status=previous_status,
            actor=actor,
        )
        return ticket

    async def soft_delete(self, ticket_id: uuid.UUID) -> bool:
        ticket = await self.get_ticket(ticket_id)
        if not ticket:
            return False
        ticket.is_deleted = True
        ticket.deleted_at = datetime.now(timezone.utc)
        await self.db.flush()
        if settings.GLPI_AUTO_SYNC:
            await self.delete_from_glpi(ticket)
        return True

    async def _select_auto_assignee(self, *, ticket: Ticket, method: str) -> Optional[User]:
        normalized_method = self._normalize_assignment_method(method)

        result = await self.db.execute(
            select(User).where(
                User.role.in_([UserRole.AGENT, UserRole.ADMIN]),
                User.status == UserStatus.ACTIVE,
                User.is_deleted == False,
            )
        )
        candidates = list(result.scalars().all())
        if not candidates:
            return None

        stats = await self._load_candidate_stats([candidate.id for candidate in candidates])

        if normalized_method == "By category":
            category_candidate = await self._select_category_assignee(ticket, candidates, stats)
            if category_candidate:
                return category_candidate

        def workload_key(user: User) -> tuple[int, datetime]:
            entry = stats.get(user.id) or {"open_count": 0, "last_assigned_at": datetime.min.replace(tzinfo=timezone.utc)}
            return (
                int(entry["open_count"]),
                entry["last_assigned_at"],
            )

        def round_robin_key(user: User) -> tuple[datetime, int]:
            entry = stats.get(user.id) or {"open_count": 0, "last_assigned_at": datetime.min.replace(tzinfo=timezone.utc)}
            return (
                entry["last_assigned_at"],
                int(entry["open_count"]),
            )

        if normalized_method == "Round-robin":
            return sorted(candidates, key=round_robin_key)[0]

        return sorted(candidates, key=workload_key)[0]

    @staticmethod
    def _as_bool(value: object, *, default: bool) -> bool:
        if isinstance(value, bool):
            return value
        if isinstance(value, str):
            normalized = value.strip().lower()
            if normalized in {"true", "1", "yes", "on"}:
                return True
            if normalized in {"false", "0", "no", "off"}:
                return False
        if value is None:
            return default
        return bool(value)

    @staticmethod
    def _normalize_assignment_method(method: str | None) -> str:
        normalized = (method or "").strip().lower()
        normalized = normalized.replace("_", " ").replace("-", " ")
        normalized = " ".join(normalized.split())

        if normalized in {"round robin", "roundrobin"}:
            return "Round-robin"
        if normalized == "by category":
            return "By category"
        if normalized == "by workload":
            return "By workload"
        return "By workload"

    async def _load_candidate_stats(self, candidate_ids: list[uuid.UUID]) -> dict[uuid.UUID, dict]:
        result = await self.db.execute(
            select(
                Ticket.assigned_agent_id,
                Ticket.status,
                Ticket.created_at,
            ).where(
                Ticket.assigned_agent_id.in_(candidate_ids),
                Ticket.is_deleted == False,
            )
        )

        default_last_assigned = datetime.min.replace(tzinfo=timezone.utc)
        stats = {
            candidate_id: {
                "open_count": 0,
                "last_assigned_at": default_last_assigned,
            }
            for candidate_id in candidate_ids
        }

        for assigned_agent_id, status, created_at in result.all():
            if assigned_agent_id is None:
                continue
            if status in OPEN_WORKLOAD_STATUSES:
                stats[assigned_agent_id]["open_count"] += 1
            if created_at and created_at > stats[assigned_agent_id]["last_assigned_at"]:
                stats[assigned_agent_id]["last_assigned_at"] = created_at

        return stats

    async def _select_category_assignee(
        self,
        ticket: Ticket,
        candidates: list[User],
        stats: dict[uuid.UUID, dict],
    ) -> Optional[User]:
        classification = classify_text(text=ticket.description or "", subject=ticket.subject or "")
        candidate_ids = [candidate.id for candidate in candidates]
        result = await self.db.execute(
            select(AgentSkill).where(
                AgentSkill.agent_id.in_(candidate_ids),
                AgentSkill.skill_category == classification.intent_category,
            )
        )
        skills = list(result.scalars().all())
        if not skills:
            return None

        candidate_lookup = {candidate.id: candidate for candidate in candidates}

        def skill_key(skill: AgentSkill) -> tuple[float, int, datetime]:
            candidate_stats = stats.get(skill.agent_id) or {
                "open_count": 0,
                "last_assigned_at": datetime.min.replace(tzinfo=timezone.utc),
            }
            return (
                -float(skill.proficiency),
                int(candidate_stats["open_count"]),
                candidate_stats["last_assigned_at"],
            )

        best_skill = sorted(skills, key=skill_key)[0]
        return candidate_lookup.get(best_skill.agent_id)

    # ──────────────────────────────────────────────────────────────────────
    # GLPI SYNCHRONIZATION
    # ──────────────────────────────────────────────────────────────────────

    async def sync_to_glpi(self, ticket: Ticket, escalation_summary: Optional[str] = None) -> bool:
        """
        Synchronize ticket to GLPI.
        
        Args:
            ticket: Ticket to synchronize
            escalation_summary: Optional summary to add as followup if escalated
            
        Returns:
            True if sync succeeded, False otherwise
        """
        if not settings.GLPI_ENABLED:
            logger.debug("GLPI sync disabled")
            return False

        glpi_client = GlpiClient()
        try:
            # Use Laravel platform admin/super_admin users for GLPI user IDs
            requester_glpi_id = None
            assigned_glpi_id = None
            try:
                async with laravel_session_factory() as laravel_db:
                    result = await laravel_db.execute(
                        text(
                            "SELECT glpi_user_id FROM users "
                            "WHERE glpi_user_id IS NOT NULL "
                            "AND role IN ('admin', 'super_admin') "
                            "ORDER BY glpi_user_id LIMIT 1"
                        )
                    )
                    row = result.fetchone()
                    if row:
                        requester_glpi_id = int(row[0])
                        assigned_glpi_id = int(row[0])
            except Exception as e:
                logger.warning("Could not fetch Laravel admin GLPI ID: %s", e)

            # If already synced, update; otherwise create
            if ticket.glpi_ticket_id:
                logger.info(f"Updating GLPI ticket {ticket.glpi_ticket_id} for ticket {ticket.id}")
                await glpi_client.update_ticket(
                    ticket.glpi_ticket_id,
                    title=ticket.subject,
                    description=ticket.description,
                    status=ticket.status,
                    priority=ticket.priority,
                    resolution_note=ticket.resolution_note,
                    assigned_agent_id=assigned_glpi_id,
                )
            else:
                logger.info(f"Creating new GLPI ticket for ticket {ticket.id}")
                result = await glpi_client.create_ticket(
                    title=ticket.subject,
                    description=ticket.description,
                    priority=ticket.priority,
                    requester_id=requester_glpi_id,
                )
                glpi_ticket_id = result.get('id') or result.get('glpi_ticket_id')
                if glpi_ticket_id:
                    ticket.glpi_ticket_id = int(glpi_ticket_id)
                    # If we have an assignee, update it now that we have a GLPI ID
                    if assigned_glpi_id:
                        await glpi_client.update_ticket(
                            ticket.glpi_ticket_id,
                            assigned_agent_id=assigned_glpi_id
                        )

            # Add escalation summary as private followup if provided
            if escalation_summary and ticket.glpi_ticket_id:
                logger.info(f"Adding escalation followup to GLPI ticket {ticket.glpi_ticket_id}")
                await glpi_client.add_followup(
                    ticket.glpi_ticket_id,
                    content=escalation_summary,
                    is_private=True
                )

            ticket.glpi_sync_status = "synced"
            ticket.glpi_sync_error = None
            await self.db.flush()
            # No refresh here to avoid clearing unsaved state if any, though flush should be fine
            logger.info(f"Successfully synced ticket {ticket.id} to GLPI")
            return True

        except GlpiClientError as e:
            logger.error(f"Failed to sync ticket {ticket.id} to GLPI: {str(e)}")
            ticket.glpi_sync_status = "failed"
            ticket.glpi_sync_error = str(e)
            await self.db.flush()
            return False
        finally:
            await glpi_client.close()

    async def delete_from_glpi(self, ticket: Ticket) -> bool:
        """
        Delete a local ticket's GLPI counterpart when the local ticket is soft-deleted.
        """
        if not settings.GLPI_ENABLED:
            logger.debug("GLPI delete sync disabled")
            return False

        if not ticket.glpi_ticket_id:
            logger.debug("Ticket %s has no GLPI ticket to delete", ticket.id)
            return True

        glpi_client = GlpiClient()
        try:
            logger.info(f"Deleting GLPI ticket {ticket.glpi_ticket_id} for ticket {ticket.id}")
            await glpi_client.delete_ticket(ticket.glpi_ticket_id)
            ticket.glpi_sync_status = "synced"
            ticket.glpi_sync_error = None
            await self.db.flush()
            return True
        except GlpiClientError as e:
            logger.error(f"Failed to delete GLPI ticket for {ticket.id}: {str(e)}")
            ticket.glpi_sync_status = "failed"
            ticket.glpi_sync_error = str(e)
            await self.db.flush()
            return False
        finally:
            await glpi_client.close()

    async def sync_from_glpi(self, glpi_ticket_id: int) -> Optional[Ticket]:
        """
        Fetch ticket from GLPI and update local database.
        
        Args:
            glpi_ticket_id: GLPI ticket ID
            
        Returns:
            Updated Ticket or None if not found
        """
        if not settings.GLPI_ENABLED:
            logger.debug("GLPI sync disabled")
            return None

        glpi_client = GlpiClient()
        try:
            # Fetch from GLPI
            glpi_data = await glpi_client.get_ticket(glpi_ticket_id)
            
            # Find or create local ticket
            result = await self.db.execute(
                select(Ticket).where(
                    Ticket.glpi_ticket_id == glpi_ticket_id,
                    Ticket.is_deleted == False
                )
            )
            ticket = result.scalar_one_or_none()
            
            if not ticket:
                logger.warning(f"No local ticket found for GLPI ID {glpi_ticket_id}")
                return None

            # Update from GLPI data
            ticket.subject = glpi_data.get('name', ticket.subject)
            ticket.description = glpi_data.get('content', ticket.description)
            status_code = glpi_data.get('status', 2)
            ticket.status = GlpiClient.map_glpi_to_fastapi_status(status_code)
            priority_code = glpi_data.get('priority', 3)
            ticket.priority = GlpiClient.map_glpi_to_fastapi_priority(priority_code)
            ticket.glpi_sync_status = "synced"
            ticket.glpi_sync_error = None

            await self.db.flush()
            await self.db.refresh(ticket)
            logger.info(f"Successfully synced ticket {ticket.id} from GLPI")
            return ticket

        except GlpiClientError as e:
            logger.error(f"Failed to sync from GLPI ticket {glpi_ticket_id}: {str(e)}")
            return None
        finally:
            await glpi_client.close()
