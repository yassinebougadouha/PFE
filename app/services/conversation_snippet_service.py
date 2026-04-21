"""
Conversation snippet service for shared assisted-draft macros.
"""

from __future__ import annotations

import uuid
from typing import Optional

from sqlalchemy import func, or_, select
from sqlalchemy.ext.asyncio import AsyncSession

from app.db.models.conversation_snippet import ConversationSnippet
from app.db.models.enums import ChannelType
from app.schemas.conversation import ConversationSnippetCreate, ConversationSnippetUpdate


class ConversationSnippetService:
    def __init__(self, db: AsyncSession):
        self.db = db

    async def list_snippets(
        self,
        *,
        channel: Optional[ChannelType] = None,
        include_inactive: bool = False,
        skip: int = 0,
        limit: int = 200,
    ) -> tuple[list[ConversationSnippet], int]:
        query = select(ConversationSnippet)
        count_query = select(func.count(ConversationSnippet.id))

        if not include_inactive:
            query = query.where(ConversationSnippet.is_active == True)
            count_query = count_query.where(ConversationSnippet.is_active == True)

        if channel is not None:
            channel_filter = or_(ConversationSnippet.channel == channel, ConversationSnippet.channel.is_(None))
            query = query.where(channel_filter)
            count_query = count_query.where(channel_filter)

        total = (await self.db.execute(count_query)).scalar() or 0
        result = await self.db.execute(
            query
            .order_by(ConversationSnippet.title.asc(), ConversationSnippet.created_at.asc())
            .offset(skip)
            .limit(limit)
        )
        return list(result.scalars().all()), int(total)

    async def get_snippet(self, snippet_id: uuid.UUID) -> ConversationSnippet | None:
        result = await self.db.execute(
            select(ConversationSnippet).where(ConversationSnippet.id == snippet_id)
        )
        return result.scalar_one_or_none()

    async def create_snippet(
        self,
        payload: ConversationSnippetCreate,
        *,
        actor_id: uuid.UUID,
    ) -> ConversationSnippet:
        snippet = ConversationSnippet(
            title=payload.title.strip(),
            body=payload.body.strip(),
            description=(payload.description or "").strip() or None,
            shortcut=(payload.shortcut or "").strip().lower() or None,
            channel=payload.channel,
            is_active=payload.is_active,
            created_by_id=actor_id,
            updated_by_id=actor_id,
        )
        self.db.add(snippet)
        await self.db.flush()
        await self.db.refresh(snippet)
        return snippet

    async def update_snippet(
        self,
        snippet_id: uuid.UUID,
        payload: ConversationSnippetUpdate,
        *,
        actor_id: uuid.UUID,
    ) -> ConversationSnippet | None:
        snippet = await self.get_snippet(snippet_id)
        if not snippet:
            return None

        updates = payload.model_dump(exclude_unset=True)
        if "title" in updates and updates["title"] is not None:
            updates["title"] = updates["title"].strip()
        if "body" in updates and updates["body"] is not None:
            updates["body"] = updates["body"].strip()
        if "description" in updates:
            updates["description"] = ((updates["description"] or "").strip() or None)
        if "shortcut" in updates:
            updates["shortcut"] = ((updates["shortcut"] or "").strip().lower() or None)

        for field, value in updates.items():
            setattr(snippet, field, value)

        snippet.updated_by_id = actor_id
        await self.db.flush()
        await self.db.refresh(snippet)
        return snippet

    async def delete_snippet(self, snippet_id: uuid.UUID, *, actor_id: uuid.UUID) -> bool:
        snippet = await self.get_snippet(snippet_id)
        if not snippet:
            return False

        snippet.is_active = False
        snippet.updated_by_id = actor_id
        await self.db.flush()
        return True
