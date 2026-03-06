"""add decision engine tables

Revision ID: d4e5f6a7b8c9
Revises: c3d4e5f6a7b8
Create Date: 2026-03-10
"""

from alembic import op

revision = "d4e5f6a7b8c9"
down_revision = "c3d4e5f6a7b8"
branch_labels = None
depends_on = None


def upgrade() -> None:
    # ── Create enum types ────────────────────────────────
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'intent_category') THEN
                CREATE TYPE intent_category AS ENUM
                    ('BILLING','TECHNICAL','ACCOUNT','GENERAL','COMPLAINT','FEATURE_REQUEST','SECURITY','URGENT');
            END IF;
        END $$;
    """)
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'confidence_level') THEN
                CREATE TYPE confidence_level AS ENUM ('HIGH','MEDIUM','LOW');
            END IF;
        END $$;
    """)
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'risk_level') THEN
                CREATE TYPE risk_level AS ENUM ('LOW','MEDIUM','HIGH','CRITICAL');
            END IF;
        END $$;
    """)
    op.execute("""
        DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'decision_outcome') THEN
                CREATE TYPE decision_outcome AS ENUM
                    ('AUTO_RESOLVE','SUGGEST_RESPONSE','CLARIFY','ESCALATE_HUMAN','ROUTE_AGENT');
            END IF;
        END $$;
    """)

    # ── Create decision_logs table (raw SQL to avoid metadata conflicts) ──
    op.execute("""
        CREATE TABLE decision_logs (
            id UUID PRIMARY KEY,
            ticket_id UUID NOT NULL REFERENCES tickets(id),
            intent_category intent_category NOT NULL,
            confidence_score FLOAT NOT NULL,
            confidence_level confidence_level NOT NULL,
            risk_score FLOAT NOT NULL,
            risk_level risk_level NOT NULL,
            decision_outcome decision_outcome NOT NULL,
            suggested_agent_id UUID REFERENCES users(id),
            response_suggestions JSON,
            reasoning TEXT,
            matched_rules JSON,
            escalation_summary TEXT,
            created_at TIMESTAMPTZ NOT NULL,
            updated_at TIMESTAMPTZ NOT NULL
        );
    """)
    op.execute("CREATE INDEX ix_decision_logs_ticket_id ON decision_logs (ticket_id);")
    op.execute("CREATE INDEX ix_decision_logs_ticket_created ON decision_logs (ticket_id, created_at);")
    op.execute("CREATE INDEX ix_decision_logs_outcome ON decision_logs (decision_outcome);")

    # ── Create agent_skills table ────────────────────────
    op.execute("""
        CREATE TABLE agent_skills (
            id UUID PRIMARY KEY,
            agent_id UUID NOT NULL REFERENCES users(id),
            skill_category intent_category NOT NULL,
            proficiency FLOAT NOT NULL DEFAULT 0.5,
            max_concurrent_tickets INTEGER NOT NULL DEFAULT 10,
            created_at TIMESTAMPTZ NOT NULL,
            updated_at TIMESTAMPTZ NOT NULL
        );
    """)
    op.execute("CREATE INDEX ix_agent_skills_agent_id ON agent_skills (agent_id);")
    op.execute("CREATE UNIQUE INDEX ix_agent_skills_agent_category ON agent_skills (agent_id, skill_category);")


def downgrade() -> None:
    op.execute("DROP TABLE IF EXISTS agent_skills;")
    op.execute("DROP TABLE IF EXISTS decision_logs;")
    op.execute("DROP TYPE IF EXISTS decision_outcome;")
    op.execute("DROP TYPE IF EXISTS risk_level;")
    op.execute("DROP TYPE IF EXISTS confidence_level;")
    op.execute("DROP TYPE IF EXISTS intent_category;")
