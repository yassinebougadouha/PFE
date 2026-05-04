"""
Shared State Registry for Voice Agents.

Used to track session-specific variables like escalation status 
across the lifetime of a LiveKit room without introducing circular imports.
"""

from typing import Dict, Any

# mapping: room_name -> session data dict
session_states: Dict[str, Dict[str, Any]] = {}

def get_session_state(room_name: str) -> Dict[str, Any]:
    """Retrieve or initialize the state for a given room."""
    if room_name not in session_states:
        session_states[room_name] = {
            "escalated": False,
            "escalation_reason": "No reason provided",
            "escalation_ticket_id": None,
            "escalation_dispatched_at": None,
        }
    return session_states[room_name]

def pop_session_state(room_name: str) -> Dict[str, Any]:
    """Remove and return the state for a given room, used on cleanup."""
    return session_states.pop(room_name, {
        "escalated": False,
        "escalation_reason": "No reason provided",
    })
