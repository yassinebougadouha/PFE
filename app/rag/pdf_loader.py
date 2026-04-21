"""
PDF text extraction for the RAG knowledge base.

Extracts text, metadata, and page-level content from PDF files
using PyMuPDF (fitz). Handles multi-page documents with page tracking.
"""

from __future__ import annotations

import logging
import os
from dataclasses import dataclass, field
from pathlib import Path
from typing import Optional

try:
    import fitz  # PyMuPDF
except ModuleNotFoundError:
    fitz = None

logger = logging.getLogger(__name__)


# ── Data structures ───────────────────────────────────────

@dataclass
class PDFPage:
    """Extracted content from a single PDF page."""
    page_number: int          # 1-based
    text: str
    char_count: int
    word_count: int


@dataclass
class PDFDocument:
    """Full extracted PDF with metadata."""
    filename: str
    filepath: str
    title: Optional[str]
    author: Optional[str]
    subject: Optional[str]
    page_count: int
    total_chars: int
    total_words: int
    pages: list[PDFPage]
    metadata_raw: dict = field(default_factory=dict)

    @property
    def full_text(self) -> str:
        """Concatenate all pages into a single string."""
        return "\n\n".join(
            f"[Page {p.page_number}]\n{p.text}"
            for p in self.pages
            if p.text.strip()
        )

    @property
    def text_only(self) -> str:
        """Concatenate all pages without page markers."""
        return "\n\n".join(
            p.text for p in self.pages if p.text.strip()
        )


# ── Extraction ────────────────────────────────────────────

def extract_pdf(filepath: str | Path) -> PDFDocument:
    """
    Extract text and metadata from a PDF file.

    Args:
        filepath: Path to the PDF file.

    Returns:
        PDFDocument with pages, metadata, and full text.

    Raises:
        FileNotFoundError: If the file does not exist.
        ValueError: If the file is not a valid PDF.
    """
    filepath = Path(filepath)
    if fitz is None:
        raise RuntimeError(
            "PyMuPDF is not installed in this runtime. Install 'PyMuPDF' to enable PDF ingestion."
        )

    if not filepath.exists():
        raise FileNotFoundError(f"PDF not found: {filepath}")
    if filepath.suffix.lower() != ".pdf":
        raise ValueError(f"Not a PDF file: {filepath}")

    logger.info("Extracting PDF: %s", filepath.name)

    try:
        doc = fitz.open(str(filepath))
    except Exception as e:
        raise ValueError(f"Failed to open PDF '{filepath.name}': {e}") from e

    # Extract metadata
    meta = doc.metadata or {}
    title = meta.get("title", "").strip() or None
    author = meta.get("author", "").strip() or None
    subject = meta.get("subject", "").strip() or None

    # Extract pages
    pages: list[PDFPage] = []
    total_chars = 0
    total_words = 0

    for page_num in range(doc.page_count):
        page = doc[page_num]
        text = page.get_text("text").strip()

        char_count = len(text)
        word_count = len(text.split()) if text else 0

        total_chars += char_count
        total_words += word_count

        pages.append(PDFPage(
            page_number=page_num + 1,
            text=text,
            char_count=char_count,
            word_count=word_count,
        ))

    doc.close()

    result = PDFDocument(
        filename=filepath.name,
        filepath=str(filepath),
        title=title,
        author=author,
        subject=subject,
        page_count=len(pages),
        total_chars=total_chars,
        total_words=total_words,
        pages=pages,
        metadata_raw=meta,
    )

    logger.info(
        "Extracted PDF '%s': %d pages, %d words, %d chars",
        filepath.name, result.page_count, total_words, total_chars,
    )
    return result


def list_pdf_files(directory: str | Path) -> list[Path]:
    """
    List all PDF files in a directory (non-recursive).

    Args:
        directory: Path to scan for PDFs.

    Returns:
        Sorted list of PDF file paths.
    """
    directory = Path(directory)
    if not directory.exists():
        logger.warning("Documents directory does not exist: %s", directory)
        return []

    pdfs = sorted(directory.glob("*.pdf"))
    logger.info("Found %d PDF files in %s", len(pdfs), directory)
    return pdfs


def list_pdf_files_recursive(directory: str | Path) -> list[Path]:
    """
    List all PDF files in a directory and subdirectories.

    Args:
        directory: Root path to scan for PDFs.

    Returns:
        Sorted list of PDF file paths.
    """
    directory = Path(directory)
    if not directory.exists():
        return []

    pdfs = sorted(directory.rglob("*.pdf"))
    logger.info("Found %d PDF files (recursive) in %s", len(pdfs), directory)
    return pdfs
