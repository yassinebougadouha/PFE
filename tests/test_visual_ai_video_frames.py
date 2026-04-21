import pytest

from app.visual_ai.video_frames import _parse_duration, _parse_fps


def test_parse_fps_fraction():
    fps = _parse_fps("30000/1001")
    assert 29.9 < fps < 30.1


def test_parse_fps_numeric():
    fps = _parse_fps("24")
    assert fps == 24.0


def test_parse_fps_invalid_zero_denominator():
    with pytest.raises(ValueError):
        _parse_fps("24/0")


def test_parse_duration_numeric():
    duration = _parse_duration("12.5")
    assert duration == 12.5


def test_parse_duration_invalid():
    with pytest.raises(ValueError):
        _parse_duration("0")
