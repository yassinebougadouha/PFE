--
-- PostgreSQL database dump
--

\restrict htmGrVuYQ9Nmoj98wIXscFhpFFAUfZ3V3iqeDDyzfptr6ygGrxyHQtCF9ve2RbE

-- Dumped from database version 16.13
-- Dumped by pg_dump version 16.13

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
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    user_name character varying(255),
    user_role character varying(255),
    action character varying(255) NOT NULL,
    module character varying(255) NOT NULL,
    description text,
    ip_address character varying(255),
    user_agent character varying(255),
    old_values json,
    new_values json,
    status character varying(255) DEFAULT 'success'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: category_admin_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.category_admin_mappings (
    id bigint NOT NULL,
    category character varying(255) NOT NULL,
    admin_id bigint NOT NULL,
    teams_channel character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: category_admin_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.category_admin_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: category_admin_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.category_admin_mappings_id_seq OWNED BY public.category_admin_mappings.id;


--
-- Name: chat_access_grants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.chat_access_grants (
    id bigint NOT NULL,
    admin_id bigint NOT NULL,
    client_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: chat_access_grants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.chat_access_grants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: chat_access_grants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.chat_access_grants_id_seq OWNED BY public.chat_access_grants.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: glpi_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.glpi_categories (
    id bigint NOT NULL,
    glpi_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    completename character varying(255),
    parent_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: glpi_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.glpi_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: glpi_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.glpi_categories_id_seq OWNED BY public.glpi_categories.id;


--
-- Name: glpi_sync_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.glpi_sync_logs (
    id bigint NOT NULL,
    ticket_id bigint,
    action character varying(255) NOT NULL,
    status character varying(255) NOT NULL,
    payload text,
    response text,
    error text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: glpi_sync_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.glpi_sync_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: glpi_sync_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.glpi_sync_logs_id_seq OWNED BY public.glpi_sync_logs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    type character varying(255) NOT NULL,
    icon character varying(255) DEFAULT 'notifications'::character varying NOT NULL,
    color character varying(255) DEFAULT 'primary'::character varying NOT NULL,
    title character varying(255) NOT NULL,
    body text,
    url character varying(255),
    ticket_id bigint,
    is_read boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.notifications_id_seq OWNED BY public.notifications.id;


--
-- Name: otp_codes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.otp_codes (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    code character varying(6) NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    used boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    type character varying(10) DEFAULT 'email'::character varying NOT NULL,
    phone character varying(20)
);


--
-- Name: COLUMN otp_codes.type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.otp_codes.type IS 'email | sms';


--
-- Name: COLUMN otp_codes.phone; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.otp_codes.phone IS 'Numéro pour OTP SMS';


--
-- Name: otp_codes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.otp_codes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: otp_codes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.otp_codes_id_seq OWNED BY public.otp_codes.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    id bigint NOT NULL,
    key character varying(255) NOT NULL,
    value text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.settings_id_seq OWNED BY public.settings.id;


--
-- Name: ticket_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_comments (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    user_id bigint NOT NULL,
    content text NOT NULL,
    attachment_path character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_comments_id_seq OWNED BY public.ticket_comments.id;


--
-- Name: ticket_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_events (
    id bigint NOT NULL,
    ticket_id bigint NOT NULL,
    action character varying(255) NOT NULL,
    payload json,
    glpi_response json,
    sync_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_events_id_seq OWNED BY public.ticket_events.id;


--
-- Name: tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tickets (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text NOT NULL,
    urgency smallint DEFAULT '3'::smallint NOT NULL,
    impact smallint DEFAULT '3'::smallint NOT NULL,
    priority smallint DEFAULT '3'::smallint NOT NULL,
    glpi_ticket_id bigint,
    sync_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    last_error text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    category character varying(255),
    solution text,
    attachments text,
    solved_by bigint,
    resolved_at timestamp(0) without time zone,
    glpi_category_id bigint,
    glpi_assigned_user_id bigint,
    glpi_resolution_time integer,
    glpi_logs json,
    sla_breached boolean DEFAULT false NOT NULL,
    sla_due_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    assigned_to bigint,
    source character varying(20) DEFAULT 'web'::character varying NOT NULL
);


--
-- Name: tickets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.tickets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: tickets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.tickets_id_seq OWNED BY public.tickets.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    role character varying(255) DEFAULT 'client'::character varying NOT NULL,
    last_login_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    phone character varying(20),
    phone_mobile character varying(20),
    timezone character varying(50) DEFAULT 'Africa/Tunis'::character varying,
    locale character varying(5) DEFAULT 'fr'::character varying,
    whatsapp character varying(20),
    teams_email character varying(255),
    avatar character varying(255),
    profile_completed boolean DEFAULT false NOT NULL,
    glpi_user_id bigint,
    notifications_read json,
    must_change_password boolean DEFAULT false NOT NULL,
    teams_webhook_url character varying(255),
    client_type character varying(20),
    phone_verified boolean DEFAULT false NOT NULL,
    first_name character varying(100),
    last_name character varying(100),
    birthday date,
    gender character varying(20)
);


--
-- Name: COLUMN users.client_type; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.users.client_type IS 'client | user';


--
-- Name: COLUMN users.phone_verified; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.users.phone_verified IS 'True si le numéro a été vérifié par OTP SMS';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: category_admin_mappings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_admin_mappings ALTER COLUMN id SET DEFAULT nextval('public.category_admin_mappings_id_seq'::regclass);


--
-- Name: chat_access_grants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_access_grants ALTER COLUMN id SET DEFAULT nextval('public.chat_access_grants_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: glpi_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.glpi_categories ALTER COLUMN id SET DEFAULT nextval('public.glpi_categories_id_seq'::regclass);


--
-- Name: glpi_sync_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.glpi_sync_logs ALTER COLUMN id SET DEFAULT nextval('public.glpi_sync_logs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: notifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications ALTER COLUMN id SET DEFAULT nextval('public.notifications_id_seq'::regclass);


--
-- Name: otp_codes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.otp_codes ALTER COLUMN id SET DEFAULT nextval('public.otp_codes_id_seq'::regclass);


--
-- Name: settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings ALTER COLUMN id SET DEFAULT nextval('public.settings_id_seq'::regclass);


--
-- Name: ticket_comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments ALTER COLUMN id SET DEFAULT nextval('public.ticket_comments_id_seq'::regclass);


--
-- Name: ticket_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_events ALTER COLUMN id SET DEFAULT nextval('public.ticket_events_id_seq'::regclass);


--
-- Name: tickets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets ALTER COLUMN id SET DEFAULT nextval('public.tickets_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.audit_logs (id, user_id, user_name, user_role, action, module, description, ip_address, user_agent, old_values, new_values, status, created_at, updated_at) FROM stdin;
1	1	superadmin	client	LOGIN	Auth	Connexion: superadmin (yassinebougadouha0@gmail.com) — Rôle: client	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 16:55:18	2026-05-15 16:55:18
2	1	superadmin	client	LOGIN	Auth	Connexion: superadmin (yassinebougadouha0@gmail.com) — Rôle: client	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0	\N	\N	success	2026-05-15 17:03:55	2026-05-15 17:03:55
3	1	superadmin	super_admin	CREATE	Users	Création admin: farah mzoughi (farah.mzough@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 17:18:17	2026-05-15 17:18:17
4	1	superadmin	super_admin	UPDATE	Settings	Mise à jour config GLPI	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 19:14:45	2026-05-15 19:14:45
5	1	superadmin	super_admin	UPDATE	Settings	Mise à jour config GLPI	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 19:15:47	2026-05-15 19:15:47
6	1	superadmin	super_admin	UPDATE	Settings	Mise à jour config GLPI	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 19:17:41	2026-05-15 19:17:41
7	1	superadmin	super_admin	IMPORT	Users	Import GLPI: 0 crees, 0 existants, 0 ignores	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 19:18:08	2026-05-15 19:18:08
8	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Configuration SMTP/notifications mise à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 19:49:39	2026-05-15 19:49:39
9	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Logo application mis à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 19:54:48	2026-05-15 19:54:48
10	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Logo application mis à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 20:02:41	2026-05-15 20:02:41
11	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Logo application mis à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 20:02:42	2026-05-15 20:02:42
12	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Configuration SMTP/notifications mise à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 20:33:59	2026-05-15 20:33:59
13	1	superadmin	super_admin	DELETE USER	Users	Suppression admin: farah mzoughi (farah.mzough@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-15 20:50:33	2026-05-15 20:50:33
14	1	superadmin	super_admin	CREATE	Users	Création admin: farah mzoughi (farah.mzough@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 20:55:56	2026-05-15 20:55:56
15	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Satr YabFam (satryabfam@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 21:59:55	2026-05-15 21:59:55
16	\N	Système	system	CREATE	Tickets	Ticket #1 créé depuis email de satryabfam@gmail.com: probleme api	127.0.0.1	Symfony	\N	\N	success	2026-05-15 21:59:59	2026-05-15 21:59:59
17	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Configuration SMTP/notifications mise à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 22:04:19	2026-05-15 22:04:19
18	\N	Système	system	CREATE	Tickets	Ticket #2 créé depuis email de satryabfam@gmail.com: cc	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:16:32	2026-05-15 22:16:32
19	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Configuration SMTP/notifications mise à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 22:27:15	2026-05-15 22:27:15
20	1	superadmin	super_admin	DELETE USER	Users	Suppression client: Satr YabFam (satryabfam@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-15 22:28:02	2026-05-15 22:28:02
21	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Satr YabFam (satryabfam@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:32:02	2026-05-15 22:32:02
22	\N	Système	system	CREATE	Tickets	Ticket #3 créé depuis email de satryabfam@gmail.com: Ticket soumis avec succès	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:32:04	2026-05-15 22:32:04
23	\N	Système	system	CREATE	Tickets	Ticket #4 créé depuis email de satryabfam@gmail.com: probleme api	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:32:07	2026-05-15 22:32:07
24	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Mzoughi Farah (farahmzoughi83@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:32:10	2026-05-15 22:32:10
25	\N	Système	system	CREATE	Tickets	Ticket #5 créé depuis email de farahmzoughi83@gmail.com: probleme api	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:32:11	2026-05-15 22:32:11
26	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: eya mzoughi (eyamzoughi597@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:44:21	2026-05-15 22:44:21
27	\N	Système	system	CREATE	Tickets	Ticket #6 créé depuis email de eyamzoughi597@gmail.com: autre	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:44:23	2026-05-15 22:44:23
28	1	superadmin	super_admin	DELETE USER	Users	Suppression client: Satr YabFam (satryabfam@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-15 22:55:16	2026-05-15 22:55:16
29	1	superadmin	super_admin	DELETE USER	Users	Suppression client: Mzoughi Farah (farahmzoughi83@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-15 22:57:37	2026-05-15 22:57:37
30	1	superadmin	super_admin	DELETE USER	Users	Suppression client: eya mzoughi (eyamzoughi597@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-15 22:57:50	2026-05-15 22:57:50
31	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Satr YabFam (satryabfam@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:58:23	2026-05-15 22:58:23
32	\N	Système	system	CREATE	Tickets	Ticket #7 créé depuis email de satryabfam@gmail.com: 0JZF8q0hnWjJ	127.0.0.1	Symfony	\N	\N	success	2026-05-15 22:58:25	2026-05-15 22:58:25
33	1	superadmin	super_admin	DELETE USER	Users	Suppression client: Satr YabFam (satryabfam@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-15 23:13:30	2026-05-15 23:13:30
34	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: eya mzoughi (eyamzoughi597@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 23:16:26	2026-05-15 23:16:26
35	\N	Système	system	CREATE	Tickets	Ticket #8 créé depuis email de eyamzoughi597@gmail.com: demande rapidement.	127.0.0.1	Symfony	\N	\N	success	2026-05-15 23:16:28	2026-05-15 23:16:28
36	1	superadmin	super_admin	DELETE USER	Users	Suppression client: eya mzoughi (eyamzoughi597@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-15 23:23:25	2026-05-15 23:23:25
37	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Satr YabFam (satryabfam@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 23:24:19	2026-05-15 23:24:19
38	\N	Système	system	CREATE	Tickets	Ticket #9 créé depuis email de satryabfam@gmail.com: docker cp	127.0.0.1	Symfony	\N	\N	success	2026-05-15 23:24:20	2026-05-15 23:24:20
39	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Configuration SMS L2T mise à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 23:34:07	2026-05-15 23:34:07
40	1	superadmin	super_admin	UPDATE SETTINGS	Settings	Configuration SMS L2T mise à jour	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-15 23:35:23	2026-05-15 23:35:23
41	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: eya mzoughi (eyamzoughi597@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-15 23:54:32	2026-05-15 23:54:32
42	\N	Système	system	CREATE	Tickets	Ticket #10 créé depuis email de eyamzoughi597@gmail.com: cc	127.0.0.1	Symfony	\N	\N	success	2026-05-15 23:54:35	2026-05-15 23:54:35
43	1	superadmin	super_admin	DELETE USER	Users	Suppression client: eya mzoughi (eyamzoughi597@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-16 00:00:18	2026-05-16 00:00:18
44	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: eya mzoughi (eyamzoughi597@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-16 00:02:22	2026-05-16 00:02:22
45	\N	Système	system	CREATE	Tickets	Ticket #11 créé depuis email de eyamzoughi597@gmail.com: bnj	127.0.0.1	Symfony	\N	\N	success	2026-05-16 00:02:23	2026-05-16 00:02:23
46	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Elaa Bougadouha (bougadouhaelaa@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-16 09:42:23	2026-05-16 09:42:23
47	\N	Système	system	CREATE	Tickets	Ticket #12 créé depuis email de bougadouhaelaa@gmail.com: cc	127.0.0.1	Symfony	\N	\N	success	2026-05-16 09:42:25	2026-05-16 09:42:25
48	1	superadmin	super_admin	DELETE USER	Users	Suppression client: Elaa Bougadouha (bougadouhaelaa@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-16 10:36:25	2026-05-16 10:36:25
49	1	superadmin	super_admin	DELETE USER	Users	Suppression client: Satr YabFam (satryabfam@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-16 10:36:49	2026-05-16 10:36:49
50	1	superadmin	super_admin	DELETE USER	Users	Suppression client: محمد نائلة عتيق (farahmzoughi83@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-16 10:37:58	2026-05-16 10:37:58
51	1	superadmin	super_admin	DELETE USER	Users	Suppression client: eya mzoughi (eyamzoughi597@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-16 10:38:21	2026-05-16 10:38:21
52	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Elaa Bougadouha (bougadouhaelaa@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-16 10:38:26	2026-05-16 10:38:26
53	\N	Système	system	CREATE	Tickets	Ticket #13 créé depuis email de bougadouhaelaa@gmail.com: bbb	127.0.0.1	Symfony	\N	\N	success	2026-05-16 10:38:28	2026-05-16 10:38:28
54	1	superadmin	super_admin	DELETE USER	Users	Suppression client: Elaa Bougadouha (bougadouhaelaa@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	warning	2026-05-16 10:52:12	2026-05-16 10:52:12
55	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Mzoughi Salah (mzghsalah@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-16 11:04:25	2026-05-16 11:04:25
56	\N	Système	system	CREATE	Tickets	Ticket #14 créé depuis email de mzghsalah@gmail.com: bnj	127.0.0.1	Symfony	\N	\N	success	2026-05-16 11:04:27	2026-05-16 11:04:27
57	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: Farah Yassine (farahyassine182@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-16 11:28:22	2026-05-16 11:28:22
58	\N	Système	system	CREATE	Tickets	Ticket #15 créé depuis email de farahyassine182@gmail.com: autre	127.0.0.1	Symfony	\N	\N	success	2026-05-16 11:28:23	2026-05-16 11:28:23
59	1	superadmin	super_admin	CREATE	Users	Création admin: naila (nailaattig@gmail.com)	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	\N	\N	success	2026-05-16 11:57:17	2026-05-16 11:57:17
60	\N	Système	system	CREATE	Users	Compte auto-créé depuis email: mzoughi salah (salahmzoughi372@gmail.com)	127.0.0.1	Symfony	\N	\N	success	2026-05-16 12:06:25	2026-05-16 12:06:25
61	\N	Système	system	CREATE	Tickets	Ticket #16 créé depuis email de salahmzoughi372@gmail.com: test	127.0.0.1	Symfony	\N	\N	success	2026-05-16 12:06:26	2026-05-16 12:06:26
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache (key, value, expiration) FROM stdin;
l2t-cache-yassinebougadouha7@gmail.com|172.23.0.1:timer	i:1778860933;	1778860933
l2t-cache-yassinebougadouha7@gmail.com|172.23.0.1	i:1;	1778860933
l2t-cache-bougadouhaelaa@gmail.com|172.23.0.1:timer	i:1778923756;	1778923756
l2t-cache-bougadouhaelaa@gmail.com|172.23.0.1	i:1;	1778923756
l2t-cache-satryabfam@gmail.com|172.23.0.1:timer	i:1778880921;	1778880921
l2t-cache-satryabfam@gmail.com|172.23.0.1	i:2;	1778880921
l2t-cache-eyamzoughi597@gmail.com|172.23.0.1:timer	i:1778882118;	1778882118
l2t-cache-eyamzoughi597@gmail.com|172.23.0.1	i:1;	1778882118
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: category_admin_mappings; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.category_admin_mappings (id, category, admin_id, teams_channel, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: chat_access_grants; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.chat_access_grants (id, admin_id, client_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: glpi_categories; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.glpi_categories (id, glpi_id, name, completename, parent_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: glpi_sync_logs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.glpi_sync_logs (id, ticket_id, action, status, payload, response, error, created_at, updated_at) FROM stdin;
1	\N	create_user	success	{"name":"farah.mzough@gmail.com","realname":"farah mzoughi","email":"farah.mzough@gmail.com","password":"Admin1234.","is_active":1}	{"id":7,"message":"Item successfully added: farah.mzough@gmail.com"}	\N	2026-05-15 20:55:55	2026-05-15 20:55:55
2	\N	create_user	success	{"name":"satryabfam@gmail.com","realname":"Satr YabFam","email":"satryabfam@gmail.com","password":"0JZF8q0hnWjJ","password2":"0JZF8q0hnWjJ","is_active":1}	{"id":8,"message":"Item successfully added: satryabfam@gmail.com"}	\N	2026-05-15 22:32:02	2026-05-15 22:32:02
3	\N	create	success	{"name":"Ticket soumis avec succ\\u00e8s","content":"Ticket soumis avec succ\\u00e8sTicket soumis avec succ\\u00e8s","urgency":1,"impact":1,"priority":1,"status":1}	{"id":1,"message":"Item successfully added: Ticket soumis avec succ\\u00e8s"}	\N	2026-05-15 22:32:06	2026-05-15 22:32:06
4	\N	create	success	{"name":"probleme api","content":"probleme api","urgency":4,"impact":4,"priority":4,"status":1}	{"id":2,"message":"Item successfully added: probleme api"}	\N	2026-05-15 22:32:09	2026-05-15 22:32:09
5	\N	create_user	success	{"name":"farahmzoughi83@gmail.com","realname":"Mzoughi Farah","email":"farahmzoughi83@gmail.com","password":"jBQRO9RDb3ae","password2":"jBQRO9RDb3ae","is_active":1}	{"id":9,"message":"Item successfully added: farahmzoughi83@gmail.com"}	\N	2026-05-15 22:32:10	2026-05-15 22:32:10
6	\N	create	success	{"name":"probleme api","content":"probleme api","urgency":4,"impact":4,"priority":4,"status":1}	{"id":3,"message":"Item successfully added: probleme api"}	\N	2026-05-15 22:32:17	2026-05-15 22:32:17
7	\N	create_user	success	{"name":"eyamzoughi597@gmail.com","realname":"eya mzoughi","email":"eyamzoughi597@gmail.com","password":"ECl4Mlg13F2a","password2":"ECl4Mlg13F2a","is_active":1}	{"id":10,"message":"Item successfully added: eyamzoughi597@gmail.com"}	\N	2026-05-15 22:44:21	2026-05-15 22:44:21
8	\N	create	success	{"name":"autre","content":"autre","urgency":3,"impact":3,"priority":3,"status":1}	{"id":4,"message":"Item successfully added: autre"}	\N	2026-05-15 22:44:25	2026-05-15 22:44:25
9	\N	create	success	{"name":"0JZF8q0hnWjJ","content":"0JZF8q0hnWjJ","urgency":1,"impact":1,"priority":1,"status":1}	{"id":5,"message":"Item successfully added: 0JZF8q0hnWjJ"}	\N	2026-05-15 22:58:27	2026-05-15 22:58:27
10	\N	create	success	{"name":"demande rapidement.","content":"Nous avons bien re\\u00e7u votre email et cr\\u00e9\\u00e9 automatiquement un compte sur\\r\\nnotre plateforme de support L2T. Votre ticket a \\u00e9t\\u00e9 enregistr\\u00e9 et notre\\r\\n\\u00e9quipe va traiter votre demande rapidement.","urgency":2,"impact":1,"priority":2,"status":1}	{"id":6,"message":"Item successfully added: demande rapidement."}	\N	2026-05-15 23:16:30	2026-05-15 23:16:30
11	\N	create	success	{"name":"docker cp","content":"docker cp\\r\\n\\/mnt\\/c\\/Users\\/MSIPULSE\\/pfe_laravel_back\\/platform-glpi-main\\/app\\/Console\\/Commands\\/FetchSupportEmails.php\\r\\nplatform-glpi-main-laravel.test-1:\\/var\\/www\\/html\\/app\\/Console\\/Commands\\/FetchSupportEmails.php","urgency":2,"impact":2,"priority":3,"status":1}	{"id":7,"message":"Item successfully added: docker cp"}	\N	2026-05-15 23:24:23	2026-05-15 23:24:23
12	\N	create_user	success	{"name":"bougadouhaelaa@gmail.com","realname":"Elaa Bougadouha","email":"bougadouhaelaa@gmail.com","password":"NWq5Y60wDcZS","password2":"NWq5Y60wDcZS","is_active":1}	{"id":11,"message":"Item successfully added: bougadouhaelaa@gmail.com"}	\N	2026-05-16 09:42:22	2026-05-16 09:42:22
13	\N	create_user	success	{"name":"mzghsalah@gmail.com","realname":"Mzoughi Salah","email":"mzghsalah@gmail.com","password":"Lp5JugWzfjHn","password2":"Lp5JugWzfjHn","is_active":1}	{"id":12,"message":"Item successfully added: mzghsalah@gmail.com"}	\N	2026-05-16 11:04:24	2026-05-16 11:04:24
14	\N	create_user	success	{"name":"farahyassine182@gmail.com","realname":"Farah Yassine","email":"farahyassine182@gmail.com","password":"sbUhL5xiaMhI","password2":"sbUhL5xiaMhI","is_active":1}	{"id":13,"message":"Item successfully added: farahyassine182@gmail.com"}	\N	2026-05-16 11:28:21	2026-05-16 11:28:21
15	\N	create_user	success	{"name":"nailaattig@gmail.com","realname":"naila","email":"nailaattig@gmail.com","password":"Admin1234.","password2":"Admin1234.","is_active":1}	{"id":14,"message":"Item successfully added: nailaattig@gmail.com"}	\N	2026-05-16 11:57:17	2026-05-16 11:57:17
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_02_05_083622_add_role_to_users_table	1
5	2026_02_09_185819_create_tickets_table	1
6	2026_02_09_190556_create_ticket_events_table	1
7	2026_02_16_222907_add_super_admin_role	1
8	2026_02_18_000001_create_settings_table	1
9	2026_02_20_000001_create_audit_logs_table	1
10	2026_02_21_add_profile_fields_to_users_table	1
11	2026_02_22_add_fields_to_tickets_table	1
12	2026_02_23_add_solved_by_to_tickets	1
13	2026_02_25_083910_create_otp_codes_table	1
14	2026_02_26_000001_add_contact_fields_to_users_table	1
15	2026_02_26_000001_add_resolved_at_to_tickets	1
16	2026_02_26_000002_create_ticket_comments_table	1
17	2026_03_12_000001_add_glpi_fields	1
18	2026_03_12_210722_add_status_to_tickets_table	1
19	2026_03_22_000001_add_notifications_read_to_users	1
20	2026_03_25_000001_add_assigned_to_tickets	1
21	2026_03_25_000002_create_notifications_table	1
22	2026_03_29_000001_add_source_to_tickets	1
23	2026_03_29_000003_add_must_change_password_to_users	1
24	2026_03_29_000004_create_settings_table	1
25	2026_04_08_000001_add_teams_webhook_to_users	1
26	2026_04_15_000001_add_client_type_to_users_table	1
27	2026_04_15_000002_add_sms_fields	1
28	2026_04_19_000001_create_chat_access_grants_table	1
29	2026_04_25_000001_add_personal_fields_to_users_table	1
\.


--
-- Data for Name: notifications; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.notifications (id, user_id, type, icon, color, title, body, url, ticket_id, is_read, created_at, updated_at) FROM stdin;
1	1	new_admin	admin_panel_settings	primary	Nouvel admin créé : farah mzoughi	farah.mzough@gmail.com	http://localhost:8085/super-admin/admins/3	\N	t	2026-05-15 17:18:17	2026-05-15 17:53:07
2	1	new_admin	admin_panel_settings	primary	Nouvel admin créé : farah mzoughi	farah.mzough@gmail.com	http://localhost:8085/super-admin/admins/4	\N	t	2026-05-15 20:55:56	2026-05-15 20:57:36
3	4	new_ticket	email	info	📧 Ticket #1 via email : probleme api	probleme api	http://localhost:8085/admin/tickets/1	1	t	2026-05-15 21:59:59	2026-05-15 22:02:35
4	4	new_ticket	email	info	📧 Ticket #2 via email : cc	ccc	http://localhost:8085/admin/tickets/2	2	f	2026-05-15 22:16:32	2026-05-15 22:16:32
5	4	new_ticket	email	info	📧 Ticket #3 via email : Ticket soumis avec succès	Ticket soumis avec succèsTicket soumis avec succès	http://localhost:8085/admin/tickets/3	3	f	2026-05-15 22:32:04	2026-05-15 22:32:04
6	4	new_ticket	email	info	📧 Ticket #4 via email : probleme api	probleme api	http://localhost:8085/admin/tickets/4	4	f	2026-05-15 22:32:07	2026-05-15 22:32:07
7	4	new_ticket	email	info	📧 Ticket #5 via email : probleme api	probleme api	http://localhost:8085/admin/tickets/5	5	f	2026-05-15 22:32:11	2026-05-15 22:32:11
8	4	new_ticket	email	info	📧 Ticket #6 via email : autre	autre	http://localhost:8085/admin/tickets/6	6	f	2026-05-15 22:44:23	2026-05-15 22:44:23
9	4	new_ticket	email	info	📧 Ticket #7 via email : 0JZF8q0hnWjJ	0JZF8q0hnWjJ	http://localhost:8085/admin/tickets/7	7	f	2026-05-15 22:58:25	2026-05-15 22:58:25
10	4	new_ticket	email	info	📧 Ticket #8 via email : demande rapidement.	Nous avons bien reçu votre email et créé automatiquement un compte sur\r\nnotre pl...	http://localhost:8085/admin/tickets/8	8	f	2026-05-15 23:16:28	2026-05-15 23:16:28
11	4	new_ticket	email	info	📧 Ticket #9 via email : docker cp	docker cp\r\n/mnt/c/Users/MSIPULSE/pfe_laravel_back/platform-glpi-main/app/Console...	http://localhost:8085/admin/tickets/9	9	f	2026-05-15 23:24:20	2026-05-15 23:24:20
12	4	new_ticket	email	info	📧 Ticket #10 via email : cc	cccc	http://localhost:8085/admin/tickets/10	10	f	2026-05-15 23:54:35	2026-05-15 23:54:35
13	4	new_ticket	email	info	📧 Ticket #11 via email : bnj	bnj	http://localhost:8085/admin/tickets/11	11	f	2026-05-16 00:02:23	2026-05-16 00:02:23
14	4	new_ticket	email	info	📧 Ticket #12 via email : cc	bnj	http://localhost:8085/admin/tickets/12	12	f	2026-05-16 09:42:25	2026-05-16 09:42:25
15	4	new_ticket	email	info	📧 Ticket #13 via email : bbb	Nous avons bien reçu votre email et créé automatiquement un compte sur\r\nnotre pl...	http://localhost:8085/admin/tickets/13	13	f	2026-05-16 10:38:28	2026-05-16 10:38:28
16	4	new_ticket	email	info	📧 Ticket #14 via email : bnj	bnjj	http://localhost:8085/admin/tickets/14	14	f	2026-05-16 11:04:27	2026-05-16 11:04:27
17	4	new_ticket	email	info	📧 Ticket #15 via email : autre	test	http://localhost:8085/admin/tickets/15	15	f	2026-05-16 11:28:24	2026-05-16 11:28:24
18	1	new_admin	admin_panel_settings	primary	Nouvel admin créé : naila	nailaattig@gmail.com	http://localhost:8085/super-admin/admins/19	\N	f	2026-05-16 11:57:17	2026-05-16 11:57:17
19	4	new_ticket	email	info	📧 Ticket #16 via email : test	test	http://localhost:8085/admin/tickets/16	16	f	2026-05-16 12:06:26	2026-05-16 12:06:26
20	19	new_ticket	email	info	📧 Ticket #16 via email : test	test	http://localhost:8085/admin/tickets/16	16	f	2026-05-16 12:06:26	2026-05-16 12:06:26
\.


--
-- Data for Name: otp_codes; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.otp_codes (id, email, code, expires_at, used, created_at, updated_at, type, phone) FROM stdin;
4	farahmzoughi83@gmail.com	328863	2026-05-15 23:45:58	t	2026-05-15 23:35:58	2026-05-15 23:36:46	sms	21656626317
3	farahmzoughi83@gmail.com	789432	2026-05-15 23:45:56	t	2026-05-15 23:35:56	2026-05-15 23:36:46	email	\N
5	mzghsalah@gmail.com	345804	2026-05-16 11:37:14	t	2026-05-16 11:27:14	2026-05-16 11:28:13	sms	21656626317
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
Ts5NiGI0dbI1EXK62O4H9RhDfFUmxT9GxR6VPzk5	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoid1FWNzB2cXZwc3o4a0JBcDNpTGRNeTd1UmlGY1VVd1FaTTBGQm85bSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933475
qokMyCpMiFg7p41Lq6VEH3Py4QnJptSf9M3BRMYM	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoia0N4ZVo4eXdnR2NzS3dCcFZ3WFg5TjhYR0UxeUJiQ0dGME4wdnFkSSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933477
xoYqCRzzjP8sArDPKUBx2vhlUB3NCSLqJjNpnnMj	\N	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0	YToyOntzOjY6Il90b2tlbiI7czo0MDoiZnF2aFp5dkluV1pOT2ZQZkVJM3pkdUF5eno0aE15cjhTOVAxYVNZMCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778932996
isJVih49GxUJabmnTQhq85Usis8PbQ4RQEvrGqJH	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoiTGdnMVZDUkIzNkFDU3NPZUZYa3BGZWc2aEtFU1NyT3Zla053cFBESCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933070
o4HOuOM3FoGjQPMRMjAxDtVsvUXLxf1s8N9nd91e	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoiQTh5ckRZNkdGZ3I0dGhDV1VRWnJIb2FUV0xscVRLdlNXMWUxSFFwZSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933475
mppyqRadFAEhXEN8pLn9pNj8pePTkp8fYEMB1Xx4	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoiVk1NYklVM2oyMzZHbWxPaXVUR1NqOXJNa0QxZ1ZQbFVFNUZDbFpVQSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933494
Yafm8UPWtXk24PopYxMjpzQYEAnQim9vKOGNAVVq	17	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoicWtONGoxV2RBYTMxUmoxVzR1emdIWGx3M0EyZ29DNmpWb2NJZmt2ZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4NS9ub3RpZmljYXRpb25zL3BvbGwiO3M6NToicm91dGUiO3M6MTg6Im5vdGlmaWNhdGlvbnMucG9sbCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE3O3M6MTM6InBlbmRpbmdfcGhvbmUiO3M6MTE6IjIxNjU2NjI2MzE3Ijt9	1778940906
wNrzvBx51Lun7glUHOZmuK6Q3NWkW9El1RDBGovK	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoiQUVyWHdsRVVjMVd4bk5oMjJFY0U5ZzFyV3N5ekZ1VUw1M09QSGZlZSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933072
WqqDMa0QSePaB2ZiPlqexiESJHq4zijk9kHnpCYk	17	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0	YTo0OntzOjY6Il90b2tlbiI7czo0MDoidTdudG1kb2ZtS0FJcExjYW5lVTlIUzYzZ0c4bFoxaVJZMU05bmFDaCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4NSI7czo1OiJyb3V0ZSI7czo0OiJob21lIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTc7fQ==	1778933403
b8jRSGQsTxPLPCIhmF5OhoKKsRlAzaGL6hShtq7J	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoiQTFvQWVCZGNDRjFFdTg4a0lWWEdlTUx6cTd2YzREbHZoZ05jbUI3eCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933477
sXfHJtmon1HSyAeGYXLfP7QrVDBNfBd66AMt1U1A	\N	172.23.0.4	python-httpx/0.28.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoiNEtXbHZKa2RoR3FwYWp6VzZWNDE2MjdnVDMwYlBnZTVhRlQ0eTE0SyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1778933498
tLZNwdOXfyXroWzH2ADYsp5qrRXA0H3attvssnTV	1	172.23.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQ0lJa21VczJ4NFJ3VWtja2lnWHA3WTBhRndVM0liZEdQNkltdm1SSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDA6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4NS9ub3RpZmljYXRpb25zL3BvbGwiO3M6NToicm91dGUiO3M6MTg6Im5vdGlmaWNhdGlvbnMucG9sbCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==	1778940906
\.


--
-- Data for Name: settings; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.settings (id, key, value, created_at, updated_at) FROM stdin;
14	sla_très haute	4h	2026-05-15 13:03:05	2026-05-15 13:03:05
15	sla_haute	8h	2026-05-15 13:03:05	2026-05-15 13:03:05
16	sla_moyenne	24h	2026-05-15 13:03:05	2026-05-15 13:03:05
17	sla_basse	48h	2026-05-15 13:03:05	2026-05-15 13:03:05
25	smtp_host	smtp.gmail.com	2026-05-15 13:03:05	2026-05-15 13:03:05
26	smtp_port	587	2026-05-15 13:03:05	2026-05-15 13:03:05
27	smtp_encryption	tls	2026-05-15 13:03:05	2026-05-15 13:03:05
28	smtp_from_name	L2T Support	2026-05-15 13:03:05	2026-05-15 13:03:05
1	app_name	L2T	2026-05-15 13:03:06	2026-05-15 13:03:06
2	support_email	support@l2t.com	2026-05-15 13:03:06	2026-05-15 13:03:06
3	description	Plateforme de gestion des tickets L2T	2026-05-15 13:03:06	2026-05-15 13:03:06
4	locale	fr	2026-05-15 13:03:06	2026-05-15 13:03:06
5	timezone	Africa/Tunis	2026-05-15 13:03:06	2026-05-15 13:03:06
6	primary_color	#667eea	2026-05-15 13:03:06	2026-05-15 13:03:06
7	secondary_color	#764ba2	2026-05-15 13:03:06	2026-05-15 13:03:06
8	theme_mode	light	2026-05-15 13:03:06	2026-05-15 13:03:06
9	sidebar_size	normal	2026-05-15 13:03:06	2026-05-15 13:03:06
10	ticket_label	Ticket	2026-05-15 13:03:06	2026-05-15 13:03:06
11	auto_assignment	0	2026-05-15 13:03:06	2026-05-15 13:03:06
12	auto_assignment_method	Round-robin	2026-05-15 13:03:06	2026-05-15 13:03:06
13	allow_client_close	0	2026-05-15 13:03:06	2026-05-15 13:03:06
18	min_password_length	8	2026-05-15 13:03:06	2026-05-15 13:03:06
19	session_timeout	120	2026-05-15 13:03:06	2026-05-15 13:03:06
20	max_login_attempts	5	2026-05-15 13:03:06	2026-05-15 13:03:06
21	password_complexity	1	2026-05-15 13:03:06	2026-05-15 13:03:06
22	allow_registration	1	2026-05-15 13:03:06	2026-05-15 13:03:06
23	require_email_verification	0	2026-05-15 13:03:06	2026-05-15 13:03:06
24	two_factor_auth	0	2026-05-15 13:03:06	2026-05-15 13:03:06
30	notify_new_ticket	1	2026-05-15 13:03:06	2026-05-15 13:03:06
31	notify_status_change	1	2026-05-15 13:03:06	2026-05-15 13:03:06
32	notify_new_comment	1	2026-05-15 13:03:06	2026-05-15 13:03:06
33	notify_assigned	1	2026-05-15 13:03:06	2026-05-15 13:03:06
34	notify_overdue	1	2026-05-15 13:03:06	2026-05-15 13:03:06
35	notify_resolved	1	2026-05-15 13:03:06	2026-05-15 13:03:06
37	glpi_app_token	nBuheJ0hEkes0bzBstuuqWKy8u3EOXOzhylF3QNb	2026-05-15 19:14:45	2026-05-15 19:14:45
36	glpi_url	http://host.docker.internal:8081	2026-05-15 19:14:45	2026-05-15 19:15:47
39	mail_mode	gmail	2026-05-15 19:49:38	2026-05-15 19:49:38
40	gmail_from_email	l2t.glpi2026@gmail.com	2026-05-15 19:49:38	2026-05-15 19:49:38
63	sms_notify_resolved	0	2026-05-15 23:34:07	2026-05-15 23:34:07
41	smtp_from_email	l2t.glpi2026@gmail.com	2026-05-15 19:49:39	2026-05-15 19:49:39
46	user_4_theme_mode	light	2026-05-15 21:41:19	2026-05-15 21:41:19
42	gmail_client_id	390138478457-6pjjcdqpled5rqqjvi3764qi7hcu7geg.apps.googleusercontent.com	2026-05-15 19:49:39	2026-05-15 19:49:39
45	logo_path	storage/logos/logo-l2t.png	2026-05-15 19:54:48	2026-05-15 19:54:48
47	user_4_primary_color	#0f766e	2026-05-15 21:41:19	2026-05-15 21:41:19
51	sms_sender	TunSMS Test	2026-05-15 23:34:07	2026-05-15 23:35:23
43	gmail_client_secret	eyJpdiI6IkxyWG1LKzc5RHRwNzB1MzQwdlBTWEE9PSIsInZhbHVlIjoiQUpQd2FQOTEwcW9DZWd2MjhuNFhlaGVpL3o1N3NEb0wvaVhGRG83WlFXWWJlTG1jS0RZUHg5a0pZK25LMm5hWSIsIm1hYyI6ImVmZWUwMGE0Y2NkNDU2OTA3ZGNmZjUzZWM0MzRlMDllN2YzZDRmZjk4YTRjOThjYzMxOWFjYWNlOTZkY2Y5ZjciLCJ0YWciOiIifQ==	2026-05-15 19:49:39	2026-05-15 20:33:59
48	user_4_secondary_color	#115e59	2026-05-15 21:41:19	2026-05-15 21:41:19
38	glpi_user_token	eyJpdiI6Ims4Um1HV3hpelhrZExJRnlnN2JtWUE9PSIsInZhbHVlIjoiT1g4OUN2US9FNS9ucHZ5aFB6Snh4VDNXdUcvL1QyNW9EOWtDNGxYa0w3Q2ZXbFV0b0VEenA2OTAwcUtVM2MrVjJzOVRmSlNGM2VtYytYYUlpdlA5WUE9PSIsIm1hYyI6IjhhOTBjNWZmNTBmYTQxYjAyOWU3MDY4YTJkNjQ4ZWRjNDYzMTMxOTEwM2FiYWZhNGUwNGFlM2QyNjJjZDNhNDUiLCJ0YWciOiIifQ==	2026-05-15 19:14:45	2026-05-16 10:28:04
29	smtp_username	support@l2t.com	2026-05-15 13:03:05	2026-05-15 22:27:15
44	gmail_refresh_token	eyJpdiI6ImdkakdsR1VkMldxcmdDaTVFaHlZN0E9PSIsInZhbHVlIjoiVlJOWGFiUlNDdW5Yb0owelh4UmhtQU9jUWNiaVhkemNpY0dhN01WTk9vbGZyS3dTWGV0eG9uVFJTRHJrS25vQk9JdHB5N3AxZmtza0VFeFFWaHdlQm5BVGIzRnlGdzB6bSttaFhubzFYZU1wUkhKcGVVUGs0aGxXUGIvYk5Qbno2RllTYU80eDVCSElMUG0wRmlSQmRpQytIR3orZnpYc25zaVBUVE5nNkdVPSIsIm1hYyI6IjkzNmZjM2FmNDllMjk4MDcxY2VlYTIxNmM1OWQ5YWRiODQzMjUyYjZkOWUxOGZmNTUzZjljMDEyNTdiZDQxMWYiLCJ0YWciOiIifQ==	2026-05-15 19:49:39	2026-05-15 22:27:15
49	sms_api_url	https://mystudents.tunisiesms.tn/api/sms	2026-05-15 23:34:07	2026-05-15 23:34:07
50	sms_api_key	35j!JYFlbC2AFQhzem11CXkw6Z44jw7puUc8i6T1PGBxdpNmOuU42oSP=Zc0scWeERj0YCt=gn4bdPOkKyJZndkAOxklJiLYZq5ymp55	2026-05-15 23:34:07	2026-05-15 23:34:07
52	sms_api_type	post_json	2026-05-15 23:34:07	2026-05-15 23:34:07
53	sms_max_chars	150	2026-05-15 23:34:07	2026-05-15 23:34:07
54	sms_fct	sms	2026-05-15 23:34:07	2026-05-15 23:34:07
55	sms_param_fct	fct	2026-05-15 23:34:07	2026-05-15 23:34:07
56	sms_param_key	key	2026-05-15 23:34:07	2026-05-15 23:34:07
57	sms_param_sender	sender	2026-05-15 23:34:07	2026-05-15 23:34:07
58	sms_param_mobile	mobile	2026-05-15 23:34:07	2026-05-15 23:34:07
59	sms_param_msg	sms	2026-05-15 23:34:07	2026-05-15 23:34:07
60	sms_notify_new_ticket	0	2026-05-15 23:34:07	2026-05-15 23:34:07
61	sms_notify_status_change	0	2026-05-15 23:34:07	2026-05-15 23:34:07
62	sms_notify_reply	0	2026-05-15 23:34:07	2026-05-15 23:34:07
\.


--
-- Data for Name: ticket_comments; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.ticket_comments (id, ticket_id, user_id, content, attachment_path, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: ticket_events; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.ticket_events (id, ticket_id, action, payload, glpi_response, sync_status, error_message, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: tickets; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.tickets (id, user_id, title, description, urgency, impact, priority, glpi_ticket_id, sync_status, last_error, created_at, updated_at, category, solution, attachments, solved_by, resolved_at, glpi_category_id, glpi_assigned_user_id, glpi_resolution_time, glpi_logs, sla_breached, sla_due_at, status, assigned_to, source) FROM stdin;
14	17	bnj	bnjj	1	1	1	\N	pending	\N	2026-05-16 11:04:27	2026-05-16 11:04:27	autre	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	open	\N	email
15	18	autre	test	1	1	1	\N	pending	\N	2026-05-16 11:28:23	2026-05-16 11:28:23	autre	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	open	\N	email
16	20	test	test	1	1	1	\N	pending	\N	2026-05-16 12:06:26	2026-05-16 12:06:26	autre	\N	\N	\N	\N	\N	\N	\N	\N	f	\N	open	\N	email
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, role, last_login_at, is_active, phone, phone_mobile, timezone, locale, whatsapp, teams_email, avatar, profile_completed, glpi_user_id, notifications_read, must_change_password, teams_webhook_url, client_type, phone_verified, first_name, last_name, birthday, gender) FROM stdin;
18	Farah Yassine	farahyassine182@gmail.com	\N	$2y$12$8brMIYdh7FjcDiwdIN3jFu5CVfcQ7knYgmdQbVH5JHo011mvKzwVq	\N	2026-05-16 11:28:20	2026-05-16 11:30:49	client	\N	t	\N	\N	Africa/Tunis	fr	\N	\N	\N	f	13	\N	t	\N	user	f	\N	\N	\N	\N
19	naila	nailaattig@gmail.com	\N	$2y$12$KXe5gIqFf.pldMMSxOYwNuMV076H3cFx73QQuswpo.FA.C1hfdPLO	\N	2026-05-16 11:57:16	2026-05-16 11:58:56	admin	\N	t	\N	\N	Africa/Tunis	fr	\N	\N	\N	f	14	\N	t	\N	\N	f	\N	\N	\N	\N
4	farah mzoughi	farah.mzough@gmail.com	\N	$2y$12$qAhpRi60RJF.xURMC.jZBe9KG84d9J97jwh49dT4RkP03kdQfpu7K	\N	2026-05-15 20:55:54	2026-05-16 12:08:02	admin	\N	t	\N	\N	Africa/Tunis	fr	\N	\N	\N	f	7	\N	f	\N	\N	f	\N	\N	\N	\N
20	mzoughi salah	salahmzoughi372@gmail.com	\N	$2y$12$J6AhvxGC7iO1CaJ/xcsOe.GGnBtXxXCOjTD1PTmByQ.X0WlyhRTau	\N	2026-05-16 12:06:24	2026-05-16 12:10:11	client	\N	t	\N	\N	Africa/Tunis	fr	\N	\N	\N	f	15	\N	t	\N	client	f	\N	\N	\N	\N
17	Mzoughi Salah	mzghsalah@gmail.com	\N	$2y$12$1VaFHUzh0yr3ZkXruhimIeKjQb00s2pGma7CP43YF7cYRhbLQCsSS	\N	2026-05-16 11:04:24	2026-05-16 13:04:05	client	\N	t	21656626317	\N	Africa/Tunis	fr	\N	\N	\N	f	12	\N	f	\N	user	t	\N	\N	\N	\N
1	superadmin	yassinebougadouha0@gmail.com	\N	$2y$12$zgQayyKmdkUXHfiwpDyQ8usK/BKmWqIEAZC/3yKrgZkRSTzKdFn/2	h1tgeLkH4Sv6V5Yc3DqLo5MYEyPmkka7IExeBwMY7Rpnr0o6Lpbq0Rsxzr3I	2026-05-15 16:55:00	2026-05-16 10:35:18	super_admin	\N	t	\N	\N	Africa/Tunis	fr	\N	\N	\N	f	\N	\N	f	\N	\N	f	\N	\N	\N	\N
\.


--
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 61, true);


--
-- Name: category_admin_mappings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.category_admin_mappings_id_seq', 1, false);


--
-- Name: chat_access_grants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.chat_access_grants_id_seq', 1, false);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: glpi_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.glpi_categories_id_seq', 1, false);


--
-- Name: glpi_sync_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.glpi_sync_logs_id_seq', 15, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 29, true);


--
-- Name: notifications_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.notifications_id_seq', 20, true);


--
-- Name: otp_codes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.otp_codes_id_seq', 5, true);


--
-- Name: settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.settings_id_seq', 63, true);


--
-- Name: ticket_comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ticket_comments_id_seq', 1, false);


--
-- Name: ticket_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.ticket_events_id_seq', 1, false);


--
-- Name: tickets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.tickets_id_seq', 16, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 20, true);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: category_admin_mappings category_admin_mappings_category_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_admin_mappings
    ADD CONSTRAINT category_admin_mappings_category_unique UNIQUE (category);


--
-- Name: category_admin_mappings category_admin_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_admin_mappings
    ADD CONSTRAINT category_admin_mappings_pkey PRIMARY KEY (id);


--
-- Name: chat_access_grants chat_access_grants_admin_id_client_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_access_grants
    ADD CONSTRAINT chat_access_grants_admin_id_client_id_unique UNIQUE (admin_id, client_id);


--
-- Name: chat_access_grants chat_access_grants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_access_grants
    ADD CONSTRAINT chat_access_grants_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: glpi_categories glpi_categories_glpi_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.glpi_categories
    ADD CONSTRAINT glpi_categories_glpi_id_unique UNIQUE (glpi_id);


--
-- Name: glpi_categories glpi_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.glpi_categories
    ADD CONSTRAINT glpi_categories_pkey PRIMARY KEY (id);


--
-- Name: glpi_sync_logs glpi_sync_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.glpi_sync_logs
    ADD CONSTRAINT glpi_sync_logs_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: otp_codes otp_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.otp_codes
    ADD CONSTRAINT otp_codes_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_key_unique UNIQUE (key);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: ticket_comments ticket_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_pkey PRIMARY KEY (id);


--
-- Name: ticket_events ticket_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_events
    ADD CONSTRAINT ticket_events_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: notifications_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_ticket_id_index ON public.notifications USING btree (ticket_id);


--
-- Name: notifications_user_id_is_read_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_user_id_is_read_index ON public.notifications USING btree (user_id, is_read);


--
-- Name: otp_codes_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX otp_codes_email_index ON public.otp_codes USING btree (email);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: category_admin_mappings category_admin_mappings_admin_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.category_admin_mappings
    ADD CONSTRAINT category_admin_mappings_admin_id_foreign FOREIGN KEY (admin_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: chat_access_grants chat_access_grants_admin_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_access_grants
    ADD CONSTRAINT chat_access_grants_admin_id_foreign FOREIGN KEY (admin_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: chat_access_grants chat_access_grants_client_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_access_grants
    ADD CONSTRAINT chat_access_grants_client_id_foreign FOREIGN KEY (client_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_comments ticket_comments_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_comments ticket_comments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_events ticket_events_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_events
    ADD CONSTRAINT ticket_events_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_solved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_solved_by_foreign FOREIGN KEY (solved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict htmGrVuYQ9Nmoj98wIXscFhpFFAUfZ3V3iqeDDyzfptr6ygGrxyHQtCF9ve2RbE

