"""
Vector retriever — performs semantic and hybrid search against the knowledge base.

Uses pgvector's cosine distance operator for similarity search,
with optional keyword (ILIKE) pre-filtering for hybrid mode.
"""

from __future__ import annotations

import logging
from typing import Optional

from sqlalchemy import select, func, text, and_, or_
from sqlalchemy.ext.asyncio import AsyncSession

from app.rag.models import KnowledgeArticle, ArticleChunk, EMBEDDING_DIM
from app.rag.enums import ArticleStatus, ArticleCategory, ChunkStatus
from app.rag.embeddings import embed_text, get_model_name
from app.rag.schemas import (
    SearchRequest,
    SearchHit,
    SearchResponse,
    HybridSearchRequest,
)

logger = logging.getLogger(__name__)


class VectorRetriever:
    """Retrieves relevant knowledge chunks via vector similarity."""

    def __init__(self, db: AsyncSession):
        self.db = db

    # ── Semantic Search ───────────────────────────────────

    async def semantic_search(self, request: SearchRequest) -> SearchResponse:
        """
        Pure semantic search: embed the query, then find the nearest
        chunks using pgvector cosine distance.
        """
        query_embedding = embed_text(request.query)

        # Build the pgvector cosine distance expression
        # cosine_distance = 1 - cosine_similarity → lower is better
        distance_expr = ArticleChunk.embedding.cosine_distance(query_embedding)
        similarity_expr = (1 - distance_expr).label("similarity")

        # Base query: join chunks → articles (only PUBLISHED + INDEXED)
        stmt = (
            select(
                ArticleChunk,
                KnowledgeArticle.title.label("article_title"),
                KnowledgeArticle.category.label("article_category"),
                similarity_expr,
            )
            .join(KnowledgeArticle, ArticleChunk.article_id == KnowledgeArticle.id)
            .where(
                and_(
                    KnowledgeArticle.status == ArticleStatus.PUBLISHED,
                    KnowledgeArticle.is_deleted == False,
                    ArticleChunk.status == ChunkStatus.INDEXED,
                    ArticleChunk.embedding.isnot(None),
                )
            )
        )

        # Optional filters
        if request.category:
            stmt = stmt.where(KnowledgeArticle.category == request.category)
        if request.language:
            stmt = stmt.where(KnowledgeArticle.language == request.language)

        # Order by similarity (desc) = order by distance (asc)
        stmt = stmt.order_by(distance_expr.asc()).limit(request.top_k)

        result = await self.db.execute(stmt)
        rows = result.all()

        hits: list[SearchHit] = []
        for row in rows:
            chunk = row[0]
            article_title = row[1]
            article_category = row[2]
            similarity = float(row[3])

            if similarity < request.min_similarity:
                continue

            hits.append(SearchHit(
                article_id=chunk.article_id,
                article_title=article_title,
                article_category=article_category,
                chunk_id=chunk.id,
                chunk_index=chunk.chunk_index,
                chunk_content=chunk.content if request.include_content else None,
                similarity=round(similarity, 4),
                token_count=chunk.token_count,
            ))

        logger.info(
            "Semantic search: query=%r → %d hits (top_k=%d, min_sim=%.2f)",
            request.query[:80], len(hits), request.top_k, request.min_similarity,
        )

        return SearchResponse(
            query=request.query,
            hits=hits,
            total_hits=len(hits),
            model_used=get_model_name(),
        )

    # ── Hybrid Search ─────────────────────────────────────

    async def hybrid_search(self, request: HybridSearchRequest) -> SearchResponse:
        """
        Hybrid search: combine keyword matching (ILIKE) with semantic
        similarity, using weighted scoring.

        Final score = keyword_weight × keyword_score + (1 - keyword_weight) × semantic_score
        """
        query_embedding = embed_text(request.query)
        keywords = request.query.lower().split()

        # Semantic component
        distance_expr = ArticleChunk.embedding.cosine_distance(query_embedding)
        semantic_score = (1 - distance_expr).label("semantic_score")

        # Keyword component: count how many keywords match the chunk
        keyword_conditions = [
            func.lower(ArticleChunk.content).contains(kw) for kw in keywords
        ]
        # CASE WHEN ... for each keyword, sum them up, normalize
        keyword_match_count = sum(
            func.cast(
                func.lower(ArticleChunk.content).contains(kw),
                type_=func.integer if False else None,
            )
            for kw in keywords
        ) if keywords else text("0")

        # Simpler approach: use ILIKE for pre-filtering, semantic for ranking
        # Build keyword ILIKE condition: any keyword matches
        keyword_filter = or_(
            *[ArticleChunk.content.ilike(f"%{kw}%") for kw in keywords]
        ) if keywords else text("TRUE")

        stmt = (
            select(
                ArticleChunk,
                KnowledgeArticle.title.label("article_title"),
                KnowledgeArticle.category.label("article_category"),
                semantic_score,
            )
            .join(KnowledgeArticle, ArticleChunk.article_id == KnowledgeArticle.id)
            .where(
                and_(
                    KnowledgeArticle.status == ArticleStatus.PUBLISHED,
                    KnowledgeArticle.is_deleted == False,
                    ArticleChunk.status == ChunkStatus.INDEXED,
                    ArticleChunk.embedding.isnot(None),
                )
            )
        )

        # Optional filters
        if request.category:
            stmt = stmt.where(KnowledgeArticle.category == request.category)
        if request.language:
            stmt = stmt.where(KnowledgeArticle.language == request.language)

        # For hybrid: fetch more candidates, then re-rank
        candidate_limit = request.top_k * 3
        stmt = stmt.order_by(distance_expr.asc()).limit(candidate_limit)

        result = await self.db.execute(stmt)
        rows = result.all()

        # Re-rank with hybrid scoring
        scored_hits: list[tuple[SearchHit, float]] = []
        for row in rows:
            chunk = row[0]
            article_title = row[1]
            article_category = row[2]
            sem_score = float(row[3])

            # Simple keyword score: fraction of keywords found in chunk
            kw_score = 0.0
            if keywords:
                chunk_lower = chunk.content.lower()
                matches = sum(1 for kw in keywords if kw in chunk_lower)
                kw_score = matches / len(keywords)

            # Weighted hybrid score
            hybrid_score = (
                request.keyword_weight * kw_score
                + (1 - request.keyword_weight) * sem_score
            )

            if hybrid_score < request.min_similarity:
                continue

            hit = SearchHit(
                article_id=chunk.article_id,
                article_title=article_title,
                article_category=article_category,
                chunk_id=chunk.id,
                chunk_index=chunk.chunk_index,
                chunk_content=chunk.content,
                similarity=round(hybrid_score, 4),
                token_count=chunk.token_count,
            )
            scored_hits.append((hit, hybrid_score))

        # Sort by hybrid score descending, take top_k
        scored_hits.sort(key=lambda x: x[1], reverse=True)
        hits = [h for h, _ in scored_hits[:request.top_k]]

        logger.info(
            "Hybrid search: query=%r → %d hits (kw_weight=%.2f)",
            request.query[:80], len(hits), request.keyword_weight,
        )

        return SearchResponse(
            query=request.query,
            hits=hits,
            total_hits=len(hits),
            model_used=get_model_name(),
        )

    # ── Context builder (for decision engine integration) ──

    async def get_context_for_query(
        self,
        query: str,
        top_k: int = 3,
        category: Optional[ArticleCategory] = None,
    ) -> list[dict]:
        """
        Simplified retrieval for internal use (e.g., decision engine).
        Returns a list of {article_title, chunk_content, similarity} dicts.
        """
        request = SearchRequest(
            query=query,
            top_k=top_k,
            category=category,
            min_similarity=0.3,
            include_content=True,
        )
        response = await self.semantic_search(request)
        return [
            {
                "article_title": hit.article_title,
                "chunk_content": hit.chunk_content,
                "similarity": hit.similarity,
                "article_id": str(hit.article_id),
            }
            for hit in response.hits
        ]
