"""
Shared enumerations used across models.
"""

import enum


class UserRole(str, enum.Enum):
    CLIENT = "CLIENT"
    AGENT = "AGENT"
    ADMIN = "ADMIN"


class UserStatus(str, enum.Enum):
    ACTIVE = "ACTIVE"
    SUSPENDED = "SUSPENDED"


class ChannelType(str, enum.Enum):
    CHAT = "CHAT"
    EMAIL = "EMAIL"
    TICKET = "TICKET"
    CALL_TRANSCRIPT = "CALL_TRANSCRIPT"


class ConversationStatus(str, enum.Enum):
    OPEN = "OPEN"
    PENDING = "PENDING"
    CLOSED = "CLOSED"


class TicketStatus(str, enum.Enum):
    OPEN = "OPEN"
    IN_PROGRESS = "IN_PROGRESS"
    WAITING_ON_CUSTOMER = "WAITING_ON_CUSTOMER"
    ESCALATED = "ESCALATED"
    RESOLVED = "RESOLVED"
    CLOSED = "CLOSED"


class TicketPriority(str, enum.Enum):
    LOW = "LOW"
    MEDIUM = "MEDIUM"
    HIGH = "HIGH"
    CRITICAL = "CRITICAL"


class EmailStatus(str, enum.Enum):
    RECEIVED = "RECEIVED"
    PROCESSING = "PROCESSING"
    CONVERTED = "CONVERTED"
    REPLIED = "REPLIED"
    FAILED = "FAILED"


class AuditAction(str, enum.Enum):
    CREATE = "CREATE"
    UPDATE = "UPDATE"
    DELETE = "DELETE"
    LOGIN = "LOGIN"
    LOGOUT = "LOGOUT"
    ESCALATE = "ESCALATE"
    ASSIGN = "ASSIGN"
    STATUS_CHANGE = "STATUS_CHANGE"
    REPLY = "REPLY"
