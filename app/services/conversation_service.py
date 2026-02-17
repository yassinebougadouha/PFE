"""
Conversation service — CRUD for chat conversations & messages.
"""

import uuid
from typing import Optional

from sqlalchemy import select, func
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.conversation import Conversation, Message
from app.db.models.enums import ConversationStatus, ChannelType
from app.schemas.conversation import ConversationCreate, ConversationUpdate, MessageCreate


class ConversationService:

    def __init__(self, db: AsyncSession):
        self.db = db

    # ── Conversations ────────────────────────────────────

    async def create_conversation(self, user_id: uuid.UUID, payload: ConversationCreate) -> Conversation:
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

    async def list_conversations(
        self,
        user_id: Optional[uuid.UUID] = None,
        status: Optional[ConversationStatus] = None,
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

        total = (await self.db.execute(count_q)).scalar() or 0
        result = await self.db.execute(
            query.offset(skip).limit(limit).order_by(Conversation.updated_at.desc())
        )
        return list(result.scalars().all()), total

    async def update_conversation(
        self, conversation_id: uuid.UUID, payload: ConversationUpdate,
    ) -> Optional[Conversation]:
        conv = await self.get_conversation(conversation_id)
        if not conv:
            return None
        for field, value in payload.model_dump(exclude_unset=True).items():
            setattr(conv, field, value)
        await self.db.flush()
        await self.db.refresh(conv)
        return conv

    # ── Messages ─────────────────────────────────────────

    async def add_message(
        self, conversation_id: uuid.UUID, sender_id: uuid.UUID, payload: MessageCreate,
    ) -> Message:
        msg = Message(
            conversation_id=conversation_id,
            sender_id=sender_id,
            content=payload.content,
            is_internal=payload.is_internal,
        )
        self.db.add(msg)
        await self.db.flush()
        await self.db.refresh(msg)
        return msg

    async def get_messages(
        self, conversation_id: uuid.UUID, skip: int = 0, limit: int = 100,
    ) -> list[Message]:
        result = await self.db.execute(
            select(Message)
            .where(Message.conversation_id == conversation_id)
            .order_by(Message.created_at.asc())
            .offset(skip)
            .limit(limit)
        )
        return list(result.scalars().all())
