<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. alembic_version
        Schema::create('alembic_version', function (Blueprint $table) {
            $table->string('version_num', 32)->primary();
        });

        // 2. conversations
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('channel', 50);
            $table->string('status', 50);
            $table->string('subject', 500)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_pinned')->default(false)->index();
            $table->boolean('ai_auto_reply_enabled')->default(true)->index();
            $table->timestampTz('ai_auto_reply_paused_until')->nullable()->index();
            $table->timestampTz('sla_snoozed_until')->nullable()->index();
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampsTz();
        });

        // 3. conversation_snippets
        Schema::create('conversation_snippets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 120)->index();
            $table->text('body');
            $table->string('description', 300)->nullable();
            $table->string('shortcut', 32)->nullable()->index();
            $table->string('channel', 50)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->uuid('created_by_id')->nullable()->index();
            $table->uuid('updated_by_id')->nullable()->index();
            $table->timestampsTz();
        });

        // 4. messages
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id')->index();
            $table->uuid('sender_id')->index();
            $table->text('content');
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_read')->default(false)->index();
            $table->string('attachment_path', 1000)->nullable();
            $table->string('attachment_filename', 500)->nullable();
            $table->string('attachment_content_type', 255)->nullable();
            $table->integer('attachment_size')->nullable();
            $table->timestampsTz();
        });

        // 5. emails
        Schema::create('emails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sender_address', 320)->index();
            $table->string('recipient_address', 320);
            $table->string('subject', 500);
            $table->text('body');
            $table->text('raw_headers')->nullable();
            $table->string('status', 50);
            $table->string('gmail_message_id', 255)->nullable()->unique();
            $table->string('gmail_thread_id', 255)->nullable()->index();
            $table->boolean('is_outbound')->default(false)->index();
            $table->boolean('is_read')->default(false)->index();
            $table->boolean('is_starred')->default(false)->index();
            $table->jsonb('labels')->default('[]');
            $table->uuid('in_reply_to_id')->nullable();
            $table->uuid('replied_by_id')->nullable();
            $table->timestampsTz();
        });

        // 6. gmail_credentials
        Schema::create('gmail_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('gmail_address', 320);
            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('token_uri', 500);
            $table->text('scopes');
            $table->boolean('is_active')->default(true);
            $table->string('last_history_id', 50)->nullable();
            $table->timestampsTz();
        });

        // 7. knowledge_articles
        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 500)->index();
            $table->text('content');
            $table->text('summary')->nullable();
            $table->string('category', 100)->index();
            $table->string('status', 50)->default('DRAFT')->index();
            $table->json('tags')->default('[]');
            $table->string('source', 255)->nullable();
            $table->string('language', 10)->default('en');
            $table->json('metadata_extra')->default('{}');
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->boolean('is_indexed')->default(false);
            $table->integer('chunk_count')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->boolean('is_deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampsTz();
        });

        // 8. article_chunks (pgvector)
        DB::statement('
            CREATE TABLE article_chunks (
                id uuid PRIMARY KEY,
                article_id uuid NOT NULL REFERENCES knowledge_articles(id) ON DELETE CASCADE,
                chunk_index integer NOT NULL,
                content text NOT NULL,
                token_count integer NOT NULL DEFAULT 0,
                embedding vector(384),
                status varchar(50) NOT NULL DEFAULT \'PENDING\',
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
        ');
        DB::statement('CREATE UNIQUE INDEX ix_chunks_article_index ON article_chunks (article_id, chunk_index)');
        DB::statement('CREATE INDEX ix_article_chunks_article_id ON article_chunks (article_id)');
        DB::statement('CREATE INDEX ix_chunks_embedding_cosine ON article_chunks USING hnsw (embedding vector_cosine_ops) WITH (m=16, ef_construction=64)');

        // 9. voice_call_logs
        Schema::create('voice_call_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('room_name', 255)->index();
            $table->string('room_sid', 255)->nullable()->unique();
            $table->text('transcript')->nullable();
            $table->string('audio_file_path', 1024)->nullable();
            $table->double('duration_seconds')->nullable();
            $table->timestampTz('started_at')->default(DB::raw('now()'));
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();
        });

        // 10. screenshots
        Schema::create('screenshots', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('conversation_id')->nullable()->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('filename', 500);
            $table->string('file_path', 1000);
            $table->integer('file_size');
            $table->string('mime_type', 50)->default('image/png');
            $table->boolean('consent')->default(false);
            $table->jsonb('metadata_')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestampTz('deleted_at')->nullable();
            $table->timestampsTz();
        });

        // 11. visual_analyses (pgvector)
        DB::statement('
            CREATE TABLE visual_analyses (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                screenshot_id uuid NOT NULL REFERENCES screenshots(id) ON DELETE CASCADE,
                provider varchar(20) NOT NULL,
                ocr_text text,
                caption text,
                elements jsonb,
                labels jsonb,
                regions jsonb,
                embedding vector(512),
                raw_result jsonb,
                confidence double precision,
                processing_ms integer,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
        ');
        DB::statement('CREATE INDEX ix_visual_analyses_screenshot_id ON visual_analyses (screenshot_id)');
        DB::statement('CREATE INDEX ix_visual_analyses_provider ON visual_analyses (provider)');
        DB::statement('CREATE INDEX ix_visual_analyses_embedding_hnsw ON visual_analyses USING hnsw (embedding vector_cosine_ops) WITH (m=16, ef_construction=64)');

        // 12. ui_states (pgvector)
        DB::statement('
            CREATE TABLE ui_states (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                conversation_id uuid NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
                analysis_id uuid REFERENCES visual_analyses(id) ON DELETE SET NULL,
                screenshot_id uuid REFERENCES screenshots(id) ON DELETE SET NULL,
                state_label varchar(100),
                state_data jsonb,
                embedding vector(512),
                sequence_num integer NOT NULL DEFAULT 0,
                gap_detected boolean NOT NULL DEFAULT false,
                gap_details jsonb,
                gap_severity varchar(50),
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
        ');
        DB::statement('CREATE INDEX ix_ui_states_conversation_seq ON ui_states (conversation_id, sequence_num)');
        DB::statement('CREATE INDEX ix_ui_states_embedding_hnsw ON ui_states USING hnsw (embedding vector_cosine_ops) WITH (m=16, ef_construction=64)');

        // 13. reference_screens (pgvector)
        DB::statement('
            CREATE TABLE reference_screens (
                id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
                name varchar(200) NOT NULL,
                description text,
                screen_key varchar(100) NOT NULL UNIQUE,
                file_path varchar(1000) NOT NULL,
                embedding vector(512),
                expected_elements jsonb,
                expected_ocr_text text,
                created_at timestamptz NOT NULL DEFAULT now(),
                updated_at timestamptz NOT NULL DEFAULT now()
            )
        ');
        DB::statement('CREATE INDEX ix_reference_screens_embedding_hnsw ON reference_screens USING hnsw (embedding vector_cosine_ops) WITH (m=16, ef_construction=64)');

        // 14. agent_skills
        Schema::create('agent_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('agent_id')->index();
            $table->string('skill_category', 100);
            $table->double('proficiency')->default(0.5);
            $table->integer('max_concurrent_tickets')->default(10);
            $table->timestampsTz();
            $table->unique(['agent_id', 'skill_category']);
        });

        // 15. decision_logs
        Schema::create('decision_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->index();
            $table->string('intent_category', 100);
            $table->double('confidence_score');
            $table->string('confidence_level', 50);
            $table->double('risk_score');
            $table->string('risk_level', 50);
            $table->string('decision_outcome', 50)->index();
            $table->uuid('suggested_agent_id')->nullable();
            $table->json('response_suggestions')->nullable();
            $table->text('reasoning')->nullable();
            $table->json('matched_rules')->nullable();
            $table->text('escalation_summary')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_logs');
        Schema::dropIfExists('agent_skills');
        DB::statement('DROP TABLE IF EXISTS reference_screens');
        DB::statement('DROP TABLE IF EXISTS ui_states');
        DB::statement('DROP TABLE IF EXISTS visual_analyses');
        Schema::dropIfExists('screenshots');
        Schema::dropIfExists('voice_call_logs');
        DB::statement('DROP TABLE IF EXISTS article_chunks');
        Schema::dropIfExists('knowledge_articles');
        Schema::dropIfExists('gmail_credentials');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_snippets');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('alembic_version');
    }
};