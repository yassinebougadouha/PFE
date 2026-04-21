"""
Decision engine runtime configuration.

Configuration values are persisted in the shared settings table under
section "decision_engine" and normalized before use.
"""

from dataclasses import asdict, dataclass
from typing import Any

from sqlalchemy.ext.asyncio import AsyncSession

from app.services.settings_service import SettingsService


DEFAULT_DECISION_ENGINE_CONFIG: dict[str, Any] = {
    "decision_confidence_high_threshold": 0.7,
    "decision_confidence_medium_threshold": 0.4,
    "decision_risk_critical_threshold": 0.7,
    "decision_risk_high_threshold": 0.5,
    "decision_risk_medium_threshold": 0.3,
    "decision_low_confidence_risk_boost": 0.08,
    "decision_medium_confidence_risk_boost": 0.03,
    "decision_enforce_security_escalation": True,
    "decision_enforce_critical_escalation": True,
    "decision_low_confidence_general_suggest": True,
}

DECISION_ENGINE_SECTION = "decision_engine"
DECISION_ENGINE_SETTING_KEYS = tuple(DEFAULT_DECISION_ENGINE_CONFIG.keys())


@dataclass(slots=True)
class DecisionEngineRuntimeConfig:
    confidence_high_threshold: float
    confidence_medium_threshold: float
    risk_critical_threshold: float
    risk_high_threshold: float
    risk_medium_threshold: float
    low_confidence_risk_boost: float
    medium_confidence_risk_boost: float
    enforce_security_escalation: bool
    enforce_critical_escalation: bool
    low_confidence_general_suggest: bool

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)


def _as_float(value: Any, default: float, minimum: float, maximum: float) -> float:
    try:
        numeric = float(value)
    except (TypeError, ValueError):
        numeric = default
    return max(minimum, min(maximum, numeric))


def _as_bool(value: Any, default: bool) -> bool:
    if isinstance(value, bool):
        return value
    if isinstance(value, str):
        normalized = value.strip().lower()
        if normalized in {"1", "true", "yes", "on"}:
            return True
        if normalized in {"0", "false", "no", "off"}:
            return False
    if value is None:
        return default
    return bool(value)


def normalize_runtime_config(values: dict[str, Any]) -> DecisionEngineRuntimeConfig:
    high_conf = _as_float(
        values.get("decision_confidence_high_threshold"),
        DEFAULT_DECISION_ENGINE_CONFIG["decision_confidence_high_threshold"],
        0.45,
        0.98,
    )
    medium_conf = _as_float(
        values.get("decision_confidence_medium_threshold"),
        DEFAULT_DECISION_ENGINE_CONFIG["decision_confidence_medium_threshold"],
        0.05,
        0.94,
    )
    if medium_conf >= high_conf:
        medium_conf = max(0.05, min(high_conf - 0.01, 0.94))

    critical_risk = _as_float(
        values.get("decision_risk_critical_threshold"),
        DEFAULT_DECISION_ENGINE_CONFIG["decision_risk_critical_threshold"],
        0.45,
        1.0,
    )
    high_risk = _as_float(
        values.get("decision_risk_high_threshold"),
        DEFAULT_DECISION_ENGINE_CONFIG["decision_risk_high_threshold"],
        0.2,
        0.98,
    )
    medium_risk = _as_float(
        values.get("decision_risk_medium_threshold"),
        DEFAULT_DECISION_ENGINE_CONFIG["decision_risk_medium_threshold"],
        0.05,
        0.9,
    )

    if high_risk >= critical_risk:
        high_risk = max(0.2, min(critical_risk - 0.01, 0.98))
    if medium_risk >= high_risk:
        medium_risk = max(0.05, min(high_risk - 0.01, 0.9))

    return DecisionEngineRuntimeConfig(
        confidence_high_threshold=high_conf,
        confidence_medium_threshold=medium_conf,
        risk_critical_threshold=critical_risk,
        risk_high_threshold=high_risk,
        risk_medium_threshold=medium_risk,
        low_confidence_risk_boost=_as_float(
            values.get("decision_low_confidence_risk_boost"),
            DEFAULT_DECISION_ENGINE_CONFIG["decision_low_confidence_risk_boost"],
            0.0,
            0.4,
        ),
        medium_confidence_risk_boost=_as_float(
            values.get("decision_medium_confidence_risk_boost"),
            DEFAULT_DECISION_ENGINE_CONFIG["decision_medium_confidence_risk_boost"],
            0.0,
            0.25,
        ),
        enforce_security_escalation=_as_bool(
            values.get("decision_enforce_security_escalation"),
            bool(DEFAULT_DECISION_ENGINE_CONFIG["decision_enforce_security_escalation"]),
        ),
        enforce_critical_escalation=_as_bool(
            values.get("decision_enforce_critical_escalation"),
            bool(DEFAULT_DECISION_ENGINE_CONFIG["decision_enforce_critical_escalation"]),
        ),
        low_confidence_general_suggest=_as_bool(
            values.get("decision_low_confidence_general_suggest"),
            bool(DEFAULT_DECISION_ENGINE_CONFIG["decision_low_confidence_general_suggest"]),
        ),
    )


async def load_runtime_config(db: AsyncSession) -> DecisionEngineRuntimeConfig:
    settings = await SettingsService(db).get_all_settings()
    merged = dict(DEFAULT_DECISION_ENGINE_CONFIG)
    for key in DECISION_ENGINE_SETTING_KEYS:
        if key in settings:
            merged[key] = settings[key]
    return normalize_runtime_config(merged)


def runtime_config_to_settings(config: DecisionEngineRuntimeConfig) -> dict[str, Any]:
    return {
        "decision_confidence_high_threshold": config.confidence_high_threshold,
        "decision_confidence_medium_threshold": config.confidence_medium_threshold,
        "decision_risk_critical_threshold": config.risk_critical_threshold,
        "decision_risk_high_threshold": config.risk_high_threshold,
        "decision_risk_medium_threshold": config.risk_medium_threshold,
        "decision_low_confidence_risk_boost": config.low_confidence_risk_boost,
        "decision_medium_confidence_risk_boost": config.medium_confidence_risk_boost,
        "decision_enforce_security_escalation": config.enforce_security_escalation,
        "decision_enforce_critical_escalation": config.enforce_critical_escalation,
        "decision_low_confidence_general_suggest": config.low_confidence_general_suggest,
    }
