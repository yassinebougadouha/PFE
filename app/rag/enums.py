"""
RAG module enumerations.
"""

import enum


class ArticleStatus(str, enum.Enum):
    """Lifecycle status of a knowledge article."""
    DRAFT = "DRAFT"
    PUBLISHED = "PUBLISHED"
    ARCHIVED = "ARCHIVED"


class ArticleCategory(str, enum.Enum):
    """Semantic category for knowledge articles."""
    TECHNICAL = "TECHNICAL"
    BILLING = "BILLING"
    ACCOUNT = "ACCOUNT"
    GENERAL = "GENERAL"
    SECURITY = "SECURITY"
    TROUBLESHOOTING = "TROUBLESHOOTING"
    FAQ = "FAQ"
    POLICY = "POLICY"
    ONBOARDING = "ONBOARDING"
    FEATURE_GUIDE = "FEATURE_GUIDE"


class ChunkStatus(str, enum.Enum):
    """Indexing status of a chunk."""
    PENDING = "PENDING"
    INDEXED = "INDEXED"
    FAILED = "FAILED"
