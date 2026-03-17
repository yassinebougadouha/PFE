"""
Comprehensive test suite for the response_providers module.
Tests: imports, channel formatter, provider instantiation, schema validation,
       API endpoint accessibility, and full generation pipeline.
"""

import httpx
import asyncio
import json
import sys

BASE = "http://localhost:8000/api/v1"
PASS = 0
FAIL = 0


def ok(label: str):
    global PASS
    PASS += 1
    print(f"  ✅ {label}")


def fail(label: str, detail: str = ""):
    global FAIL
    FAIL += 1
    print(f"  ❌ {label} — {detail}")


async def get_token(client: httpx.AsyncClient) -> str:
    """Login and return a bearer token."""
    resp = await client.post(f"{BASE}/auth/login", json={
        "email": "admin@test.com",
        "password": "Admin123!",
    })
    if resp.status_code == 200:
        return resp.json()["access_token"]
    # Try registering first
    reg_resp = await client.post(f"{BASE}/auth/register", json={
        "email": "admin@test.com",
        "password": "Admin123!",
        "full_name": "Test Admin",
        "role": "admin",
    })
    print(f"  [auth] register status={reg_resp.status_code}")
    resp = await client.post(f"{BASE}/auth/login", json={
        "email": "admin@test.com",
        "password": "Admin123!",
    })
    if resp.status_code != 200:
        raise RuntimeError(f"Could not authenticate: {resp.text}")
    return resp.json()["access_token"]


async def run_tests():
    global PASS, FAIL

    # ── 1. Import tests ──────────────────────────────────
    print("\n🔧 1. Module import tests")
    try:
        from app.rag.response_providers.enums import AIProvider, ResponseChannel, ResponseTone
        ok("enums import")
    except Exception as e:
        fail("enums import", str(e))

    try:
        from app.rag.response_providers.schemas import (
            GenerateRequest, GenerateResponse, SourceReference,
            MultiChannelPreviewResponse, ProvidersStatusResponse
        )
        ok("schemas import")
    except Exception as e:
        fail("schemas import", str(e))

    try:
        from app.rag.response_providers.base import BaseProvider, CHANNEL_INSTRUCTIONS, TONE_INSTRUCTIONS
        ok("base import")
    except Exception as e:
        fail("base import", str(e))

    try:
        from app.rag.response_providers.openai_provider import OpenAIProvider
        ok("openai_provider import")
    except Exception as e:
        fail("openai_provider import", str(e))

    try:
        from app.rag.response_providers.claude_provider import ClaudeProvider
        ok("claude_provider import")
    except Exception as e:
        fail("claude_provider import", str(e))

    try:
        from app.rag.response_providers.gemini_provider import GeminiProvider
        ok("gemini_provider import")
    except Exception as e:
        fail("gemini_provider import", str(e))

    try:
        from app.rag.response_providers.channel_formatter import format_response
        ok("channel_formatter import")
    except Exception as e:
        fail("channel_formatter import", str(e))

    try:
        from app.rag.response_providers.service import ResponseGenerationService, get_provider
        ok("service import")
    except Exception as e:
        fail("service import", str(e))

    try:
        from app.rag.response_providers.routes import router
        ok("routes import")
    except Exception as e:
        fail("routes import", str(e))

    try:
        from app.rag.response_providers import (
            AIProvider as AI, ResponseChannel as RC,
            ResponseTone as RT, ResponseGenerationService as RGS, get_provider as gp,
        )
        ok("__init__ re-exports")
    except Exception as e:
        fail("__init__ re-exports", str(e))

    # ── 2. Enum tests ────────────────────────────────────
    print("\n🔧 2. Enum tests")
    from app.rag.response_providers.enums import AIProvider, ResponseChannel, ResponseTone

    assert len(AIProvider) == 3, "Expected 3 providers"
    ok(f"AIProvider has {len(AIProvider)} members")

    assert len(ResponseChannel) == 5, "Expected 5 channels"
    ok(f"ResponseChannel has {len(ResponseChannel)} members")

    assert len(ResponseTone) == 5, "Expected 5 tones"
    ok(f"ResponseTone has {len(ResponseTone)} members")

    assert AIProvider("openai") == AIProvider.OPENAI
    ok("AIProvider string lookup")

    assert ResponseChannel("CHAT") == ResponseChannel.CHAT
    ok("ResponseChannel string lookup")

    # ── 3. Schema validation tests ───────────────────────
    print("\n🔧 3. Schema validation tests")
    from app.rag.response_providers.schemas import GenerateRequest, GenerateResponse, SourceReference

    req = GenerateRequest(query="How do I reset my password?")
    assert req.channel == ResponseChannel.CHAT
    assert req.tone == ResponseTone.PROFESSIONAL
    assert req.top_k == 5
    ok("GenerateRequest defaults")

    req2 = GenerateRequest(
        query="Help me",
        channel=ResponseChannel.EMAIL,
        provider=AIProvider.CLAUDE,
        tone=ResponseTone.EMPATHETIC,
        customer_name="John",
        agent_name="Support Bot",
    )
    assert req2.provider == AIProvider.CLAUDE
    assert req2.customer_name == "John"
    ok("GenerateRequest custom fields")

    src = SourceReference(
        article_id="abc-123",
        article_title="Password Reset Guide",
        similarity=0.92,
        chunk_preview="To reset your password...",
    )
    assert src.similarity == 0.92
    ok("SourceReference creation")

    try:
        GenerateRequest(query="x")  # min_length=2
        fail("GenerateRequest min_length", "should reject 1 char")
    except Exception:
        ok("GenerateRequest min_length validation")

    # ── 4. Base provider tests ───────────────────────────
    print("\n🔧 4. Base provider tests")
    from app.rag.response_providers.base import BaseProvider, CHANNEL_INSTRUCTIONS, TONE_INSTRUCTIONS

    assert len(CHANNEL_INSTRUCTIONS) == 5
    ok(f"CHANNEL_INSTRUCTIONS covers {len(CHANNEL_INSTRUCTIONS)} channels")

    assert len(TONE_INSTRUCTIONS) == 5
    ok(f"TONE_INSTRUCTIONS covers {len(TONE_INSTRUCTIONS)} tones")

    # ── 5. Provider instantiation tests ──────────────────
    print("\n🔧 5. Provider instantiation tests")
    from app.rag.response_providers.openai_provider import OpenAIProvider
    from app.rag.response_providers.claude_provider import ClaudeProvider
    from app.rag.response_providers.gemini_provider import GeminiProvider

    oai = OpenAIProvider()
    assert oai.provider == AIProvider.OPENAI
    assert "gpt-4o" in oai.available_models
    ok(f"OpenAI: provider={oai.provider.value}, default={oai.default_model}")

    claude = ClaudeProvider()
    assert claude.provider == AIProvider.CLAUDE
    ok(f"Claude: provider={claude.provider.value}, default={claude.default_model}")

    gemini = GeminiProvider()
    assert gemini.provider == AIProvider.GEMINI
    ok(f"Gemini: provider={gemini.provider.value}, default={gemini.default_model}")

    # ── 6. Provider selection (service) ──────────────────
    print("\n🔧 6. Provider selection tests")
    from app.rag.response_providers.service import get_provider

    p1 = get_provider(AIProvider.OPENAI)
    assert isinstance(p1, OpenAIProvider)
    ok("get_provider(OPENAI) → OpenAIProvider")

    p2 = get_provider(AIProvider.CLAUDE)
    assert isinstance(p2, ClaudeProvider)
    ok("get_provider(CLAUDE) → ClaudeProvider")

    p3 = get_provider(AIProvider.GEMINI)
    assert isinstance(p3, GeminiProvider)
    ok("get_provider(GEMINI) → GeminiProvider")

    p_default = get_provider(None)
    assert isinstance(p_default, BaseProvider)
    ok(f"get_provider(None) → default ({p_default.provider.value})")

    # ── 7. System prompt building ────────────────────────
    print("\n🔧 7. System prompt building tests")

    rag_context = [
        {"article_title": "Getting Started", "chunk_content": "Welcome to our platform..."},
        {"article_title": "FAQ", "chunk_content": "Q: How do I login? A: Click the login button."},
    ]

    prompt = oai.build_system_prompt(
        channel=ResponseChannel.CHAT,
        tone=ResponseTone.FRIENDLY,
        rag_context=rag_context,
    )
    assert "customer support" in prompt.lower()
    assert "Getting Started" in prompt
    assert "FAQ" in prompt
    ok("System prompt includes RAG context")

    prompt_email = oai.build_system_prompt(
        channel=ResponseChannel.EMAIL,
        tone=ResponseTone.PROFESSIONAL,
        rag_context=[],
        customer_name="Alice",
        agent_name="Bot",
    )
    assert "email" in prompt_email.lower()
    assert "Alice" in prompt_email
    ok("Email prompt includes channel rules + customer name")

    prompt_voice = oai.build_system_prompt(
        channel=ResponseChannel.VOICE,
        tone=ResponseTone.CONCISE,
        rag_context=[],
    )
    assert "spoken" in prompt_voice.lower() or "speech" in prompt_voice.lower() or "read aloud" in prompt_voice.lower()
    ok("Voice prompt includes TTS instructions")

    prompt_wa = oai.build_system_prompt(
        channel=ResponseChannel.WHATSAPP,
        tone=ResponseTone.EMPATHETIC,
        rag_context=rag_context,
        language="fr",
    )
    assert "fr" in prompt_wa.lower() or "French" in prompt_wa
    ok("WhatsApp prompt includes language instruction")

    # ── 8. Channel formatter tests ───────────────────────
    print("\n🔧 8. Channel formatter tests")
    from app.rag.response_providers.channel_formatter import (
        format_response, format_for_chat, format_for_email,
        format_for_whatsapp, format_for_voice, format_for_ticket,
    )

    raw = """## Getting Started

Here's how to **reset your password**:

1. Go to https://example.com/reset
2. Enter your email
3. Check your inbox

For more info, see [our docs](https://docs.example.com).

Best regards"""

    # Chat
    chat_out = format_for_chat(raw)
    assert "##" in chat_out  # markdown preserved
    ok("CHAT: markdown preserved")

    # Email
    email_out = format_for_email(raw, customer_name="Bob", agent_name="Support")
    assert email_out.startswith("Hello Bob")
    ok("EMAIL: greeting prepended with customer name")

    email_out2 = format_for_email("Hello there,\n\nHere's your answer.\n\nBest regards,\nTeam")
    assert email_out2.startswith("Hello there")  # greeting not doubled
    ok("EMAIL: existing greeting preserved")

    # WhatsApp
    wa_out = format_for_whatsapp(raw)
    assert "##" not in wa_out  # headers stripped
    assert "**" not in wa_out  # double-bold converted to single *
    ok("WHATSAPP: markdown headers stripped, bold converted")

    # Voice
    voice_out = format_for_voice(raw)
    assert "**" not in voice_out
    assert "https://" not in voice_out
    assert "#" not in voice_out
    ok("VOICE: markdown + URLs stripped for TTS")

    # Ticket
    sources = [
        {"article_title": "Reset Guide", "similarity": 0.95},
        {"article_title": "FAQ", "similarity": 0.82},
    ]
    ticket_out = format_for_ticket("Here is the answer.", sources=sources)
    assert "Reset Guide" in ticket_out
    assert "95%" in ticket_out
    ok("TICKET: source references appended")

    # format_response dispatcher
    for ch in ResponseChannel:
        result = format_response("Test content", ch)
        assert isinstance(result, str) and len(result) > 0
    ok(f"format_response dispatches for all {len(ResponseChannel)} channels")

    # ── 9. Claude message conversion ─────────────────────
    print("\n🔧 9. Claude message conversion tests")
    system, msgs = claude._convert_messages([
        {"role": "system", "content": "You are helpful."},
        {"role": "user", "content": "Hello"},
        {"role": "assistant", "content": "Hi there"},
        {"role": "user", "content": "Help me"},
    ])
    assert "helpful" in system
    assert len(msgs) == 3
    assert msgs[0]["role"] == "user"
    ok("Claude: system extracted, roles preserved")

    # ── 10. Gemini message conversion ────────────────────
    print("\n🔧 10. Gemini message conversion tests")
    g_system, g_contents = gemini._convert_messages([
        {"role": "system", "content": "Be concise."},
        {"role": "user", "content": "Question"},
        {"role": "assistant", "content": "Answer"},
    ])
    assert "concise" in g_system
    assert g_contents[1]["role"] == "model"  # assistant → model
    ok("Gemini: system extracted, assistant→model conversion")

    # ── 11. API endpoint tests ───────────────────────────
    print("\n🔧 11. API endpoint tests")

    async with httpx.AsyncClient(timeout=30.0) as client:
        token = await get_token(client)
        headers = {"Authorization": f"Bearer {token}"}

        # OpenAPI includes our routes
        resp = await client.get("http://localhost:8000/openapi.json")
        openapi_spec = resp.json()
        paths = list(openapi_spec.get("paths", {}).keys())
        gen_paths = [p for p in paths if "/rag/generate" in p]
        assert len(gen_paths) >= 3, f"Expected >= 3 generate paths, got {gen_paths}"
        ok(f"OpenAPI: {len(gen_paths)} /rag/generate endpoints registered")

        # List providers
        resp = await client.get(f"{BASE}/rag/generate/providers", headers=headers)
        assert resp.status_code == 200
        data = resp.json()
        assert len(data["providers"]) == 3
        ok(f"GET /providers → {len(data['providers'])} providers listed")

        provider_names = {p["provider"] for p in data["providers"]}
        assert provider_names == {"openai", "claude", "gemini"}
        ok("All 3 providers present in status")

        has_default = any(p["is_default"] for p in data["providers"])
        assert has_default
        ok(f"Default provider flagged: {data['default_provider']}")

        # Health check (should return false since no API keys configured)
        for prov in ["openai", "claude", "gemini"]:
            resp = await client.get(
                f"{BASE}/rag/generate/providers/{prov}/health",
                headers=headers,
            )
            assert resp.status_code == 200
            ok(f"GET /providers/{prov}/health → {resp.json()}")

        # POST /generate without API key → 503
        resp = await client.post(
            f"{BASE}/rag/generate",
            headers=headers,
            json={"query": "How do I reset my password?", "provider": "openai"},
        )
        assert resp.status_code == 503
        ok("POST /generate with unconfigured provider → 503")

        # POST /generate validation — missing query
        resp = await client.post(
            f"{BASE}/rag/generate",
            headers=headers,
            json={},
        )
        assert resp.status_code == 422
        ok("POST /generate missing query → 422")

        # POST /generate with all channel types in request
        for channel in ["CHAT", "EMAIL", "WHATSAPP", "VOICE", "TICKET"]:
            resp = await client.post(
                f"{BASE}/rag/generate",
                headers=headers,
                json={
                    "query": "Test query",
                    "channel": channel,
                    "provider": "gemini",
                },
            )
            # Expect 503 (no key) — but validates the channel is accepted
            assert resp.status_code in (200, 503), f"Channel {channel} got {resp.status_code}"
            ok(f"POST /generate channel={channel} accepted (status={resp.status_code})")

        # POST /stream
        resp = await client.post(
            f"{BASE}/rag/generate/stream",
            headers=headers,
            json={"query": "Hello"},
        )
        # SSE endpoint returns 200 even if provider fails (error in stream)
        assert resp.status_code == 200
        ok("POST /generate/stream → 200 (SSE started)")

        # POST /preview
        resp = await client.post(
            f"{BASE}/rag/generate/preview",
            headers=headers,
            json={"query": "Help", "provider": "openai"},
        )
        assert resp.status_code in (200, 503)
        ok(f"POST /generate/preview → {resp.status_code}")

        # Auth required
        resp = await client.get(f"{BASE}/rag/generate/providers")
        assert resp.status_code in (401, 403)
        ok("Endpoints require authentication")

    # ── Summary ──────────────────────────────────────────
    print(f"\n{'='*50}")
    print(f"  RESULTS: {PASS} passed, {FAIL} failed out of {PASS + FAIL}")
    print(f"{'='*50}\n")
    return FAIL


if __name__ == "__main__":
    exit_code = asyncio.run(run_tests())
    sys.exit(exit_code)
