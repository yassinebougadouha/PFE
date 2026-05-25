--
-- PostgreSQL database dump
--

\restrict 4aA28SZhMq6NXSvevnMRW20cUSXmdUexDuM6acOVNRBCOcUhWIXxj1fhuW36qgm

-- Dumped from database version 16.10 (Debian 16.10-1.pgdg12+1)
-- Dumped by pg_dump version 16.13 (Ubuntu 16.13-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: agent_skills; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.agent_skills (
    id uuid NOT NULL,
    agent_id uuid NOT NULL,
    skill_category public.intent_category NOT NULL,
    proficiency double precision DEFAULT 0.5 NOT NULL,
    max_concurrent_tickets integer DEFAULT 10 NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL
);


ALTER TABLE public.agent_skills OWNER TO postgres;

--
-- Name: alembic_version; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.alembic_version (
    version_num character varying(32) NOT NULL
);


ALTER TABLE public.alembic_version OWNER TO postgres;

--
-- Name: article_chunks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.article_chunks (
    id uuid NOT NULL,
    article_id uuid NOT NULL,
    chunk_index integer NOT NULL,
    content text NOT NULL,
    token_count integer DEFAULT 0 NOT NULL,
    embedding public.vector(384),
    status public.chunk_status DEFAULT 'PENDING'::public.chunk_status NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.article_chunks OWNER TO postgres;

--
-- Name: conversation_snippets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conversation_snippets (
    title character varying(120) NOT NULL,
    body text NOT NULL,
    description character varying(300),
    shortcut character varying(32),
    channel public.channel_type,
    is_active boolean DEFAULT true NOT NULL,
    created_by_id uuid,
    updated_by_id uuid,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    id uuid NOT NULL
);


ALTER TABLE public.conversation_snippets OWNER TO postgres;

--
-- Name: conversations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.conversations (
    user_id uuid NOT NULL,
    channel public.channel_type NOT NULL,
    status public.conversation_status NOT NULL,
    subject character varying(500),
    id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    is_deleted boolean NOT NULL,
    deleted_at timestamp with time zone,
    is_pinned boolean NOT NULL,
    ai_auto_reply_enabled boolean DEFAULT true NOT NULL,
    ai_auto_reply_paused_until timestamp with time zone,
    sla_snoozed_until timestamp with time zone
);


ALTER TABLE public.conversations OWNER TO postgres;

--
-- Name: decision_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.decision_logs (
    id uuid NOT NULL,
    ticket_id uuid NOT NULL,
    intent_category public.intent_category NOT NULL,
    confidence_score double precision NOT NULL,
    confidence_level public.confidence_level NOT NULL,
    risk_score double precision NOT NULL,
    risk_level public.risk_level NOT NULL,
    decision_outcome public.decision_outcome NOT NULL,
    suggested_agent_id uuid,
    response_suggestions json,
    reasoning text,
    matched_rules json,
    escalation_summary text,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL
);


ALTER TABLE public.decision_logs OWNER TO postgres;

--
-- Name: emails; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.emails (
    sender_address character varying(320) NOT NULL,
    recipient_address character varying(320) NOT NULL,
    subject character varying(500) NOT NULL,
    body text NOT NULL,
    raw_headers text,
    status public.email_status NOT NULL,
    id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    gmail_message_id character varying(255),
    gmail_thread_id character varying(255),
    is_outbound boolean DEFAULT false NOT NULL,
    in_reply_to_id uuid,
    replied_by_id uuid,
    is_read boolean DEFAULT false NOT NULL,
    is_starred boolean DEFAULT false NOT NULL,
    labels jsonb DEFAULT '[]'::jsonb NOT NULL
);


ALTER TABLE public.emails OWNER TO postgres;

--
-- Name: gmail_credentials; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.gmail_credentials (
    user_id uuid NOT NULL,
    gmail_address character varying(320) NOT NULL,
    access_token text NOT NULL,
    refresh_token text NOT NULL,
    token_uri character varying(500) NOT NULL,
    scopes text NOT NULL,
    is_active boolean NOT NULL,
    last_history_id character varying(50),
    id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL
);


ALTER TABLE public.gmail_credentials OWNER TO postgres;

--
-- Name: knowledge_articles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.knowledge_articles (
    id uuid NOT NULL,
    title character varying(500) NOT NULL,
    content text NOT NULL,
    summary text,
    category public.article_category NOT NULL,
    status public.article_status DEFAULT 'DRAFT'::public.article_status NOT NULL,
    tags json DEFAULT '[]'::json,
    source character varying(255),
    language character varying(10) DEFAULT 'en'::character varying NOT NULL,
    metadata_extra json DEFAULT '{}'::json,
    created_by uuid,
    updated_by uuid,
    is_indexed boolean DEFAULT false NOT NULL,
    chunk_count integer DEFAULT 0 NOT NULL,
    total_tokens integer DEFAULT 0 NOT NULL,
    is_deleted boolean DEFAULT false NOT NULL,
    deleted_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.knowledge_articles OWNER TO postgres;

--
-- Name: messages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.messages (
    conversation_id uuid NOT NULL,
    sender_id uuid NOT NULL,
    content text NOT NULL,
    is_internal boolean NOT NULL,
    id uuid NOT NULL,
    created_at timestamp with time zone NOT NULL,
    updated_at timestamp with time zone NOT NULL,
    is_read boolean DEFAULT false NOT NULL,
    attachment_path character varying(1000),
    attachment_filename character varying(500),
    attachment_content_type character varying(255),
    attachment_size integer
);


ALTER TABLE public.messages OWNER TO postgres;

--
-- Name: reference_screens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reference_screens (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    name character varying(200) NOT NULL,
    description text,
    screen_key character varying(100) NOT NULL,
    file_path character varying(1000) NOT NULL,
    embedding public.vector(512),
    expected_elements jsonb,
    expected_ocr_text text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.reference_screens OWNER TO postgres;

--
-- Name: screenshots; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.screenshots (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    conversation_id uuid,
    user_id uuid,
    filename character varying(500) NOT NULL,
    file_path character varying(1000) NOT NULL,
    file_size integer NOT NULL,
    mime_type character varying(50) DEFAULT 'image/png'::character varying NOT NULL,
    consent boolean DEFAULT false NOT NULL,
    metadata_ jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    is_deleted boolean DEFAULT false NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE public.screenshots OWNER TO postgres;

--
-- Name: ui_states; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ui_states (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    conversation_id uuid NOT NULL,
    analysis_id uuid,
    screenshot_id uuid,
    state_label character varying(100),
    state_data jsonb,
    embedding public.vector(512),
    sequence_num integer DEFAULT 0 NOT NULL,
    gap_detected boolean DEFAULT false NOT NULL,
    gap_details jsonb,
    gap_severity public.gap_severity,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.ui_states OWNER TO postgres;

--
-- Name: visual_analyses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.visual_analyses (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    screenshot_id uuid NOT NULL,
    provider character varying(20) NOT NULL,
    ocr_text text,
    caption text,
    elements jsonb,
    labels jsonb,
    regions jsonb,
    embedding public.vector(512),
    raw_result jsonb,
    confidence double precision,
    processing_ms integer,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.visual_analyses OWNER TO postgres;

--
-- Name: voice_call_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.voice_call_logs (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    room_name character varying(255) NOT NULL,
    room_sid character varying(255),
    transcript text,
    audio_file_path character varying(1024),
    duration_seconds double precision,
    started_at timestamp with time zone DEFAULT now() NOT NULL,
    ended_at timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.voice_call_logs OWNER TO postgres;

--
-- Name: agent_skills agent_skills_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_skills
    ADD CONSTRAINT agent_skills_pkey PRIMARY KEY (id);


--
-- Name: alembic_version alembic_version_pkc; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.alembic_version
    ADD CONSTRAINT alembic_version_pkc PRIMARY KEY (version_num);


--
-- Name: article_chunks article_chunks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.article_chunks
    ADD CONSTRAINT article_chunks_pkey PRIMARY KEY (id);


--
-- Name: conversation_snippets conversation_snippets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversation_snippets
    ADD CONSTRAINT conversation_snippets_pkey PRIMARY KEY (id);


--
-- Name: conversations conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_pkey PRIMARY KEY (id);


--
-- Name: decision_logs decision_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.decision_logs
    ADD CONSTRAINT decision_logs_pkey PRIMARY KEY (id);


--
-- Name: emails emails_gmail_message_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.emails
    ADD CONSTRAINT emails_gmail_message_id_key UNIQUE (gmail_message_id);


--
-- Name: emails emails_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.emails
    ADD CONSTRAINT emails_pkey PRIMARY KEY (id);


--
-- Name: gmail_credentials gmail_credentials_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gmail_credentials
    ADD CONSTRAINT gmail_credentials_pkey PRIMARY KEY (id);


--
-- Name: knowledge_articles knowledge_articles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.knowledge_articles
    ADD CONSTRAINT knowledge_articles_pkey PRIMARY KEY (id);


--
-- Name: messages messages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_pkey PRIMARY KEY (id);


--
-- Name: reference_screens reference_screens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reference_screens
    ADD CONSTRAINT reference_screens_pkey PRIMARY KEY (id);


--
-- Name: reference_screens reference_screens_screen_key_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reference_screens
    ADD CONSTRAINT reference_screens_screen_key_key UNIQUE (screen_key);


--
-- Name: screenshots screenshots_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.screenshots
    ADD CONSTRAINT screenshots_pkey PRIMARY KEY (id);


--
-- Name: ui_states ui_states_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ui_states
    ADD CONSTRAINT ui_states_pkey PRIMARY KEY (id);


--
-- Name: visual_analyses visual_analyses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.visual_analyses
    ADD CONSTRAINT visual_analyses_pkey PRIMARY KEY (id);


--
-- Name: voice_call_logs voice_call_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.voice_call_logs
    ADD CONSTRAINT voice_call_logs_pkey PRIMARY KEY (id);


--
-- Name: voice_call_logs voice_call_logs_room_sid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.voice_call_logs
    ADD CONSTRAINT voice_call_logs_room_sid_key UNIQUE (room_sid);


--
-- Name: ix_agent_skills_agent_category; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ix_agent_skills_agent_category ON public.agent_skills USING btree (agent_id, skill_category);


--
-- Name: ix_agent_skills_agent_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_agent_skills_agent_id ON public.agent_skills USING btree (agent_id);


--
-- Name: ix_article_chunks_article_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_article_chunks_article_id ON public.article_chunks USING btree (article_id);


--
-- Name: ix_articles_category_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_articles_category_status ON public.knowledge_articles USING btree (category, status);


--
-- Name: ix_articles_language; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_articles_language ON public.knowledge_articles USING btree (language);


--
-- Name: ix_chunks_article_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ix_chunks_article_index ON public.article_chunks USING btree (article_id, chunk_index);


--
-- Name: ix_chunks_embedding_cosine; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_chunks_embedding_cosine ON public.article_chunks USING hnsw (embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: ix_conversation_snippets_channel; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversation_snippets_channel ON public.conversation_snippets USING btree (channel);


--
-- Name: ix_conversation_snippets_channel_active; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversation_snippets_channel_active ON public.conversation_snippets USING btree (channel, is_active);


--
-- Name: ix_conversation_snippets_created_by_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversation_snippets_created_by_id ON public.conversation_snippets USING btree (created_by_id);


--
-- Name: ix_conversation_snippets_is_active; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversation_snippets_is_active ON public.conversation_snippets USING btree (is_active);


--
-- Name: ix_conversation_snippets_shortcut; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversation_snippets_shortcut ON public.conversation_snippets USING btree (shortcut);


--
-- Name: ix_conversation_snippets_title; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversation_snippets_title ON public.conversation_snippets USING btree (title);


--
-- Name: ix_conversation_snippets_updated_by_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversation_snippets_updated_by_id ON public.conversation_snippets USING btree (updated_by_id);


--
-- Name: ix_conversations_ai_auto_reply_enabled; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_ai_auto_reply_enabled ON public.conversations USING btree (ai_auto_reply_enabled);


--
-- Name: ix_conversations_ai_auto_reply_paused_until; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_ai_auto_reply_paused_until ON public.conversations USING btree (ai_auto_reply_paused_until);


--
-- Name: ix_conversations_deleted_channel_pinned_updated; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_deleted_channel_pinned_updated ON public.conversations USING btree (is_deleted, channel, is_pinned, updated_at DESC);


--
-- Name: ix_conversations_is_pinned; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_is_pinned ON public.conversations USING btree (is_pinned);


--
-- Name: ix_conversations_sla_snoozed_until; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_sla_snoozed_until ON public.conversations USING btree (sla_snoozed_until);


--
-- Name: ix_conversations_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_user_id ON public.conversations USING btree (user_id);


--
-- Name: ix_conversations_user_pinned_updated; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_user_pinned_updated ON public.conversations USING btree (user_id, is_pinned, updated_at);


--
-- Name: ix_conversations_user_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_conversations_user_status ON public.conversations USING btree (user_id, status);


--
-- Name: ix_decision_logs_outcome; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_decision_logs_outcome ON public.decision_logs USING btree (decision_outcome);


--
-- Name: ix_decision_logs_ticket_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_decision_logs_ticket_created ON public.decision_logs USING btree (ticket_id, created_at);


--
-- Name: ix_decision_logs_ticket_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_decision_logs_ticket_id ON public.decision_logs USING btree (ticket_id);


--
-- Name: ix_emails_gmail_thread; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_gmail_thread ON public.emails USING btree (gmail_thread_id);


--
-- Name: ix_emails_gmail_thread_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_gmail_thread_id ON public.emails USING btree (gmail_thread_id);


--
-- Name: ix_emails_is_read; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_is_read ON public.emails USING btree (is_read);


--
-- Name: ix_emails_is_starred; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_is_starred ON public.emails USING btree (is_starred);


--
-- Name: ix_emails_outbound_created_replied_by; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_outbound_created_replied_by ON public.emails USING btree (is_outbound, created_at, replied_by_id);


--
-- Name: ix_emails_read_starred; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_read_starred ON public.emails USING btree (is_read, is_starred);


--
-- Name: ix_emails_sender_address; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_sender_address ON public.emails USING btree (sender_address);


--
-- Name: ix_emails_sender_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_emails_sender_status ON public.emails USING btree (sender_address, status);


--
-- Name: ix_gmail_credentials_active; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_gmail_credentials_active ON public.gmail_credentials USING btree (user_id, is_active);


--
-- Name: ix_gmail_credentials_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ix_gmail_credentials_user_id ON public.gmail_credentials USING btree (user_id);


--
-- Name: ix_knowledge_articles_category; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_knowledge_articles_category ON public.knowledge_articles USING btree (category);


--
-- Name: ix_knowledge_articles_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_knowledge_articles_status ON public.knowledge_articles USING btree (status);


--
-- Name: ix_knowledge_articles_title; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_knowledge_articles_title ON public.knowledge_articles USING btree (title);


--
-- Name: ix_messages_conversation_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_messages_conversation_created ON public.messages USING btree (conversation_id, created_at);


--
-- Name: ix_messages_conversation_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_messages_conversation_id ON public.messages USING btree (conversation_id);


--
-- Name: ix_messages_is_read; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_messages_is_read ON public.messages USING btree (is_read);


--
-- Name: ix_messages_sender_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_messages_sender_id ON public.messages USING btree (sender_id);


--
-- Name: ix_reference_screens_embedding_hnsw; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_reference_screens_embedding_hnsw ON public.reference_screens USING hnsw (embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: ix_screenshots_conversation_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_screenshots_conversation_id ON public.screenshots USING btree (conversation_id);


--
-- Name: ix_screenshots_user_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_screenshots_user_id ON public.screenshots USING btree (user_id);


--
-- Name: ix_ui_states_conversation_seq; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_ui_states_conversation_seq ON public.ui_states USING btree (conversation_id, sequence_num);


--
-- Name: ix_ui_states_embedding_hnsw; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_ui_states_embedding_hnsw ON public.ui_states USING hnsw (embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: ix_visual_analyses_embedding_hnsw; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_visual_analyses_embedding_hnsw ON public.visual_analyses USING hnsw (embedding public.vector_cosine_ops) WITH (m='16', ef_construction='64');


--
-- Name: ix_visual_analyses_provider; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_visual_analyses_provider ON public.visual_analyses USING btree (provider);


--
-- Name: ix_visual_analyses_screenshot_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_visual_analyses_screenshot_id ON public.visual_analyses USING btree (screenshot_id);


--
-- Name: ix_voice_call_logs_room_name; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX ix_voice_call_logs_room_name ON public.voice_call_logs USING btree (room_name);


--
-- Name: agent_skills agent_skills_agent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.agent_skills
    ADD CONSTRAINT agent_skills_agent_id_fkey FOREIGN KEY (agent_id) REFERENCES public.users(id);


--
-- Name: article_chunks article_chunks_article_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.article_chunks
    ADD CONSTRAINT article_chunks_article_id_fkey FOREIGN KEY (article_id) REFERENCES public.knowledge_articles(id) ON DELETE CASCADE;


--
-- Name: conversations conversations_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversations
    ADD CONSTRAINT conversations_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: decision_logs decision_logs_suggested_agent_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.decision_logs
    ADD CONSTRAINT decision_logs_suggested_agent_id_fkey FOREIGN KEY (suggested_agent_id) REFERENCES public.users(id);


--
-- Name: decision_logs decision_logs_ticket_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.decision_logs
    ADD CONSTRAINT decision_logs_ticket_id_fkey FOREIGN KEY (ticket_id) REFERENCES public.tickets(id);


--
-- Name: emails emails_in_reply_to_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.emails
    ADD CONSTRAINT emails_in_reply_to_id_fkey FOREIGN KEY (in_reply_to_id) REFERENCES public.emails(id);


--
-- Name: emails emails_replied_by_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.emails
    ADD CONSTRAINT emails_replied_by_id_fkey FOREIGN KEY (replied_by_id) REFERENCES public.users(id);


--
-- Name: conversation_snippets fk_conversation_snippets_created_by_id_users; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversation_snippets
    ADD CONSTRAINT fk_conversation_snippets_created_by_id_users FOREIGN KEY (created_by_id) REFERENCES public.users(id);


--
-- Name: conversation_snippets fk_conversation_snippets_updated_by_id_users; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.conversation_snippets
    ADD CONSTRAINT fk_conversation_snippets_updated_by_id_users FOREIGN KEY (updated_by_id) REFERENCES public.users(id);


--
-- Name: gmail_credentials gmail_credentials_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gmail_credentials
    ADD CONSTRAINT gmail_credentials_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- Name: knowledge_articles knowledge_articles_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.knowledge_articles
    ADD CONSTRAINT knowledge_articles_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id);


--
-- Name: knowledge_articles knowledge_articles_updated_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.knowledge_articles
    ADD CONSTRAINT knowledge_articles_updated_by_fkey FOREIGN KEY (updated_by) REFERENCES public.users(id);


--
-- Name: messages messages_conversation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_conversation_id_fkey FOREIGN KEY (conversation_id) REFERENCES public.conversations(id);


--
-- Name: messages messages_sender_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.messages
    ADD CONSTRAINT messages_sender_id_fkey FOREIGN KEY (sender_id) REFERENCES public.users(id);


--
-- Name: screenshots screenshots_conversation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.screenshots
    ADD CONSTRAINT screenshots_conversation_id_fkey FOREIGN KEY (conversation_id) REFERENCES public.conversations(id) ON DELETE SET NULL;


--
-- Name: screenshots screenshots_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.screenshots
    ADD CONSTRAINT screenshots_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ui_states ui_states_analysis_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ui_states
    ADD CONSTRAINT ui_states_analysis_id_fkey FOREIGN KEY (analysis_id) REFERENCES public.visual_analyses(id) ON DELETE SET NULL;


--
-- Name: ui_states ui_states_conversation_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ui_states
    ADD CONSTRAINT ui_states_conversation_id_fkey FOREIGN KEY (conversation_id) REFERENCES public.conversations(id) ON DELETE CASCADE;


--
-- Name: ui_states ui_states_screenshot_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ui_states
    ADD CONSTRAINT ui_states_screenshot_id_fkey FOREIGN KEY (screenshot_id) REFERENCES public.screenshots(id) ON DELETE SET NULL;


--
-- Name: visual_analyses visual_analyses_screenshot_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.visual_analyses
    ADD CONSTRAINT visual_analyses_screenshot_id_fkey FOREIGN KEY (screenshot_id) REFERENCES public.screenshots(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 4aA28SZhMq6NXSvevnMRW20cUSXmdUexDuM6acOVNRBCOcUhWIXxj1fhuW36qgm

