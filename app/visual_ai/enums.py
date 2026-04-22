"""
Visual AI module enumerations.
"""

import enum


class VisualAIProvider(str, enum.Enum):
    """Available visual analysis providers."""
    GEMINI = "gemini"


class GapSeverity(str, enum.Enum):
    """Severity level of a detected UI gap."""
    NO_GAP = "NO_GAP"
    MINOR = "MINOR"
    SIGNIFICANT = "SIGNIFICANT"
    CRITICAL = "CRITICAL"


class UIElementType(str, enum.Enum):
    """Types of UI elements detected in screenshots."""
    BUTTON = "BUTTON"
    INPUT_FIELD = "INPUT_FIELD"
    ERROR_MESSAGE = "ERROR_MESSAGE"
    SUCCESS_MESSAGE = "SUCCESS_MESSAGE"
    LOADING_STATE = "LOADING_STATE"
    NAVIGATION = "NAVIGATION"
    FORM = "FORM"
    MODAL = "MODAL"
    TABLE = "TABLE"
    IMAGE = "IMAGE"
    LINK = "LINK"
    TEXT_BLOCK = "TEXT_BLOCK"
    HEADER = "HEADER"
    UNKNOWN = "UNKNOWN"
