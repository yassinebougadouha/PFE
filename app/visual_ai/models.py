"""
SQLAlchemy models for the Visual AI module.

Screenshot        — uploaded screenshot with consent metadata
VisualAnalysis    — analysis result (OCR, caption, elements, embedding)
UIState           — timeline entry tracking UI state per conversation
ReferenceScreen   — expected/reference screenshots for gap detection
"""

import uuid

from sqlalchemy import (
    String, Text, Float, Integer, Boolean, ForeignKey, Index, JSON,
)
from sqlalchemy.dialects.postgresql import UUID, ENUM
from sqlalchemy.orm import Mapped, mapped_column, relationship
from pgvector.sqlalchemy import Vector

from app.db.base import Base, TimestampMixin, UUIDPrimaryKeyMixin, SoftDeleteMixin
from app.visual_ai.enums import VisualAIProvider, GapSeverity

# ── PostgreSQL-native ENUMs (migration creates them) ──────
_visual_provider = ENUM(VisualAIProvider, name="visual_ai_provider", create_type=False)
_gap_severity = ENUM(GapSeverity, name="gap_severity", create_type=False)

# Visual embedding dimension (CLIP ViT-B-32 = 512)
VISUAL_EMBEDDING_DIM = 512


class Screenshot(Base, UUIDPrimaryKeyMixin, TimestampMixin, SoftDeleteMixin):
    """An uploaded screenshot with opt-in consent."""

    __tablename__ = "screenshots"

    conversation_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("conversations.id", ondelete="SET NULL"),
        nullable=True, index=True,
    )
    user_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("users.id", ondelete="SET NULL"),
        nullable=True, index=True,
    )
    filename: Mapped[str] = mapped_column(String(500), nullable=False)
    file_path: Mapped[str] = mapped_column(String(1000), nullable=False)
    file_size: Mapped[int] = mapped_column(Integer, nullable=False)
    mime_type: Mapped[str] = mapped_column(String(50), nullable=False, default="image/png")
    consent: Mapped[bool] = mapped_column(Boolean, nullable=False, default=False)
    metadata_: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, default=dict,
        comment="Extra metadata (browser, resolution, page URL, etc.)",
    )

    # ── Relationships ─────────────────────────────────────
    analyses: Mapped[list["VisualAnalysis"]] = relationship(
        "VisualAnalysis", back_populates="screenshot",
        cascade="all, delete-orphan",
    )
    ui_states: Mapped[list["UIState"]] = relationship(
        "UIState", back_populates="screenshot",
        cascade="all, delete-orphan",
    )

    def __repr__(self) -> str:
        return f"<Screenshot {self.id} file={self.filename!r}>"


class VisualAnalysis(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    """Result of analyzing a screenshot with a visual provider."""

    __tablename__ = "visual_analyses"

    screenshot_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("screenshots.id", ondelete="CASCADE"),
        nullable=False, index=True,
    )
    provider: Mapped[str] = mapped_column(String(20), nullable=False)
    ocr_text: Mapped[str | None] = mapped_column(Text, nullable=True)
    caption: Mapped[str | None] = mapped_column(Text, nullable=True)
    elements: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, default=list,
        comment="Detected UI elements: [{type, label, bbox, confidence}]",
    )
    labels: Mapped[list | None] = mapped_column(
        JSON, nullable=True, default=list,
        comment="Image-level labels (Google Vision LABEL_DETECTION, etc.)",
    )
    regions: Mapped[list | None] = mapped_column(
        JSON, nullable=True, default=list,
        comment="Region descriptions [{bbox, description}]",
    )

    # ── pgvector embedding (CLIP 512-dim) ─────────────────
    embedding: Mapped[list | None] = mapped_column(
        Vector(VISUAL_EMBEDDING_DIM), nullable=True,
    )

    raw_result: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, comment="Full raw provider response",
    )
    confidence: Mapped[float | None] = mapped_column(Float, nullable=True)
    processing_ms: Mapped[int | None] = mapped_column(Integer, nullable=True)

    # ── Relationship ──────────────────────────────────────
    screenshot: Mapped["Screenshot"] = relationship(
        "Screenshot", back_populates="analyses",
    )
    ui_state: Mapped["UIState | None"] = relationship(
        "UIState", back_populates="analysis", uselist=False,
    )

    __table_args__ = (
        Index("ix_visual_analyses_provider", "provider"),
    )

    def __repr__(self) -> str:
        return f"<VisualAnalysis {self.id} provider={self.provider}>"


class UIState(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    """A point-in-time UI state in a conversation's visual timeline."""

    __tablename__ = "ui_states"

    conversation_id: Mapped[uuid.UUID] = mapped_column(
        UUID(as_uuid=True), ForeignKey("conversations.id", ondelete="CASCADE"),
        nullable=False, index=True,
    )
    analysis_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("visual_analyses.id", ondelete="SET NULL"),
        nullable=True,
    )
    screenshot_id: Mapped[uuid.UUID | None] = mapped_column(
        UUID(as_uuid=True), ForeignKey("screenshots.id", ondelete="SET NULL"),
        nullable=True,
    )
    state_label: Mapped[str | None] = mapped_column(
        String(100), nullable=True,
        comment="Human-readable label: 'login_page', 'dashboard', 'error_page'",
    )
    state_data: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, default=dict,
        comment="Structured state data (OCR summary, elements, etc.)",
    )

    # ── pgvector embedding (CLIP 512-dim) ─────────────────
    embedding: Mapped[list | None] = mapped_column(
        Vector(VISUAL_EMBEDDING_DIM), nullable=True,
    )

    sequence_num: Mapped[int] = mapped_column(Integer, nullable=False, default=0)
    gap_detected: Mapped[bool] = mapped_column(Boolean, nullable=False, default=False)
    gap_details: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, default=dict,
        comment="Gap detection result: {score, severity, diffs, hints}",
    )
    gap_severity: Mapped[GapSeverity | None] = mapped_column(
        _gap_severity, nullable=True,
    )

    # ── Relationships ─────────────────────────────────────
    analysis: Mapped["VisualAnalysis | None"] = relationship(
        "VisualAnalysis", back_populates="ui_state",
    )
    screenshot: Mapped["Screenshot | None"] = relationship(
        "Screenshot", back_populates="ui_states",
    )

    __table_args__ = (
        Index("ix_ui_states_conversation_seq", "conversation_id", "sequence_num"),
    )

    def __repr__(self) -> str:
        return f"<UIState {self.id} conv={self.conversation_id} seq={self.sequence_num}>"


class ReferenceScreen(Base, UUIDPrimaryKeyMixin, TimestampMixin):
    """A reference/expected screenshot for gap detection comparison."""

    __tablename__ = "reference_screens"

    name: Mapped[str] = mapped_column(String(200), nullable=False)
    description: Mapped[str | None] = mapped_column(Text, nullable=True)
    screen_key: Mapped[str] = mapped_column(
        String(100), nullable=False, unique=True,
        comment="Unique key: 'login_page', 'dashboard', 'settings', etc.",
    )
    file_path: Mapped[str] = mapped_column(String(1000), nullable=False)

    # ── pgvector embedding (CLIP 512-dim) ─────────────────
    embedding: Mapped[list | None] = mapped_column(
        Vector(VISUAL_EMBEDDING_DIM), nullable=True,
    )

    expected_elements: Mapped[dict | None] = mapped_column(
        JSON, nullable=True, default=list,
        comment="Expected UI elements: [{type, label}]",
    )
    expected_ocr_text: Mapped[str | None] = mapped_column(Text, nullable=True)

    def __repr__(self) -> str:
        return f"<ReferenceScreen {self.id} key={self.screen_key!r}>"
