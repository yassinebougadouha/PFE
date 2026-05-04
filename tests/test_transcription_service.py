import os
import asyncio
import httpx

os.environ["DEBUG"] = "true"

from app.services import transcription_service


def _json_response(method: str, url: str, *, payload=None, headers=None) -> httpx.Response:
    return httpx.Response(
        200,
        json=payload,
        headers=headers,
        request=httpx.Request(method, url),
    )


def test_save_upload_and_transcribe_uses_inline_gemini_for_small_audio(monkeypatch):
    class InlineGeminiClient:
        calls: list[tuple[str, str]] = []

        def __init__(self, *args, **kwargs):
            pass

        async def __aenter__(self):
            return self

        async def __aexit__(self, exc_type, exc, tb):
            return False

        async def post(self, url, *, headers=None, json=None, content=None):
            self.calls.append(("POST", url))
            assert headers["x-goog-api-key"] == "test-gemini-key"
            assert json["contents"][0]["parts"][1]["inline_data"]["mime_type"] == "audio/webm"
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
                                            '{"text":"Hello from Gemini",'
                                            '"language":"en","segments":[]}'
                                        )
                                    }
                                ]
                            }
                        }
                    ]
                },
            )

    monkeypatch.setattr(transcription_service.settings, "GEMINI_API_KEY", "test-gemini-key", raising=False)
    monkeypatch.setattr(transcription_service.settings, "GEMINI_TRANSCRIPTION_MODEL", "gemini-test", raising=False)
    monkeypatch.setattr(
        transcription_service.settings,
        "GEMINI_TRANSCRIPTION_INLINE_MAX_BYTES",
        1024,
        raising=False,
    )
    monkeypatch.setattr(transcription_service.httpx, "AsyncClient", InlineGeminiClient)

    result = asyncio.run(
        transcription_service.save_upload_and_transcribe(
            b"fake-audio",
            "voice-message.webm",
            content_type="audio/webm;codecs=opus",
        )
    )

    assert result == {
        "text": "Hello from Gemini",
        "language": "en",
        "segments": [],
    }
    assert any("generateContent" in url for _, url in InlineGeminiClient.calls)
    assert not any("/upload/v1beta/files" in url for _, url in InlineGeminiClient.calls)


def test_save_upload_and_transcribe_uses_files_api_for_large_audio(monkeypatch):
    class UploadedGeminiClient:
        calls: list[tuple[str, str]] = []

        def __init__(self, *args, **kwargs):
            pass

        async def __aenter__(self):
            return self

        async def __aexit__(self, exc_type, exc, tb):
            return False

        async def post(self, url, *, headers=None, json=None, content=None):
            self.calls.append(("POST", url))

            if url == transcription_service.GEMINI_FILES_URL:
                assert headers["x-goog-api-key"] == "test-gemini-key"
                return _json_response(
                    "POST",
                    url,
                    headers={"x-goog-upload-url": "https://upload.example/audio-session"},
                    payload={},
                )

            if url == "https://upload.example/audio-session":
                assert content == b"x" * 32
                return _json_response(
                    "POST",
                    url,
                    payload={
                        "file": {
                            "uri": "https://files.example/audio",
                            "name": "files/audio-123",
                        }
                    },
                )

            assert "generateContent" in url
            assert json["contents"][0]["parts"][1]["file_data"]["file_uri"] == "https://files.example/audio"
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
                                            "```json\n"
                                            '{"text":"Longer audio transcript",'
                                            '"language":"en","segments":['
                                            '{"start":0,"end":1.4,"text":"Longer audio transcript"}]}'
                                            "\n```"
                                        )
                                    }
                                ]
                            }
                        }
                    ]
                },
            )

        async def delete(self, url, *, headers=None):
            self.calls.append(("DELETE", url))
            return _json_response("DELETE", url, payload={})

    monkeypatch.setattr(transcription_service.settings, "GEMINI_API_KEY", "test-gemini-key", raising=False)
    monkeypatch.setattr(transcription_service.settings, "GEMINI_TRANSCRIPTION_MODEL", "gemini-test", raising=False)
    monkeypatch.setattr(
        transcription_service.settings,
        "GEMINI_TRANSCRIPTION_INLINE_MAX_BYTES",
        8,
        raising=False,
    )
    monkeypatch.setattr(transcription_service.httpx, "AsyncClient", UploadedGeminiClient)

    result = asyncio.run(
        transcription_service.save_upload_and_transcribe(
            b"x" * 32,
            "voice-message.mp3",
            content_type="audio/mpeg",
        )
    )

    assert result == {
        "text": "Longer audio transcript",
        "language": "en",
        "segments": [
            {
                "start": 0.0,
                "end": 1.4,
                "text": "Longer audio transcript",
            }
        ],
    }
    assert ("POST", transcription_service.GEMINI_FILES_URL) in UploadedGeminiClient.calls
    assert ("DELETE", f"{transcription_service.GEMINI_API_BASE_URL}/v1beta/files/audio-123") in UploadedGeminiClient.calls


def test_save_upload_and_transcribe_accepts_custom_prompt(monkeypatch):
    class PromptGeminiClient:
        prompt = ""

        def __init__(self, *args, **kwargs):
            pass

        async def __aenter__(self):
            return self

        async def __aexit__(self, exc_type, exc, tb):
            return False

        async def post(self, url, *, headers=None, json=None, content=None):
            PromptGeminiClient.prompt = json["contents"][0]["parts"][0]["text"]
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
                                            '{"text":"client: hello\\nai: hi",'
                                            '"language":"en","segments":[]}'
                                        )
                                    }
                                ]
                            }
                        }
                    ]
                },
            )

    monkeypatch.setattr(transcription_service.settings, "GEMINI_API_KEY", "test-gemini-key", raising=False)
    monkeypatch.setattr(transcription_service.settings, "GEMINI_TRANSCRIPTION_MODEL", "gemini-test", raising=False)
    monkeypatch.setattr(
        transcription_service.settings,
        "GEMINI_TRANSCRIPTION_INLINE_MAX_BYTES",
        1024,
        raising=False,
    )
    monkeypatch.setattr(transcription_service.httpx, "AsyncClient", PromptGeminiClient)

    result = asyncio.run(
        transcription_service.save_upload_and_transcribe(
            b"fake-audio",
            "support-call.wav",
            content_type="audio/wav",
            prompt="custom support-call prompt",
        )
    )

    assert PromptGeminiClient.prompt == "custom support-call prompt"
    assert result["text"] == "client: hello\nai: hi"
