import uuid
from types import SimpleNamespace

from app.services.conversation_playbook_service import ConversationPlaybookService


def _snapshot(
    *,
    triggers: list[str],
    pending: bool = True,
    snoozed: bool = False,
    risk_level: str = "low",
    breached: bool = False,
    seconds_remaining: int | None = None,
):
    return SimpleNamespace(
        conversation_id=uuid.uuid4(),
        pending_customer_message_id=uuid.uuid4() if pending else None,
        triggers=[SimpleNamespace(key=key) for key in triggers],
        snoozed=snoozed,
        risk_level=risk_level,
        breached=breached,
        seconds_remaining=seconds_remaining,
    )


def test_autopilot_config_uses_defaults_and_type_coercion():
    service = ConversationPlaybookService(db=SimpleNamespace())
    config = service._build_autopilot_config(
        {
            "conversation_sla_autopilot_enabled": "true",
            "conversation_sla_auto_assign_enabled": "false",
            "conversation_sla_auto_escalate_minutes_before_breach": "20",
            "conversation_sla_auto_assign_minutes_before_breach": "8",
            "conversation_sla_autopilot_respect_snooze": "1",
        }
    )

    assert config.enabled is True
    assert config.auto_assign_enabled is False
    assert config.escalate_minutes_before_breach == 20
    assert config.assign_minutes_before_breach == 8
    assert config.respect_snooze is True


def test_should_auto_escalate_honors_threshold_and_snooze():
    service = ConversationPlaybookService(db=SimpleNamespace())
    config = service._build_autopilot_config(
        {
            "conversation_sla_autopilot_enabled": True,
            "conversation_sla_auto_assign_enabled": True,
            "conversation_sla_auto_escalate_minutes_before_breach": 15,
            "conversation_sla_auto_assign_minutes_before_breach": 10,
            "conversation_sla_autopilot_respect_snooze": True,
        }
    )

    at_threshold_snapshot = _snapshot(
        triggers=["no_agent_reply_within_sla"],
        seconds_remaining=15 * 60,
    )
    assert service._should_auto_escalate(at_threshold_snapshot, autopilot=config) is True

    snoozed_snapshot = _snapshot(
        triggers=["no_agent_reply_within_sla"],
        snoozed=True,
        seconds_remaining=60,
    )
    assert service._should_auto_escalate(snoozed_snapshot, autopilot=config) is False


def test_should_auto_assign_requires_setting_or_high_risk_signal():
    service = ConversationPlaybookService(db=SimpleNamespace())
    enabled = service._build_autopilot_config(
        {
            "conversation_sla_autopilot_enabled": True,
            "conversation_sla_auto_assign_enabled": True,
            "conversation_sla_auto_escalate_minutes_before_breach": 30,
            "conversation_sla_auto_assign_minutes_before_breach": 5,
            "conversation_sla_autopilot_respect_snooze": False,
        }
    )
    disabled_assign = service._build_autopilot_config(
        {
            "conversation_sla_autopilot_enabled": True,
            "conversation_sla_auto_assign_enabled": False,
            "conversation_sla_auto_escalate_minutes_before_breach": 30,
            "conversation_sla_auto_assign_minutes_before_breach": 5,
            "conversation_sla_autopilot_respect_snooze": False,
        }
    )

    high_risk_snapshot = _snapshot(
        triggers=["high_risk_intent"],
        risk_level="high",
        seconds_remaining=30 * 60,
    )
    assert service._should_auto_assign(high_risk_snapshot, autopilot=enabled) is True
    assert service._should_auto_assign(high_risk_snapshot, autopilot=disabled_assign) is False

    near_breach_snapshot = _snapshot(
        triggers=["no_agent_reply_within_sla"],
        risk_level="low",
        seconds_remaining=4 * 60,
    )
    assert service._should_auto_assign(near_breach_snapshot, autopilot=enabled) is True
