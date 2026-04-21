import asyncio
from types import SimpleNamespace

from app.visual_ai.routes import generate_troubleshooting_wizard
from app.visual_ai.schemas import TroubleshootingWizardRequest


def test_troubleshooting_wizard_returns_steps():
    payload = TroubleshootingWizardRequest(
        goal="Help customer complete checkout",
        issue_summary="Submit button remains disabled",
        observed_screen_caption="Checkout form with disabled button",
        observed_text="No explicit error shown",
        user_actions_attempted=["Refreshed page"],
        context_hints=["Problem started this morning"],
        max_steps=5,
    )

    response = asyncio.run(
        generate_troubleshooting_wizard(
            payload=payload,
            user=SimpleNamespace(id="user-1"),
        )
    )

    assert response.issue_summary == "Submit button remains disabled"
    assert response.provider == "rule-engine"
    assert len(response.steps) == 5
    assert response.steps[0].step_number == 1


def test_troubleshooting_wizard_sets_high_risk_for_error_markers():
    payload = TroubleshootingWizardRequest(
        goal="Fix login flow",
        issue_summary="User gets forbidden error while logging in",
        observed_screen_caption="Login screen with error banner",
        observed_text="403 Forbidden and timeout appeared",
        user_actions_attempted=["Reset password", "Retried with same account"],
        max_steps=4,
    )

    response = asyncio.run(
        generate_troubleshooting_wizard(
            payload=payload,
            user=SimpleNamespace(id="user-1"),
        )
    )

    assert response.risk_level == "high"
    assert response.estimated_time_minutes >= 12
    assert all(step.instructions for step in response.steps)
