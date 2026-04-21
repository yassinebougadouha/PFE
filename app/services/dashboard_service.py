"""
Dashboard aggregation service.
"""

import uuid
from collections import defaultdict
from datetime import date, datetime, timedelta, timezone
from statistics import median

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.audit_log import AuditLog
from app.db.models.conversation import Conversation, Message
from app.db.models.email import Email
from app.db.models.enums import (
    AuditAction,
    ChannelType,
    TicketPriority,
    TicketStatus,
    UserRole,
    UserStatus,
)
from app.db.models.ticket import Ticket
from app.db.models.user import User
from app.decision_engine.enums import DecisionOutcome
from app.decision_engine.models import DecisionLog
from app.schemas.dashboard import (
    AssistedDraftAgentSummary,
    AssistedDraftChannelSummary,
    AssistedDraftDailySummary,
    AssistedDraftPerformanceSummary,
    AiOpsSummary,
    DailyTicketSummary,
    DashboardCounts,
    DashboardSummaryResponse,
    DashboardUserStats,
    LeaderboardEntry,
    MonthlyTicketSummary,
    PersonalPerformanceSummary,
    RecentTicketSummary,
    UrgentTicketSummary,
    WeeklyTicketSummary,
    WeeklyActivitySummary,
)


class DashboardService:
    ASSISTED_DRAFT_LOOKBACK_DAYS = 30
    ASSISTED_DRAFT_ALLOWED_LOOKBACK_DAYS = {7, 30, 90}

    def __init__(self, db: AsyncSession):
        self.db = db

    async def get_summary(
        self,
        current_user: User,
        assisted_draft_days: int | None = None,
    ) -> DashboardSummaryResponse:
        scope = current_user.role.value.lower()
        tickets = await self._load_scoped_tickets(current_user)
        lookback_days = self._normalize_assisted_draft_lookback_days(assisted_draft_days)

        counts = DashboardCounts(
            total=len(tickets),
            open=sum(1 for ticket in tickets if ticket.status == TicketStatus.OPEN),
            in_progress=sum(1 for ticket in tickets if ticket.status == TicketStatus.IN_PROGRESS),
            escalated=sum(1 for ticket in tickets if ticket.status == TicketStatus.ESCALATED),
            resolved=sum(1 for ticket in tickets if ticket.status == TicketStatus.RESOLVED),
            closed=sum(1 for ticket in tickets if ticket.status == TicketStatus.CLOSED),
        )

        weekly = self._build_weekly_summary(tickets)
        daily_tickets = self._build_daily_tickets(tickets)
        weekly_tickets = self._build_weekly_tickets(tickets)
        monthly_tickets = self._build_monthly_tickets(tickets)
        user_stats = await self._build_user_stats(current_user)
        personal_performance = await self._build_personal_performance(current_user)
        urgent_tickets = [
            UrgentTicketSummary.model_validate(ticket)
            for ticket in self._build_urgent_tickets(tickets)
        ]
        leaderboard = await self._build_leaderboard(current_user)
        ai_ops = await self._build_ai_ops(current_user, tickets)
        assisted_draft = await self._build_assisted_draft_performance(
            current_user,
            lookback_days=lookback_days,
        )
        recent_tickets = [
            RecentTicketSummary.model_validate(ticket)
            for ticket in sorted(tickets, key=lambda item: item.created_at, reverse=True)[:8]
        ]

        return DashboardSummaryResponse(
            scope=scope,
            counts=counts,
            weekly=weekly,
            daily_tickets=daily_tickets,
            weekly_tickets=weekly_tickets,
            monthly_tickets=monthly_tickets,
            user_stats=user_stats,
            personal_performance=personal_performance,
            urgent_tickets=urgent_tickets,
            leaderboard=leaderboard,
            ai_ops=ai_ops,
            assisted_draft=assisted_draft,
            recent_tickets=recent_tickets,
        )

    @staticmethod
    def _build_daily_tickets(tickets: list[Ticket], days: int = 7) -> list[DailyTicketSummary]:
        counts_by_day: dict[date, int] = {}
        for ticket in tickets:
            created_at = ticket.created_at
            created_day = created_at.astimezone(timezone.utc).date() if created_at.tzinfo else created_at.date()
            counts_by_day[created_day] = counts_by_day.get(created_day, 0) + 1

        today = datetime.now(timezone.utc).date()
        series: list[DailyTicketSummary] = []
        for offset in range(-(days - 1), 1):
            day = today + timedelta(days=offset)
            series.append(
                DailyTicketSummary(
                    day=day.strftime("%d %b"),
                    count=counts_by_day.get(day, 0),
                )
            )

        return series

    @staticmethod
    def _build_weekly_tickets(tickets: list[Ticket], weeks: int = 8) -> list[WeeklyTicketSummary]:
        counts_by_week_start: dict[date, int] = {}
        for ticket in tickets:
            created_at = ticket.created_at
            created_day = created_at.astimezone(timezone.utc).date() if created_at.tzinfo else created_at.date()
            week_start = created_day - timedelta(days=created_day.weekday())
            counts_by_week_start[week_start] = counts_by_week_start.get(week_start, 0) + 1

        today = datetime.now(timezone.utc).date()
        current_week_start = today - timedelta(days=today.weekday())

        series: list[WeeklyTicketSummary] = []
        for offset in range(-(weeks - 1), 1):
            week_start = current_week_start + timedelta(weeks=offset)
            series.append(
                WeeklyTicketSummary(
                    week=f"Week of {week_start.strftime('%d %b')}",
                    count=counts_by_week_start.get(week_start, 0),
                )
            )

        return series

    @staticmethod
    def _build_monthly_tickets(tickets: list[Ticket], months: int = 6) -> list[MonthlyTicketSummary]:
        counts_by_month: dict[tuple[int, int], int] = {}
        for ticket in tickets:
            created_at = ticket.created_at
            counts_by_month[(created_at.year, created_at.month)] = counts_by_month.get((created_at.year, created_at.month), 0) + 1

        now = datetime.now(timezone.utc)

        def shift_month(year: int, month: int, delta: int) -> tuple[int, int]:
            absolute = year * 12 + (month - 1) + delta
            return absolute // 12, absolute % 12 + 1

        series: list[MonthlyTicketSummary] = []
        for offset in range(-(months - 1), 1):
            year, month = shift_month(now.year, now.month, offset)
            month_start = datetime(year, month, 1, tzinfo=timezone.utc)
            series.append(
                MonthlyTicketSummary(
                    month=month_start.strftime("%b %Y"),
                    count=counts_by_month.get((year, month), 0),
                )
            )

        return series

    async def _build_user_stats(self, current_user: User) -> DashboardUserStats:
        # Keep global user directory stats visible to admins only.
        if current_user.role != UserRole.ADMIN:
            return DashboardUserStats(
                total_users=0,
                total_admins=0,
                total_agents=0,
                total_clients=0,
                active_agents=0,
            )

        role_counts_result = await self.db.execute(
            select(User.role, func.count(User.id)).where(
                User.is_deleted == False,
            ).group_by(User.role)
        )
        role_counts = {row[0]: int(row[1]) for row in role_counts_result.all()}

        active_agents_result = await self.db.execute(
            select(func.count(User.id)).where(
                User.is_deleted == False,
                User.role == UserRole.AGENT,
                User.status == UserStatus.ACTIVE,
            )
        )
        active_agents = int(active_agents_result.scalar() or 0)

        return DashboardUserStats(
            total_users=sum(role_counts.values()),
            total_admins=role_counts.get(UserRole.ADMIN, 0),
            total_agents=role_counts.get(UserRole.AGENT, 0),
            total_clients=role_counts.get(UserRole.CLIENT, 0),
            active_agents=active_agents,
        )

    async def _build_personal_performance(self, current_user: User) -> PersonalPerformanceSummary:
        if current_user.role not in {UserRole.ADMIN, UserRole.AGENT}:
            return PersonalPerformanceSummary(
                my_resolved_tickets=0,
                my_open_assigned_tickets=0,
            )

        resolved_result = await self.db.execute(
            select(func.count(Ticket.id)).where(
                Ticket.is_deleted == False,
                Ticket.solved_by_id == current_user.id,
            )
        )
        my_resolved_tickets = int(resolved_result.scalar() or 0)

        open_assigned_result = await self.db.execute(
            select(func.count(Ticket.id)).where(
                Ticket.is_deleted == False,
                Ticket.assigned_agent_id == current_user.id,
                Ticket.status.in_(
                    [
                        TicketStatus.OPEN,
                        TicketStatus.IN_PROGRESS,
                        TicketStatus.WAITING_ON_CUSTOMER,
                        TicketStatus.ESCALATED,
                    ]
                ),
            )
        )
        my_open_assigned_tickets = int(open_assigned_result.scalar() or 0)

        return PersonalPerformanceSummary(
            my_resolved_tickets=my_resolved_tickets,
            my_open_assigned_tickets=my_open_assigned_tickets,
        )

    async def _load_scoped_tickets(self, current_user: User) -> list[Ticket]:
        query = select(Ticket).where(Ticket.is_deleted == False)
        if current_user.role == UserRole.CLIENT:
            query = query.where(Ticket.creator_id == current_user.id)
        elif current_user.role == UserRole.AGENT:
            query = query.where(Ticket.assigned_agent_id == current_user.id)
        result = await self.db.execute(query)
        return list(result.scalars().all())

    @staticmethod
    def _build_weekly_summary(tickets: list[Ticket]) -> WeeklyActivitySummary:
        now = datetime.now(timezone.utc)
        week_start = now - timedelta(days=7)
        prior_week_start = now - timedelta(days=14)

        created_this_week = sum(1 for ticket in tickets if ticket.created_at >= week_start)
        resolved_this_week = sum(1 for ticket in tickets if ticket.resolved_at and ticket.resolved_at >= week_start)
        created_last_week = sum(
            1 for ticket in tickets if prior_week_start <= ticket.created_at < week_start
        )
        resolved_last_week = sum(
            1 for ticket in tickets if ticket.resolved_at and prior_week_start <= ticket.resolved_at < week_start
        )

        return WeeklyActivitySummary(
            created_this_week=created_this_week,
            resolved_this_week=resolved_this_week,
            created_last_week=created_last_week,
            resolved_last_week=resolved_last_week,
        )

    @staticmethod
    def _build_urgent_tickets(tickets: list[Ticket]) -> list[Ticket]:
        urgent_statuses = {
            TicketStatus.OPEN,
            TicketStatus.IN_PROGRESS,
            TicketStatus.WAITING_ON_CUSTOMER,
            TicketStatus.ESCALATED,
        }
        urgent_priorities = {TicketPriority.HIGH, TicketPriority.CRITICAL}

        def sort_key(ticket: Ticket):
            priority_rank = 0 if ticket.priority == TicketPriority.CRITICAL else 1
            return (priority_rank, ticket.created_at)

        return sorted(
            [
                ticket
                for ticket in tickets
                if ticket.status in urgent_statuses and ticket.priority in urgent_priorities
            ],
            key=sort_key,
        )[:5]

    async def _build_leaderboard(self, current_user: User) -> list[LeaderboardEntry]:
        if current_user.role not in {UserRole.ADMIN, UserRole.AGENT}:
            return []

        result = await self.db.execute(
            select(User).where(
                User.role == UserRole.AGENT,
                User.is_deleted == False,
            )
        )
        users = list(result.scalars().all())
        if not users:
            return []

        tickets_result = await self.db.execute(
            select(Ticket).where(
                Ticket.is_deleted == False,
                Ticket.assigned_agent_id.is_not(None),
            )
        )
        tickets = list(tickets_result.scalars().all())

        leaderboard: list[LeaderboardEntry] = []
        for user in users:
            resolved_count = sum(1 for ticket in tickets if ticket.solved_by_id == user.id)
            open_assigned_count = sum(
                1
                for ticket in tickets
                if ticket.assigned_agent_id == user.id and ticket.status in {
                    TicketStatus.OPEN,
                    TicketStatus.IN_PROGRESS,
                    TicketStatus.WAITING_ON_CUSTOMER,
                    TicketStatus.ESCALATED,
                }
            )
            leaderboard.append(
                LeaderboardEntry(
                    user_id=user.id,
                    full_name=user.full_name,
                    resolved_count=resolved_count,
                    open_assigned_count=open_assigned_count,
                )
            )

        return sorted(
            leaderboard,
            key=lambda entry: (-entry.resolved_count, entry.open_assigned_count, entry.full_name.lower()),
        )[:5]

    async def _build_ai_ops(self, current_user: User, scoped_tickets: list[Ticket]) -> AiOpsSummary:
        ticket_ids = [ticket.id for ticket in scoped_tickets]
        if not ticket_ids:
            return AiOpsSummary(
                total_decisions=0,
                auto_resolved=0,
                escalated=0,
                escalation_rate=0.0,
                urgent_open_count=len(self._build_urgent_tickets(scoped_tickets)),
            )

        query = select(
            DecisionLog.decision_outcome,
            func.count(DecisionLog.id),
        ).where(DecisionLog.ticket_id.in_(ticket_ids)).group_by(DecisionLog.decision_outcome)
        result = await self.db.execute(query)
        counts_by_outcome = {row[0]: row[1] for row in result.all()}

        total = sum(counts_by_outcome.values())
        auto_resolved = counts_by_outcome.get(DecisionOutcome.AUTO_RESOLVE, 0)
        escalated = counts_by_outcome.get(DecisionOutcome.ESCALATE_HUMAN, 0)

        return AiOpsSummary(
            total_decisions=total,
            auto_resolved=auto_resolved,
            escalated=escalated,
            escalation_rate=round(escalated / total, 3) if total else 0.0,
            urgent_open_count=len(self._build_urgent_tickets(scoped_tickets)),
        )

    async def _build_assisted_draft_performance(
        self,
        current_user: User,
        lookback_days: int | None = None,
    ) -> AssistedDraftPerformanceSummary:
        normalized_lookback_days = self._normalize_assisted_draft_lookback_days(lookback_days)
        if current_user.role == UserRole.CLIENT:
            return self._empty_assisted_draft_performance(lookback_days=normalized_lookback_days)

        now = datetime.now(timezone.utc)
        since = now - timedelta(days=normalized_lookback_days)

        log_query = select(AuditLog).where(
            AuditLog.action == AuditAction.REPLY,
            AuditLog.resource_type == "assisted_draft",
            AuditLog.created_at >= since,
        )
        if current_user.role == UserRole.AGENT:
            log_query = log_query.where(AuditLog.user_id == current_user.id)

        logs_result = await self.db.execute(log_query.order_by(AuditLog.created_at.asc()))
        logs = list(logs_result.scalars().all())

        sent_query = (
            select(Conversation.channel, Message.created_at)
            .join(Conversation, Message.conversation_id == Conversation.id)
            .where(
                Message.is_internal == False,
                Message.sender_id != Conversation.user_id,
                Message.created_at >= since,
                Conversation.is_deleted == False,
                Conversation.channel.in_([ChannelType.CHAT, ChannelType.WHATSAPP]),
            )
        )
        if current_user.role == UserRole.AGENT:
            sent_query = sent_query.where(Message.sender_id == current_user.id)

        sent_result = await self.db.execute(sent_query)
        sent_rows = list(sent_result.all())

        email_sent_query = select(Email.created_at).where(
            Email.is_outbound.is_(True),
            Email.created_at >= since,
        )
        if current_user.role == UserRole.AGENT:
            email_sent_query = email_sent_query.where(Email.replied_by_id == current_user.id)

        email_sent_result = await self.db.execute(email_sent_query)
        email_sent_rows = [
            self._ensure_utc(created_at)
            for created_at in email_sent_result.scalars().all()
        ]

        start_day = (now - timedelta(days=normalized_lookback_days - 1)).date()
        daily_counts: dict[date, dict[str, int]] = defaultdict(
            lambda: {"generated": 0, "accepted": 0, "sent": 0}
        )
        per_channel_counts: dict[str, dict[str, int]] = {
            "chat": {"generated": 0, "accepted": 0, "sent": 0},
            "whatsapp": {"generated": 0, "accepted": 0, "sent": 0},
            "email": {"generated": 0, "accepted": 0, "sent": 0},
        }

        pending_generated_by_key: dict[tuple[str, uuid.UUID | None, str], list[datetime]] = defaultdict(list)
        latency_seconds_global: list[float] = []
        edited_samples_global: list[bool] = []
        latency_by_channel: dict[str, list[float]] = {"chat": [], "whatsapp": [], "email": []}
        edited_by_channel: dict[str, list[bool]] = {"chat": [], "whatsapp": [], "email": []}

        generated_by_agent: dict[uuid.UUID, int] = defaultdict(int)
        accepted_by_agent: dict[uuid.UUID, int] = defaultdict(int)

        total_generated = 0
        total_accepted = 0

        for log in logs:
            raw_meta = log.meta if isinstance(log.meta, dict) else {}
            event = str(raw_meta.get("event", "")).strip().lower()
            if event not in {"generated", "accepted"}:
                continue

            channel = str(raw_meta.get("channel", "chat")).strip().lower()
            if channel not in {"chat", "whatsapp", "email"}:
                channel = "chat"

            created_at = self._ensure_utc(log.created_at)
            created_day = created_at.date()
            if created_day >= start_day:
                daily_counts[created_day][event] += 1

            per_channel_counts[channel][event] += 1

            key = (str(log.resource_id or ""), log.user_id, channel)
            if event == "generated":
                total_generated += 1
                if log.user_id:
                    generated_by_agent[log.user_id] += 1
                pending_generated_by_key[key].append(created_at)
                continue

            total_accepted += 1
            if log.user_id:
                accepted_by_agent[log.user_id] += 1

            explicit_latency = self._coerce_non_negative_number(raw_meta.get("assisted_draft_seconds_to_send"))
            if explicit_latency is None and pending_generated_by_key[key]:
                generated_at = pending_generated_by_key[key].pop()
                explicit_latency = max(0.0, (created_at - generated_at).total_seconds())
            elif pending_generated_by_key[key]:
                pending_generated_by_key[key].pop()

            if explicit_latency is not None:
                latency_seconds_global.append(explicit_latency)
                latency_by_channel[channel].append(explicit_latency)

            edited = self._coerce_optional_bool(raw_meta.get("assisted_draft_edited"))
            if edited is not None:
                edited_samples_global.append(edited)
                edited_by_channel[channel].append(edited)

        total_sent = 0
        for raw_channel, created_at in sent_rows:
            channel_value = getattr(raw_channel, "value", raw_channel)
            channel = str(channel_value or "").strip().lower()
            if channel not in {"chat", "whatsapp"}:
                continue

            total_sent += 1
            per_channel_counts[channel]["sent"] += 1

            created_day = self._ensure_utc(created_at).date()
            if created_day >= start_day:
                daily_counts[created_day]["sent"] += 1

        for created_at in email_sent_rows:
            total_sent += 1
            per_channel_counts["email"]["sent"] += 1

            created_day = created_at.date()
            if created_day >= start_day:
                daily_counts[created_day]["sent"] += 1

        daily = []
        for offset in range(-(normalized_lookback_days - 1), 1):
            day = (now + timedelta(days=offset)).date()
            counts = daily_counts.get(day, {"generated": 0, "accepted": 0, "sent": 0})
            daily.append(
                AssistedDraftDailySummary(
                    day=day.strftime("%d %b"),
                    generated=counts["generated"],
                    accepted=counts["accepted"],
                    sent=counts["sent"],
                )
            )

        channels = []
        for channel in ("chat", "whatsapp", "email"):
            channel_counts = per_channel_counts[channel]
            channel_generated = channel_counts["generated"]
            channel_accepted = channel_counts["accepted"]
            channel_sent = channel_counts["sent"]
            channel_edited = edited_by_channel[channel]
            channel_latency = latency_by_channel[channel]
            channels.append(
                AssistedDraftChannelSummary(
                    channel=channel,
                    generated=channel_generated,
                    accepted=channel_accepted,
                    sent=channel_sent,
                    acceptance_rate=round(channel_accepted / channel_generated, 3) if channel_generated else 0.0,
                    assisted_share=round(channel_accepted / channel_sent, 3) if channel_sent else 0.0,
                    edited_rate=round(sum(1 for item in channel_edited if item) / len(channel_edited), 3)
                    if channel_edited
                    else 0.0,
                    edited_samples=len(channel_edited),
                    median_seconds_to_send=round(float(median(channel_latency)), 1) if channel_latency else None,
                    latency_samples=len(channel_latency),
                )
            )

        top_agents: list[AssistedDraftAgentSummary] = []
        if current_user.role == UserRole.ADMIN and accepted_by_agent:
            user_ids = list(accepted_by_agent.keys())
            users_result = await self.db.execute(
                select(User.id, User.full_name).where(User.id.in_(user_ids))
            )
            names = {user_id: full_name for user_id, full_name in users_result.all()}

            sorted_ids = sorted(
                user_ids,
                key=lambda user_id: (
                    -accepted_by_agent.get(user_id, 0),
                    -generated_by_agent.get(user_id, 0),
                    names.get(user_id, "").lower(),
                ),
            )
            for user_id in sorted_ids[:5]:
                generated = generated_by_agent.get(user_id, 0)
                accepted = accepted_by_agent.get(user_id, 0)
                top_agents.append(
                    AssistedDraftAgentSummary(
                        user_id=user_id,
                        full_name=names.get(user_id, "Unknown agent"),
                        generated=generated,
                        accepted=accepted,
                        acceptance_rate=round(accepted / generated, 3) if generated else 0.0,
                    )
                )

        return AssistedDraftPerformanceSummary(
            lookback_days=normalized_lookback_days,
            total_generated=total_generated,
            total_accepted=total_accepted,
            total_sent=total_sent,
            acceptance_rate=round(total_accepted / total_generated, 3) if total_generated else 0.0,
            assisted_share=round(total_accepted / total_sent, 3) if total_sent else 0.0,
            edited_rate=round(sum(1 for item in edited_samples_global if item) / len(edited_samples_global), 3)
            if edited_samples_global
            else 0.0,
            edited_samples=len(edited_samples_global),
            median_seconds_to_send=round(float(median(latency_seconds_global)), 1)
            if latency_seconds_global
            else None,
            latency_samples=len(latency_seconds_global),
            channels=channels,
            daily=daily,
            top_agents=top_agents,
        )

    @classmethod
    def _empty_assisted_draft_performance(
        cls,
        *,
        lookback_days: int | None = None,
    ) -> AssistedDraftPerformanceSummary:
        normalized_lookback_days = cls._normalize_assisted_draft_lookback_days(lookback_days)
        return AssistedDraftPerformanceSummary(
            lookback_days=normalized_lookback_days,
            total_generated=0,
            total_accepted=0,
            total_sent=0,
            acceptance_rate=0.0,
            assisted_share=0.0,
            edited_rate=0.0,
            edited_samples=0,
            median_seconds_to_send=None,
            latency_samples=0,
            channels=[
                AssistedDraftChannelSummary(
                    channel="chat",
                    generated=0,
                    accepted=0,
                    sent=0,
                    acceptance_rate=0.0,
                    assisted_share=0.0,
                    edited_rate=0.0,
                    edited_samples=0,
                    median_seconds_to_send=None,
                    latency_samples=0,
                ),
                AssistedDraftChannelSummary(
                    channel="whatsapp",
                    generated=0,
                    accepted=0,
                    sent=0,
                    acceptance_rate=0.0,
                    assisted_share=0.0,
                    edited_rate=0.0,
                    edited_samples=0,
                    median_seconds_to_send=None,
                    latency_samples=0,
                ),
                AssistedDraftChannelSummary(
                    channel="email",
                    generated=0,
                    accepted=0,
                    sent=0,
                    acceptance_rate=0.0,
                    assisted_share=0.0,
                    edited_rate=0.0,
                    edited_samples=0,
                    median_seconds_to_send=None,
                    latency_samples=0,
                ),
            ],
            daily=[
                AssistedDraftDailySummary(
                    day=(datetime.now(timezone.utc).date() + timedelta(days=offset)).strftime("%d %b"),
                    generated=0,
                    accepted=0,
                    sent=0,
                )
                for offset in range(-(normalized_lookback_days - 1), 1)
            ],
            top_agents=[],
        )

    @classmethod
    def _normalize_assisted_draft_lookback_days(cls, value: int | None) -> int:
        if value is None:
            return cls.ASSISTED_DRAFT_LOOKBACK_DAYS

        try:
            parsed = int(value)
        except (TypeError, ValueError):
            return cls.ASSISTED_DRAFT_LOOKBACK_DAYS

        if parsed not in cls.ASSISTED_DRAFT_ALLOWED_LOOKBACK_DAYS:
            return cls.ASSISTED_DRAFT_LOOKBACK_DAYS
        return parsed

    @staticmethod
    def _ensure_utc(value: datetime) -> datetime:
        if value.tzinfo is None:
            return value.replace(tzinfo=timezone.utc)
        return value.astimezone(timezone.utc)

    @staticmethod
    def _coerce_non_negative_number(value: object) -> float | None:
        if value is None:
            return None
        try:
            numeric = float(value)
        except (TypeError, ValueError):
            return None
        if numeric < 0:
            return None
        return numeric

    @staticmethod
    def _coerce_optional_bool(value: object) -> bool | None:
        if isinstance(value, bool):
            return value
        if isinstance(value, str):
            normalized = value.strip().lower()
            if normalized in {"true", "1", "yes", "on"}:
                return True
            if normalized in {"false", "0", "no", "off"}:
                return False
        return None
