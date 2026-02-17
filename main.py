"""
Root entry point — re-exports the app from the app package.
Run with: uvicorn main:app --reload
Or:       uvicorn app.main:app --reload
"""

from app.main import app  # noqa: F401
