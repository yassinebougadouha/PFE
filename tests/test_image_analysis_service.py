import os
import asyncio
import httpx

os.environ["DEBUG"] = "true"

from app.services import image_analysis_service


def _json_response(method: str, url: str, *, payload=None) -> httpx.Response:
    return httpx.Response(
        200,
        json=payload,
        request=httpx.Request(method, url),
    )


def test_analyze_chat_image_uses_gemini_and_parses_json(monkeypatch):
    class FakeGeminiClient:
        captured_json = None

        def __init__(self, *args, **kwargs):
            pass

        async def __aenter__(self):
            return self

        async def __aexit__(self, exc_type, exc, tb):
            return False

        async def post(self, url, *, json=None):
            FakeGeminiClient.captured_json = json
            return _json_response(
                "POST",
                url,
                payload={
                    "candidates": [
                        {
                            "content": {
                                "parts": [
                                    {
                                        "text": (
                                            '{"summary":"A login page with an error banner.",'
                                            '"visible_text":"Invalid password",'
                                            '"issue_signals":["The user is on a failed login attempt."],'
                                            '"suggested_focus":"Help the customer recover access"}'
                                        )
                                    }
                                ]
                            }
                        }
                    ]
                },
            )

    monkeypatch.setattr(image_analysis_service.settings, "GEMINI_API_KEY", "test-gemini-key", raising=False)
    monkeypatch.setattr(
        image_analysis_service.settings,
        "GEMINI_IMAGE_ANALYSIS_MODEL",
        "gemini-test",
        raising=False,
    )
    monkeypatch.setattr(image_analysis_service.httpx, "AsyncClient", FakeGeminiClient)

    result = asyncio.run(
        image_analysis_service.analyze_chat_image(
            b"fake-image-bytes",
            mime_type="image/jpeg",
            filename="login-error.jpg",
            customer_message="Can you check this screenshot?",
        )
    )

    assert result == {
        "summary": "A login page with an error banner.",
        "visible_text": "Invalid password",
        "issue_signals": ["The user is on a failed login attempt."],
        "suggested_focus": "Help the customer recover access",
    }
    assert (
        FakeGeminiClient.captured_json["contents"][0]["parts"][1]["inline_data"]["mime_type"]
        == "image/jpeg"
    )


def test_analyze_chat_image_falls_back_to_plain_text_summary(monkeypatch):
    class FakeGeminiClient:
        def __init__(self, *args, **kwargs):
            pass

        async def __aenter__(self):
            return self

        async def __aexit__(self, exc_type, exc, tb):
            return False

        async def post(self, url, *, json=None):
            return _json_response(
                "POST",
                url,
                payload={
                    "candidates": [
                        {
                            "content": {
                                "parts": [
                                    {"text": "A blurry support screenshot with a visible payment error."}
                                ]
                            }
                        }
                    ]
                },
            )

    monkeypatch.setattr(image_analysis_service.settings, "GEMINI_API_KEY", "test-gemini-key", raising=False)
    monkeypatch.setattr(image_analysis_service.httpx, "AsyncClient", FakeGeminiClient)

    result = asyncio.run(
        image_analysis_service.analyze_chat_image(
            b"fake-image-bytes",
            mime_type="image/png",
        )
    )

    assert result == {
        "summary": "A blurry support screenshot with a visible payment error.",
        "visible_text": "",
        "issue_signals": [],
        "suggested_focus": "",
    }
