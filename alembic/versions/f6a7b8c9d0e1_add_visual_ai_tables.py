"""
Alembic migration — add Visual AI tables.

Revision ID: f6a7b8c9d0e1
Revises: e5f6a7b8c9d0
"""

from typing import Union

revision = "f6a7b8c9d0e1"
down_revision = "e5f6a7b8c9d0"
branch_labels: Union[str, None] = None
depends_on: Union[str, None] = None

from alembic import op


def upgrade() -> None:
    # ── Ensure pgvector extension ─────────────────────────
    op.execute("CREATE EXTENSION IF NOT EXISTS vector")

    # ── ENUMs ─────────────────────────────────────────────
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'visual_ai_provider') THEN
                CREATE TYPE visual_ai_provider AS ENUM (
                    'local-basic', 'local-advanced', 'google'
                );
            END IF;
        END $$;
    """)

    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gap_severity') THEN
                CREATE TYPE gap_severity AS ENUM (
                    'NO_GAP', 'MINOR', 'SIGNIFICANT', 'CRITICAL'
                );
            END IF;
        END $$;
    """)

    # ── screenshots ───────────────────────────────────────
    op.execute("""
        CREATE TABLE IF NOT EXISTS screenshots (
            id            UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
            conversation_id UUID      REFERENCES conversations(id) ON DELETE SET NULL,
            user_id       UUID        REFERENCES users(id) ON DELETE SET NULL,
            filename      VARCHAR(500)  NOT NULL,
            file_path     VARCHAR(1000) NOT NULL,
            file_size     INTEGER       NOT NULL,
            mime_type     VARCHAR(50)   NOT NULL DEFAULT 'image/png',
            consent       BOOLEAN       NOT NULL DEFAULT false,
            metadata_     JSONB,
            created_at    TIMESTAMPTZ   NOT NULL DEFAULT now(),
            updated_at    TIMESTAMPTZ   NOT NULL DEFAULT now(),
            is_deleted    BOOLEAN       NOT NULL DEFAULT false,
            deleted_at    TIMESTAMPTZ
        );
    """)
    op.execute("CREATE INDEX IF NOT EXISTS ix_screenshots_conversation_id ON screenshots(conversation_id)")
    op.execute("CREATE INDEX IF NOT EXISTS ix_screenshots_user_id ON screenshots(user_id)")

    # ── visual_analyses ───────────────────────────────────
    op.execute("""
        CREATE TABLE IF NOT EXISTS visual_analyses (
            id              UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
            screenshot_id   UUID        NOT NULL REFERENCES screenshots(id) ON DELETE CASCADE,
            provider        VARCHAR(20) NOT NULL,
            ocr_text        TEXT,
            caption         TEXT,
            elements        JSONB,
            labels          JSONB,
            regions         JSONB,
            embedding       vector(512),
            raw_result      JSONB,
            confidence      DOUBLE PRECISION,
            processing_ms   INTEGER,
            created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
            updated_at      TIMESTAMPTZ NOT NULL DEFAULT now()
        );
    """)
    op.execute("CREATE INDEX IF NOT EXISTS ix_visual_analyses_screenshot_id ON visual_analyses(screenshot_id)")
    op.execute("CREATE INDEX IF NOT EXISTS ix_visual_analyses_provider ON visual_analyses(provider)")
    # HNSW index for visual embedding similarity search
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_visual_analyses_embedding_hnsw
        ON visual_analyses
        USING hnsw (embedding vector_cosine_ops)
        WITH (m = 16, ef_construction = 64)
    """)

    # ── ui_states ─────────────────────────────────────────
    op.execute("""
        CREATE TABLE IF NOT EXISTS ui_states (
            id                UUID        PRIMARY KEY DEFAULT gen_random_uuid(),
            conversation_id   UUID        NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
            analysis_id       UUID        REFERENCES visual_analyses(id) ON DELETE SET NULL,
            screenshot_id     UUID        REFERENCES screenshots(id) ON DELETE SET NULL,
            state_label       VARCHAR(100),
            state_data        JSONB,
            embedding         vector(512),
            sequence_num      INTEGER     NOT NULL DEFAULT 0,
            gap_detected      BOOLEAN     NOT NULL DEFAULT false,
            gap_details       JSONB,
            gap_severity      gap_severity,
            created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
            updated_at        TIMESTAMPTZ NOT NULL DEFAULT now()
        );
    """)
    op.execute("CREATE INDEX IF NOT EXISTS ix_ui_states_conversation_seq ON ui_states(conversation_id, sequence_num)")
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_ui_states_embedding_hnsw
        ON ui_states
        USING hnsw (embedding vector_cosine_ops)
        WITH (m = 16, ef_construction = 64)
    """)

    # ── reference_screens ─────────────────────────────────
    op.execute("""
        CREATE TABLE IF NOT EXISTS reference_screens (
            id                  UUID          PRIMARY KEY DEFAULT gen_random_uuid(),
            name                VARCHAR(200)  NOT NULL,
            description         TEXT,
            screen_key          VARCHAR(100)  NOT NULL UNIQUE,
            file_path           VARCHAR(1000) NOT NULL,
            embedding           vector(512),
            expected_elements   JSONB,
            expected_ocr_text   TEXT,
            created_at          TIMESTAMPTZ   NOT NULL DEFAULT now(),
            updated_at          TIMESTAMPTZ   NOT NULL DEFAULT now()
        );
    """)
    op.execute("""
        CREATE INDEX IF NOT EXISTS ix_reference_screens_embedding_hnsw
        ON reference_screens
        USING hnsw (embedding vector_cosine_ops)
        WITH (m = 16, ef_construction = 64)
    """)


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS ui_states CASCADE")
    op.execute("DROP TABLE IF EXISTS visual_analyses CASCADE")
    op.execute("DROP TABLE IF EXISTS screenshots CASCADE")
    op.execute("DROP TABLE IF EXISTS reference_screens CASCADE")
    op.execute("DROP TYPE IF EXISTS gap_severity")
    op.execute("DROP TYPE IF EXISTS visual_ai_provider")
