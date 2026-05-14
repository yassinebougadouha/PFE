"""
Conversation service for chat conversations, messages, and client auto-replies.
"""

from __future__ import annotations

import asyncio
import json
import logging
import re
import uuid
from datetime import datetime, timezone
from pathlib import Path
from typing import Optional

from sqlalchemy import func, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import get_settings
from app.core.security import hash_password
from app.db.models.conversation import Conversation, Message
from app.db.models.enums import ChannelType, ConversationStatus, UserRole, UserStatus
from app.db.models.user import User
from app.db.session import async_session_factory
from app.rag.response_providers.enums import ResponseChannel, ResponseTone
from app.rag.response_providers.schemas import GenerateRequest
from app.rag.response_providers.service import ResponseGenerationService
from app.rag.retriever import VectorRetriever
from app.schemas.conversation import ConversationCreate, ConversationUpdate, MessageCreate
from app.services.auto_reply_policy import (
    evaluate_conversation_auto_reply,
    is_channel_auto_reply_enabled,
)
from app.services.image_analysis_service import analyze_chat_image

try:
    import fitz  # PyMuPDF
except ImportError:  # pragma: no cover - handled by runtime fallback
    fitz = None

logger = logging.getLogger(__name__)
settings = get_settings()

SUPPORT_BOT_EMAIL = "support-assistant@system.local"
SUPPORT_BOT_NAME = "Support Assistant"
CHAT_AUTO_REPLY_HISTORY_LIMIT = 8
CHAT_AUTO_REPLY_CONTEXT_TOP_K = 2
CHAT_AUTO_REPLY_MAX_TOKENS = 220
CHAT_AUTO_REPLY_TEMPERATURE = 0.15
WHATSAPP_DRAFT_TOP_K = 2
WHATSAPP_DRAFT_MAX_TOKENS = 180
WHATSAPP_DRAFT_TEMPERATURE = 0.12
TEXT_ATTACHMENT_SUFFIXES = {
    ".txt",
    ".md",
    ".csv",
    ".json",
    ".log",
    ".yaml",
    ".yml",
    ".xml",
}


class ConversationService:
    def __init__(self, db: AsyncSession):
        self.db = db

    async def create_conversation(
        self,
        user_id: uuid.UUID,
        payload: ConversationCreate,
    ) -> Conversation:
        conv = Conversation(
            user_id=user_id,
            channel=payload.channel,
            subject=payload.subject,
        )
        self.db.add(conv)
        await self.db.flush()
        await self.db.refresh(conv)
        return conv

    async def get_conversation(self, conversation_id: uuid.UUID) -> Optional[Conversation]:
        result = await self.db.execute(
            select(Conversation).where(
                Conversation.id == conversation_id,
                Conversation.is_deleted == False,
            )
        )
        return result.scalar_one_or_none()

    async def get_message(self, message_id: uuid.UUID) -> Optional[Message]:
        result = await self.db.execute(
            select(Message).where(Message.id == message_id)
        )
        return result.scalar_one_or_none()

    async def get_user(self, user_id: uuid.UUID) -> Optional[User]:
        result = await self.db.execute(
            select(User).where(
                User.id == user_id,
                User.is_deleted == False,
            )
        )
        return result.scalar_one_or_none()

    async def list_conversations(
        self,
        user_id: Optional[uuid.UUID] = None,
        status: Optional[ConversationStatus] = None,
        channel: Optional[ChannelType] = None,
        include_total: bool = True,
        skip: int = 0,
        limit: int = 50,
    ) -> tuple[list[Conversation], int]:
        query = select(Conversation).where(Conversation.is_deleted == False)
        count_q = select(func.count(Conversation.id)).where(Conversation.is_deleted == False)

        if user_id:
            query = query.where(Conversation.user_id == user_id)
            count_q = count_q.where(Conversation.user_id == user_id)
        if status:
            query = query.where(Conversation.status == status)
            count_q = count_q.where(Conversation.status == status)
        if channel:
            query = query.where(Conversation.channel == channel)
            count_q = count_q.where(Conversation.channel == channel)

        total = ((await self.db.execute(count_q)).scalar() or 0) if include_total else 0
        result = await self.db.execute(
            query.offset(skip).limit(limit).order_by(
                Conversation.is_pinned.desc(),
                Conversation.updated_at.desc(),
            )
        )
        return list(result.scalars().all()), total

    async def update_conversation(
        self,
        conversation_id: uuid.UUID,
        payload: ConversationUpdate,
    ) -> Optional[Conversation]:
        conv = await self.get_conversation(conversation_id)
        if not conv:
            return None
        for field, value in payload.model_dump(exclude_unset=True).items():
            setattr(conv, field, value)
        await self.db.flush()
        await self.db.refresh(conv)
        return conv

    async def delete_conversation(self, conversation_id: uuid.UUID) -> bool:
        conv = await self.get_conversation(conversation_id)
        if not conv:
            return False

        conv.is_deleted = True
        conv.deleted_at = datetime.now(timezone.utc)
        await self.db.flush()
        return True

    async def add_message(
        self,
        conversation_id: uuid.UUID,
        sender_id: uuid.UUID,
        payload: MessageCreate,
        *,
        attachment_path: str | None = None,
        attachment_filename: str | None = None,
        attachment_content_type: str | None = None,
        attachment_size: int | None = None,
    ) -> Message:
        conv = await self.get_conversation(conversation_id)
        if not conv:
            raise ValueError("Conversation not found")

        msg = Message(
            conversation_id=conversation_id,
            sender_id=sender_id,
            content=payload.content,
            is_internal=payload.is_internal,
            attachment_path=attachment_path,
            attachment_filename=attachment_filename,
            attachment_content_type=attachment_content_type,
            attachment_size=attachment_size,
        )
        self.db.add(msg)
        conv.updated_at = datetime.now(timezone.utc)
        await self.db.flush()
        await self.db.refresh(msg)
        return msg

    async def get_messages(
        self,
        conversation_id: uuid.UUID,
        skip: int = 0,
        limit: int = 100,
        include_internal: bool = True,
    ) -> list[Message]:
        query = (
            select(Message)
            .where(Message.conversation_id == conversation_id)
            .order_by(Message.created_at.asc())
            .offset(skip)
            .limit(limit)
        )
        if not include_internal:
            query = query.where(Message.is_internal == False)

        result = await self.db.execute(query)
        return list(result.scalars().all())

    async def generate_support_reply(
        self,
        conversation: Conversation,
        customer: User,
        latest_message: Message,
    ) -> Optional[Message]:
        if conversation.channel != ChannelType.CHAT or latest_message.is_internal:
            return None

        channel_enabled = await is_channel_auto_reply_enabled(self.db, "chat", default=True)
        evaluation = evaluate_conversation_auto_reply(
            channel_enabled=channel_enabled,
            conversation_enabled=bool(getattr(conversation, "ai_auto_reply_enabled", True)),
            paused_until=getattr(conversation, "ai_auto_reply_paused_until", None),
        )
        if not evaluation.effective_enabled:
            return None

        request, attachment_context = await self.build_support_reply_request(
            conversation=conversation,
            customer=customer,
            latest_message=latest_message,
        )
        reply_text = await self._generate_reply_text(request, attachment_context=attachment_context)
        if not reply_text:
            return None

        return await self.save_support_reply(conversation=conversation, reply_text=reply_text)

    async def build_support_reply_request(
        self,
        conversation: Conversation,
        customer: User,
        latest_message: Message,
    ) -> tuple[GenerateRequest, dict | None]:
        if latest_message.is_internal:
            raise ValueError("Support reply requests are only built for external customer messages")

        response_channel = self._resolve_response_channel(conversation.channel)

        # Keep database-backed work on this shared AsyncSession sequential.
        # asyncpg allows only one operation at a time per connection, so
        # gathering these coroutines can intermittently raise
        # "another operation is in progress" during assisted draft creation.
        conversation_history = await self._build_conversation_history(
            conversation_id=conversation.id,
            customer_id=customer.id,
            latest_message_id=latest_message.id,
        )
        attachment_context = await self._build_attachment_context(latest_message)
        query = self._build_reply_query(
            latest_message,
            attachment_context=attachment_context,
            response_channel=response_channel,
        )
        language_sample = self._build_language_sample(
            latest_message.content,
            attachment_context=attachment_context,
        )

        if response_channel == ResponseChannel.WHATSAPP:
            top_k = min(max(1, settings.AUTO_REPLY_TOP_K), WHATSAPP_DRAFT_TOP_K)
            max_tokens = min(settings.AI_RESPONSE_MAX_TOKENS, WHATSAPP_DRAFT_MAX_TOKENS)
            temperature = WHATSAPP_DRAFT_TEMPERATURE
        else:
            top_k = min(max(1, settings.AUTO_REPLY_TOP_K), CHAT_AUTO_REPLY_CONTEXT_TOP_K)
            max_tokens = min(settings.AI_RESPONSE_MAX_TOKENS, CHAT_AUTO_REPLY_MAX_TOKENS)
            temperature = CHAT_AUTO_REPLY_TEMPERATURE

        request = GenerateRequest(
            query=query,
            channel=response_channel,
            tone=ResponseTone.CONCISE,
            top_k=top_k,
            conversation_history=conversation_history,
            language=self._detect_language(language_sample),
            include_sources=False,
            max_tokens=max_tokens,
            temperature=temperature,
        )
        return request, attachment_context

    @staticmethod
    def _resolve_response_channel(channel: ChannelType) -> ResponseChannel:
        if channel == ChannelType.CHAT:
            return ResponseChannel.CHAT
        if channel == ChannelType.WHATSAPP:
            return ResponseChannel.WHATSAPP
        raise ValueError("Support reply requests are only built for chat and WhatsApp conversations")

    async def save_support_reply(
        self,
        *,
        conversation: Conversation,
        reply_text: str,
    ) -> Message:
        support_bot = await self._get_or_create_support_bot()
        reply = Message(
            conversation_id=conversation.id,
            sender_id=support_bot.id,
            content=reply_text,
            is_internal=False,
        )
        self.db.add(reply)
        conversation.updated_at = datetime.now(timezone.utc)
        await self.db.flush()
        await self.db.refresh(reply)
        return reply

    async def has_support_reply_after(
        self,
        conversation_id: uuid.UUID,
        customer_id: uuid.UUID,
        latest_message: Message,
    ) -> bool:
        result = await self.db.execute(
            select(Message.id)
            .where(
                Message.conversation_id == conversation_id,
                Message.is_internal == False,
                Message.sender_id != customer_id,
                Message.created_at > latest_message.created_at,
            )
            .limit(1)
        )
        return result.scalar_one_or_none() is not None

    async def _build_conversation_history(
        self,
        conversation_id: uuid.UUID,
        customer_id: uuid.UUID,
        latest_message_id: uuid.UUID,
    ) -> list[dict]:
        messages = await self.get_messages(
            conversation_id=conversation_id,
            limit=CHAT_AUTO_REPLY_HISTORY_LIMIT,
            include_internal=False,
        )
        history: list[dict] = []
        for message in messages:
            if message.id == latest_message_id:
                continue
            history.append(
                {
                    "role": "user" if message.sender_id == customer_id else "assistant",
                    "content": message.content,
                }
            )
        return history[-10:]

    async def _generate_reply_text(
        self,
        request: GenerateRequest,
        *,
        attachment_context: dict | None = None,
    ) -> str:
        service = ResponseGenerationService(self.db)
        try:
            response = await service.generate(request)
            text = (response.response or "").strip()
            if text:
                return text
        except Exception:
            logger.warning("Conversation auto-reply generation failed", exc_info=True)

        contextual = await self._contextual_fallback_reply(request.query)
        if contextual:
            return contextual

        attachment_fallback = self._fallback_attachment_reply(
            language=request.language,
            attachment_context=attachment_context,
        )
        if attachment_fallback:
            return attachment_fallback

        return self._default_support_reply(request.language)

    async def _contextual_fallback_reply(self, query: str) -> Optional[str]:
        try:
            hits = await VectorRetriever(self.db).get_context_for_query(
                query=query,
                top_k=min(max(1, settings.AUTO_REPLY_TOP_K), 3),
            )
        except Exception:
            logger.warning("Conversation fallback search failed", exc_info=True)
            return None

        if not hits:
            return None

        lead_title = hits[0].get("article_title") or "our knowledge base"
        lead_sentence = self._pick_best_sentence(query=query, hits=hits)
        if not lead_sentence:
            snippet = " ".join((hits[0].get("chunk_content") or "").split())
            if not snippet:
                return None
            lead_sentence = snippet[:420].rstrip()
            if not lead_sentence.endswith((".", "!", "?")):
                lead_sentence += "."

        return (
            f"I found this in {lead_title}: {lead_sentence}\n\n"
            "If you want, I can help turn that into the exact next steps for your case."
        )

    async def _build_attachment_context(self, message: Message) -> Optional[dict]:
        if not message.attachment_filename:
            return None

        attachment_kind = self._attachment_kind(message)
        attachment_path = Path(message.attachment_path or "")
        filename = message.attachment_filename or attachment_path.name
        content_type = (message.attachment_content_type or "").strip().lower()

        if attachment_kind == "audio":
            return None

        if attachment_kind == "image":
            if not attachment_path.exists():
                logger.warning("Image attachment missing on disk for message %s", message.id)
                return {
                    "kind": "image",
                    "filename": filename,
                    "content_type": content_type,
                }

            try:
                image_analysis = await analyze_chat_image(
                    attachment_path.read_bytes(),
                    mime_type=message.attachment_content_type,
                    filename=filename,
                    customer_message=message.content,
                )
                return {
                    "kind": "image",
                    "filename": filename,
                    "content_type": content_type,
                    **image_analysis,
                }
            except Exception:
                logger.warning("Image attachment analysis failed for message %s", message.id)
                return {
                    "kind": "image",
                    "filename": filename,
                    "content_type": content_type,
                }

        if not attachment_path.exists():
            return {
                "kind": attachment_kind,
                "filename": filename,
                "content_type": content_type,
            }

        if attachment_kind == "text":
            preview = self._read_text_attachment_preview(attachment_path)
            return {
                "kind": "text",
                "filename": filename,
                "content_type": content_type,
                "preview": preview,
            }

        if attachment_kind == "pdf":
            preview = self._read_pdf_attachment_preview(attachment_path)
            return {
                "kind": "pdf",
                "filename": filename,
                "content_type": content_type,
                "preview": preview,
            }

        return {
            "kind": "file",
            "filename": filename,
            "content_type": content_type,
        }

    @staticmethod
    def _attachment_kind(message: Message) -> str:
        content_type = (message.attachment_content_type or "").strip().lower()
        suffix = Path(message.attachment_filename or "").suffix.lower()

        if content_type.startswith("image/"):
            return "image"
        if content_type.startswith("audio/"):
            return "audio"
        if content_type == "application/pdf" or suffix == ".pdf":
            return "pdf"
        if content_type.startswith("text/") or suffix in TEXT_ATTACHMENT_SUFFIXES:
            return "text"
        return "file"

    @staticmethod
    def _read_text_attachment_preview(attachment_path: Path) -> str:
        try:
            text = attachment_path.read_text(encoding="utf-8", errors="replace")
        except Exception:
            logger.warning("Failed to read text attachment %s", attachment_path, exc_info=True)
            return ""

        compact = text.strip()
        if not compact:
            return ""

        return compact[:1800].rstrip()

    @staticmethod
    def _read_pdf_attachment_preview(attachment_path: Path) -> str:
        if fitz is None:
            return ""

        try:
            doc = fitz.open(str(attachment_path))
        except Exception:
            logger.warning("Failed to open PDF attachment %s", attachment_path, exc_info=True)
            return ""

        try:
            text_parts: list[str] = []
            for page_index in range(min(doc.page_count, 2)):
                page = doc.load_page(page_index)
                extracted = page.get_text("text").strip()
                if extracted:
                    text_parts.append(extracted)
                if sum(len(part) for part in text_parts) >= 1800:
                    break
            return "\n".join(text_parts)[:1800].rstrip()
        finally:
            doc.close()

    @staticmethod
    def _is_generated_attachment_caption(text: str) -> bool:
        normalized = (text or "").strip().lower()
        return normalized.startswith("shared an image:") or normalized.startswith("shared a file:")

    @classmethod
    def _build_reply_query(
        cls,
        latest_message: Message,
        *,
        attachment_context: dict | None = None,
        response_channel: ResponseChannel = ResponseChannel.CHAT,
    ) -> str:
        raw_message = (latest_message.content or "").strip()
        has_meaningful_message = bool(raw_message and not cls._is_generated_attachment_caption(raw_message))
        query_parts: list[str] = []

        if has_meaningful_message:
            query_parts.append(f"Customer message: {raw_message}")

        if attachment_context:
            attachment_kind = attachment_context.get("kind")
            filename = (attachment_context.get("filename") or latest_message.attachment_filename or "").strip()
            content_type = (attachment_context.get("content_type") or latest_message.attachment_content_type or "").strip()

            if attachment_kind == "image":
                query_parts.append("Attached image analysis:")
                summary = (attachment_context.get("summary") or "").strip()
                visible_text = (attachment_context.get("visible_text") or "").strip()
                suggested_focus = (attachment_context.get("suggested_focus") or "").strip()
                issue_signals = [
                    str(item).strip()
                    for item in (attachment_context.get("issue_signals") or [])
                    if str(item).strip()
                ]

                if summary:
                    query_parts.append(f"Summary: {summary}")
                if visible_text:
                    query_parts.append(f"Visible text: {visible_text}")
                for issue in issue_signals[:3]:
                    query_parts.append(f"Issue signal: {issue}")
                if suggested_focus:
                    query_parts.append(f"Likely support focus: {suggested_focus}")
                if not has_meaningful_message:
                    query_parts.append(
                        "The customer mainly wants help understanding or acting on what is visible in the image."
                    )
            elif attachment_kind in {"text", "pdf"}:
                preview = (attachment_context.get("preview") or "").strip()
                query_parts.append(f"Attached file: {filename}")
                query_parts.append("This attachment is a file, not an image. Do not describe it visually.")
                if content_type:
                    query_parts.append(f"Content type: {content_type}")
                if attachment_kind == "pdf":
                    query_parts.append("The attachment is a PDF document.")
                else:
                    query_parts.append("The attachment is a text-based document.")
                if preview:
                    query_parts.append(f"Extracted file text:\n{preview}")
                else:
                    query_parts.append("No readable text could be extracted from the attachment.")
                if not has_meaningful_message:
                    query_parts.append(
                        "Base your reply on the extracted file text when possible and offer help with the document."
                    )
            else:
                query_parts.append(f"Attached file: {filename}")
                query_parts.append("This attachment is a non-image file. Do not claim to see or analyze it visually.")
                if content_type:
                    query_parts.append(f"Content type: {content_type}")
                if not has_meaningful_message:
                    query_parts.append(
                        "Acknowledge receiving the file and ask which part the customer wants help with."
                    )

        query = "\n".join(part for part in query_parts if part).strip() or raw_message

        if response_channel == ResponseChannel.WHATSAPP:
            query = (
                f"{query}\n\n"
                "Channel instruction: WhatsApp reply. Keep it concise (1-3 short sentences), "
                "mobile-friendly, and action-oriented. Avoid email-style greetings/signatures "
                "and avoid markdown-heavy formatting."
            )

        return query

    @staticmethod
    def _build_language_sample(
        message_text: str,
        *,
        attachment_context: dict | None = None,
    ) -> str:
        parts = [message_text or ""]
        if attachment_context:
            parts.append(str(attachment_context.get("summary") or ""))
            parts.append(str(attachment_context.get("visible_text") or ""))
            parts.append(str(attachment_context.get("preview") or ""))
            issue_signals = attachment_context.get("issue_signals") or []
            if isinstance(issue_signals, list):
                parts.extend(str(item) for item in issue_signals)
        return "\n".join(part for part in parts if part).strip()

    @staticmethod
    def _fallback_attachment_reply(
        *,
        language: str | None,
        attachment_context: dict | None,
    ) -> str | None:
        if not attachment_context:
            return None

        kind = (attachment_context.get("kind") or "").strip().lower()
        if kind == "image":
            summary = str(attachment_context.get("summary") or "").strip()
            visible_text = str(attachment_context.get("visible_text") or "").strip()
            issue_signals = [
                str(item).strip()
                for item in (attachment_context.get("issue_signals") or [])
                if str(item).strip()
            ]
            focus = str(attachment_context.get("suggested_focus") or "").strip()
            if not any((summary, visible_text, issue_signals, focus)):
                return None

            def ensure_sentence(value: str) -> str:
                cleaned = value.strip()
                if not cleaned:
                    return ""
                if cleaned.endswith((".", "!", "?")):
                    return cleaned
                return f"{cleaned}."

            lang = (language or "en").lower()
            if lang == "fr":
                parts: list[str] = []
                if summary:
                    parts.append(ensure_sentence(summary))
                if visible_text:
                    parts.append(f"Texte visible : {ensure_sentence(visible_text)}")
                if issue_signals:
                    parts.append(ensure_sentence(issue_signals[0]))
                if focus:
                    parts.append(f"Le point principal semble etre : {ensure_sentence(focus)}")
                parts.append("Dites-moi ce que vous voulez faire sur cette image et je vous guiderai.")
                return " ".join(part for part in parts if part)

            if lang == "ar":
                parts = []
                if summary:
                    parts.append(ensure_sentence(summary))
                if visible_text:
                    parts.append(f"Visible text: {ensure_sentence(visible_text)}")
                if issue_signals:
                    parts.append(ensure_sentence(issue_signals[0]))
                if focus:
                    parts.append(f"The main support focus looks like: {ensure_sentence(focus)}")
                parts.append("Tell me what you want to do on this screen and I will guide you.")
                return " ".join(part for part in parts if part)

            parts = []
            if summary:
                parts.append(ensure_sentence(summary))
            if visible_text:
                parts.append(f"Visible text: {ensure_sentence(visible_text)}")
            if issue_signals:
                parts.append(ensure_sentence(issue_signals[0]))
            if focus:
                parts.append(f"The main support focus looks like: {ensure_sentence(focus)}")
            parts.append("Tell me what you want to do on this screen and I will guide you.")
            return " ".join(part for part in parts if part)

        filename = str(attachment_context.get("filename") or "the file").strip()
        preview = str(attachment_context.get("preview") or "").strip()
        compact_preview = re.sub(r"\s+", " ", preview).strip()[:260].rstrip()
        lang = (language or "en").lower()

        if kind in {"text", "pdf"} and compact_preview:
            if lang == "fr":
                return (
                    f"J'ai pu lire une partie de {filename}. Voici l'essentiel : {compact_preview}. "
                    "Dites-moi ce que vous voulez verifier ou faire avec ce fichier et je vous aiderai."
                )
            return (
                f"I could read part of {filename}. Here is the key text: {compact_preview}. "
                "Tell me what you want to check or do with this file and I will help."
            )

        if lang == "fr":
            return (
                f"J'ai bien recu {filename}. Je ne vais pas le decrire comme une image. "
                "Dites-moi quelle partie de ce fichier vous voulez verifier et je vous aiderai."
            )
        return (
            f"I received {filename}. I will treat it as a file, not an image. "
            "Tell me which part you want help with and I will guide you."
        )

    @staticmethod
    def _pick_best_sentence(query: str, hits: list[dict]) -> str:
        query_terms = [
            token
            for token in re.findall(r"[a-zA-Z0-9]+", (query or "").lower())
            if len(token) >= 4
        ]
        stop_words = {
            "help",
            "with",
            "your",
            "need",
            "have",
            "what",
            "when",
            "where",
            "please",
            "about",
        }
        query_terms = [token for token in query_terms if token not in stop_words]

        best_sentence = ""
        best_score = -1
        for hit in hits:
            chunk = " ".join((hit.get("chunk_content") or "").split())
            if not chunk:
                continue
            for sentence in re.split(r"(?<=[.!?])\s+", chunk):
                candidate = sentence.strip()
                if len(candidate) < 24:
                    continue
                score = sum(1 for token in query_terms if token in candidate.lower())
                if score > best_score:
                    best_score = score
                    best_sentence = candidate

        return best_sentence[:420].rstrip()

    async def _get_or_create_support_bot(self) -> User:
        result = await self.db.execute(
            select(User).where(User.email == SUPPORT_BOT_EMAIL)
        )
        user = result.scalar_one_or_none()
        if user:
            if user.is_deleted:
                user.is_deleted = False
                user.deleted_at = None
            if user.status != UserStatus.ACTIVE:
                user.status = UserStatus.ACTIVE
            if user.role != UserRole.AGENT:
                user.role = UserRole.AGENT
            if user.full_name != SUPPORT_BOT_NAME:
                user.full_name = SUPPORT_BOT_NAME
            return user

        user = User(
            email=SUPPORT_BOT_EMAIL,
            hashed_password=hash_password(uuid.uuid4().hex),
            full_name=SUPPORT_BOT_NAME,
            role=UserRole.AGENT,
            status=UserStatus.ACTIVE,
        )
        self.db.add(user)
        await self.db.flush()
        await self.db.refresh(user)
        return user

    @staticmethod
    def _resolve_auto_reply_tone() -> ResponseTone:
        raw = (settings.AUTO_REPLY_TONE or "").strip().lower()
        try:
            return ResponseTone(raw)
        except ValueError:
            return ResponseTone.PROFESSIONAL

    @staticmethod
    def _detect_language(text: str) -> str:
        sample = (text or "").strip().lower()
        if not sample:
            return "en"

        if re.search(r"[\u0600-\u06FF]", sample):
            return "ar"

        french_markers = (
            "bonjour",
            "merci",
            "probleme",
            "comment",
            "pourquoi",
            "quelle",
            "votre",
            "commande",
            "facture",
        )
        if any(marker in sample for marker in french_markers):
            return "fr"

        return "en"

    @staticmethod
    def _default_support_reply(language: str | None) -> str:
        lang = (language or "en").lower()
        if lang == "fr":
            return (
                "Je n'ai pas trouve une reponse assez precise dans la base de connaissances "
                "pour le moment. Un agent support peut reprendre cette conversation si vous "
                "avez besoin d'une verification plus poussee."
            )
        if lang == "ar":
            return (
                "I could not find a precise enough answer in the knowledge base just yet. "
                "A support agent can continue this conversation if you need a deeper review."
            )
        return (
            "I could not find a precise enough answer in the knowledge base just yet. "
            "A support agent can continue this conversation if you need a deeper review."
        )

    @classmethod
    async def generate_support_reply_for_message(
        cls,
        conversation_id: uuid.UUID,
        customer_id: uuid.UUID,
        latest_message_id: uuid.UUID,
    ) -> None:
        async with async_session_factory() as db:
            try:
                svc = cls(db)
                conversation = await svc.get_conversation(conversation_id)
                customer = await svc.get_user(customer_id)
                latest_message = await svc.get_message(latest_message_id)

                if not conversation or not customer or not latest_message:
                    return

                if conversation.user_id != customer.id:
                    return

                if await svc.has_support_reply_after(
                    conversation_id=conversation_id,
                    customer_id=customer_id,
                    latest_message=latest_message,
                ):
                    return

                await svc.generate_support_reply(conversation, customer, latest_message)

                # Re-evaluate playbooks after assistant reply so ticket summary stays in sync.
                from app.services.conversation_playbook_service import ConversationPlaybookService

                await ConversationPlaybookService(db).get_predictor(
                    conversation_id,
                    event="support_reply",
                    auto_apply_playbook=True,
                )
                await db.commit()
            except Exception:
                await db.rollback()
                logger.exception(
                    "Background conversation auto-reply failed for conversation %s",
                    conversation_id,
                )
