"""
Conversation auto-escalation playbooks and SLA predictor service.
"""

from __future__ import annotations

import json
import logging
import re
import uuid
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from typing import Optional

from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.db.models.audit_log import AuditLog
from app.db.models.conversation import Conversation, Message
from app.db.models.enums import (
    AuditAction,
    TicketPriority,
    TicketStatus,
    UserRole,
    UserStatus,
)
from app.db.models.ticket import Ticket
from app.db.models.user import User
from app.db.session import async_session_factory
from app.decision_engine.classifier import classify_text
from app.decision_engine.config import load_runtime_config
from app.decision_engine.scorer import assess_risk
from app.rag.response_providers.enums import AIProvider
from app.rag.response_providers.service import get_provider
from app.schemas.conversation import (
    ConversationPlaybookTrigger,
    ConversationSlaPredictorResponse,
)
from app.schemas.ticket import TicketCreate
from app.services.audit_service import AuditService
from app.services.conversation_service import ConversationService
from app.services.notification_service import NotificationService
from app.services.settings_service import SettingsService
from app.services.ticket_service import OPEN_WORKLOAD_STATUSES, TicketService

logger = logging.getLogger(__name__)
settings = get_settings()

PAUSE_TOO_LONG_MINUTES = 120
PLAYBOOK_DEDUP_MINUTES = 10
DEFAULT_SLA_WARNING_WINDOW_SECONDS = 45 * 60
DEFAULT_AUTOPILOT_ESCALATE_MINUTES = 15
DEFAULT_AUTOPILOT_ASSIGN_MINUTES = 10
SUMMARY_MAX_MESSAGES = 120
SUMMARY_MAX_TRANSCRIPT_CHARS = 14_000

_SUMMARY_ALLOWED_STATES = {
    "unresolved",
    "in_progress",
    "partially_resolved",
    "resolved",
    "unknown",
}

_SUMMARY_ALLOWED_SENTIMENTS = {
    "calm",
    "frustrated",
    "urgent",
    "neutral",
    "unknown",
}

_PRIORITY_RANK = {
    TicketPriority.LOW: 1,
    TicketPriority.MEDIUM: 2,
    TicketPriority.HIGH: 3,
    TicketPriority.CRITICAL: 4,
}


@dataclass(frozen=True)
class SlaAutopilotConfig:
    enabled: bool
    auto_assign_enabled: bool
    escalate_minutes_before_breach: int
    assign_minutes_before_breach: int
    respect_snooze: bool


class ConversationPlaybookService:
    def __init__(self, db: AsyncSession):
        self.db = db
        self.settings_service = SettingsService(db)

    @classmethod
    async def evaluate_playbooks_for_conversation(
        cls,
        conversation_id: uuid.UUID,
        *,
        event: str,
    ) -> None:
        """Background-safe helper to evaluate playbooks without request transaction coupling."""
        async with async_session_factory() as db:
            service = cls(db)
            try:
                await service.get_predictor(
                    conversation_id,
                    event=event,
                    auto_apply_playbook=True,
                )
                await db.commit()
            except Exception:
                await db.rollback()
                logger.warning(
                    "Failed conversation playbook evaluation for %s",
                    conversation_id,
                    exc_info=True,
                )

    async def get_predictor(
        self,
        conversation_id: uuid.UUID,
        *,
        event: str = "predictor_poll",
        auto_apply_playbook: bool = True,
    ) -> ConversationSlaPredictorResponse:
        conversation = await self._get_conversation(conversation_id)
        if not conversation:
            raise ValueError("Conversation not found")

        settings = await self.settings_service.get_all_settings()
        autopilot = self._build_autopilot_config(settings)

        snapshot = await self._build_snapshot(conversation, settings=settings)
        if auto_apply_playbook and self._should_sync_ticket_for_event(event):
            await self._sync_conversation_ticket(
                conversation=conversation,
                snapshot=snapshot,
                event=event,
            )
            snapshot = await self._build_snapshot(conversation, settings=settings)

        if auto_apply_playbook and self._should_auto_escalate(snapshot, autopilot=autopilot):
            await self._apply_escalation(
                conversation=conversation,
                snapshot=snapshot,
                event=event,
                force=False,
                actor_user_id=None,
                note=None,
                requested_agent_id=None,
                auto_assign_enabled=self._should_auto_assign(snapshot, autopilot=autopilot),
                autopilot=autopilot,
            )
            snapshot = await self._build_snapshot(conversation, settings=settings)

        return snapshot

    async def escalate_now(
        self,
        conversation_id: uuid.UUID,
        *,
        actor_user_id: uuid.UUID,
        note: str | None,
    ) -> tuple[ConversationSlaPredictorResponse, Ticket]:
        conversation = await self._get_conversation(conversation_id)
        if not conversation:
            raise ValueError("Conversation not found")

        snapshot = await self._build_snapshot(conversation)
        ticket = await self._apply_escalation(
            conversation=conversation,
            snapshot=snapshot,
            event="manual_escalate",
            force=True,
            actor_user_id=actor_user_id,
            note=note,
            requested_agent_id=None,
            auto_assign_enabled=True,
            autopilot=None,
        )
        refreshed = await self._build_snapshot(conversation)
        return refreshed, ticket

    async def assign_now(
        self,
        conversation_id: uuid.UUID,
        *,
        actor_user: User,
        agent_id: uuid.UUID | None,
    ) -> tuple[ConversationSlaPredictorResponse, Ticket, uuid.UUID | None]:
        conversation = await self._get_conversation(conversation_id)
        if not conversation:
            raise ValueError("Conversation not found")

        requested_agent_id = agent_id
        if requested_agent_id is None and actor_user.role == UserRole.AGENT:
            requested_agent_id = actor_user.id

        snapshot = await self._build_snapshot(conversation)
        ticket = await self._get_open_escalation_ticket(conversation.id)
        if ticket is None:
            ticket = await self._apply_escalation(
                conversation=conversation,
                snapshot=snapshot,
                event="manual_assign",
                force=True,
                actor_user_id=actor_user.id,
                note="Escalation ticket auto-created via assign action",
                requested_agent_id=requested_agent_id,
                auto_assign_enabled=True,
                autopilot=None,
            )
        else:
            assignee = await self._resolve_assignee(
                requested_agent_id,
                ticket=ticket,
                respect_auto_assignment_settings=False,
            )
            if assignee and ticket.assigned_agent_id != assignee.id:
                ticket = await TicketService(self.db).assign_agent(ticket.id, assignee.id) or ticket
            await AuditService(self.db).log(
                action=AuditAction.ASSIGN,
                resource_type="ticket",
                resource_id=str(ticket.id),
                user_id=actor_user.id,
                description="Conversation SLA quick action assignment",
                meta={
                    "conversation_id": str(conversation.id),
                    "assigned_agent_id": str(ticket.assigned_agent_id) if ticket.assigned_agent_id else None,
                    "source": "conversation_sla_assign",
                },
            )

        refreshed = await self._build_snapshot(conversation)
        return refreshed, ticket, ticket.assigned_agent_id

    async def snooze(
        self,
        conversation_id: uuid.UUID,
        *,
        actor_user_id: uuid.UUID,
        minutes: int,
    ) -> tuple[ConversationSlaPredictorResponse, datetime]:
        conversation = await self._get_conversation(conversation_id)
        if not conversation:
            raise ValueError("Conversation not found")

        snoozed_until = datetime.now(timezone.utc) + timedelta(minutes=minutes)
        conversation.sla_snoozed_until = snoozed_until
        await self.db.flush()

        await AuditService(self.db).log(
            action=AuditAction.UPDATE,
            resource_type="conversation",
            resource_id=str(conversation.id),
            user_id=actor_user_id,
            description=f"Snoozed SLA alerts for {minutes} minute(s)",
            meta={
                "conversation_id": str(conversation.id),
                "minutes": minutes,
                "snoozed_until": snoozed_until.isoformat(),
                "source": "conversation_sla_snooze",
            },
        )

        snapshot = await self._build_snapshot(conversation)
        return snapshot, snoozed_until

    async def _build_snapshot(
        self,
        conversation: Conversation,
        *,
        settings: dict | None = None,
    ) -> ConversationSlaPredictorResponse:
        now = datetime.now(timezone.utc)
        settings = settings or await self.settings_service.get_all_settings()
        decision_config = await load_runtime_config(self.db)
        customer = await self._get_user(conversation.user_id)
        pending_customer_message = await self._get_pending_customer_message(conversation)
        latest_agent_reply = await self._get_latest_agent_reply(conversation)
        escalation_ticket = await self._get_open_escalation_ticket(conversation.id)

        risk_level = "low"
        at_risk = False
        breached = False
        reply_due_at: datetime | None = None
        seconds_remaining: int | None = None
        triggers: list[ConversationPlaybookTrigger] = []

        if pending_customer_message is not None:
            classification = classify_text(
                text=pending_customer_message.content,
                subject=conversation.subject or "",
                high_confidence_threshold=decision_config.confidence_high_threshold,
                medium_confidence_threshold=decision_config.confidence_medium_threshold,
            )
            risk_assessment = assess_risk(
                text=pending_customer_message.content,
                subject=conversation.subject or "",
                classification=classification,
                existing_priority=TicketPriority.MEDIUM,
                has_escalation_flag=False,
                critical_threshold=decision_config.risk_critical_threshold,
                high_threshold=decision_config.risk_high_threshold,
                medium_threshold=decision_config.risk_medium_threshold,
                low_confidence_risk_boost=decision_config.low_confidence_risk_boost,
                medium_confidence_risk_boost=decision_config.medium_confidence_risk_boost,
            )
            risk_level = risk_assessment.risk_level.value.lower()
            if customer and customer.is_vip:
                risk_level = "critical"

            target_priority = self._target_priority(
                risk_level=risk_level,
                breached=False,
                vip=bool(customer and customer.is_vip),
            )
            sla_hours = self._resolve_sla_hours(settings, target_priority)
            reply_due_at = pending_customer_message.created_at + timedelta(hours=sla_hours)
            seconds_remaining = int((reply_due_at - now).total_seconds())

            warning_window_seconds = min(
                DEFAULT_SLA_WARNING_WINDOW_SECONDS,
                max(10 * 60, int(sla_hours * 3600 * 0.35)),
            )
            at_risk = seconds_remaining > 0 and seconds_remaining <= warning_window_seconds
            breached = seconds_remaining <= 0

            if conversation.ai_auto_reply_paused_until and conversation.ai_auto_reply_paused_until > now:
                pause_remaining_minutes = max(
                    0,
                    int((conversation.ai_auto_reply_paused_until - now).total_seconds() // 60),
                )
                if pause_remaining_minutes >= PAUSE_TOO_LONG_MINUTES:
                    triggers.append(
                        ConversationPlaybookTrigger(
                            key="pause_active_too_long",
                            reason=(
                                "Conversation auto-reply pause is still active for "
                                f"{pause_remaining_minutes} minute(s)"
                            ),
                            meta={
                                "pause_until": conversation.ai_auto_reply_paused_until.isoformat(),
                                "pause_remaining_minutes": pause_remaining_minutes,
                            },
                        )
                    )

            if not conversation.ai_auto_reply_enabled:
                triggers.append(
                    ConversationPlaybookTrigger(
                        key="conversation_toggle_off",
                        reason="Conversation-level AI auto-reply toggle is disabled",
                        meta={"ai_auto_reply_enabled": False},
                    )
                )

            if risk_level in {"high", "critical"}:
                triggers.append(
                    ConversationPlaybookTrigger(
                        key="high_risk_intent",
                        reason=(
                            f"Latest customer message classified as {classification.intent_category.value} "
                            f"with {risk_level} risk"
                        ),
                        meta={
                            "intent_category": classification.intent_category.value,
                            "confidence_score": classification.confidence_score,
                            "confidence_level": classification.confidence_level.value,
                            "risk_score": risk_assessment.risk_score,
                            "risk_level": risk_assessment.risk_level.value,
                        },
                    )
                )

            if breached:
                triggers.append(
                    ConversationPlaybookTrigger(
                        key="no_agent_reply_within_sla",
                        reason="No non-customer reply was sent within the SLA window",
                        meta={
                            "reply_due_at": reply_due_at.isoformat() if reply_due_at else None,
                            "seconds_overdue": abs(seconds_remaining or 0),
                        },
                    )
                )

            if customer and customer.is_vip:
                triggers.append(
                    ConversationPlaybookTrigger(
                        key="vip_customer",
                        reason="Conversation customer is marked as VIP",
                        meta={"customer_id": str(customer.id)},
                    )
                )

        snoozed_until = conversation.sla_snoozed_until if conversation.sla_snoozed_until and conversation.sla_snoozed_until > now else None
        snoozed = snoozed_until is not None

        if snoozed:
            triggers = [trigger for trigger in triggers if trigger.key != "no_agent_reply_within_sla"]

        recommended_actions: list[str] = []
        if pending_customer_message is not None:
            if triggers:
                recommended_actions.append("escalate")
            if escalation_ticket and escalation_ticket.assigned_agent_id is None:
                recommended_actions.append("assign")
            if (at_risk or breached) and not snoozed:
                recommended_actions.append("snooze")

        deduped_actions: list[str] = []
        for action in recommended_actions:
            if action not in deduped_actions:
                deduped_actions.append(action)

        return ConversationSlaPredictorResponse(
            conversation_id=conversation.id,
            channel=conversation.channel,
            pending_customer_message_id=pending_customer_message.id if pending_customer_message else None,
            pending_customer_message_at=(
                pending_customer_message.created_at if pending_customer_message else None
            ),
            latest_agent_reply_at=latest_agent_reply.created_at if latest_agent_reply else None,
            reply_due_at=reply_due_at,
            seconds_remaining=seconds_remaining,
            risk_level=risk_level,
            at_risk=at_risk,
            breached=breached,
            snoozed=snoozed,
            snoozed_until=snoozed_until,
            triggers=triggers,
            recommended_actions=deduped_actions,
            escalation_ticket_id=escalation_ticket.id if escalation_ticket else None,
            escalation_ticket_priority=escalation_ticket.priority if escalation_ticket else None,
            generated_at=now,
        )

    def _should_auto_escalate(
        self,
        snapshot: ConversationSlaPredictorResponse,
        *,
        autopilot: SlaAutopilotConfig,
    ) -> bool:
        if not autopilot.enabled:
            return False
        if not snapshot.triggers or snapshot.pending_customer_message_id is None:
            return False
        if snapshot.snoozed and autopilot.respect_snooze:
            return False

        if any(trigger.key == "vip_customer" for trigger in snapshot.triggers):
            return True
        if snapshot.risk_level == "critical":
            return True
        if snapshot.breached:
            return True

        if snapshot.seconds_remaining is None:
            return False
        threshold_seconds = max(0, autopilot.escalate_minutes_before_breach) * 60
        return snapshot.seconds_remaining <= threshold_seconds

    def _should_auto_assign(
        self,
        snapshot: ConversationSlaPredictorResponse,
        *,
        autopilot: SlaAutopilotConfig,
    ) -> bool:
        if not autopilot.enabled or not autopilot.auto_assign_enabled:
            return False
        if snapshot.pending_customer_message_id is None:
            return False
        if snapshot.snoozed and autopilot.respect_snooze:
            return False

        if any(trigger.key == "vip_customer" for trigger in snapshot.triggers):
            return True
        if snapshot.risk_level in {"high", "critical"}:
            return True
        if snapshot.breached:
            return True

        if snapshot.seconds_remaining is None:
            return False
        threshold_seconds = max(0, autopilot.assign_minutes_before_breach) * 60
        return snapshot.seconds_remaining <= threshold_seconds

    @staticmethod
    def _should_sync_ticket_for_event(event: str) -> bool:
        normalized = (event or "").strip().lower()
        if not normalized or normalized == "predictor_poll":
            return False
        if "message" in normalized:
            return True
        return normalized in {"support_reply", "support_stream_reply", "conversation_update"}

    async def _sync_conversation_ticket(
        self,
        *,
        conversation: Conversation,
        snapshot: ConversationSlaPredictorResponse,
        event: str,
    ) -> Ticket:
        ticket_service = TicketService(self.db)
        ticket = await self._get_open_conversation_ticket(conversation.id)
        target_priority = self._target_priority(
            risk_level=snapshot.risk_level,
            breached=snapshot.breached,
            vip=any(trigger.key == "vip_customer" for trigger in snapshot.triggers),
        )
        summary_context = await self._build_ai_summary_context(conversation)
        description = self._build_auto_sync_description(
            conversation,
            snapshot,
            event=event,
            summary_context=summary_context,
        )

        if ticket is None:
            ticket = await ticket_service.create_ticket(
                conversation.user_id,
                TicketCreate(
                    subject=(conversation.subject or "Conversation support ticket")[:500],
                    description=description,
                    priority=target_priority,
                    channel_source=conversation.channel,
                    conversation_id=conversation.id,
                ),
            )
            ticket.escalation_flag = False
            if ticket.status not in OPEN_WORKLOAD_STATUSES:
                ticket.status = TicketStatus.OPEN
        else:
            ticket.subject = (conversation.subject or "Conversation support ticket")[:500]
            ticket.description = description
            if _PRIORITY_RANK.get(target_priority, 0) > _PRIORITY_RANK.get(ticket.priority, 0):
                ticket.priority = target_priority

        await self.db.flush()
        await self.db.refresh(ticket)
        if settings.GLPI_AUTO_SYNC:
            await ticket_service.sync_to_glpi(ticket)
        return ticket

    async def _apply_escalation(
        self,
        *,
        conversation: Conversation,
        snapshot: ConversationSlaPredictorResponse,
        event: str,
        force: bool,
        actor_user_id: uuid.UUID | None,
        note: str | None,
        requested_agent_id: uuid.UUID | None,
        auto_assign_enabled: bool,
        autopilot: SlaAutopilotConfig | None,
    ) -> Ticket:
        ticket_service = TicketService(self.db)

        existing_ticket = await self._get_open_escalation_ticket(conversation.id)
        if existing_ticket is None:
            existing_ticket = await self._get_open_conversation_ticket(conversation.id)

        target_priority = self._target_priority(
            risk_level=snapshot.risk_level,
            breached=snapshot.breached,
            vip=any(trigger.key == "vip_customer" for trigger in snapshot.triggers),
        )
        playbook_fingerprint = self._playbook_fingerprint(snapshot, event=event)

        if (
            not force
            and existing_ticket
            and existing_ticket.escalation_flag
            and await self._has_recent_fingerprint(conversation.id, playbook_fingerprint)
        ):
            if "AI summary context:" not in (existing_ticket.description or ""):
                summary_context = await self._build_ai_summary_context(conversation)
                existing_ticket.description = self._build_escalation_description(
                    conversation,
                    snapshot,
                    note=note,
                    summary_context=summary_context,
                )
                await self.db.flush()
                await self.db.refresh(existing_ticket)

            if existing_ticket.assigned_agent_id is None and auto_assign_enabled:
                assignee = await self._resolve_assignee(
                    requested_agent_id=None,
                    ticket=existing_ticket,
                    ticket_service=ticket_service,
                    respect_auto_assignment_settings=True,
                )
                if assignee:
                    try:
                        existing_ticket = await ticket_service.assign_agent(existing_ticket.id, assignee.id) or existing_ticket
                        await self._log_autopilot_assignment(
                            conversation=conversation,
                            ticket=existing_ticket,
                            assignee=assignee,
                            event=event,
                            autopilot=autopilot,
                        )
                    except ValueError:
                        logger.warning(
                            "Conversation escalation assignment failed for ticket %s",
                            existing_ticket.id,
                            exc_info=True,
                        )
            if settings.GLPI_AUTO_SYNC:
                await ticket_service.sync_to_glpi(existing_ticket)
            return existing_ticket

        summary_context = await self._build_ai_summary_context(conversation)
        escalation_description = self._build_escalation_description(
            conversation,
            snapshot,
            note=note,
            summary_context=summary_context,
        )

        created_ticket = False
        if existing_ticket is None:
            ticket = await ticket_service.create_ticket(
                conversation.user_id,
                TicketCreate(
                    subject=(conversation.subject or "Conversation escalation")[:500],
                    description=escalation_description,
                    priority=target_priority,
                    channel_source=conversation.channel,
                    conversation_id=conversation.id,
                ),
            )
            ticket.escalation_flag = True
            ticket.status = TicketStatus.ESCALATED
            created_ticket = True
        else:
            ticket = existing_ticket
            ticket.escalation_flag = True
            if ticket.status in OPEN_WORKLOAD_STATUSES:
                ticket.status = TicketStatus.ESCALATED
            if _PRIORITY_RANK.get(target_priority, 0) > _PRIORITY_RANK.get(ticket.priority, 0):
                ticket.priority = target_priority
            ticket.description = escalation_description

        assignee = None
        if requested_agent_id is not None or auto_assign_enabled:
            assignee = await self._resolve_assignee(
                requested_agent_id,
                ticket=ticket,
                ticket_service=ticket_service,
                respect_auto_assignment_settings=requested_agent_id is None,
            )
        if assignee and ticket.assigned_agent_id != assignee.id:
            try:
                ticket = await ticket_service.assign_agent(ticket.id, assignee.id) or ticket
                await self._log_autopilot_assignment(
                    conversation=conversation,
                    ticket=ticket,
                    assignee=assignee,
                    event=event,
                    autopilot=autopilot,
                )
            except ValueError:
                logger.warning("Conversation escalation assignment failed for ticket %s", ticket.id, exc_info=True)

        await self.db.flush()
        await self.db.refresh(ticket)
        if settings.GLPI_AUTO_SYNC:
            await ticket_service.sync_to_glpi(ticket)

        if created_ticket:
            handoff_text = self._build_customer_handoff_message(ticket.id)
            await ConversationService(self.db).save_support_reply(
                conversation=conversation,
                reply_text=handoff_text,
            )

        await self._notify_admins(conversation=conversation, ticket=ticket, snapshot=snapshot)
        await AuditService(self.db).log(
            action=AuditAction.ESCALATE,
            resource_type="conversation",
            resource_id=str(conversation.id),
            user_id=actor_user_id,
            description="Conversation escalation playbook executed",
            meta={
                "event": event,
                "force": force,
                "source": "sla_autopilot" if autopilot else "conversation_sla",
                "note": note,
                "triggers": [trigger.model_dump(mode="json") for trigger in snapshot.triggers],
                "ticket_id": str(ticket.id),
                "ticket_priority": ticket.priority.value,
                "assigned_agent_id": str(ticket.assigned_agent_id) if ticket.assigned_agent_id else None,
                "playbook_fingerprint": playbook_fingerprint,
                "autopilot_enabled": autopilot.enabled if autopilot else None,
                "autopilot_auto_assign_enabled": autopilot.auto_assign_enabled if autopilot else None,
                "autopilot_escalate_minutes_before_breach": (
                    autopilot.escalate_minutes_before_breach if autopilot else None
                ),
                "autopilot_assign_minutes_before_breach": (
                    autopilot.assign_minutes_before_breach if autopilot else None
                ),
                "autopilot_respect_snooze": autopilot.respect_snooze if autopilot else None,
            },
        )

        return ticket

    async def _notify_admins(
        self,
        *,
        conversation: Conversation,
        ticket: Ticket,
        snapshot: ConversationSlaPredictorResponse,
    ) -> None:
        admin_ids = await self._admin_user_ids()
        if not admin_ids:
            return

        title = "Conversation auto-escalated"
        body = (
            f"Conversation {str(conversation.id)[:8]} was escalated with {snapshot.risk_level} risk. "
            f"Ticket {str(ticket.id)[:8]} is now {ticket.status.value}."
        )
        await NotificationService(self.db).create_many(
            user_ids=admin_ids,
            type="conversation_escalation",
            title=title,
            body=body,
            resource_type="conversation",
            resource_id=str(conversation.id),
            action_url=f"/conversations?conversation={conversation.id}",
            meta={
                "ticket_id": str(ticket.id),
                "risk_level": snapshot.risk_level,
                "trigger_keys": [trigger.key for trigger in snapshot.triggers],
            },
        )

    async def _log_autopilot_assignment(
        self,
        *,
        conversation: Conversation,
        ticket: Ticket,
        assignee: User,
        event: str,
        autopilot: SlaAutopilotConfig | None,
    ) -> None:
        if autopilot is None:
            return

        await AuditService(self.db).log(
            action=AuditAction.ASSIGN,
            resource_type="ticket",
            resource_id=str(ticket.id),
            user_id=None,
            description="Conversation SLA autopilot assignment executed",
            meta={
                "source": "sla_autopilot",
                "event": event,
                "conversation_id": str(conversation.id),
                "assigned_agent_id": str(assignee.id),
                "ticket_priority": ticket.priority.value,
                "autopilot_escalate_minutes_before_breach": autopilot.escalate_minutes_before_breach,
                "autopilot_assign_minutes_before_breach": autopilot.assign_minutes_before_breach,
                "autopilot_respect_snooze": autopilot.respect_snooze,
            },
        )

    async def _has_recent_fingerprint(self, conversation_id: uuid.UUID, fingerprint: str) -> bool:
        now = datetime.now(timezone.utc)
        threshold = now - timedelta(minutes=PLAYBOOK_DEDUP_MINUTES)

        result = await self.db.execute(
            select(AuditLog)
            .where(
                AuditLog.action == AuditAction.ESCALATE,
                AuditLog.resource_type == "conversation",
                AuditLog.resource_id == str(conversation_id),
            )
            .order_by(AuditLog.created_at.desc())
            .limit(20)
        )
        logs = list(result.scalars().all())
        for entry in logs:
            if entry.created_at < threshold:
                break
            meta = entry.meta or {}
            if isinstance(meta, dict) and meta.get("playbook_fingerprint") == fingerprint:
                return True
        return False

    def _playbook_fingerprint(self, snapshot: ConversationSlaPredictorResponse, *, event: str) -> str:
        trigger_keys = sorted(trigger.key for trigger in snapshot.triggers)
        pending_message = str(snapshot.pending_customer_message_id) if snapshot.pending_customer_message_id else "none"
        return "|".join(
            [
                event,
                pending_message,
                snapshot.risk_level,
                "1" if snapshot.breached else "0",
                ",".join(trigger_keys),
            ]
        )

    def _build_escalation_description(
        self,
        conversation: Conversation,
        snapshot: ConversationSlaPredictorResponse,
        *,
        note: str | None,
        summary_context: dict | None = None,
    ) -> str:
        lines = [
            "Conversation escalation triggered by playbook.",
            f"Conversation ID: {conversation.id}",
            f"Risk level: {snapshot.risk_level}",
        ]
        if snapshot.reply_due_at:
            lines.append(f"Reply due at: {snapshot.reply_due_at.isoformat()}")
        if snapshot.seconds_remaining is not None:
            lines.append(f"Seconds remaining: {snapshot.seconds_remaining}")
        if snapshot.pending_customer_message_id:
            lines.append(f"Pending customer message ID: {snapshot.pending_customer_message_id}")
        if snapshot.triggers:
            lines.append("Triggers:")
            lines.extend([f"- {trigger.key}: {trigger.reason}" for trigger in snapshot.triggers])

        if summary_context:
            lines.append("")
            lines.append("AI summary context:")
            lines.append(f"- Problem: {summary_context.get('problem_summary') or 'Unknown'}")
            lines.append(
                f"- Resolution state: {summary_context.get('resolution_state') or 'unknown'}"
            )
            lines.append(
                f"- Resolution detail: {summary_context.get('resolution_description') or 'Unknown'}"
            )
            lines.append(f"- Next action: {summary_context.get('next_action') or 'Unknown'}")
            lines.append(
                f"- Customer sentiment: {summary_context.get('customer_sentiment') or 'unknown'}"
            )
            language = summary_context.get("language")
            if language:
                lines.append(f"- Language: {language}")
            provider = summary_context.get("provider")
            model = summary_context.get("model")
            if provider:
                lines.append(
                    f"- Summary source: {provider}{f'/{model}' if model else ''}"
                )
            generated_at = summary_context.get("generated_at")
            if generated_at:
                lines.append(f"- Summary generated at: {generated_at}")

        if note:
            lines.append("")
            lines.append(f"Operator note: {note.strip()}")
        return "\n".join(lines)

    def _build_auto_sync_description(
        self,
        conversation: Conversation,
        snapshot: ConversationSlaPredictorResponse,
        *,
        event: str,
        summary_context: dict | None = None,
    ) -> str:
        lines = [
            "Conversation ticket auto-synced from playbook events.",
            f"Event: {event}",
            f"Conversation ID: {conversation.id}",
            f"Conversation status: {getattr(conversation.status, 'value', str(conversation.status))}",
            f"Risk level: {snapshot.risk_level}",
        ]
        if snapshot.reply_due_at:
            lines.append(f"Reply due at: {snapshot.reply_due_at.isoformat()}")
        if snapshot.seconds_remaining is not None:
            lines.append(f"Seconds remaining: {snapshot.seconds_remaining}")
        if snapshot.pending_customer_message_id:
            lines.append(f"Pending customer message ID: {snapshot.pending_customer_message_id}")

        if summary_context:
            lines.append("")
            lines.append("AI summary context:")
            lines.append(f"- Problem: {summary_context.get('problem_summary') or 'Unknown'}")
            lines.append(
                f"- Resolution state: {summary_context.get('resolution_state') or 'unknown'}"
            )
            lines.append(
                f"- Resolution detail: {summary_context.get('resolution_description') or 'Unknown'}"
            )
            lines.append(f"- Next action: {summary_context.get('next_action') or 'Unknown'}")
            lines.append(
                f"- Customer sentiment: {summary_context.get('customer_sentiment') or 'unknown'}"
            )
            language = summary_context.get("language")
            if language:
                lines.append(f"- Language: {language}")
            provider = summary_context.get("provider")
            model = summary_context.get("model")
            if provider:
                lines.append(
                    f"- Summary source: {provider}{f'/{model}' if model else ''}"
                )
            generated_at = summary_context.get("generated_at")
            if generated_at:
                lines.append(f"- Summary generated at: {generated_at}")

        return "\n".join(lines)

    @staticmethod
    def _summary_provider_order() -> list[AIProvider]:
        raw = (getattr(settings, "AI_RESPONSE_PROVIDER", "") or "").strip().lower()
        try:
            preferred = AIProvider(raw)
        except ValueError:
            preferred = AIProvider.OPENAI

        order = [preferred]
        for provider in AIProvider:
            if provider not in order:
                order.append(provider)
        return order

    @staticmethod
    def _normalize_choice(raw_value: object, allowed: set[str], default: str) -> str:
        value = str(raw_value or "").strip().lower().replace("-", "_")
        return value if value in allowed else default

    @staticmethod
    def _extract_json_object(raw_text: str) -> dict:
        text = (raw_text or "").strip()
        if not text:
            return {}

        try:
            parsed = json.loads(text)
            return parsed if isinstance(parsed, dict) else {}
        except json.JSONDecodeError:
            pass

        match = re.search(r"\{.*\}", text, flags=re.DOTALL)
        if not match:
            return {}

        try:
            parsed = json.loads(match.group(0))
            return parsed if isinstance(parsed, dict) else {}
        except json.JSONDecodeError:
            return {}

    @staticmethod
    def _build_summary_messages(
        *,
        customer_label: str,
        conversation_status: str,
        transcript: str,
    ) -> list[dict]:
        system = (
            "You are a customer-support QA analyst. "
            "Analyze the conversation and return only valid JSON. "
            "No markdown, no explanations outside JSON."
        )

        user_prompt = (
            "Analyze the support conversation and summarize it.\n"
            "Return JSON with exactly these keys:\n"
            "problem_summary, resolution_state, resolution_description, next_action, customer_sentiment, language\n"
            "Allowed values:\n"
            "resolution_state: unresolved | in_progress | partially_resolved | resolved | unknown\n"
            "customer_sentiment: calm | frustrated | urgent | neutral | unknown\n"
            "Rules:\n"
            "- problem_summary: 1-3 concise sentences describing the customer problem.\n"
            "- resolution_description: concise current state of resolution.\n"
            "- next_action: most relevant immediate support action.\n"
            "- language: detected conversation language code (for example fr, en, ar).\n"
            "- Use unknown when uncertain.\n"
            "- Base your answer only on the conversation below.\n\n"
            f"Customer: {customer_label}\n"
            f"Conversation status: {conversation_status}\n\n"
            "Conversation transcript:\n"
            f"{transcript}"
        )

        return [
            {"role": "system", "content": system},
            {"role": "user", "content": user_prompt},
        ]

    async def _build_ai_summary_context(self, conversation: Conversation) -> dict:
        messages_result = await self.db.execute(
            select(Message)
            .where(
                Message.conversation_id == conversation.id,
                Message.is_internal == False,
            )
            .order_by(Message.created_at.desc())
            .limit(SUMMARY_MAX_MESSAGES)
        )
        recent_messages_desc = list(messages_result.scalars().all())
        messages = list(reversed(recent_messages_desc))

        if not messages:
            now_iso = datetime.now(timezone.utc).isoformat()
            return {
                "problem_summary": "No conversation messages are available yet.",
                "resolution_state": "unknown",
                "resolution_description": "No resolution state can be inferred without messages.",
                "next_action": "Wait for customer details or send a clarification message.",
                "customer_sentiment": "unknown",
                "language": None,
                "provider": "none",
                "model": "none",
                "generated_at": now_iso,
            }

        transcript_lines: list[str] = []
        latest_customer_text = ""

        for message in messages:
            role = "Customer" if message.sender_id == conversation.user_id else "Agent"
            content = " ".join((message.content or "").split())
            if not content:
                continue
            if len(content) > 500:
                content = content[:500].rstrip() + "..."

            if role == "Customer":
                latest_customer_text = content

            timestamp = message.created_at.isoformat(timespec="seconds") if message.created_at else ""
            transcript_lines.append(f"{timestamp} | {role}: {content}")

        transcript = "\n".join(transcript_lines)
        if len(transcript) > SUMMARY_MAX_TRANSCRIPT_CHARS:
            transcript = transcript[-SUMMARY_MAX_TRANSCRIPT_CHARS:]

        customer = await self._get_user(conversation.user_id)
        customer_label = (
            (customer.full_name if customer else "")
            or (customer.email if customer else "")
            or str(conversation.user_id)
        )

        llm_messages = self._build_summary_messages(
            customer_label=customer_label,
            conversation_status=getattr(conversation.status, "value", str(conversation.status)),
            transcript=transcript,
        )

        for provider_enum in self._summary_provider_order():
            provider = get_provider(provider_enum)
            if not getattr(provider, "_is_configured", False):
                continue

            try:
                generated = await provider.generate(
                    messages=llm_messages,
                    temperature=0.2,
                    max_tokens=600,
                )
                parsed = self._extract_json_object(str(generated.get("content", "")))

                problem_summary = str(
                    parsed.get("problem_summary")
                    or parsed.get("issue_summary")
                    or ""
                ).strip()
                if not problem_summary:
                    problem_summary = (
                        latest_customer_text
                        or "The customer reported an issue, but the exact problem is not fully clear yet."
                    )

                resolution_description = str(
                    parsed.get("resolution_description")
                    or parsed.get("resolution_status")
                    or ""
                ).strip() or "Resolution status is not clearly established yet."

                next_action = str(parsed.get("next_action") or "").strip() or (
                    "Review the latest customer message and provide a concrete next troubleshooting or account action."
                )

                resolution_state = self._normalize_choice(
                    parsed.get("resolution_state"),
                    _SUMMARY_ALLOWED_STATES,
                    "unknown",
                )
                sentiment = self._normalize_choice(
                    parsed.get("customer_sentiment"),
                    _SUMMARY_ALLOWED_SENTIMENTS,
                    "unknown",
                )

                language = str(parsed.get("language") or "").strip() or None
                if language and len(language) > 16:
                    language = language[:16]

                return {
                    "problem_summary": problem_summary,
                    "resolution_state": resolution_state,
                    "resolution_description": resolution_description,
                    "next_action": next_action,
                    "customer_sentiment": sentiment,
                    "language": language,
                    "provider": provider_enum.value,
                    "model": str(generated.get("model") or "unknown"),
                    "generated_at": datetime.now(timezone.utc).isoformat(),
                }
            except Exception:
                logger.warning(
                    "Conversation ticket summary generation failed for %s via %s",
                    conversation.id,
                    provider_enum.value,
                    exc_info=True,
                )

        return {
            "problem_summary": (
                latest_customer_text
                or "The customer reported an issue, but the exact problem is not fully clear yet."
            ),
            "resolution_state": "unknown",
            "resolution_description": "Resolution status is not clearly established yet.",
            "next_action": "Review the latest customer message and provide a concrete next support action.",
            "customer_sentiment": "unknown",
            "language": None,
            "provider": "fallback",
            "model": "none",
            "generated_at": datetime.now(timezone.utc).isoformat(),
        }

    def _build_customer_handoff_message(self, ticket_id: uuid.UUID) -> str:
        short_ref = str(ticket_id).split("-")[0].upper()
        return (
            "Thanks for your patience. I am escalating this to a specialist for deeper investigation. "
            f"Your handoff reference is {short_ref}. We will update you shortly."
        )

    def _target_priority(self, *, risk_level: str, breached: bool, vip: bool) -> TicketPriority:
        if vip or risk_level == "critical":
            return TicketPriority.CRITICAL
        if breached or risk_level == "high":
            return TicketPriority.HIGH
        if risk_level == "medium":
            return TicketPriority.MEDIUM
        return TicketPriority.LOW

    def _resolve_sla_hours(self, settings: dict, priority: TicketPriority) -> int:
        key_map = {
            TicketPriority.CRITICAL: "sla_critical_hours",
            TicketPriority.HIGH: "sla_high_hours",
            TicketPriority.MEDIUM: "sla_medium_hours",
            TicketPriority.LOW: "sla_low_hours",
        }
        key = key_map.get(priority, "sla_medium_hours")
        try:
            return max(1, int(settings.get(key, 24)))
        except Exception:
            return 24

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
    def _as_int(value: object, *, default: int, minimum: int = 0, maximum: int = 24 * 60) -> int:
        try:
            parsed = int(value)
        except (TypeError, ValueError):
            parsed = default
        return max(minimum, min(maximum, parsed))

    def _build_autopilot_config(self, settings: dict) -> SlaAutopilotConfig:
        return SlaAutopilotConfig(
            enabled=self._as_bool(
                settings.get("conversation_sla_autopilot_enabled"),
                default=True,
            ),
            auto_assign_enabled=self._as_bool(
                settings.get("conversation_sla_auto_assign_enabled"),
                default=True,
            ),
            escalate_minutes_before_breach=self._as_int(
                settings.get("conversation_sla_auto_escalate_minutes_before_breach"),
                default=DEFAULT_AUTOPILOT_ESCALATE_MINUTES,
            ),
            assign_minutes_before_breach=self._as_int(
                settings.get("conversation_sla_auto_assign_minutes_before_breach"),
                default=DEFAULT_AUTOPILOT_ASSIGN_MINUTES,
            ),
            respect_snooze=self._as_bool(
                settings.get("conversation_sla_autopilot_respect_snooze"),
                default=True,
            ),
        )

    async def _resolve_assignee(
        self,
        requested_agent_id: uuid.UUID | None,
        *,
        ticket: Ticket | None,
        ticket_service: TicketService | None = None,
        respect_auto_assignment_settings: bool = True,
    ) -> User | None:
        if requested_agent_id:
            result = await self.db.execute(
                select(User).where(
                    User.id == requested_agent_id,
                    User.role.in_([UserRole.AGENT, UserRole.ADMIN]),
                    User.status == UserStatus.ACTIVE,
                    User.is_deleted == False,
                )
            )
            return result.scalar_one_or_none()

        if ticket is None:
            return None

        assignment_method = "By workload"
        if respect_auto_assignment_settings:
            settings = await self.settings_service.get_all_settings()
            if not self._as_bool(settings.get("auto_assignment"), default=False):
                return None
            assignment_method = str(settings.get("auto_assignment_method", "Round-robin"))

        selector = ticket_service or TicketService(self.db)
        return await selector.select_auto_assignee(ticket=ticket, method=assignment_method)

    async def _admin_user_ids(self) -> list[uuid.UUID]:
        result = await self.db.execute(
            select(User.id).where(
                User.role == UserRole.ADMIN,
                User.status == UserStatus.ACTIVE,
                User.is_deleted == False,
            )
        )
        return [user_id for user_id in result.scalars().all()]

    async def _get_conversation(self, conversation_id: uuid.UUID) -> Conversation | None:
        result = await self.db.execute(
            select(Conversation).where(
                Conversation.id == conversation_id,
                Conversation.is_deleted == False,
            )
        )
        return result.scalar_one_or_none()

    async def _get_user(self, user_id: uuid.UUID) -> User | None:
        result = await self.db.execute(
            select(User).where(
                User.id == user_id,
                User.is_deleted == False,
            )
        )
        return result.scalar_one_or_none()

    async def _get_open_escalation_ticket(self, conversation_id: uuid.UUID) -> Ticket | None:
        result = await self.db.execute(
            select(Ticket)
            .where(
                Ticket.conversation_id == conversation_id,
                Ticket.escalation_flag == True,
                Ticket.is_deleted == False,
                Ticket.status.in_(list(OPEN_WORKLOAD_STATUSES)),
            )
            .order_by(Ticket.created_at.desc())
            .limit(1)
        )
        return result.scalar_one_or_none()

    async def _get_open_conversation_ticket(self, conversation_id: uuid.UUID) -> Ticket | None:
        result = await self.db.execute(
            select(Ticket)
            .where(
                Ticket.conversation_id == conversation_id,
                Ticket.is_deleted == False,
                Ticket.status.in_(list(OPEN_WORKLOAD_STATUSES)),
            )
            .order_by(Ticket.escalation_flag.desc(), Ticket.created_at.desc())
            .limit(1)
        )
        return result.scalar_one_or_none()

    async def _get_pending_customer_message(self, conversation: Conversation) -> Message | None:
        latest_customer_result = await self.db.execute(
            select(Message)
            .where(
                Message.conversation_id == conversation.id,
                Message.is_internal == False,
                Message.sender_id == conversation.user_id,
            )
            .order_by(Message.created_at.desc())
            .limit(1)
        )
        latest_customer = latest_customer_result.scalar_one_or_none()
        if latest_customer is None:
            return None

        reply_after_result = await self.db.execute(
            select(Message.id)
            .where(
                Message.conversation_id == conversation.id,
                Message.is_internal == False,
                Message.sender_id != conversation.user_id,
                Message.created_at > latest_customer.created_at,
            )
            .limit(1)
        )
        reply_after = reply_after_result.scalar_one_or_none()
        if reply_after is not None:
            return None

        return latest_customer

    async def _get_latest_agent_reply(self, conversation: Conversation) -> Message | None:
        result = await self.db.execute(
            select(Message)
            .where(
                Message.conversation_id == conversation.id,
                Message.is_internal == False,
                Message.sender_id != conversation.user_id,
            )
            .order_by(Message.created_at.desc())
            .limit(1)
        )
        return result.scalar_one_or_none()
