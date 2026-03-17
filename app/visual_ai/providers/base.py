"""
Abstract base class for Visual AI providers.

Every provider must implement four methods:
  - extract_ocr()
  - analyze_ui()
  - encode_embedding()
  - full_analysis()
"""

from abc import ABC, abstractmethod

from app.visual_ai.schemas import OCRResult, UIAnalysisResult, FullAnalysisResult


class BaseVisualProvider(ABC):
    """Interface contract for all visual AI providers."""

    @property
    @abstractmethod
    def provider_name(self) -> str:
        """Return the provider identifier string."""
        ...

    @abstractmethod
    async def extract_ocr(self, image: bytes) -> OCRResult:
        """Extract text from a screenshot image."""
        ...

    @abstractmethod
    async def analyze_ui(self, image: bytes) -> UIAnalysisResult:
        """Detect UI elements, generate a caption, identify regions."""
        ...

    @abstractmethod
    async def encode_embedding(self, image: bytes) -> list[float]:
        """Generate a 512-dim visual embedding for similarity search."""
        ...

    @abstractmethod
    async def full_analysis(self, image: bytes) -> FullAnalysisResult:
        """Run the complete pipeline: OCR + UI analysis + embedding."""
        ...
