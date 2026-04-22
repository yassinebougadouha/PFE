import asyncio
from types import SimpleNamespace

from app.visual_ai.providers import get_visual_provider
from app.visual_ai.schemas import FullAnalysisResult, OCRResult, UIAnalysisResult
from app.visual_ai.service import VisualAIService


class _FakeProvider:
    provider_name = "fake-provider"

    async def extract_ocr(self, image: bytes) -> OCRResult:
        return OCRResult(text="Billing settings", confidence=0.9, word_count=2)

    async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
        return UIAnalysisResult(caption="Billing settings page", labels=["billing", "settings"])

    async def encode_embedding(self, image: bytes) -> list[float]:
        base = float(len(image) % 10) / 10.0
        return [base, 0.1, 0.2, 0.3]

    async def full_analysis(self, image: bytes) -> FullAnalysisResult:
        return FullAnalysisResult(
            ocr=OCRResult(text="settings page", confidence=0.9, word_count=2),
            ui_analysis=UIAnalysisResult(caption="Settings screen", labels=["settings", "form"]),
            embedding=[0.1, 0.2, 0.3, 0.4],
            provider="fake-provider",
            confidence=0.9,
        )


def test_sample_frames_low_fps():
    frames = [(f"frame-{i}".encode("utf-8"), "image/png") for i in range(10)]
    sampled = VisualAIService.sample_frames_low_fps(
        frames,
        source_fps=10.0,
        target_fps=2.0,
        max_frames=10,
    )
    assert len(sampled) == 2
    assert sampled[0][0] == b"frame-0"
    assert sampled[1][0] == b"frame-5"


def test_get_visual_provider_accepts_gemini_alias():
    get_visual_provider.cache_clear()
    provider = get_visual_provider("gemini")
    assert provider.provider_name == "gemini"


def test_analyze_screenshare_frames_provider_embeddings(monkeypatch):
    provider = _FakeProvider()
    monkeypatch.setattr("app.visual_ai.service.get_visual_provider", lambda _name=None: provider)

    svc = VisualAIService(db=None)
    frames = [(b"img-a", "image/png"), (b"img-b", "image/png"), (b"img-c", "image/png")]

    result = asyncio.run(
        svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=6.0,
            target_fps=2.0,
            provider_name="local-basic",
            use_gemini_embeddings=False,
        )
    )

    assert result["embedding_backend"] == "fake-provider"
    assert result["processed_frames"] >= 1
    assert result["final_frame"]["provider"] == "fake-provider"
    assert "assistance_hints" in result
    assert result["assistance_hints"][0].startswith("Visible text includes:")


def test_analyze_screenshare_frames_gemini_embeddings(monkeypatch):
    provider = _FakeProvider()
    monkeypatch.setattr("app.visual_ai.service.get_visual_provider", lambda _name=None: provider)
    monkeypatch.setattr(
        "app.visual_ai.service.embed_image_with_gemini",
        lambda _img, **_kwargs: [0.4, 0.3, 0.2, 0.1],
    )

    svc = VisualAIService(db=None)
    frames = [(b"img-a", "image/png"), (b"img-b", "image/png")]

    result = asyncio.run(
        svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=4.0,
            target_fps=1.0,
            provider_name="google",
            use_gemini_embeddings=True,
        )
    )

    assert result["embedding_backend"] == "gemini"
    assert result["embedding_dimension"] == 4


def test_analyze_screenshare_frames_falls_back_when_gemini_embeddings_fail(monkeypatch):
    provider = _FakeProvider()
    monkeypatch.setattr("app.visual_ai.service.get_visual_provider", lambda _name=None: provider)

    def _raise_quota(*_args, **_kwargs):
        raise RuntimeError("quota exceeded")

    monkeypatch.setattr("app.visual_ai.service.embed_image_with_gemini", _raise_quota)

    svc = VisualAIService(db=None)
    frames = [(b"img-a", "image/png"), (b"img-b", "image/png")]

    result = asyncio.run(
        svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=4.0,
            target_fps=1.0,
            provider_name="google",
            use_gemini_embeddings=True,
        )
    )

    assert result["embedding_backend"] == "fake-provider (gemini-fallback)"
    assert result["embedding_dimension"] == 4
    assert result["assistance_hints"][0].startswith("Gemini embeddings were temporarily unavailable")


def test_analyze_screenshare_frames_handles_total_embedding_unavailability(monkeypatch):
    class _NoEmbeddingProvider(_FakeProvider):
        async def encode_embedding(self, image: bytes) -> list[float]:
            raise RuntimeError("embedding backend down")

    provider = _NoEmbeddingProvider()
    monkeypatch.setattr("app.visual_ai.service.get_visual_provider", lambda _name=None: provider)

    def _raise_unavailable(*_args, **_kwargs):
        raise RuntimeError("gemini unavailable")

    monkeypatch.setattr("app.visual_ai.service.embed_image_with_gemini", _raise_unavailable)

    svc = VisualAIService(db=None)
    frames = [(b"img-a", "image/png")]

    result = asyncio.run(
        svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=2.0,
            target_fps=1.0,
            provider_name="google",
            use_gemini_embeddings=True,
        )
    )

    assert result["embedding_backend"] == "unavailable"
    assert result["embedding_dimension"] == 0
    assert result["assistance_hints"][0].startswith("Embedding analysis is temporarily unavailable")


def test_analyze_screenshare_frames_times_out_slow_provider_steps(monkeypatch):
    class _SlowProvider(_FakeProvider):
        async def extract_ocr(self, image: bytes) -> OCRResult:
            await asyncio.sleep(0.7)
            return await super().extract_ocr(image)

        async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
            await asyncio.sleep(0.7)
            return await super().analyze_ui(image)

    provider = _SlowProvider()
    monkeypatch.setattr("app.visual_ai.service.get_visual_provider", lambda _name=None: provider)
    monkeypatch.setattr(
        "app.core.config.get_settings",
        lambda: SimpleNamespace(
            VISUAL_SCREENSHARE_MAX_FRAMES=120,
            VISUAL_SCREENSHARE_USE_GEMINI_EMBEDDINGS=False,
            VISUAL_SCREENSHARE_PROVIDER_STEP_TIMEOUT_SECONDS=0.01,
            VISUAL_SCREENSHARE_PROVIDER_EMBEDDING_TIMEOUT_SECONDS=0.01,
        ),
    )

    svc = VisualAIService(db=None)
    frames = [(b"img-a", "image/png")]

    result = asyncio.run(
        svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=2.0,
            target_fps=1.0,
            provider_name="google",
            use_gemini_embeddings=False,
        )
    )

    assert result["processed_frames"] == 1
    assert result["final_frame"]["caption"] == ""
    assert result["final_frame"]["ocr_text_preview"] == ""
    assert result["assistance_hints"]


def test_analyze_screenshare_frames_prioritizes_blank_frame_guidance(monkeypatch):
    class _BlankFrameProvider(_FakeProvider):
        async def extract_ocr(self, image: bytes) -> OCRResult:
            return OCRResult(text="", confidence=0.0, word_count=0)

        async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
            return UIAnalysisResult(caption="A completely black image, no UI elements are visible.", labels=[])

    provider = _BlankFrameProvider()
    monkeypatch.setattr("app.visual_ai.service.get_visual_provider", lambda _name=None: provider)
    monkeypatch.setattr(
        "app.visual_ai.service.embed_image_with_gemini",
        lambda _img, **_kwargs: [0.4, 0.3, 0.2, 0.1],
    )

    svc = VisualAIService(db=None)
    frames = [(b"img-a", "image/png")]

    result = asyncio.run(
        svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=2.0,
            target_fps=2.0,
            provider_name="gemini",
            use_gemini_embeddings=True,
        )
    )

    assert result["assistance_hints"][0].startswith("The shared frame looks blank")


def test_analyze_screenshare_frames_does_not_flag_normal_dark_scene_as_blank(monkeypatch):
    class _DarkReadableProvider(_FakeProvider):
        async def extract_ocr(self, image: bytes) -> OCRResult:
            return OCRResult(text="Bloodhounds Episode 8", confidence=0.8, word_count=3)

        async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
            return UIAnalysisResult(
                caption="A dark video scene is visible in a streaming player interface.",
                labels=["video player", "streaming"],
            )

    provider = _DarkReadableProvider()
    monkeypatch.setattr("app.visual_ai.service.get_visual_provider", lambda _name=None: provider)
    monkeypatch.setattr(
        "app.visual_ai.service.embed_image_with_gemini",
        lambda _img, **_kwargs: [0.4, 0.3, 0.2, 0.1],
    )

    svc = VisualAIService(db=None)
    frames = [(b"img-a", "image/png")]

    result = asyncio.run(
        svc.analyze_screenshare_frames(
            frames=frames,
            source_fps=2.0,
            target_fps=2.0,
            provider_name="gemini",
            use_gemini_embeddings=True,
        )
    )

    assert not any(hint.startswith("The shared frame looks blank") for hint in result["assistance_hints"])
    assert result["assistance_hints"][0].startswith("Visible text includes:")
