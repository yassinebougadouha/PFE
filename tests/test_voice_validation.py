import os
from io import BytesIO

import pytest
from fastapi import HTTPException
from starlette.datastructures import UploadFile

os.environ["DEBUG"] = "true"

from app.api.routes.voice import _validate_audio_file


def test_validate_audio_file_accepts_parameterized_webm_mime():
    upload = UploadFile(
        file=BytesIO(b"audio"),
        filename="voice-message.webm",
        headers={"content-type": "audio/webm;codecs=opus"},
    )

    _validate_audio_file(upload)


def test_validate_audio_file_accepts_parameterized_mp4_mime():
    upload = UploadFile(
        file=BytesIO(b"audio"),
        filename="voice-message.m4a",
        headers={"content-type": "audio/mp4; codecs=mp4a.40.2"},
    )

    _validate_audio_file(upload)


def test_validate_audio_file_rejects_unsupported_mime():
    upload = UploadFile(
        file=BytesIO(b"audio"),
        filename="voice-message.bin",
        headers={"content-type": "application/octet-stream"},
    )

    with pytest.raises(HTTPException) as exc_info:
        _validate_audio_file(upload)

    assert exc_info.value.status_code == 415
