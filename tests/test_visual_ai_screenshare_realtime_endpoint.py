import io
import asyncio
from types import SimpleNamespace

import pytest
from fastapi import HTTPException
from starlette.datastructures import UploadFile

from app.services.support_call_screen_context import support_call_screen_context_store
from app.visual_ai.routes import screenshare_assist, screenshare_assist_realtime_chunk


def test_screenshare_assist_realtime_chunk_happy_path(monkeypatch):
    async def _fake_analyze(self, **_kwargs):
        return {
            "source_fps": 8.0,
            "target_fps": 2.0,
            "uploaded_frames": 8,
            "processed_frames": 2,
            "embedding_backend": "gemini",
            "embedding_dimension": 512,
            "avg_transition_score": 0.11,
            "max_transition_score": 0.28,
            "reference_similarity": None,
            "final_frame": {
                "provider": "google",
                "caption": "user is in account settings",
                "ocr_text_preview": "settings",
                "element_count": 4,
                "labels": ["settings"],
            },
            "assistance_hints": ["ok"],
        }

    monkeypatch.setattr(
        "app.visual_ai.routes.extract_frames_from_video_bytes",
        lambda *_args, **_kwargs: ([(b"f1", "image/png"), (b"f2", "image/png")], 8.0),
    )
    monkeypatch.setattr("app.visual_ai.service.VisualAIService.analyze_screenshare_frames", _fake_analyze)

    up = UploadFile(filename="chunk.webm", file=io.BytesIO(b"video-data"), headers={"content-type": "video/webm"})
    user = SimpleNamespace(id="user-1")

    result = asyncio.run(
        screenshare_assist_realtime_chunk(
            db=None,
            user=user,
            consent=True,
            session_id="session-1",
            chunk_index=3,
            target_fps=2.0,
            provider="gemini",
            reference_key=None,
            use_gemini_embeddings=True,
            file=None,
            video=up,
        )
    )

    assert result["session_id"] == "session-1"
    assert result["chunk_index"] == 3
    assert result["processed_frames"] == 2


def test_screenshare_assist_realtime_chunk_publishes_support_call_context(monkeypatch):
    room_name = "support-call-user-1"
    support_call_screen_context_store.clear(room_name)

    async def _fake_analyze(self, **_kwargs):
        return {
            "source_fps": 8.0,
            "target_fps": 2.0,
            "uploaded_frames": 8,
            "processed_frames": 2,
            "embedding_backend": "gemini",
            "embedding_dimension": 512,
            "avg_transition_score": 0.11,
            "max_transition_score": 0.28,
            "reference_similarity": None,
            "final_frame": {
                "provider": "google",
                "caption": "user is on the billing page",
                "ocr_text_preview": "billing",
                "element_count": 4,
                "labels": ["billing"],
            },
            "assistance_hints": ["Guide them to the submit button."],
        }

    monkeypatch.setattr(
        "app.visual_ai.routes.extract_frames_from_video_bytes",
        lambda *_args, **_kwargs: ([(b"f1", "image/png"), (b"f2", "image/png")], 8.0),
    )
    monkeypatch.setattr("app.visual_ai.service.VisualAIService.analyze_screenshare_frames", _fake_analyze)

    up = UploadFile(filename="chunk.webm", file=io.BytesIO(b"video-data"), headers={"content-type": "video/webm"})
    user = SimpleNamespace(id="user-1")

    try:
        asyncio.run(
            screenshare_assist_realtime_chunk(
                db=None,
                user=user,
                consent=True,
                session_id="session-1",
                chunk_index=3,
                target_fps=2.0,
                provider="gemini",
                reference_key=None,
                use_gemini_embeddings=True,
                support_call_room_name=room_name,
                file=None,
                video=up,
            )
        )

        snapshot = support_call_screen_context_store.get_snapshot(room_name)
        assert snapshot["has_context"] is True
        assert "billing page" in snapshot["latest_analysis_text"]
        assert snapshot["latest_capture_mode"] == "chunk"
        assert snapshot["latest_frame_number"] == 3
        assert snapshot["latest_hints"] == ["Guide them to the submit button."]
    finally:
        support_call_screen_context_store.clear(room_name)


def test_screenshare_assist_frames_publishes_support_call_context(monkeypatch):
    room_name = "support-call-user-1"
    support_call_screen_context_store.clear(room_name)

    async def _fake_analyze(self, **_kwargs):
        return {
            "source_fps": 2.0,
            "target_fps": 2.0,
            "uploaded_frames": 1,
            "processed_frames": 1,
            "embedding_backend": "gemini",
            "embedding_dimension": 512,
            "avg_transition_score": 0.08,
            "max_transition_score": 0.08,
            "reference_similarity": None,
            "final_frame": {
                "provider": "google",
                "caption": "frame fallback summary",
                "ocr_text_preview": "billing",
                "element_count": 2,
                "labels": ["billing"],
            },
            "assistance_hints": ["Continue on the billing page."],
        }

    monkeypatch.setattr("app.visual_ai.service.VisualAIService.analyze_screenshare_frames", _fake_analyze)

    frame = UploadFile(filename="frame.png", file=io.BytesIO(b"frame-data"), headers={"content-type": "image/png"})
    user = SimpleNamespace(id="user-1")

    try:
        asyncio.run(
            screenshare_assist(
                db=None,
                user=user,
                frames=[frame],
                consent=True,
                source_fps=2.0,
                target_fps=2.0,
                provider="gemini",
                reference_key=None,
                use_gemini_embeddings=True,
                support_call_room_name=room_name,
                frame_number=1,
                chunk_index=1,
            )
        )

        snapshot = support_call_screen_context_store.get_snapshot(room_name)
        assert snapshot["has_context"] is True
        assert "frame fallback summary" in snapshot["latest_analysis_text"]
        assert snapshot["latest_capture_mode"] == "frame"
        assert snapshot["latest_frame_number"] == 1
        assert snapshot["latest_hints"] == ["Continue on the billing page."]
    finally:
        support_call_screen_context_store.clear(room_name)


def test_screenshare_assist_realtime_chunk_requires_upload():
    user = SimpleNamespace(id="user-1")

    with pytest.raises(HTTPException) as exc:
        asyncio.run(
            screenshare_assist_realtime_chunk(
                db=None,
                user=user,
                consent=True,
                session_id="session-1",
                chunk_index=1,
                target_fps=2.0,
                provider=None,
                reference_key=None,
                use_gemini_embeddings=None,
                file=None,
                video=None,
            )
        )

    assert exc.value.status_code == 400
    assert "uploaded" in exc.value.detail.lower()
