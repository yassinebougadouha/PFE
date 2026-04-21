import io
import asyncio
from types import SimpleNamespace

import pytest
from fastapi import HTTPException
from starlette.datastructures import UploadFile

from app.visual_ai.routes import screenshare_assist_video


def test_screenshare_assist_video_happy_path(monkeypatch):
    async def _fake_analyze(self, **_kwargs):
        return {
            "source_fps": 10.0,
            "target_fps": 1.0,
            "uploaded_frames": 10,
            "processed_frames": 2,
            "embedding_backend": "gemini",
            "embedding_dimension": 512,
            "avg_transition_score": 0.12,
            "max_transition_score": 0.30,
            "reference_similarity": None,
            "final_frame": {
                "provider": "google",
                "caption": "settings page",
                "ocr_text_preview": "settings",
                "element_count": 3,
                "labels": ["settings"],
            },
            "assistance_hints": ["ok"],
        }

    monkeypatch.setattr(
        "app.visual_ai.routes.extract_frames_from_video_bytes",
        lambda *_args, **_kwargs: ([(b"f1", "image/png"), (b"f2", "image/png")], 10.0),
    )
    monkeypatch.setattr("app.visual_ai.service.VisualAIService.analyze_screenshare_frames", _fake_analyze)

    up = UploadFile(filename="test.mp4", file=io.BytesIO(b"video-data"), headers={"content-type": "video/mp4"})
    user = SimpleNamespace(id="user-1")

    result = asyncio.run(
        screenshare_assist_video(
            db=None,
            user=user,
            file=up,
            consent=True,
            target_fps=1.0,
            provider=None,
            reference_key=None,
            use_gemini_embeddings=True,
        )
    )

    assert result["processed_frames"] == 2
    assert result["embedding_backend"] == "gemini"


def test_screenshare_assist_video_rejects_invalid_type():
    up = UploadFile(filename="test.txt", file=io.BytesIO(b"not-video"), headers={"content-type": "text/plain"})
    user = SimpleNamespace(id="user-1")

    with pytest.raises(HTTPException) as exc:
        asyncio.run(
            screenshare_assist_video(
                db=None,
                user=user,
                file=up,
                consent=True,
                target_fps=1.0,
                provider=None,
                reference_key=None,
                use_gemini_embeddings=None,
            )
        )

    assert exc.value.status_code == 400


def test_screenshare_assist_video_rejects_too_large_file(monkeypatch):
    monkeypatch.setattr(
        "app.core.config.get_settings",
        lambda: SimpleNamespace(
            VISUAL_SCREENSHARE_TARGET_FPS=1.0,
            VISUAL_SCREENSHARE_MAX_FRAMES=120,
            VISUAL_SCREENSHARE_MAX_VIDEO_MB=1,
            VISUAL_SCREENSHARE_MAX_VIDEO_DURATION_SECONDS=300,
        ),
    )

    big_payload = b"x" * (2 * 1024 * 1024)  # 2MB > 1MB max
    up = UploadFile(filename="test.mp4", file=io.BytesIO(big_payload), headers={"content-type": "video/mp4"})
    user = SimpleNamespace(id="user-1")

    with pytest.raises(HTTPException) as exc:
        asyncio.run(
            screenshare_assist_video(
                db=None,
                user=user,
                file=up,
                consent=True,
                target_fps=1.0,
                provider=None,
                reference_key=None,
                use_gemini_embeddings=None,
            )
        )

    assert exc.value.status_code == 400
    assert "too large" in exc.value.detail.lower()


def test_screenshare_assist_video_rejects_too_long_duration(monkeypatch):
    monkeypatch.setattr(
        "app.core.config.get_settings",
        lambda: SimpleNamespace(
            VISUAL_SCREENSHARE_TARGET_FPS=1.0,
            VISUAL_SCREENSHARE_MAX_FRAMES=120,
            VISUAL_SCREENSHARE_MAX_VIDEO_MB=50,
            VISUAL_SCREENSHARE_MAX_VIDEO_DURATION_SECONDS=10,
        ),
    )
    monkeypatch.setattr(
        "app.visual_ai.routes.extract_frames_from_video_bytes",
        lambda *_args, **_kwargs: (_ for _ in ()).throw(ValueError("Video duration 30.00s exceeds max allowed 10.00s")),
    )

    up = UploadFile(filename="test.mp4", file=io.BytesIO(b"video-data"), headers={"content-type": "video/mp4"})
    user = SimpleNamespace(id="user-1")

    with pytest.raises(HTTPException) as exc:
        asyncio.run(
            screenshare_assist_video(
                db=None,
                user=user,
                file=up,
                consent=True,
                target_fps=1.0,
                provider=None,
                reference_key=None,
                use_gemini_embeddings=None,
            )
        )

    assert exc.value.status_code == 400
    assert "duration" in exc.value.detail.lower()
