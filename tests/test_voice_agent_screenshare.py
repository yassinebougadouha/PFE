import asyncio
from unittest.mock import patch, AsyncMock
from typing import Any
from types import SimpleNamespace

from voice_agents.generic_agent import GenericAgent

# A minimal subclass of GenericAgent for testing
class MockAgent(GenericAgent):
    def __init__(self):
        super().__init__(
            instructions="Test",
            llm=None,
            tts=None,
        )

    @property
    def session(self):
        mock_session = AsyncMock()
        mock_session.room.name = "test-room"
        return mock_session


class JobContextRoomAgent(GenericAgent):
    def __init__(self):
        super().__init__(
            instructions="Test",
            llm=None,
            tts=None,
        )

    @property
    def session(self):
        return SimpleNamespace()

def test_get_live_screen_analysis_no_context():
    agent = MockAgent()
    
    # Mock the rag_bridge response
    with patch("voice_agents.rag_bridge.get_support_call_screen_context", new_callable=AsyncMock) as mock_get_context:
        mock_get_context.return_value = {"has_context": False}
        
        result = asyncio.run(agent.get_live_screen_analysis())
        assert "I don't have any live screen analysis yet" in result

def test_get_live_screen_analysis_with_context():
    agent = MockAgent()
    
    mock_context: dict[str, Any] = {
        "has_context": True,
        "latest_analysis_text": "The user is on the billing page.",
        "latest_caption": "Billing screen",
        "latest_hints": ["Guide them to the submit button"],
        "latest_capture_mode": "video",
        "latest_frame_number": 42,
        "age_seconds": 10.5,
        "recent_events": [
            {
                "analysis_text": "The user is on the billing page.",
                "capture_mode": "video",
                "frame_number": 42,
            }
        ]
    }
    
    with patch("voice_agents.rag_bridge.get_support_call_screen_context", new_callable=AsyncMock) as mock_get_context:
        mock_get_context.return_value = mock_context
        
        result = asyncio.run(agent.get_live_screen_analysis())
        assert "billing page" in result
        assert "Billing screen" in result
        assert "Guide them to the submit button" in result
        assert "video #42" in result

def test_generate_screen_answer():
    agent = MockAgent()
    
    mock_context: dict[str, Any] = {
        "has_context": True,
        "latest_analysis_text": "A button that says 'Deploy'.",
        "age_seconds": 5.0,
    }
    
    with patch("voice_agents.rag_bridge.get_support_call_screen_context", new_callable=AsyncMock) as mock_get_context:
        mock_get_context.return_value = mock_context
        
        with patch("voice_agents.rag_bridge.generate_rag_response", new_callable=AsyncMock) as mock_generate:
            mock_generate.return_value = "The button says deploy."
            
            result = asyncio.run(agent.generate_screen_answer("What does the button say?"))
            assert result == "The button says deploy."
            mock_generate.assert_called_once()
            call_args = mock_generate.call_args[1]
            assert "A button that says 'Deploy'." in call_args["query"]
            assert "What does the button say?" in call_args["query"]


def test_generate_screen_answer_falls_back_to_direct_packet_answer():
    agent = MockAgent()

    mock_context: dict[str, Any] = {
        "has_context": True,
        "latest_analysis_text": "A button that says 'Deploy'.",
        "latest_hints": ["Click Deploy to continue."],
        "age_seconds": 5.0,
    }

    with patch("voice_agents.rag_bridge.get_support_call_screen_context", new_callable=AsyncMock) as mock_get_context:
        mock_get_context.return_value = mock_context

        with patch("voice_agents.rag_bridge.generate_rag_response", new_callable=AsyncMock) as mock_generate:
            mock_generate.return_value = None

            result = asyncio.run(agent.generate_screen_answer("What does the button say?"))

            assert "Based on the latest shared-screen packet" in result
            assert "Deploy" in result
            assert "Click Deploy to continue." in result


def test_get_live_screen_analysis_uses_job_context_room_name_when_session_has_no_room():
    agent = JobContextRoomAgent()

    mock_context: dict[str, Any] = {
        "has_context": True,
        "latest_analysis_text": "The user is on the billing page.",
        "age_seconds": 3.0,
    }

    with patch("voice_agents.generic_agent.get_job_context") as mock_job_context:
        mock_job_context.return_value = SimpleNamespace(
            room=SimpleNamespace(name="support-call-runtime-room")
        )
        with patch("voice_agents.rag_bridge.get_support_call_screen_context", new_callable=AsyncMock) as mock_get_context:
            mock_get_context.return_value = mock_context

            result = asyncio.run(agent.get_live_screen_analysis())

            assert "billing page" in result
            mock_get_context.assert_awaited_once_with("support-call-runtime-room")
