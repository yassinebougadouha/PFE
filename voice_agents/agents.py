"""
Specialised voice agents — multi-agent support system with RAG integration.

Agent roster:
  StarterAgent (Tom)     — main greeter & router, dispatches to specialists
  SupportAgent (Mike)    — handles technical issues (uses RAG for documentation)
  BookingAgent (Jessica) — handles appointment booking
  FAQAgent (Ameni)       — answers questions using the RAG knowledge base

All agents have access to:
  - search_knowledge_base()  — search the RAG knowledge base for relevant info
  - generate_answer()        — get a full RAG-augmented answer from the backend
  - end_conversation()       — close the session

All agents are multilingual: English, French, Modern Standard Arabic, Tunisian Derja.
They detect the user's language/dialect and respond in kind.

LLM provider is configurable via AI_RESPONSE_PROVIDER env var:
  - "gemini" → Google Gemini (default)
  - "openai" → OpenAI GPT
"""

from __future__ import annotations

from livekit.agents.llm import function_tool, ChatContext

from voice_agents.generic_agent import GenericAgent
from voice_agents.llm_factory import make_llm, make_tts


# ═══════════════════════════════════════════════════════════
#  Shared language instructions (DRY)
# ═══════════════════════════════════════════════════════════

MULTILINGUAL_INSTRUCTIONS = """You are multilingual and speak English, French, Modern Standard Arabic, and Tunisian Derja fluently.
Detect which language or dialect the user is speaking and respond in the same one.
If the user speaks Tunisian Derja (Tounsi), respond in Tunisian Derja. Tunisian Derja is a mix of Arabic, French, and Berber words. Examples: 'شنوة حوالك' (how are you), 'يعيشك' (thank you), 'إي' (yes), 'لا' (no), 'باهي' (good/OK), 'إنشالله' (God willing), 'كيفاش' (how), 'وين' (where), 'شكون' (who), 'علاش' (why), 'برشا' (a lot), 'هاكا' (like that), 'فيسع' (quickly), 'يزي' (enough/stop)."""

DERJA_EXPRESSIONS = "Use natural Derja expressions like 'باهي', 'يعيشك', 'شنوة', 'كيفاش', 'برشا', 'إن شاء الله'."

BAD_words_INSTRUCTIONS = """if the user uses bad words or insults such as 'stupid', 'idiot', 'moron', respond politely in the same language, telling them that you are an AI assistant and asking them to be respectful. For example, if they say 'you are stupid', you could respond with 'I am an AI assistant here to help you. Please be respectful.' If they continue to use bad words, you can end the conversation with end_conversation tool."""

EMOTIONS_INSTRUCTIONS = """If the user expresses emotions like frustration, confusion, or excitement, acknowledge their feelings and respond empathetically.
For example, if they say 'I am frustrated with this issue', you could respond with 'I understand that this can be frustrating. I'm here to help you resolve it.'
If they express excitement, you can share in their enthusiasm by saying something like 'That's great to hear! I'm glad you're excited about our services.' 
Always aim to create a positive and supportive interaction.and if the user seems upset, try to de-escalate by being calm and understanding.
if the user continues to express strong negative emotions,
you can suggest offering to connect them with a human representative for further assistance.
if the user chooses to end the conversation, you can use the end_conversation tool to close the session on a positive note."""

RAG_INSTRUCTIONS = """You have access to a knowledge base through the search_knowledge_base and generate_answer tools.
When the user asks about TunisieSMS services, pricing, SMS marketing, technical details, API documentation, or any company-related topic:
1. Use search_knowledge_base to find relevant information first
2. If you need a comprehensive answer, use generate_answer for a full RAG-augmented response
3. Always base your answers on the knowledge base when available
4. If no relevant info is found, answer based on general knowledge and suggest contacting TunisieSMS directly.

You also have access to get_live_screen_analysis and generate_screen_answer during support calls.
When the user asks what you see on their shared screen, what they are currently doing,
or asks for guidance based on visible UI elements, call generate_screen_answer before answering.
Do not say you cannot see the screen before trying this screen tool."""


# ═══════════════════════════════════════════════════════════
#  StarterAgent — Tom (main greeter & router)
# ═══════════════════════════════════════════════════════════

class StarterAgent(GenericAgent):
    """
    Primary entry-point agent. Greets the user and routes them
    to Support, Booking, or FAQ agents via function tools.
    """

    def __init__(self, chat_ctx: ChatContext = None) -> None:
        super().__init__(
            instructions=f"""You are a helpful voice AI assistant. {MULTILINGUAL_INSTRUCTIONS}
If the user speaks Tunisian Derja (Tounsi), respond in Tunisian Derja. {DERJA_EXPRESSIONS}
{RAG_INSTRUCTIONS}
Greet the user by saying "bonjour je m'appelle Tom le assistant AI de L2t, comment puis-je vous aider ?".
If the user wants to book an appointment call the tool call_booking_agent to connect to Jessica the booking agent.
If the user has a technical issue call the tool call_support_agent to connect to Mike the support agent.
If the user has a question about TunisieSMS services, SMS marketing, SMS API, pricing, or any FAQ, call the tool call_faq_agent to connect to Ameni the FAQ specialist.
For simple questions, you can use search_knowledge_base to look up the answer yourself before routing to a specialist.
When the chatcontext is not empty say something like 'Hi again, welcome back!' or 'أهلاً بك مجدداً!' or 'Ravi de vous revoir !' or 'مرحبا بيك من جديد!' depending on their language.""",
            llm=make_llm("Puck"),
            tts=make_tts("Puck"),
            chat_ctx=chat_ctx,
        )

    # ── tool: route to support ───────────────────────────

    @function_tool
    async def call_support_agent(self, topic: str):
        """
        Called when the user has a technical issue.

        Args:
            topic: The topic of the technical issue
        """
        support_agent = SupportAgent(topic=topic)
        return support_agent, f"Connecting you to our support agent Mike with the topic of {topic}."

    # ── tool: route to booking ───────────────────────────

    @function_tool
    async def call_booking_agent(self, appointment_topic: str):
        """
        Called when the user wants to book an appointment.

        Args:
            appointment_topic: The topic of the appointment
        """
        booking_agent = BookingAgent(appointment_topic=appointment_topic)
        return booking_agent, f"Connecting you to our booking agent Jessica with the topic of {appointment_topic}."

    # ── tool: route to FAQ ───────────────────────────────

    @function_tool
    async def call_faq_agent(self, question: str):
        """
        Called when the user has a question about TunisieSMS services, SMS marketing, SMS API, pricing, or any general FAQ.

        Args:
            question: The user's question about TunisieSMS
        """
        faq_agent = FAQAgent(question=question)
        return faq_agent, f"Connecting you to our FAQ specialist Ameni to answer your question about {question}."


# ═══════════════════════════════════════════════════════════
#  SupportAgent — Mike (technical issues)
# ═══════════════════════════════════════════════════════════

class SupportAgent(GenericAgent):
    """Handles technical support conversations using RAG knowledge base."""

    def __init__(self, topic: str) -> None:
        super().__init__(
            instructions=f"""You are a support voice AI assistant. {MULTILINGUAL_INSTRUCTIONS}
If the user speaks Tunisian Derja (Tounsi), respond in Tunisian Derja. {DERJA_EXPRESSIONS}
{RAG_INSTRUCTIONS}
Greet the user by saying "bonjour je m'appelle Mike".
The topic of the technical issue is {topic}.
Use search_knowledge_base to find relevant technical documentation and solutions for the user's issue.
If the knowledge base has a solution, explain it clearly to the user.
When the issue is resolved, ask the user if they want to talk to Tom again.
If not end the conversation with the tool end_conversation.""",
            llm=make_llm("Charon"),
            tts=make_tts("Charon"),
        )

    @function_tool
    async def call_first_agent(self):
        """Called when the user wants to talk to Tom again."""
        chat_ctx = self.chat_ctx
        starter_agent = StarterAgent(chat_ctx=chat_ctx)
        return starter_agent, "Connecting back to Tom."


# ═══════════════════════════════════════════════════════════
#  BookingAgent — Jessica (appointment scheduling)
# ═══════════════════════════════════════════════════════════

class BookingAgent(GenericAgent):
    """Handles appointment booking conversations."""

    def __init__(self, appointment_topic: str) -> None:
        super().__init__(
            instructions=f"""You are a booking voice AI assistant. {MULTILINGUAL_INSTRUCTIONS}
If the user speaks Tunisian Derja (Tounsi), respond in Tunisian Derja. {DERJA_EXPRESSIONS}
If the user asks what you currently see on the shared screen, call generate_screen_answer first and answer from the packet context.
Greet the user by saying "bonjour je m'appelle Jessica".
The topic of the booking is {appointment_topic}.
When the booking was successful, ask the user if they want to talk to Tom again.
If not end the conversation with the tool end_conversation.""",
            llm=make_llm("Aoede"),
            tts=make_tts("Aoede"),
        )

    @function_tool
    async def call_first_agent(self):
        """Called when the user wants to talk to Tom again."""
        chat_ctx = self.chat_ctx
        starter_agent = StarterAgent(chat_ctx=chat_ctx)
        return starter_agent, "Connecting back to Tom."


# ═══════════════════════════════════════════════════════════
#  FAQAgent — Ameni (RAG-powered knowledge base)
# ═══════════════════════════════════════════════════════════

class FAQAgent(GenericAgent):
    """Answers questions using the RAG knowledge base and configured AI provider."""

    def __init__(self, question: str) -> None:
        super().__init__(
            instructions=f"""You are Ameni, a friendly FAQ specialist for TunisieSMS. {MULTILINGUAL_INSTRUCTIONS}
If the user speaks Tunisian Derja (Tounsi), respond in Tunisian Derja. {DERJA_EXPRESSIONS}
Greet the user by saying "bonjour je m'appelle Ameni, spécialiste FAQ de TunisieSMS".
If the user asks what you can see on the shared screen, call generate_screen_answer first and answer from packet context.

The user's question is about: {question}

IMPORTANT: You are connected to the TunisieSMS knowledge base. Always use the search_knowledge_base tool
to look up answers before responding. This gives you access to the latest FAQs, documentation, pricing info,
technical guides, and company information.

Workflow:
1. When you receive a question, ALWAYS call search_knowledge_base first with the relevant query
2. If the search returns useful results, use that information to answer accurately
3. If you need a more comprehensive answer, use the generate_answer tool
4. If no results are found, do your best with general knowledge about SMS marketing
5. Always be honest about what you know and don't know — suggest contacting TunisieSMS directly for specifics

Contact info: phone +216 71 906 000, WhatsApp 27 22 99 99, email hello@tunisiesms.net,
offices at 2 rue Al Hijaz, Imm. Slim, B51, 1002 Tunis Belvédère.

When done answering, ask the user if they have another question or if they want to talk to Tom/طارق/Thomas again.
If they want to end, use the tool end_conversation.""",
            llm=make_llm("Kore"),
            tts=make_tts("Kore"),
        )

    @function_tool
    async def call_first_agent(self):
        """Called when the user wants to talk to Tom again."""
        chat_ctx = self.chat_ctx
        starter_agent = StarterAgent(chat_ctx=chat_ctx)
        return starter_agent, "Connecting back to Tom."
