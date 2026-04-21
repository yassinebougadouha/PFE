import os
import asyncio
import uuid
from types import SimpleNamespace

from jose import jwt

os.environ["DEBUG"] = "true"

from app.api.routes.voice_agents import get_support_call_token, manager
from app.db.models.enums import UserRole


def test_support_call_token_uses_authenticated_client_identity(monkeypatch):
    user_id = uuid.uuid4()

    monkeypatch.setattr(
        manager,
        "get_effective_config",
        lambda: SimpleNamespace(
            livekit_api_key="test-key",
            livekit_api_secret="test-secret",
            livekit_url="ws://livekit:7880",
        ),
    )

    response = asyncio.run(
        get_support_call_token(
            current_user=SimpleNamespace(
                id=user_id,
                role=UserRole.CLIENT,
            )
        )
    )

    claims = jwt.decode(response.token, "test-secret", algorithms=["HS256"])

    assert response.url == "ws://127.0.0.1:7880"
    assert claims["iss"] == "test-key"
    assert claims["sub"] == f"client-{user_id}"
    assert claims["video"]["room"] == f"support-call-{user_id}"
