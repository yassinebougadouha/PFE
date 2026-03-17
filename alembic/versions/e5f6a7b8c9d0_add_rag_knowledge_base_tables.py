"""add RAG knowledge base tables

Revision ID: e5f6a7b8c9d0
Revises: d4e5f6a7b8c9
Create Date: 2026-03-06
"""

from alembic import op

revision = "e5f6a7b8c9d0"
down_revision = "d4e5f6a7b8c9"
branch_labels = None
depends_on = None


def upgrade() -> None:
    # ── Enable pgvector extension ────────────────────────
    op.execute("CREATE EXTENSION IF NOT EXISTS vector;")

    # ── Create enum types ────────────────────────────────
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'article_status') THEN
                CREATE TYPE article_status AS ENUM ('DRAFT', 'PUBLISHED', 'ARCHIVED');
            END IF;
        END $$;
    """)
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'article_category') THEN
                CREATE TYPE article_category AS ENUM
                    ('TECHNICAL', 'BILLING', 'ACCOUNT', 'GENERAL', 'SECURITY',
                     'TROUBLESHOOTING', 'FAQ', 'POLICY', 'ONBOARDING', 'FEATURE_GUIDE');
            END IF;
        END $$;
    """)
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'chunk_status') THEN
                CREATE TYPE chunk_status AS ENUM ('PENDING', 'INDEXED', 'FAILED');
            END IF;
        END $$;
    """)

    # ── Create knowledge_articles table ──────────────────
    op.execute("""
        CREATE TABLE knowledge_articles (
            id UUID PRIMARY KEY,
            title VARCHAR(500) NOT NULL,
            content TEXT NOT NULL,
            summary TEXT,
            category article_category NOT NULL,
            status article_status NOT NULL DEFAULT 'DRAFT',
            tags JSON DEFAULT '[]',
            source VARCHAR(255),
            language VARCHAR(10) NOT NULL DEFAULT 'en',
            metadata_extra JSON DEFAULT '{}',
            created_by UUID REFERENCES users(id),
            updated_by UUID REFERENCES users(id),
            is_indexed BOOLEAN NOT NULL DEFAULT FALSE,
            chunk_count INTEGER NOT NULL DEFAULT 0,
            total_tokens INTEGER NOT NULL DEFAULT 0,
            is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
            deleted_at TIMESTAMPTZ,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );
    """)
    op.execute("CREATE INDEX ix_knowledge_articles_title ON knowledge_articles (title);")
    op.execute("CREATE INDEX ix_knowledge_articles_category ON knowledge_articles (category);")
    op.execute("CREATE INDEX ix_knowledge_articles_status ON knowledge_articles (status);")
    op.execute("CREATE INDEX ix_articles_category_status ON knowledge_articles (category, status);")
    op.execute("CREATE INDEX ix_articles_language ON knowledge_articles (language);")

    # ── Create article_chunks table ──────────────────────
    op.execute("""
        CREATE TABLE article_chunks (
            id UUID PRIMARY KEY,
            article_id UUID NOT NULL REFERENCES knowledge_articles(id) ON DELETE CASCADE,
            chunk_index INTEGER NOT NULL,
            content TEXT NOT NULL,
            token_count INTEGER NOT NULL DEFAULT 0,
            embedding vector(384),
            status chunk_status NOT NULL DEFAULT 'PENDING',
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );
    """)
    op.execute("CREATE INDEX ix_article_chunks_article_id ON article_chunks (article_id);")
    op.execute("CREATE UNIQUE INDEX ix_chunks_article_index ON article_chunks (article_id, chunk_index);")

    # ── Create HNSW index for fast vector search ─────────
    # Using cosine distance operator for normalized embeddings
    op.execute("""
        CREATE INDEX ix_chunks_embedding_cosine
        ON article_chunks
        USING hnsw (embedding vector_cosine_ops)
        WITH (m = 16, ef_construction = 64);
    """)


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS article_chunks;")
    op.execute("DROP TABLE IF EXISTS knowledge_articles;")
    op.execute("DROP TYPE IF EXISTS chunk_status;")
    op.execute("DROP TYPE IF EXISTS article_category;")
    op.execute("DROP TYPE IF EXISTS article_status;")
    # Note: we don't drop the vector extension as other tables may use it
