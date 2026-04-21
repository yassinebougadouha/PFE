#!/usr/bin/env python
"""
Convenience script to run the voice agents server.

Usage:
    python run_voice_agents.py dev        # development / console mode
    python run_voice_agents.py start      # production (requires LiveKit server)
"""

if __name__ == "__main__":
    from voice_agents.server import main
    main()
