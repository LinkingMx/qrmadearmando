--
-- PostgreSQL database dump
--

\restrict HMkEDRtGkHCCwihcpvQYDCk3dvivFcWrRGiZfn9Hm4YJhb1Gn59LsBHx9ozrErq

-- Dumped from database version 16.11
-- Dumped by pg_dump version 16.11

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
-- Name: activity_log; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.activity_log (
    id bigint NOT NULL,
    log_name character varying(255),
    description text NOT NULL,
    subject_type character varying(255),
    subject_id character varying(36),
    causer_type character varying(255),
    causer_id bigint,
    properties json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event character varying(255),
    batch_uuid uuid
);


ALTER TABLE public.activity_log OWNER TO postgres;

--
-- Name: activity_log_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.activity_log_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.activity_log_id_seq OWNER TO postgres;

--
-- Name: activity_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.activity_log_id_seq OWNED BY public.activity_log.id;


--
-- Name: branch_gift_card; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.branch_gift_card (
    id bigint NOT NULL,
    branch_id bigint NOT NULL,
    gift_card_id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.branch_gift_card OWNER TO postgres;

--
-- Name: branch_gift_card_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.branch_gift_card_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.branch_gift_card_id_seq OWNER TO postgres;

--
-- Name: branch_gift_card_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.branch_gift_card_id_seq OWNED BY public.branch_gift_card.id;


--
-- Name: branches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.branches (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    brand_id bigint NOT NULL
);


ALTER TABLE public.branches OWNER TO postgres;

--
-- Name: branches_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.branches_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.branches_id_seq OWNER TO postgres;

--
-- Name: branches_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.branches_id_seq OWNED BY public.branches.id;


--
-- Name: brands; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.brands (
    id bigint NOT NULL,
    chain_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.brands OWNER TO postgres;

--
-- Name: brands_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.brands_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.brands_id_seq OWNER TO postgres;

--
-- Name: brands_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.brands_id_seq OWNED BY public.brands.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: chains; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.chains (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.chains OWNER TO postgres;

--
-- Name: chains_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.chains_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.chains_id_seq OWNER TO postgres;

--
-- Name: chains_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.chains_id_seq OWNED BY public.chains.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: gift_card_categories; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.gift_card_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    prefix character varying(10) NOT NULL,
    nature character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.gift_card_categories OWNER TO postgres;

--
-- Name: gift_card_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.gift_card_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.gift_card_categories_id_seq OWNER TO postgres;

--
-- Name: gift_card_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.gift_card_categories_id_seq OWNED BY public.gift_card_categories.id;


--
-- Name: gift_cards; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.gift_cards (
    id uuid NOT NULL,
    legacy_id character varying(255) NOT NULL,
    user_id bigint,
    status boolean DEFAULT true NOT NULL,
    expiry_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    qr_image_path character varying(255),
    deleted_at timestamp(0) without time zone,
    balance numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    gift_card_category_id bigint NOT NULL,
    scope character varying(255) DEFAULT 'chain'::character varying NOT NULL,
    chain_id bigint,
    brand_id bigint
);


ALTER TABLE public.gift_cards OWNER TO postgres;

--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
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


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_permissions OWNER TO postgres;

--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


ALTER TABLE public.model_has_roles OWNER TO postgres;

--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.permissions_id_seq OWNER TO postgres;

--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: push_subscriptions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.push_subscriptions (
    id bigint NOT NULL,
    subscribable_type character varying(255) NOT NULL,
    subscribable_id bigint NOT NULL,
    endpoint character varying(500) NOT NULL,
    public_key character varying(255),
    auth_token character varying(255),
    content_encoding character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.push_subscriptions OWNER TO postgres;

--
-- Name: push_subscriptions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.push_subscriptions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.push_subscriptions_id_seq OWNER TO postgres;

--
-- Name: push_subscriptions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.push_subscriptions_id_seq OWNED BY public.push_subscriptions.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


ALTER TABLE public.role_has_permissions OWNER TO postgres;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    gift_card_id uuid NOT NULL,
    type character varying(255) NOT NULL,
    amount numeric(10,2) NOT NULL,
    balance_before numeric(10,2) NOT NULL,
    balance_after numeric(10,2) NOT NULL,
    description text,
    admin_user_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    branch_id bigint,
    CONSTRAINT transactions_type_check CHECK (((type)::text = ANY ((ARRAY['credit'::character varying, 'debit'::character varying, 'adjustment'::character varying])::text[])))
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transactions_id_seq OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
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
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    avatar character varying(255),
    branch_id bigint,
    is_active boolean DEFAULT true NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: activity_log id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.activity_log ALTER COLUMN id SET DEFAULT nextval('public.activity_log_id_seq'::regclass);


--
-- Name: branch_gift_card id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch_gift_card ALTER COLUMN id SET DEFAULT nextval('public.branch_gift_card_id_seq'::regclass);


--
-- Name: branches id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches ALTER COLUMN id SET DEFAULT nextval('public.branches_id_seq'::regclass);


--
-- Name: brands id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands ALTER COLUMN id SET DEFAULT nextval('public.brands_id_seq'::regclass);


--
-- Name: chains id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chains ALTER COLUMN id SET DEFAULT nextval('public.chains_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: gift_card_categories id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_card_categories ALTER COLUMN id SET DEFAULT nextval('public.gift_card_categories_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: push_subscriptions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.push_subscriptions ALTER COLUMN id SET DEFAULT nextval('public.push_subscriptions_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: activity_log; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.activity_log (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, created_at, updated_at, event, batch_uuid) FROM stdin;
1	Resource	Chain Created	App\\Models\\Chain	1	\N	\N	{"name":"Cadenas Don Carlos","updated_at":"2026-02-09 00:17:18","created_at":"2026-02-09 00:17:18","id":1}	2026-02-09 00:17:18	2026-02-09 00:17:18	Created	\N
2	Resource	Brand Created	App\\Models\\Brand	1	\N	\N	{"chain_id":1,"name":"Mochomos","updated_at":"2026-02-09 00:17:18","created_at":"2026-02-09 00:17:18","id":1}	2026-02-09 00:17:18	2026-02-09 00:17:18	Created	\N
3	Resource	User Created	App\\Models\\User	1	\N	\N	{"name":"Usuario Prueba","email":"test@example.com","is_active":true,"updated_at":"2026-02-09 00:19:49","created_at":"2026-02-09 00:19:49","id":1}	2026-02-09 00:19:49	2026-02-09 00:19:49	Created	\N
4	Resource	User Updated	App\\Models\\User	1	\N	\N	{"email_verified_at":"2026-02-09 00:19:49"}	2026-02-09 00:19:49	2026-02-09 00:19:49	Updated	\N
5	Access	Usuario Prueba logged in	\N	\N	App\\Models\\User	1	{"ip":"185.85.0.29","user_agent":"curl\\/8.7.1"}	2026-02-09 00:21:54	2026-02-09 00:21:54	Login	\N
6	Access	Usuario Prueba logged in	\N	\N	App\\Models\\User	1	{"ip":"185.85.0.29","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-09 00:22:29	2026-02-09 00:22:29	Login	\N
7	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	1	App\\Models\\User	1	{"updated_at":"2025-09-29 18:38:48","branch_id":null}	2025-09-29 18:38:48	2025-09-29 18:38:48	Updated	\N
8	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	1	App\\Models\\User	1	{"updated_at":"2025-09-29 18:39:04","branch_id":"1"}	2025-09-29 18:39:04	2025-09-29 18:39:04	Updated	\N
9	Resource	Branch Updated by Armando Reyes Guajardo	App\\Models\\Branch	1	App\\Models\\User	1	{"name":"Mochomos Monterrey 2","updated_at":"2025-09-29 18:41:09"}	2025-09-29 18:41:09	2025-09-29 18:41:09	Updated	\N
10	Resource	Branch Updated by Armando Reyes Guajardo	App\\Models\\Branch	1	App\\Models\\User	1	{"name":"Mochomos Monterrey","updated_at":"2025-09-29 18:42:13"}	2025-09-29 18:42:13	2025-09-29 18:42:13	Updated	\N
11	Resource	Branch Updated by Armando Reyes Guajardo	App\\Models\\Branch	1	App\\Models\\User	1	{"name":"Mochomos Monterrey 2","updated_at":"2025-09-29 18:43:37"}	2025-09-29 18:43:37	2025-09-29 18:43:37	Updated	\N
12	Resource	Branch Updated by Armando Reyes Guajardo	App\\Models\\Branch	1	App\\Models\\User	1	{"name":"Mochomos Monterrey","updated_at":"2025-09-29 18:43:42"}	2025-09-29 18:43:42	2025-09-29 18:43:42	Updated	\N
13	Resource	Gift Card Created by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"legacy_id":"EMCAD20005","user_id":"1","status":true,"expiry_date":null,"id":"019996d7-42dc-7222-abf7-052da67509c4","updated_at":"2025-09-29 18:58:33","created_at":"2025-09-29 18:58:33"}	2025-09-29 18:58:33	2025-09-29 18:58:33	Created	\N
14	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"expiry_date":"2025-10-11 00:00:00","updated_at":"2025-09-29 18:59:46"}	2025-09-29 18:59:46	2025-09-29 18:59:46	Updated	\N
15	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36"}	2025-09-29 22:08:30	2025-09-29 22:08:30	Login	\N
16	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36"}	2025-09-30 20:05:37	2025-09-30 20:05:37	Login	\N
17	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-09-30 20:59:31","balance":1000}	2025-09-30 20:59:31	2025-09-30 20:59:31	Updated	\N
18	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	1	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"credit","amount":1000,"balance_before":"0.00","balance_after":1000,"description":"Bono de empleado del mes.","admin_user_id":1,"updated_at":"2025-09-30 20:59:31","created_at":"2025-09-30 20:59:31","id":1}	2025-09-30 20:59:31	2025-09-30 20:59:31	Created	\N
19	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-09-30 22:12:05","balance":2000}	2025-09-30 22:12:05	2025-09-30 22:12:05	Updated	\N
20	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	2	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"credit","amount":1000,"balance_before":"1000.00","balance_after":2000,"description":"Prueba de carga","admin_user_id":1,"branch_id":null,"updated_at":"2025-09-30 22:12:05","created_at":"2025-09-30 22:12:05","id":2}	2025-09-30 22:12:05	2025-09-30 22:12:05	Created	\N
21	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-09-30 22:13:30","balance":1500}	2025-09-30 22:13:30	2025-09-30 22:13:30	Updated	\N
22	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	3	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":500,"balance_before":"2000.00","balance_after":1500,"description":"Consumo de a cuenta 2005","admin_user_id":1,"branch_id":1,"updated_at":"2025-09-30 22:13:30","created_at":"2025-09-30 22:13:30","id":3}	2025-09-30 22:13:30	2025-09-30 22:13:30	Created	\N
23	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-09-30 22:14:20","balance":1300}	2025-09-30 22:14:20	2025-09-30 22:14:20	Updated	\N
24	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	4	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"adjustment","amount":-200,"balance_before":"1500.00","balance_after":1300,"description":"Error de carga!","admin_user_id":1,"branch_id":1,"updated_at":"2025-09-30 22:14:20","created_at":"2025-09-30 22:14:20","id":4}	2025-09-30 22:14:20	2025-09-30 22:14:20	Created	\N
25	Resource	User Created by Armando Reyes Guajardo	App\\Models\\User	2	App\\Models\\User	1	{"name":"Carlos Rodr\\u00edguez","email":"carlos@empresa.com","branch_id":null,"updated_at":"2025-09-30 22:39:26","created_at":"2025-09-30 22:39:26","id":2}	2025-09-30 22:39:26	2025-09-30 22:39:26	Created	\N
26	Resource	User Deleted by Armando Reyes Guajardo	App\\Models\\User	2	App\\Models\\User	1	[]	2025-09-30 22:40:02	2025-09-30 22:40:02	Deleted	\N
27	Resource	Gift Card Updated	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	\N	\N	{"updated_at":"2025-09-30 22:58:54","balance":1800}	2025-09-30 22:58:54	2025-09-30 22:58:54	Updated	\N
28	Resource	Transaction Created	App\\Models\\Transaction	5	\N	\N	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"credit","amount":500,"balance_before":"1300.00","balance_after":1800,"description":"Bono mensual Enero","admin_user_id":1,"branch_id":null,"updated_at":"2025-09-30 22:58:54","created_at":"2025-09-30 22:58:54","id":5}	2025-09-30 22:58:54	2025-09-30 22:58:54	Created	\N
29	Resource	Gift Card Updated	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	\N	\N	{"updated_at":"2025-09-30 23:00:35","balance":2300}	2025-09-30 23:00:35	2025-09-30 23:00:35	Updated	\N
30	Resource	Transaction Created	App\\Models\\Transaction	6	\N	\N	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"credit","amount":500,"balance_before":"1800.00","balance_after":2300,"description":"Bono mensual Enero","admin_user_id":1,"branch_id":null,"updated_at":"2025-09-30 23:00:35","created_at":"2025-09-30 23:00:35","id":6}	2025-09-30 23:00:35	2025-09-30 23:00:35	Created	\N
31	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-09-30 23:02:28","balance":2800}	2025-09-30 23:02:28	2025-09-30 23:02:28	Updated	\N
32	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	7	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"credit","amount":500,"balance_before":"2300.00","balance_after":2800,"description":"Bono mensual Enero","admin_user_id":1,"branch_id":null,"updated_at":"2025-09-30 23:02:28","created_at":"2025-09-30 23:02:28","id":7}	2025-09-30 23:02:28	2025-09-30 23:02:28	Created	\N
33	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-09-30 23:04:05","balance":3300}	2025-09-30 23:04:05	2025-09-30 23:04:05	Updated	\N
34	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	8	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"credit","amount":500,"balance_before":"2800.00","balance_after":3300,"description":"Carga masiva: Bono mensual Enero","admin_user_id":1,"branch_id":null,"updated_at":"2025-09-30 23:04:05","created_at":"2025-09-30 23:04:05","id":8}	2025-09-30 23:04:05	2025-09-30 23:04:05	Created	\N
35	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-10-01 01:57:08","balance":2300}	2025-10-01 01:57:08	2025-10-01 01:57:08	Updated	\N
36	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	9	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":1000,"balance_before":"3300.00","balance_after":2300,"description":"descuento xxxx","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-01 01:57:08","created_at":"2025-10-01 01:57:08","id":9}	2025-10-01 01:57:08	2025-10-01 01:57:08	Created	\N
37	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-10-01 02:35:18","balance":2100}	2025-10-01 02:35:18	2025-10-01 02:35:18	Updated	\N
38	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	10	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":200,"balance_before":"2300.00","balance_after":2100,"description":"SISTEMAS","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-01 02:35:18","created_at":"2025-10-01 02:35:18","id":10}	2025-10-01 02:35:18	2025-10-01 02:35:18	Created	\N
39	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-10-01 02:37:08","balance":2000}	2025-10-01 02:37:08	2025-10-01 02:37:08	Updated	\N
40	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	11	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":100,"balance_before":"2100.00","balance_after":2000,"description":"Descuento desde Scanner","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-01 02:37:08","created_at":"2025-10-01 02:37:08","id":11}	2025-10-01 02:37:08	2025-10-01 02:37:08	Created	\N
41	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-10-01 02:37:54","balance":1900}	2025-10-01 02:37:54	2025-10-01 02:37:54	Updated	\N
42	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	12	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":100,"balance_before":"2000.00","balance_after":1900,"description":"Descuento desde Scanner","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-01 02:37:54","created_at":"2025-10-01 02:37:54","id":12}	2025-10-01 02:37:54	2025-10-01 02:37:54	Created	\N
43	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-10-01 02:39:11","balance":1700}	2025-10-01 02:39:11	2025-10-01 02:39:11	Updated	\N
44	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	13	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":200,"balance_before":"1900.00","balance_after":1700,"description":"Descuento desde Scanner","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-01 02:39:11","created_at":"2025-10-01 02:39:11","id":13}	2025-10-01 02:39:11	2025-10-01 02:39:11	Created	\N
45	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-10-01 02:42:57","balance":1600}	2025-10-01 02:42:58	2025-10-01 02:42:58	Updated	\N
46	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	14	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":100,"balance_before":"1700.00","balance_after":1600,"description":"Descuento desde Scanner","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-01 02:42:58","created_at":"2025-10-01 02:42:58","id":14}	2025-10-01 02:42:58	2025-10-01 02:42:58	Created	\N
47	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	{"updated_at":"2025-10-01 02:45:06","balance":1500}	2025-10-01 02:45:06	2025-10-01 02:45:06	Updated	\N
48	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	15	App\\Models\\User	1	{"gift_card_id":"019996d7-42dc-7222-abf7-052da67509c4","type":"debit","amount":100,"balance_before":"1600.00","balance_after":1500,"description":"Descuento desde Scanner","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-01 02:45:06","created_at":"2025-10-01 02:45:06","id":15}	2025-10-01 02:45:06	2025-10-01 02:45:06	Created	\N
49	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36"}	2025-10-01 14:34:09	2025-10-01 14:34:09	Login	\N
50	Resource	Gift Card Deleted by Armando Reyes Guajardo	App\\Models\\GiftCard	019996d7-42dc-7222-abf7-052da67509c4	App\\Models\\User	1	[]	2025-10-01 15:12:48	2025-10-01 15:12:48	Deleted	\N
51	Resource	Gift Card Created by Armando Reyes Guajardo	App\\Models\\GiftCard	0199a055-6e58-7283-87d4-5a680f587c76	App\\Models\\User	1	{"legacy_id":"EMCAD20006","user_id":"1","status":true,"expiry_date":null,"id":"0199a055-6e58-7283-87d4-5a680f587c76","updated_at":"2025-10-01 15:12:57","created_at":"2025-10-01 15:12:57","qr_image_path":"qr-codes\\/0199a055-6e58-7283-87d4-5a680f587c76"}	2025-10-01 15:12:57	2025-10-01 15:12:57	Created	\N
82	Notification	ResetPassword Notification sent to armando.reyes@grupocosteno.com	\N	\N	\N	\N	[]	2025-10-10 18:21:32	2025-10-10 18:21:32	Notification Sent	\N
52	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36"}	2025-10-02 12:53:50	2025-10-02 12:53:50	Login	\N
53	Resource	User Created by Armando Reyes Guajardo	App\\Models\\User	3	App\\Models\\User	1	{"avatar":null,"name":"Ismael Briones","email":"i@i.com","branch_id":"1","updated_at":"2025-10-02 12:54:40","created_at":"2025-10-02 12:54:40","id":3}	2025-10-02 12:54:40	2025-10-02 12:54:40	Created	\N
54	Resource	Gift Card Created by Armando Reyes Guajardo	App\\Models\\GiftCard	0199a4fd-74bf-730f-8507-c827c0e0649d	App\\Models\\User	1	{"legacy_id":"EMCAD200007","user_id":"3","status":true,"expiry_date":null,"id":"0199a4fd-74bf-730f-8507-c827c0e0649d","updated_at":"2025-10-02 12:54:57","created_at":"2025-10-02 12:54:57","qr_image_path":"qr-codes\\/0199a4fd-74bf-730f-8507-c827c0e0649d"}	2025-10-02 12:54:58	2025-10-02 12:54:58	Created	\N
55	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	15	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
56	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	14	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
57	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	13	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
58	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	12	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
59	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	11	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
60	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	10	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
61	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	9	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
62	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	8	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
63	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	7	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
64	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	6	App\\Models\\User	1	[]	2025-10-02 12:55:41	2025-10-02 12:55:41	Deleted	\N
65	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	5	App\\Models\\User	1	[]	2025-10-02 12:55:46	2025-10-02 12:55:46	Deleted	\N
66	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	4	App\\Models\\User	1	[]	2025-10-02 12:55:46	2025-10-02 12:55:46	Deleted	\N
67	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	3	App\\Models\\User	1	[]	2025-10-02 12:55:46	2025-10-02 12:55:46	Deleted	\N
68	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	2	App\\Models\\User	1	[]	2025-10-02 12:55:46	2025-10-02 12:55:46	Deleted	\N
69	Resource	Transaction Deleted by Armando Reyes Guajardo	App\\Models\\Transaction	1	App\\Models\\User	1	[]	2025-10-02 12:55:46	2025-10-02 12:55:46	Deleted	\N
70	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36"}	2025-10-02 12:56:30	2025-10-02 12:56:30	Login	\N
71	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/140.0.0.0 Safari\\/537.36"}	2025-10-04 01:47:19	2025-10-04 01:47:19	Login	\N
72	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	0199a055-6e58-7283-87d4-5a680f587c76	App\\Models\\User	1	{"updated_at":"2025-10-04 01:58:03","balance":1000}	2025-10-04 01:58:03	2025-10-04 01:58:03	Updated	\N
73	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	16	App\\Models\\User	1	{"gift_card_id":"0199a055-6e58-7283-87d4-5a680f587c76","type":"credit","amount":1000,"balance_before":"0.00","balance_after":1000,"description":"testimg","admin_user_id":1,"branch_id":null,"updated_at":"2025-10-04 01:58:03","created_at":"2025-10-04 01:58:03","id":16}	2025-10-04 01:58:03	2025-10-04 01:58:03	Created	\N
74	Resource	Gift Card Updated by Armando Reyes Guajardo	App\\Models\\GiftCard	0199a055-6e58-7283-87d4-5a680f587c76	App\\Models\\User	1	{"updated_at":"2025-10-04 01:58:26","balance":800}	2025-10-04 01:58:26	2025-10-04 01:58:26	Updated	\N
75	Resource	Transaction Created by Armando Reyes Guajardo	App\\Models\\Transaction	17	App\\Models\\User	1	{"gift_card_id":"0199a055-6e58-7283-87d4-5a680f587c76","type":"debit","amount":200,"balance_before":"1000.00","balance_after":800,"description":"Descuento desde Scanner","admin_user_id":1,"branch_id":1,"updated_at":"2025-10-04 01:58:26","created_at":"2025-10-04 01:58:26","id":17}	2025-10-04 01:58:26	2025-10-04 01:58:26	Created	\N
76	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	1	App\\Models\\User	1	{"updated_at":"2025-10-04 02:43:14","branch_id":null}	2025-10-04 02:43:14	2025-10-04 02:43:14	Updated	\N
77	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-08 18:06:47	2025-10-08 18:06:47	Login	\N
78	Resource	Gift Card Created	App\\Models\\GiftCard	0199c530-89f3-707a-9e39-43a40a16dfa5	\N	\N	{"status":true,"expiry_date":"2026-10-08 18:58:36","id":"0199c530-89f3-707a-9e39-43a40a16dfa5","legacy_id":"EMCAD200008","updated_at":"2025-10-08 18:58:36","created_at":"2025-10-08 18:58:36","qr_image_path":"qr-codes\\/0199c530-89f3-707a-9e39-43a40a16dfa5"}	2025-10-08 18:58:36	2025-10-08 18:58:36	Created	\N
79	Resource	Gift Card Created	App\\Models\\GiftCard	0199c530-ace1-737c-b905-b09cc9d8a5a8	\N	\N	{"legacy_id":"EMCAD999999","status":true,"id":"0199c530-ace1-737c-b905-b09cc9d8a5a8","updated_at":"2025-10-08 18:58:45","created_at":"2025-10-08 18:58:45","qr_image_path":"qr-codes\\/0199c530-ace1-737c-b905-b09cc9d8a5a8"}	2025-10-08 18:58:45	2025-10-08 18:58:45	Created	\N
80	Resource	Gift Card Created	App\\Models\\GiftCard	0199c530-d967-7027-97c4-ff8352714719	\N	\N	{"status":true,"id":"0199c530-d967-7027-97c4-ff8352714719","legacy_id":"EMCAD1000000","updated_at":"2025-10-08 18:58:57","created_at":"2025-10-08 18:58:57","qr_image_path":"qr-codes\\/0199c530-d967-7027-97c4-ff8352714719"}	2025-10-08 18:58:57	2025-10-08 18:58:57	Created	\N
81	Resource	Gift Card Created by Armando Reyes Guajardo	App\\Models\\GiftCard	0199c531-aa1e-72c3-a912-442d3769f64d	App\\Models\\User	1	{"legacy_id":"EMCAD200008","user_id":"3","status":true,"expiry_date":null,"id":"0199c531-aa1e-72c3-a912-442d3769f64d","updated_at":"2025-10-08 18:59:50","created_at":"2025-10-08 18:59:50","qr_image_path":"qr-codes\\/0199c531-aa1e-72c3-a912-442d3769f64d"}	2025-10-08 18:59:50	2025-10-08 18:59:50	Created	\N
83	Notification	ResetPassword Notification sent to armando.reyes@grupocosteno.com	\N	\N	\N	\N	[]	2025-10-10 18:27:38	2025-10-10 18:27:38	Notification Sent	\N
84	Notification	ResetPassword Notification sent to armando.reyes@grupocosteno.com	\N	\N	\N	\N	[]	2025-10-10 18:34:32	2025-10-10 18:34:32	Notification Sent	\N
85	Notification	ResetPassword Notification sent to armando.reyes@grupocosteno.com	\N	\N	\N	\N	[]	2025-10-10 18:39:19	2025-10-10 18:39:19	Notification Sent	\N
86	Notification	ResetPassword Notification sent to armando.reyes@grupocosteno.com	\N	\N	\N	\N	[]	2025-10-10 18:42:02	2025-10-10 18:42:02	Notification Sent	\N
87	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-11 18:42:59	2025-10-11 18:42:59	Login	\N
88	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-11 19:06:09	2025-10-11 19:06:09	Login	\N
89	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	1	App\\Models\\User	1	{"updated_at":"2025-10-11 19:08:16","branch_id":"1"}	2025-10-11 19:08:16	2025-10-11 19:08:16	Updated	\N
90	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-11 22:55:28	2025-10-11 22:55:28	Login	\N
91	Resource	Role Created	Spatie\\Permission\\Models\\Role	1	\N	\N	{"guard_name":"web","name":"super_admin","updated_at":"2025-10-11 23:01:55","created_at":"2025-10-11 23:01:55","id":1}	2025-10-11 23:01:55	2025-10-11 23:01:55	Created	\N
92	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	3	App\\Models\\User	1	{"updated_at":"2025-10-11 23:24:13","is_active":false}	2025-10-11 23:24:13	2025-10-11 23:24:13	Updated	\N
93	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	3	App\\Models\\User	1	{"updated_at":"2025-10-11 23:24:16","is_active":true}	2025-10-11 23:24:16	2025-10-11 23:24:16	Updated	\N
94	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-11 23:27:39	2025-10-11 23:27:39	Login	\N
95	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	3	App\\Models\\User	1	{"updated_at":"2025-10-11 23:27:54","is_active":false}	2025-10-11 23:27:54	2025-10-11 23:27:54	Updated	\N
96	Access	Ismael Briones logged in	\N	\N	App\\Models\\User	3	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-11 23:28:16	2025-10-11 23:28:16	Login	\N
97	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-11 23:28:33	2025-10-11 23:28:33	Login	\N
98	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	3	App\\Models\\User	1	{"updated_at":"2025-10-11 23:31:00","is_active":true}	2025-10-11 23:31:00	2025-10-11 23:31:00	Updated	\N
99	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	3	App\\Models\\User	1	{"updated_at":"2025-10-11 23:31:08","is_active":false}	2025-10-11 23:31:08	2025-10-11 23:31:08	Updated	\N
100	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-12 14:52:26	2025-10-12 14:52:26	Login	\N
101	Resource	Role Created by Armando Reyes Guajardo	Spatie\\Permission\\Models\\Role	2	App\\Models\\User	1	{"name":"Employee","guard_name":"web","updated_at":"2025-10-12 14:52:48","created_at":"2025-10-12 14:52:48","id":2}	2025-10-12 14:52:48	2025-10-12 14:52:48	Created	\N
102	Resource	Role Created	Spatie\\Permission\\Models\\Role	3	\N	\N	{"guard_name":"web","name":"employee","updated_at":"2025-10-12 14:58:27","created_at":"2025-10-12 14:58:27","id":3}	2025-10-12 14:58:27	2025-10-12 14:58:27	Created	\N
103	Resource	Role Deleted	Spatie\\Permission\\Models\\Role	3	\N	\N	[]	2025-10-12 14:59:07	2025-10-12 14:59:07	Deleted	\N
104	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-12 15:01:43	2025-10-12 15:01:43	Login	\N
105	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit\\/605.1.15 (KHTML, like Gecko) Version\\/18.5 Mobile\\/15E148 Safari\\/604.1"}	2025-10-12 15:03:58	2025-10-12 15:03:58	Login	\N
106	Resource	User Updated by Armando Reyes Guajardo	App\\Models\\User	3	App\\Models\\User	1	{"updated_at":"2025-10-12 15:04:52","is_active":true}	2025-10-12 15:04:52	2025-10-12 15:04:52	Updated	\N
107	Access	Ismael Briones logged in	\N	\N	App\\Models\\User	3	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-12 15:05:03	2025-10-12 15:05:03	Login	\N
108	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-12 17:07:35	2025-10-12 17:07:35	Login	\N
109	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-13 14:20:44	2025-10-13 14:20:44	Login	\N
110	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36"}	2025-10-13 17:57:49	2025-10-13 17:57:49	Login	\N
111	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/605.1.15 (KHTML, like Gecko) Version\\/26.2 Safari\\/605.1.15"}	2026-02-08 17:24:46	2026-02-08 17:24:46	Login	\N
112	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-08 17:26:37	2026-02-08 17:26:37	Login	\N
113	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-08 17:58:42	2026-02-08 17:58:42	Login	\N
114	Resource	User Created	App\\Models\\User	4	\N	\N	{"name":"Alma L\\u00f3pez","email":"mariapilar59@example.net","email_verified_at":"2026-02-08 18:42:53","branch_id":null,"updated_at":"2026-02-08 18:42:53","created_at":"2026-02-08 18:42:53","id":4}	2026-02-08 18:42:53	2026-02-08 18:42:53	Created	\N
115	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-08 19:16:56	2026-02-08 19:16:56	Login	\N
116	Resource	Chain Created by Armando Reyes Guajardo	App\\Models\\Chain	2	App\\Models\\User	1	{"name":"Mi Empresa Test","updated_at":"2026-02-08 19:19:11","created_at":"2026-02-08 19:19:11","id":2}	2026-02-08 19:19:11	2026-02-08 19:19:11	Created	\N
117	Resource	Brand Created by Armando Reyes Guajardo	App\\Models\\Brand	2	App\\Models\\User	1	{"chain_id":"2","name":"Don Carlos","updated_at":"2026-02-08 19:25:56","created_at":"2026-02-08 19:25:56","id":2}	2026-02-08 19:25:56	2026-02-08 19:25:56	Created	\N
118	Resource	Brand Created by Armando Reyes Guajardo	App\\Models\\Brand	3	App\\Models\\User	1	{"chain_id":"2","name":"La Vaca","updated_at":"2026-02-08 19:26:11","created_at":"2026-02-08 19:26:11","id":3}	2026-02-08 19:26:11	2026-02-08 19:26:11	Created	\N
119	Resource	Branch Created by Armando Reyes Guajardo	App\\Models\\Branch	2	App\\Models\\User	1	{"brand_id":"2","name":"Don Carlos Centro","updated_at":"2026-02-08 19:38:59","created_at":"2026-02-08 19:38:59","id":2}	2026-02-08 19:38:59	2026-02-08 19:38:59	Created	\N
120	Resource	Branch Created by Armando Reyes Guajardo	App\\Models\\Branch	3	App\\Models\\User	1	{"brand_id":"2","name":"Don Carlos Norte","updated_at":"2026-02-08 19:40:19","created_at":"2026-02-08 19:40:19","id":3}	2026-02-08 19:40:19	2026-02-08 19:40:19	Created	\N
121	Resource	Branch Created by Armando Reyes Guajardo	App\\Models\\Branch	4	App\\Models\\User	1	{"brand_id":"3","name":"La Vaca Sur","updated_at":"2026-02-08 19:41:10","created_at":"2026-02-08 19:41:10","id":4}	2026-02-08 19:41:10	2026-02-08 19:41:10	Created	\N
122	Resource	Brand Created by Armando Reyes Guajardo	App\\Models\\Brand	4	App\\Models\\User	1	{"chain_id":"2","name":"Marca Temporal","updated_at":"2026-02-08 19:48:34","created_at":"2026-02-08 19:48:34","id":4}	2026-02-08 19:48:34	2026-02-08 19:48:34	Created	\N
123	Resource	Brand Deleted by Armando Reyes Guajardo	App\\Models\\Brand	4	App\\Models\\User	1	[]	2026-02-08 19:49:26	2026-02-08 19:49:26	Deleted	\N
124	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-08 20:05:28	2026-02-08 20:05:28	Login	\N
125	Resource	User Created by Armando Reyes Guajardo	App\\Models\\User	5	App\\Models\\User	1	{"avatar":null,"name":"Terminal DC Centro","email":"terminal@doncarlos.com","branch_id":"2","is_active":true,"updated_at":"2026-02-08 20:07:51","created_at":"2026-02-08 20:07:51","id":5}	2026-02-08 20:07:51	2026-02-08 20:07:51	Created	\N
126	Resource	Role Created	Spatie\\Permission\\Models\\Role	4	\N	\N	{"guard_name":"web","name":"BranchTerminal","updated_at":"2026-02-08 20:17:16","created_at":"2026-02-08 20:17:16","id":4}	2026-02-08 20:17:16	2026-02-08 20:17:16	Created	\N
127	Resource	Gift Card Created by Armando Reyes Guajardo	App\\Models\\GiftCard	019c3eed-2c96-717e-8715-0e6777ec38ed	App\\Models\\User	1	{"gift_card_category_id":"1","legacy_id":"EMCAD200009","user_id":null,"balance":"1000","expiry_date":null,"status":true,"scope":"brand","brand_id":"2","id":"019c3eed-2c96-717e-8715-0e6777ec38ed","updated_at":"2026-02-08 20:24:16","created_at":"2026-02-08 20:24:16","qr_image_path":"qr-codes\\/019c3eed-2c96-717e-8715-0e6777ec38ed"}	2026-02-08 20:24:16	2026-02-08 20:24:16	Created	\N
128	Resource	Gift Card Created by Armando Reyes Guajardo	App\\Models\\GiftCard	019c3eed-d3ff-7048-b4c2-7d9a00518e01	App\\Models\\User	1	{"gift_card_category_id":"1","legacy_id":"EMCAD200010","user_id":null,"balance":"1000","expiry_date":null,"status":true,"scope":"chain","chain_id":"2","id":"019c3eed-d3ff-7048-b4c2-7d9a00518e01","updated_at":"2026-02-08 20:24:59","created_at":"2026-02-08 20:24:59","qr_image_path":"qr-codes\\/019c3eed-d3ff-7048-b4c2-7d9a00518e01"}	2026-02-08 20:24:59	2026-02-08 20:24:59	Created	\N
129	Resource	Gift Card Created by Armando Reyes Guajardo	App\\Models\\GiftCard	019c3eee-b325-706e-af61-facafb2d0ebb	App\\Models\\User	1	{"gift_card_category_id":"1","legacy_id":"EMCAD200011","user_id":null,"balance":"1000","expiry_date":null,"status":true,"scope":"branch","id":"019c3eee-b325-706e-af61-facafb2d0ebb","updated_at":"2026-02-08 20:25:56","created_at":"2026-02-08 20:25:56","qr_image_path":"qr-codes\\/019c3eee-b325-706e-af61-facafb2d0ebb"}	2026-02-08 20:25:56	2026-02-08 20:25:56	Created	\N
130	Access	Terminal DC Centro logged in	\N	\N	App\\Models\\User	5	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-08 20:29:37	2026-02-08 20:29:37	Login	\N
131	Access	Terminal DC Centro logged in	\N	\N	App\\Models\\User	5	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-08 20:53:49	2026-02-08 20:53:49	Login	\N
132	Access	Terminal DC Centro logged in	\N	\N	App\\Models\\User	5	{"ip":"127.0.0.1","user_agent":"Symfony"}	2026-02-08 21:01:32	2026-02-08 21:01:32	Login	\N
133	Resource	Gift Card Updated by Terminal DC Centro	App\\Models\\GiftCard	019c3eed-2c96-717e-8715-0e6777ec38ed	App\\Models\\User	5	{"updated_at":"2026-02-08 21:04:56","balance":990}	2026-02-08 21:04:56	2026-02-08 21:04:56	Updated	\N
134	Resource	Transaction Created by Terminal DC Centro	App\\Models\\Transaction	18	App\\Models\\User	5	{"gift_card_id":"019c3eed-2c96-717e-8715-0e6777ec38ed","type":"debit","amount":10,"balance_before":"1000.00","balance_after":990,"description":"Descuento desde Scanner","admin_user_id":5,"branch_id":2,"updated_at":"2026-02-08 21:04:56","created_at":"2026-02-08 21:04:56","id":18}	2026-02-08 21:04:56	2026-02-08 21:04:56	Created	\N
135	Resource	Gift Card Updated by Terminal DC Centro	App\\Models\\GiftCard	019c3eed-d3ff-7048-b4c2-7d9a00518e01	App\\Models\\User	5	{"updated_at":"2026-02-08 21:05:20","balance":980}	2026-02-08 21:05:20	2026-02-08 21:05:20	Updated	\N
136	Resource	Transaction Created by Terminal DC Centro	App\\Models\\Transaction	19	App\\Models\\User	5	{"gift_card_id":"019c3eed-d3ff-7048-b4c2-7d9a00518e01","type":"debit","amount":20,"balance_before":"1000.00","balance_after":980,"description":"Descuento desde Scanner","admin_user_id":5,"branch_id":2,"updated_at":"2026-02-08 21:05:20","created_at":"2026-02-08 21:05:20","id":19}	2026-02-08 21:05:20	2026-02-08 21:05:20	Created	\N
137	Access	Armando Reyes Guajardo logged in	\N	\N	App\\Models\\User	1	{"ip":"127.0.0.1","user_agent":"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36"}	2026-02-08 23:13:16	2026-02-08 23:13:16	Login	\N
\.


--
-- Data for Name: branch_gift_card; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.branch_gift_card (id, branch_id, gift_card_id, created_at, updated_at) FROM stdin;
1	3	019c3eee-b325-706e-af61-facafb2d0ebb	\N	\N
\.


--
-- Data for Name: branches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.branches (id, name, created_at, updated_at, brand_id) FROM stdin;
1	Mochomos Monterrey	2025-09-29 18:26:18	2025-09-29 18:43:42	1
2	Don Carlos Centro	2026-02-08 19:38:59	2026-02-08 19:38:59	2
3	Don Carlos Norte	2026-02-08 19:40:19	2026-02-08 19:40:19	2
4	La Vaca Sur	2026-02-08 19:41:10	2026-02-08 19:41:10	3
\.


--
-- Data for Name: brands; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.brands (id, chain_id, name, created_at, updated_at) FROM stdin;
1	1	Mochomos	2026-02-09 00:17:18	2026-02-09 00:17:18
2	2	Don Carlos	2026-02-08 19:25:56	2026-02-08 19:25:56
3	2	La Vaca	2026-02-08 19:26:11	2026-02-08 19:26:11
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
qr_costeno_cache_spatie.permission.cache	a:3:{s:5:"alias";a:4:{s:1:"a";s:2:"id";s:1:"b";s:4:"name";s:1:"c";s:10:"guard_name";s:1:"r";s:5:"roles";}s:11:"permissions";a:66:{i:0;a:4:{s:1:"a";i:1;s:1:"b";s:9:"view_role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:1;a:4:{s:1:"a";i:2;s:1:"b";s:13:"view_any_role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:2;a:4:{s:1:"a";i:3;s:1:"b";s:11:"create_role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:3;a:4:{s:1:"a";i:4;s:1:"b";s:11:"update_role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:4;a:4:{s:1:"a";i:5;s:1:"b";s:11:"delete_role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:5;a:4:{s:1:"a";i:6;s:1:"b";s:15:"delete_any_role";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:6;a:4:{s:1:"a";i:7;s:1:"b";s:13:"view_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:7;a:4:{s:1:"a";i:8;s:1:"b";s:17:"view_any_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:8;a:4:{s:1:"a";i:9;s:1:"b";s:15:"create_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:9;a:4:{s:1:"a";i:10;s:1:"b";s:15:"update_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:10;a:4:{s:1:"a";i:11;s:1:"b";s:16:"restore_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:11;a:4:{s:1:"a";i:12;s:1:"b";s:20:"restore_any_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:12;a:4:{s:1:"a";i:13;s:1:"b";s:18:"replicate_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:13;a:4:{s:1:"a";i:14;s:1:"b";s:16:"reorder_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:14;a:4:{s:1:"a";i:15;s:1:"b";s:15:"delete_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:15;a:4:{s:1:"a";i:16;s:1:"b";s:19:"delete_any_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:16;a:4:{s:1:"a";i:17;s:1:"b";s:21:"force_delete_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:17;a:4:{s:1:"a";i:18;s:1:"b";s:25:"force_delete_any_activity";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:18;a:4:{s:1:"a";i:19;s:1:"b";s:11:"view_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:19;a:4:{s:1:"a";i:20;s:1:"b";s:15:"view_any_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:20;a:4:{s:1:"a";i:21;s:1:"b";s:13:"create_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:21;a:4:{s:1:"a";i:22;s:1:"b";s:13:"update_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:22;a:4:{s:1:"a";i:23;s:1:"b";s:14:"restore_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:23;a:4:{s:1:"a";i:24;s:1:"b";s:18:"restore_any_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:24;a:4:{s:1:"a";i:25;s:1:"b";s:16:"replicate_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:25;a:4:{s:1:"a";i:26;s:1:"b";s:14:"reorder_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:26;a:4:{s:1:"a";i:27;s:1:"b";s:13:"delete_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:27;a:4:{s:1:"a";i:28;s:1:"b";s:17:"delete_any_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:28;a:4:{s:1:"a";i:29;s:1:"b";s:19:"force_delete_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:29;a:4:{s:1:"a";i:30;s:1:"b";s:23:"force_delete_any_branch";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:30;a:4:{s:1:"a";i:31;s:1:"b";s:15:"view_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:31;a:4:{s:1:"a";i:32;s:1:"b";s:19:"view_any_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:32;a:4:{s:1:"a";i:33;s:1:"b";s:17:"create_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:33;a:4:{s:1:"a";i:34;s:1:"b";s:17:"update_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:34;a:4:{s:1:"a";i:35;s:1:"b";s:18:"restore_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:35;a:4:{s:1:"a";i:36;s:1:"b";s:22:"restore_any_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:36;a:4:{s:1:"a";i:37;s:1:"b";s:20:"replicate_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:37;a:4:{s:1:"a";i:38;s:1:"b";s:18:"reorder_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:38;a:4:{s:1:"a";i:39;s:1:"b";s:17:"delete_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:39;a:4:{s:1:"a";i:40;s:1:"b";s:21:"delete_any_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:40;a:4:{s:1:"a";i:41;s:1:"b";s:23:"force_delete_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:41;a:4:{s:1:"a";i:42;s:1:"b";s:27:"force_delete_any_gift::card";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:42;a:4:{s:1:"a";i:43;s:1:"b";s:16:"view_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:43;a:4:{s:1:"a";i:44;s:1:"b";s:20:"view_any_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:44;a:4:{s:1:"a";i:45;s:1:"b";s:18:"create_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:45;a:4:{s:1:"a";i:46;s:1:"b";s:18:"update_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:46;a:4:{s:1:"a";i:47;s:1:"b";s:19:"restore_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:47;a:4:{s:1:"a";i:48;s:1:"b";s:23:"restore_any_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:48;a:4:{s:1:"a";i:49;s:1:"b";s:21:"replicate_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:49;a:4:{s:1:"a";i:50;s:1:"b";s:19:"reorder_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:50;a:4:{s:1:"a";i:51;s:1:"b";s:18:"delete_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:51;a:4:{s:1:"a";i:52;s:1:"b";s:22:"delete_any_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:52;a:4:{s:1:"a";i:53;s:1:"b";s:24:"force_delete_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:53;a:4:{s:1:"a";i:54;s:1:"b";s:28:"force_delete_any_transaction";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:54;a:4:{s:1:"a";i:55;s:1:"b";s:9:"view_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:55;a:4:{s:1:"a";i:56;s:1:"b";s:13:"view_any_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:56;a:4:{s:1:"a";i:57;s:1:"b";s:11:"create_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:57;a:4:{s:1:"a";i:58;s:1:"b";s:11:"update_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:58;a:4:{s:1:"a";i:59;s:1:"b";s:12:"restore_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:59;a:4:{s:1:"a";i:60;s:1:"b";s:16:"restore_any_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:60;a:4:{s:1:"a";i:61;s:1:"b";s:14:"replicate_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:61;a:4:{s:1:"a";i:62;s:1:"b";s:12:"reorder_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:62;a:4:{s:1:"a";i:63;s:1:"b";s:11:"delete_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:63;a:4:{s:1:"a";i:64;s:1:"b";s:15:"delete_any_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:64;a:4:{s:1:"a";i:65;s:1:"b";s:17:"force_delete_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}i:65;a:4:{s:1:"a";i:66;s:1:"b";s:21:"force_delete_any_user";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:1;}}}s:5:"roles";a:1:{i:0;a:3:{s:1:"a";i:1;s:1:"b";s:11:"super_admin";s:1:"c";s:3:"web";}}}	1770668270
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: chains; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.chains (id, name, created_at, updated_at) FROM stdin;
1	Cadenas Don Carlos	2026-02-09 00:17:18	2026-02-09 00:17:18
2	Mi Empresa Test	2026-02-08 19:19:11	2026-02-08 19:19:11
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: gift_card_categories; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.gift_card_categories (id, name, prefix, nature, created_at, updated_at) FROM stdin;
1	Empleados	EMCAD	payment_method	2026-02-09 00:17:18	2026-02-09 00:17:18
\.


--
-- Data for Name: gift_cards; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.gift_cards (id, legacy_id, user_id, status, expiry_date, created_at, updated_at, qr_image_path, deleted_at, balance, gift_card_category_id, scope, chain_id, brand_id) FROM stdin;
019996d7-42dc-7222-abf7-052da67509c4	EMCAD20005	1	t	2025-10-11	2025-09-29 18:58:33	2025-10-01 15:12:48	qr-codes/019996d7-42dc-7222-abf7-052da67509c4	2025-10-01 15:12:48	1500.00	1	chain	1	\N
0199a055-6e58-7283-87d4-5a680f587c76	EMCAD20006	1	t	\N	2025-10-01 15:12:57	2025-10-04 01:58:26	qr-codes/0199a055-6e58-7283-87d4-5a680f587c76	\N	800.00	1	chain	1	\N
019c3eee-b325-706e-af61-facafb2d0ebb	EMCAD200011	\N	t	\N	2026-02-08 20:25:56	2026-02-08 20:25:56	qr-codes/019c3eee-b325-706e-af61-facafb2d0ebb	\N	1000.00	1	branch	\N	\N
0199a4fd-74bf-730f-8507-c827c0e0649d	EMCAD200007	3	t	\N	2025-10-02 12:54:57	2025-10-12 15:04:52	qr-codes/0199a4fd-74bf-730f-8507-c827c0e0649d	\N	0.00	1	chain	1	\N
0199c531-aa1e-72c3-a912-442d3769f64d	EMCAD200008	3	t	\N	2025-10-08 18:59:50	2025-10-12 15:04:52	qr-codes/0199c531-aa1e-72c3-a912-442d3769f64d	\N	0.00	1	chain	1	\N
019c3eed-2c96-717e-8715-0e6777ec38ed	EMCAD200009	\N	t	\N	2026-02-08 20:24:16	2026-02-08 21:04:56	qr-codes/019c3eed-2c96-717e-8715-0e6777ec38ed	\N	990.00	1	brand	\N	2
019c3eed-d3ff-7048-b4c2-7d9a00518e01	EMCAD200010	\N	t	\N	2026-02-08 20:24:59	2026-02-08 21:05:20	qr-codes/019c3eed-d3ff-7048-b4c2-7d9a00518e01	\N	980.00	1	chain	2	\N
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2025_08_26_100418_add_two_factor_columns_to_users_table	1
5	2025_09_28_210839_add_deleted_at_to_users_table	1
6	2025_09_28_215346_add_avatar_to_users_table	1
7	2025_09_29_003802_create_activity_log_table	1
8	2025_09_29_003803_add_event_column_to_activity_log_table	1
9	2025_09_29_003804_add_batch_uuid_column_to_activity_log_table	1
10	2025_09_29_182204_create_branches_table	1
11	2025_09_29_183252_add_branch_id_to_users_table	1
12	2025_09_29_185353_create_gift_cards_table	1
13	2025_09_29_185743_modify_legacy_id_in_gift_cards_table	1
14	2025_09_29_225949_add_qr_image_path_to_gift_cards_table	1
15	2025_09_29_232204_add_soft_deletes_to_gift_cards_table	1
16	2025_09_30_205214_create_transactions_table	1
17	2025_09_30_205327_add_balance_to_gift_cards_table	1
18	2025_09_30_220243_add_branch_id_to_transactions_table	1
19	2025_10_01_151319_fix_activity_log_subject_id_column	1
20	2025_10_01_151455_fix_transactions_gift_card_id_column	1
21	2025_10_11_230141_create_permission_tables	1
22	2025_10_11_231654_add_is_active_to_users_table	1
23	2026_02_08_000001_create_gift_card_categories_table	1
24	2026_02_08_000002_add_gift_card_category_id_to_gift_cards_table	1
25	2026_02_08_000003_migrate_existing_gift_cards_to_default_category	1
26	2026_02_08_100001_create_chains_table	1
27	2026_02_08_100002_create_brands_table	1
28	2026_02_08_100003_add_brand_id_to_branches_table	1
29	2026_02_08_100004_migrate_existing_branches_to_default_brand	1
30	2026_02_08_100005_add_scope_fields_to_gift_cards_table	1
31	2026_02_08_100006_create_branch_gift_card_table	1
32	2026_02_08_100007_migrate_existing_gift_cards_to_chain_scope	1
33	2026_02_08_213637_create_push_subscriptions_table	1
\.


--
-- Data for Name: model_has_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.model_has_permissions (permission_id, model_type, model_id) FROM stdin;
\.


--
-- Data for Name: model_has_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.model_has_roles (role_id, model_type, model_id) FROM stdin;
2	App\\Models\\User	3
4	App\\Models\\User	5
1	App\\Models\\User	1
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
armando.reyes@grupocosteno.com	$2y$12$CrGuufVNLyVD1Px6/Cwop.UsrhwRDkiZFiHRMP2B6UFVJsq0d4JX6	2025-10-10 18:42:02
\.


--
-- Data for Name: permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.permissions (id, name, guard_name, created_at, updated_at) FROM stdin;
1	view_role	web	2025-10-11 23:01:55	2025-10-11 23:01:55
2	view_any_role	web	2025-10-11 23:01:55	2025-10-11 23:01:55
3	create_role	web	2025-10-11 23:01:55	2025-10-11 23:01:55
4	update_role	web	2025-10-11 23:01:55	2025-10-11 23:01:55
5	delete_role	web	2025-10-11 23:01:55	2025-10-11 23:01:55
6	delete_any_role	web	2025-10-11 23:01:55	2025-10-11 23:01:55
7	view_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
8	view_any_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
9	create_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
10	update_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
11	restore_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
12	restore_any_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
13	replicate_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
14	reorder_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
15	delete_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
16	delete_any_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
17	force_delete_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
18	force_delete_any_activity	web	2025-10-11 23:02:13	2025-10-11 23:02:13
19	view_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
20	view_any_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
21	create_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
22	update_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
23	restore_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
24	restore_any_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
25	replicate_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
26	reorder_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
27	delete_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
28	delete_any_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
29	force_delete_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
30	force_delete_any_branch	web	2025-10-11 23:02:13	2025-10-11 23:02:13
31	view_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
32	view_any_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
33	create_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
34	update_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
35	restore_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
36	restore_any_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
37	replicate_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
38	reorder_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
39	delete_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
40	delete_any_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
41	force_delete_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
42	force_delete_any_gift::card	web	2025-10-11 23:02:13	2025-10-11 23:02:13
43	view_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
44	view_any_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
45	create_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
46	update_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
47	restore_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
48	restore_any_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
49	replicate_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
50	reorder_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
51	delete_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
52	delete_any_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
53	force_delete_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
54	force_delete_any_transaction	web	2025-10-11 23:02:13	2025-10-11 23:02:13
55	view_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
56	view_any_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
57	create_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
58	update_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
59	restore_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
60	restore_any_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
61	replicate_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
62	reorder_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
63	delete_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
64	delete_any_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
65	force_delete_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
66	force_delete_any_user	web	2025-10-11 23:02:13	2025-10-11 23:02:13
\.


--
-- Data for Name: push_subscriptions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.push_subscriptions (id, subscribable_type, subscribable_id, endpoint, public_key, auth_token, content_encoding, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: role_has_permissions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.role_has_permissions (permission_id, role_id) FROM stdin;
1	1
2	1
3	1
4	1
5	1
6	1
7	1
8	1
9	1
10	1
11	1
12	1
13	1
14	1
15	1
16	1
17	1
18	1
19	1
20	1
21	1
22	1
23	1
24	1
25	1
26	1
27	1
28	1
29	1
30	1
31	1
32	1
33	1
34	1
35	1
36	1
37	1
38	1
39	1
40	1
41	1
42	1
43	1
44	1
45	1
46	1
47	1
48	1
49	1
50	1
51	1
52	1
53	1
54	1
55	1
56	1
57	1
58	1
59	1
60	1
61	1
62	1
63	1
64	1
65	1
66	1
\.


--
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id, name, guard_name, created_at, updated_at) FROM stdin;
1	super_admin	web	2025-10-11 23:01:55	2025-10-11 23:01:55
2	Employee	web	2025-10-12 14:52:48	2025-10-12 14:52:48
4	BranchTerminal	web	2026-02-08 20:17:16	2026-02-08 20:17:16
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
a24PDUAxBpxmgjQ0ymxa9Hf1qpXM7sBQNHqxESSc	\N	185.85.0.29	curl/8.7.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiVEJIWmc0UGk5WGpmazNURHBxTW9mR0Qzd1pDWFp6MXpjaEE3WWlBSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1770596245
YGq2Cs2SqXFYDvJ0zy7rkGJSD39EkDvxas8vfGlM	\N	185.85.0.29	curl/8.7.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiN1NySU51VG5iRWdCa2hmWlB4VGxlQ3J6ZTJYR2lhM2ZGb3g5ODlkSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1770596246
upeyoEUhF4UTp1bDU0qvWj7FWBf7NtY52TETHsSi	1	185.85.0.29	curl/8.7.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOGZ0SWRpclBUWXlqa0xrVEZFakdTQWRLbW8xMnVCOGdXcEZsSWtFSiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==	1770596514
pu1AU8o01ViTAyNbq4GJTx1ehhhx1u7u3tI7Ri0G	\N	185.85.0.29	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36	YTozOntzOjY6Il90b2tlbiI7czo0MDoiU3M5OHZXZE55d3dQZTFXWGlqRzFkVnFPdnpscWNTZWM2b3FlYUo4aCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1770596407
lwyHXkM3gv0QXsst81ldx98zMu2rdf6dCBvJqHq6	\N	185.85.0.29	curl/8.7.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiTXVEYmo5Z0dZWGxqMkhoUlVMczFKRnZYbXdOWGllbHJKYUtzSmVXaSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1770596426
b5HAOEwPfdbbw0dp57Ek79K9lKevFrLETk0HJVVF	\N	185.85.0.29	curl/8.7.1	YToyOntzOjY6Il90b2tlbiI7czo0MDoiY1pxTloxM090cU1LYUR4aEdMd3lRYWxsNlJYMmN6SUxLWlNidHNneSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1770596426
Flm2vNt3QC3eBlPksD2cjhG0CBjflWairR7Qfwho	\N	185.85.0.29	curl/8.7.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiVWhDNG5jVGJvQTNyR3RPY0tlVVY0YmlWRWJONzY4YktUSnVueG1QWSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czozMToiaHR0cDovL2xvY2FsaG9zdDo4MDgwL2Rhc2hib2FyZCI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjMxOiJodHRwOi8vbG9jYWxob3N0OjgwODAvZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1770596443
PXApcvMVZCE7NdAlh81FbzsK7AXUkCDcquJdTLl8	\N	185.85.0.29	curl/8.7.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiVnIzQlJZOTVFZ1o2Qk11RXBzUGowQTlqSHpmUTZIcHZJSnNaOFFvbiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=	1770596485
zHBRP0JK9wM4BrhRrX6uP3XNaPKeULsBinhQP6H1	5	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36	YTo1OntzOjY6Il90b2tlbiI7czo0MDoiM3FZTnFOU1owYmw2WWZxQUEwWDRVeFV3Mnh5TFNib2JoRTR0NnF6ciI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly9xcm1hZGVhcm1hbmRvLnRlc3Qvc2Nhbm5lciI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjU7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiQ3TnV4bXVHZjdSUGFOWUh0VmNKbFQudXJINmh6NWxuSFNCZUJ2YmdPdmM2T0ZMdDJ6a0tZYSI7fQ==	1770592764
kcro3U9rCkAwaHLmD9qRRdhfTbS5sT2fH21zWJqJ	1	127.0.0.1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiRmJKemdQUUZ6dzhjamo4MjNDVDhCaUVhdnprYmFXb2VyWnhHZ2g2dyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9xcm1hZGVhcm1hbmRvLnRlc3QvZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9	1770592765
SdO1Y0jMqfbypjUTSsJbWpIGT5UaY7ezGtj5BFUY	1	185.85.0.29	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiNVhJT09taklZVHV4dncwNUF3Z1ZNMEVKUHRMVlVsSG1HU0gwVVVMQyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODA4MC9hZG1pbiI7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7fQ==	1770596993
\.


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transactions (id, gift_card_id, type, amount, balance_before, balance_after, description, admin_user_id, created_at, updated_at, deleted_at, branch_id) FROM stdin;
1	019996d7-42dc-7222-abf7-052da67509c4	credit	1000.00	0.00	1000.00	Bono de empleado del mes.	1	2025-09-30 20:59:31	2025-10-02 12:55:46	2025-10-02 12:55:46	\N
2	019996d7-42dc-7222-abf7-052da67509c4	credit	1000.00	1000.00	2000.00	Prueba de carga	1	2025-09-30 22:12:05	2025-10-02 12:55:46	2025-10-02 12:55:46	\N
3	019996d7-42dc-7222-abf7-052da67509c4	debit	500.00	2000.00	1500.00	Consumo de a cuenta 2005	1	2025-09-30 22:13:30	2025-10-02 12:55:46	2025-10-02 12:55:46	1
4	019996d7-42dc-7222-abf7-052da67509c4	adjustment	-200.00	1500.00	1300.00	Error de carga!	1	2025-09-30 22:14:20	2025-10-02 12:55:46	2025-10-02 12:55:46	1
5	019996d7-42dc-7222-abf7-052da67509c4	credit	500.00	1300.00	1800.00	Bono mensual Enero	1	2025-09-30 22:58:54	2025-10-02 12:55:46	2025-10-02 12:55:46	\N
6	019996d7-42dc-7222-abf7-052da67509c4	credit	500.00	1800.00	2300.00	Bono mensual Enero	1	2025-09-30 23:00:35	2025-10-02 12:55:41	2025-10-02 12:55:41	\N
7	019996d7-42dc-7222-abf7-052da67509c4	credit	500.00	2300.00	2800.00	Bono mensual Enero	1	2025-09-30 23:02:28	2025-10-02 12:55:41	2025-10-02 12:55:41	\N
8	019996d7-42dc-7222-abf7-052da67509c4	credit	500.00	2800.00	3300.00	Carga masiva: Bono mensual Enero	1	2025-09-30 23:04:05	2025-10-02 12:55:41	2025-10-02 12:55:41	\N
9	019996d7-42dc-7222-abf7-052da67509c4	debit	1000.00	3300.00	2300.00	descuento xxxx	1	2025-10-01 01:57:08	2025-10-02 12:55:41	2025-10-02 12:55:41	1
10	019996d7-42dc-7222-abf7-052da67509c4	debit	200.00	2300.00	2100.00	SISTEMAS	1	2025-10-01 02:35:18	2025-10-02 12:55:41	2025-10-02 12:55:41	1
11	019996d7-42dc-7222-abf7-052da67509c4	debit	100.00	2100.00	2000.00	Descuento desde Scanner	1	2025-10-01 02:37:08	2025-10-02 12:55:41	2025-10-02 12:55:41	1
12	019996d7-42dc-7222-abf7-052da67509c4	debit	100.00	2000.00	1900.00	Descuento desde Scanner	1	2025-10-01 02:37:54	2025-10-02 12:55:41	2025-10-02 12:55:41	1
13	019996d7-42dc-7222-abf7-052da67509c4	debit	200.00	1900.00	1700.00	Descuento desde Scanner	1	2025-10-01 02:39:11	2025-10-02 12:55:41	2025-10-02 12:55:41	1
14	019996d7-42dc-7222-abf7-052da67509c4	debit	100.00	1700.00	1600.00	Descuento desde Scanner	1	2025-10-01 02:42:58	2025-10-02 12:55:41	2025-10-02 12:55:41	1
15	019996d7-42dc-7222-abf7-052da67509c4	debit	100.00	1600.00	1500.00	Descuento desde Scanner	1	2025-10-01 02:45:06	2025-10-02 12:55:41	2025-10-02 12:55:41	1
16	0199a055-6e58-7283-87d4-5a680f587c76	credit	1000.00	0.00	1000.00	testimg	1	2025-10-04 01:58:03	2025-10-04 01:58:03	\N	\N
17	0199a055-6e58-7283-87d4-5a680f587c76	debit	200.00	1000.00	800.00	Descuento desde Scanner	1	2025-10-04 01:58:26	2025-10-04 01:58:26	\N	1
18	019c3eed-2c96-717e-8715-0e6777ec38ed	debit	10.00	1000.00	990.00	Descuento desde Scanner	5	2026-02-08 21:04:56	2026-02-08 21:04:56	\N	2
19	019c3eed-d3ff-7048-b4c2-7d9a00518e01	debit	20.00	1000.00	980.00	Descuento desde Scanner	5	2026-02-08 21:05:20	2026-02-08 21:05:20	\N	2
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, deleted_at, avatar, branch_id, is_active) FROM stdin;
2	Carlos Rodríguez	carlos@empresa.com	\N	$2y$12$RnR1P1cOuhw/1Co.z/yPguvWz6LqnZ8Km3UerqHYL.X/Qp6VFsf5G	\N	2025-09-30 22:39:26	2025-09-30 22:40:02	\N	\N	\N	2025-09-30 22:40:02	\N	\N	t
3	Ismael Briones	i@i.com	\N	$2y$12$uAQDMExYe6GA6zbWY70WseIUxali0mngs4Hw3bF8VwMCa2EwNhXgO	\N	2025-10-02 12:54:40	2025-10-12 15:04:52	\N	\N	\N	\N	\N	1	t
4	Alma López	mariapilar59@example.net	2026-02-08 18:42:53	$2y$12$CPIy.XJmDl6CZTN8B.n7hegCClIRaKgmX7.CVeeVj0R5DPU/DR9ky	4aEcis5IJU	2026-02-08 18:42:53	2026-02-08 18:42:53	\N	\N	\N	\N	\N	\N	t
5	Terminal DC Centro	terminal@doncarlos.com	\N	$2y$12$7NuxmuGf7RPaNYHtVcJlT.urH6hz5lnHSBeBvbgOvc6OFLt2zkKYa	\N	2026-02-08 20:07:51	2026-02-08 20:07:51	\N	\N	\N	\N	\N	2	t
1	Armando Reyes Guajardo	armando.reyes@grupocosteno.com	\N	$2y$12$cwdIY.fFsw7Vl3SdaqKg4etfLImfU7khdO4I4hUdBC/og1LET3gia	\N	2026-02-09 00:19:49	2026-02-09 00:19:49	\N	\N	\N	\N	avatars/01K69DWKNVYAJHCVWZ7Z9WYENH.jpeg	1	t
\.


--
-- Name: activity_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.activity_log_id_seq', 8, true);


--
-- Name: branch_gift_card_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.branch_gift_card_id_seq', 1, false);


--
-- Name: branches_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.branches_id_seq', 1, false);


--
-- Name: brands_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.brands_id_seq', 1, true);


--
-- Name: chains_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.chains_id_seq', 1, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: gift_card_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.gift_card_categories_id_seq', 1, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 33, true);


--
-- Name: permissions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.permissions_id_seq', 1, false);


--
-- Name: push_subscriptions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.push_subscriptions_id_seq', 1, false);


--
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 1, false);


--
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transactions_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: branch_gift_card branch_gift_card_branch_id_gift_card_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch_gift_card
    ADD CONSTRAINT branch_gift_card_branch_id_gift_card_id_unique UNIQUE (branch_id, gift_card_id);


--
-- Name: branch_gift_card branch_gift_card_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch_gift_card
    ADD CONSTRAINT branch_gift_card_pkey PRIMARY KEY (id);


--
-- Name: branches branches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_pkey PRIMARY KEY (id);


--
-- Name: brands brands_chain_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_chain_id_name_unique UNIQUE (chain_id, name);


--
-- Name: brands brands_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: chains chains_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chains
    ADD CONSTRAINT chains_name_unique UNIQUE (name);


--
-- Name: chains chains_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chains
    ADD CONSTRAINT chains_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: gift_card_categories gift_card_categories_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_card_categories
    ADD CONSTRAINT gift_card_categories_name_unique UNIQUE (name);


--
-- Name: gift_card_categories gift_card_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_card_categories
    ADD CONSTRAINT gift_card_categories_pkey PRIMARY KEY (id);


--
-- Name: gift_card_categories gift_card_categories_prefix_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_card_categories
    ADD CONSTRAINT gift_card_categories_prefix_unique UNIQUE (prefix);


--
-- Name: gift_cards gift_cards_legacy_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_cards
    ADD CONSTRAINT gift_cards_legacy_id_unique UNIQUE (legacy_id);


--
-- Name: gift_cards gift_cards_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_cards
    ADD CONSTRAINT gift_cards_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: push_subscriptions push_subscriptions_endpoint_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.push_subscriptions
    ADD CONSTRAINT push_subscriptions_endpoint_unique UNIQUE (endpoint);


--
-- Name: push_subscriptions push_subscriptions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.push_subscriptions
    ADD CONSTRAINT push_subscriptions_pkey PRIMARY KEY (id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: activity_log_log_name_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX activity_log_log_name_index ON public.activity_log USING btree (log_name);


--
-- Name: brands_chain_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX brands_chain_id_index ON public.brands USING btree (chain_id);


--
-- Name: causer; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX causer ON public.activity_log USING btree (causer_type, causer_id);


--
-- Name: gift_card_categories_prefix_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX gift_card_categories_prefix_index ON public.gift_card_categories USING btree (prefix);


--
-- Name: gift_cards_brand_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX gift_cards_brand_id_index ON public.gift_cards USING btree (brand_id);


--
-- Name: gift_cards_chain_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX gift_cards_chain_id_index ON public.gift_cards USING btree (chain_id);


--
-- Name: gift_cards_scope_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX gift_cards_scope_index ON public.gift_cards USING btree (scope);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: push_subscriptions_subscribable_morph_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX push_subscriptions_subscribable_morph_idx ON public.push_subscriptions USING btree (subscribable_type, subscribable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: subject; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX subject ON public.activity_log USING btree (subject_type, subject_id);


--
-- Name: branch_gift_card branch_gift_card_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch_gift_card
    ADD CONSTRAINT branch_gift_card_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE CASCADE;


--
-- Name: branch_gift_card branch_gift_card_gift_card_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branch_gift_card
    ADD CONSTRAINT branch_gift_card_gift_card_id_foreign FOREIGN KEY (gift_card_id) REFERENCES public.gift_cards(id) ON DELETE CASCADE;


--
-- Name: branches branches_brand_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.branches
    ADD CONSTRAINT branches_brand_id_foreign FOREIGN KEY (brand_id) REFERENCES public.brands(id) ON DELETE RESTRICT;


--
-- Name: brands brands_chain_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.brands
    ADD CONSTRAINT brands_chain_id_foreign FOREIGN KEY (chain_id) REFERENCES public.chains(id) ON DELETE RESTRICT;


--
-- Name: gift_cards gift_cards_brand_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_cards
    ADD CONSTRAINT gift_cards_brand_id_foreign FOREIGN KEY (brand_id) REFERENCES public.brands(id) ON DELETE RESTRICT;


--
-- Name: gift_cards gift_cards_chain_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_cards
    ADD CONSTRAINT gift_cards_chain_id_foreign FOREIGN KEY (chain_id) REFERENCES public.chains(id) ON DELETE RESTRICT;


--
-- Name: gift_cards gift_cards_gift_card_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_cards
    ADD CONSTRAINT gift_cards_gift_card_category_id_foreign FOREIGN KEY (gift_card_category_id) REFERENCES public.gift_card_categories(id) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: gift_cards gift_cards_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.gift_cards
    ADD CONSTRAINT gift_cards_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_admin_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_admin_user_id_foreign FOREIGN KEY (admin_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_gift_card_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_gift_card_id_foreign FOREIGN KEY (gift_card_id) REFERENCES public.gift_cards(id) ON DELETE CASCADE;


--
-- Name: users users_branch_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_branch_id_foreign FOREIGN KEY (branch_id) REFERENCES public.branches(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict HMkEDRtGkHCCwihcpvQYDCk3dvivFcWrRGiZfn9Hm4YJhb1Gn59LsBHx9ozrErq

