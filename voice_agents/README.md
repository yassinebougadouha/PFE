# Voice Agents — LiveKit Multi-Agent Voice AI

Real-time voice assistant powered by **LiveKit Agents SDK** and **Google Gemini**.
Features multiple specialised agents that seamlessly hand off conversations.

## Agent Roster

| Agent | Voice | Role |
|-------|-------|------|
| **Tom** (StarterAgent) | Puck | Main greeter & router — dispatches to specialists |
| **Mike** (SupportAgent) | Charon | Technical issue resolution |
| **Jessica** (BookingAgent) | Aoede | Appointment scheduling |
| **Ameni** (FAQAgent) | Kore | TunisieSMS FAQ specialist |

## Architecture

```
voice_agents/
├── __init__.py          # Module docstring
├── __main__.py          # python -m voice_agents
├── config.py            # Settings from .env / environment
├── generic_agent.py     # Base agent (auto-greet, end_conversation)
├── llm_factory.py       # make_llm() / make_tts() factory
├── agents.py            # All 4 specialised agents
├── server.py            # LiveKit AgentServer entry point
└── requirements.txt     # Separate deps (livekit-agents, plugins)
```

## Two Modes

| Mode | How it works | Best for |
|------|-------------|----------|
| **Pipeline** (default) | STT (Google) → LLM (Gemini) → TTS (Google) | Free tier, reliable |
| **Realtime** | Gemini Realtime API (single model) | Low latency, paid tier |

Toggle via `USE_REALTIME=true` in `.env`.

## Quick Start

### 1. Install dependencies

```bash
pip install -r voice_agents/requirements.txt
```

### 2. Configure environment

Add to your `.env`:
```dotenv
LIVEKIT_API_KEY=devkey
LIVEKIT_API_SECRET=secret
LIVEKIT_URL=ws://localhost:7880
GOOGLE_API_KEY=your-google-api-key
USE_REALTIME=false
```

### 3. Start a local LiveKit server

```bash
# Install: https://docs.livekit.io/home/self-hosting/local/
livekit-server --dev
```

### 4. Run the voice agent

```bash
# Console/dev mode (no LiveKit server needed)
python run_voice_agents.py dev

# Or as a module
python -m voice_agents dev

# Production mode (connects to LiveKit server)
python run_voice_agents.py start
```

## Multilingual Support

All agents auto-detect and respond in:
- **English**
- **French**
- **Modern Standard Arabic**
- **Tunisian Derja** (Tounsi)

## Agent Hand-Off Flow

```
User joins → StarterAgent (Tom) greets
  ├─ "I have a technical issue" → SupportAgent (Mike)
  ├─ "I want to book" → BookingAgent (Jessica)
  └─ "I have a question about SMS" → FAQAgent (Ameni)

Each specialist can:
  ├─ "Back to Tom" → StarterAgent (with chat history)
  └─ "End call" → end_conversation (room torn down)
```
