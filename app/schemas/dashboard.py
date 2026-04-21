"""
Dashboard summary schemas.
"""

import uuid
from datetime import datetime
from typing import Literal

from pydantic import BaseModel

from app.db.models.enums import TicketPriority, TicketStatus


class DashboardCounts(BaseModel):
    total: int
    open: int
    in_progress: int
    escalated: int
    resolved: int
    closed: int


class WeeklyActivitySummary(BaseModel):
    created_this_week: int
    resolved_this_week: int
    created_last_week: int
    resolved_last_week: int


class UrgentTicketSummary(BaseModel):
    id: uuid.UUID
    subject: str
    status: TicketStatus
    priority: TicketPriority
    assigned_agent_id: uuid.UUID | None = None
    created_at: datetime
    updated_at: datetime

    model_config = {"from_attributes": True}


class LeaderboardEntry(BaseModel):
    user_id: uuid.UUID
    full_name: str
    resolved_count: int
    open_assigned_count: int


class AiOpsSummary(BaseModel):
    total_decisions: int
    auto_resolved: int
    escalated: int
    escalation_rate: float
    urgent_open_count: int


class AssistedDraftChannelSummary(BaseModel):
    channel: Literal["chat", "whatsapp", "email"]
    generated: int
    accepted: int
    sent: int
    acceptance_rate: float
    assisted_share: float
    edited_rate: float
    edited_samples: int
    median_seconds_to_send: float | None = None
    latency_samples: int


class AssistedDraftDailySummary(BaseModel):
    day: str
    generated: int
    accepted: int
    sent: int


class AssistedDraftAgentSummary(BaseModel):
    user_id: uuid.UUID
    full_name: str
    generated: int
    accepted: int
    acceptance_rate: float


class AssistedDraftPerformanceSummary(BaseModel):
    lookback_days: int
    total_generated: int
    total_accepted: int
    total_sent: int
    acceptance_rate: float
    assisted_share: float
    edited_rate: float
    edited_samples: int
    median_seconds_to_send: float | None = None
    latency_samples: int
    channels: list[AssistedDraftChannelSummary]
    daily: list[AssistedDraftDailySummary]
    top_agents: list[AssistedDraftAgentSummary]


class DashboardUserStats(BaseModel):
    total_users: int
    total_admins: int
    total_agents: int
    total_clients: int
    active_agents: int


class PersonalPerformanceSummary(BaseModel):
    my_resolved_tickets: int
    my_open_assigned_tickets: int


class RecentTicketSummary(BaseModel):
    id: uuid.UUID
    subject: str
    status: TicketStatus
    priority: TicketPriority
    channel_source: str
    created_at: datetime

    model_config = {"from_attributes": True}


class MonthlyTicketSummary(BaseModel):
    month: str
    count: int


class WeeklyTicketSummary(BaseModel):
    week: str
    count: int


class DailyTicketSummary(BaseModel):
    day: str
    count: int


class DashboardSummaryResponse(BaseModel):
    scope: Literal["client", "agent", "admin"]
    counts: DashboardCounts
    weekly: WeeklyActivitySummary
    daily_tickets: list[DailyTicketSummary]
    weekly_tickets: list[WeeklyTicketSummary]
    monthly_tickets: list[MonthlyTicketSummary]
    user_stats: DashboardUserStats
    personal_performance: PersonalPerformanceSummary
    urgent_tickets: list[UrgentTicketSummary]
    leaderboard: list[LeaderboardEntry]
    ai_ops: AiOpsSummary
    assisted_draft: AssistedDraftPerformanceSummary
    recent_tickets: list[RecentTicketSummary]
