"""
Text chunking strategies for the RAG knowledge base.

Splits long documents into overlapping chunks suitable for embedding and retrieval.
Uses a recursive approach: paragraphs → sentences → words.
"""

from __future__ import annotations

import re
import logging
from dataclasses import dataclass, field

logger = logging.getLogger(__name__)

# ── Default settings ──────────────────────────────────────
DEFAULT_CHUNK_SIZE = 512          # target tokens per chunk
DEFAULT_CHUNK_OVERLAP = 64        # overlap tokens between consecutive chunks
APPROX_CHARS_PER_TOKEN = 4       # rough approximation for English text


@dataclass
class Chunk:
    """A text chunk with metadata."""
    index: int
    content: str
    token_count: int

    @property
    def char_count(self) -> int:
        return len(self.content)


@dataclass
class ChunkerConfig:
    """Configuration for the text chunker."""
    chunk_size: int = DEFAULT_CHUNK_SIZE
    chunk_overlap: int = DEFAULT_CHUNK_OVERLAP
    separators: list[str] = field(default_factory=lambda: [
        "\n\n",      # double newline (paragraphs)
        "\n",        # single newline
        ". ",        # sentence boundary
        "? ",        # question mark
        "! ",        # exclamation
        "; ",        # semicolon
        ", ",        # comma
        " ",         # word boundary
    ])
    strip_whitespace: bool = True


def estimate_tokens(text: str) -> int:
    """Rough token estimate: split on whitespace + punctuation."""
    return max(1, len(re.findall(r'\S+', text)))


class RecursiveTextChunker:
    """
    Splits text into chunks recursively.

    Strategy:
    1. Try splitting by the first separator (paragraphs).
    2. If any resulting piece is too large, recurse with the next separator.
    3. Merge small pieces until they reach the target chunk size.
    4. Add overlap between consecutive chunks.
    """

    def __init__(self, config: ChunkerConfig | None = None):
        self.config = config or ChunkerConfig()

    def chunk(self, text: str) -> list[Chunk]:
        """Split text into overlapping chunks."""
        if not text or not text.strip():
            return []

        raw_chunks = self._split_recursive(text, 0)
        merged = self._merge_small_chunks(raw_chunks)
        overlapped = self._add_overlap(merged)

        # Build final Chunk objects
        result: list[Chunk] = []
        for i, content in enumerate(overlapped):
            if self.config.strip_whitespace:
                content = content.strip()
            if not content:
                continue
            result.append(Chunk(
                index=i,
                content=content,
                token_count=estimate_tokens(content),
            ))

        logger.info(
            "Chunked text into %d chunks (avg %d tokens)",
            len(result),
            sum(c.token_count for c in result) // max(len(result), 1),
        )
        return result

    # ── Internal methods ──────────────────────────────────

    def _split_recursive(self, text: str, sep_idx: int) -> list[str]:
        """Recursively split text using separator hierarchy."""
        max_tokens = self.config.chunk_size

        # Base case: if text is small enough, return as-is
        if estimate_tokens(text) <= max_tokens:
            return [text]

        # If we've exhausted all separators, force-split by characters
        if sep_idx >= len(self.config.separators):
            return self._force_split(text)

        separator = self.config.separators[sep_idx]
        pieces = text.split(separator)

        result: list[str] = []
        for piece in pieces:
            if estimate_tokens(piece) <= max_tokens:
                # Re-append separator (except for the last piece)
                result.append(piece)
            else:
                # Piece still too big → recurse with next separator
                sub_pieces = self._split_recursive(piece, sep_idx + 1)
                result.extend(sub_pieces)

        return result

    def _force_split(self, text: str) -> list[str]:
        """Force-split long text by character count as last resort."""
        chars_per_chunk = self.config.chunk_size * APPROX_CHARS_PER_TOKEN
        result = []
        for i in range(0, len(text), chars_per_chunk):
            result.append(text[i:i + chars_per_chunk])
        return result

    def _merge_small_chunks(self, pieces: list[str]) -> list[str]:
        """Merge consecutive small pieces until reaching target size."""
        max_tokens = self.config.chunk_size
        merged: list[str] = []
        current = ""

        for piece in pieces:
            candidate = (current + " " + piece).strip() if current else piece
            if estimate_tokens(candidate) <= max_tokens:
                current = candidate
            else:
                if current:
                    merged.append(current)
                current = piece

        if current:
            merged.append(current)

        return merged

    def _add_overlap(self, chunks: list[str]) -> list[str]:
        """Add overlapping text between consecutive chunks."""
        overlap_tokens = self.config.chunk_overlap
        if overlap_tokens <= 0 or len(chunks) <= 1:
            return chunks

        result: list[str] = [chunks[0]]

        for i in range(1, len(chunks)):
            prev_words = chunks[i - 1].split()
            overlap_words = prev_words[-overlap_tokens:] if len(prev_words) > overlap_tokens else prev_words
            overlap_text = " ".join(overlap_words)
            result.append(overlap_text + " " + chunks[i])

        return result


# ── Convenience function ──────────────────────────────────

def chunk_text(
    text: str,
    chunk_size: int = DEFAULT_CHUNK_SIZE,
    chunk_overlap: int = DEFAULT_CHUNK_OVERLAP,
) -> list[Chunk]:
    """Chunk text using default configuration."""
    config = ChunkerConfig(chunk_size=chunk_size, chunk_overlap=chunk_overlap)
    chunker = RecursiveTextChunker(config)
    return chunker.chunk(text)
