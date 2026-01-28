--
-- PostgreSQL database dump
--

\restrict neXmBfYIpIofpLfYCa8P8fIjWcnkGKPLgzxnUEOU6KfFrlCbnt3jhzC4rYbtxse

-- Dumped from database version 18.1 (Debian 18.1-2)
-- Dumped by pg_dump version 18.1 (Debian 18.1-2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: calculate_order_totals(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.calculate_order_totals() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Calculate size totals
    NEW.small_total := NEW.small_quantity * NEW.small_price;
    NEW.medium_total := NEW.medium_quantity * NEW.medium_price;
    NEW.large_total := NEW.large_quantity * NEW.large_price;
    
    -- Calculate subtotal
    NEW.subtotal := NEW.small_total + NEW.medium_total + NEW.large_total;
    
    -- Calculate tax (10%)
    NEW.tax_amount := NEW.subtotal * 0.10;
    
    -- Calculate delivery fee (free if subtotal > 2000)
    IF NEW.subtotal >= 2000 THEN
        NEW.delivery_fee := 0;
    ELSE
        NEW.delivery_fee := 300;
    END IF;
    
    -- Calculate total amount
    NEW.total_amount := NEW.subtotal + NEW.tax_amount + NEW.delivery_fee - NEW.discount_amount;

    -- Set estimated delivery time (30 minutes from order)
    NEW.estimated_delivery_time := NEW.order_date + INTERVAL '30 minutes';
    
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.calculate_order_totals() OWNER TO postgres;

--
-- Name: generate_order_number(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.generate_order_number() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Format: PH-YYYYMMDD-00001
    IF NEW.order_number IS NULL OR NEW.order_number = '' THEN
        NEW.order_number := 'PH-' || 
                           to_char(CURRENT_DATE, 'YYYYMMDD') || '-' || 
                           LPAD(NEXTVAL('orders_order_number_seq')::text, 5, '0');
    END IF;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.generate_order_number() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: orders; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.orders (
    id integer NOT NULL,
    order_number character varying(50) NOT NULL,
    customer_name character varying(100) NOT NULL,
    customer_phone character varying(20) NOT NULL,
    customer_email character varying(100),
    customer_address text NOT NULL,
    small_quantity integer DEFAULT 0,
    medium_quantity integer DEFAULT 0,
    large_quantity integer DEFAULT 0,
    small_price numeric(10,2) NOT NULL,
    medium_price numeric(10,2) NOT NULL,
    large_price numeric(10,2) NOT NULL,
    small_total numeric(10,2) DEFAULT 0,
    medium_total numeric(10,2) DEFAULT 0,
    large_total numeric(10,2) DEFAULT 0,
    subtotal numeric(10,2) DEFAULT 0,
    tax_amount numeric(10,2) DEFAULT 0,
    delivery_fee numeric(10,2) DEFAULT 0,
    discount_amount numeric(10,2) DEFAULT 0,
    total_amount numeric(10,2) NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying,
    special_instructions text,
    estimated_delivery_time timestamp without time zone,
    actual_delivery_time timestamp without time zone,
    order_date timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT orders_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'confirmed'::character varying, 'preparing'::character varying, 'out_for_delivery'::character varying, 'delivered'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.orders OWNER TO postgres;

--
-- Name: daily_sales; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.daily_sales AS
 SELECT date(order_date) AS sale_date,
    count(*) AS total_orders,
    sum(total_amount) AS total_revenue,
    sum(small_quantity) AS small_sold,
    sum(medium_quantity) AS medium_sold,
    sum(large_quantity) AS large_sold
   FROM public.orders
  WHERE ((status)::text <> 'cancelled'::text)
  GROUP BY (date(order_date))
  ORDER BY (date(order_date)) DESC;


ALTER VIEW public.daily_sales OWNER TO postgres;

--
-- Name: order_summary; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.order_summary AS
 SELECT id,
    order_number,
    customer_name,
    customer_phone,
    customer_email,
    customer_address,
    small_quantity,
    medium_quantity,
    large_quantity,
    small_price,
    medium_price,
    large_price,
    small_total,
    medium_total,
    large_total,
    subtotal,
    tax_amount,
    delivery_fee,
    discount_amount,
    total_amount,
    status,
    special_instructions,
    estimated_delivery_time,
    actual_delivery_time,
    order_date,
    updated_at,
    ((small_quantity + medium_quantity) + large_quantity) AS total_pizzas
   FROM public.orders o
  ORDER BY order_date DESC;


ALTER VIEW public.order_summary OWNER TO postgres;

--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.orders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.orders_id_seq OWNER TO postgres;

--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: orders_order_number_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.orders_order_number_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.orders_order_number_seq OWNER TO postgres;

--
-- Name: pizzas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pizzas (
    id integer NOT NULL,
    pizza_name character varying(100) DEFAULT 'Classic Pizza'::character varying,
    description text DEFAULT 'Delicious pizza with tomato sauce, mozzarella cheese, and fresh basil'::text,
    small_price numeric(10,2) DEFAULT 800 NOT NULL,
    medium_price numeric(10,2) DEFAULT 1200 NOT NULL,
    large_price numeric(10,2) DEFAULT 1500 NOT NULL,
    small_size character varying(20) DEFAULT '20cm'::character varying,
    medium_size character varying(20) DEFAULT '30cm'::character varying,
    large_size character varying(20) DEFAULT '40cm'::character varying,
    image_url text DEFAULT 'https://images.unsplash.com/photo-1601924638867-3ec62c7e5c79'::text,
    is_available boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.pizzas OWNER TO postgres;

--
-- Name: pizzas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pizzas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pizzas_id_seq OWNER TO postgres;

--
-- Name: pizzas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pizzas_id_seq OWNED BY public.pizzas.id;


--
-- Name: staff_shifts; Type: TABLE; Schema: public; Owner: team_4
--

CREATE TABLE public.staff_shifts (
    id integer NOT NULL,
    shift_date date NOT NULL,
    shift_type character varying(20) NOT NULL,
    morning_start time without time zone DEFAULT '08:00:00'::time without time zone,
    morning_end time without time zone DEFAULT '16:00:00'::time without time zone,
    evening_start time without time zone DEFAULT '16:00:00'::time without time zone,
    evening_end time without time zone DEFAULT '23:59:59'::time without time zone,
    staff_count integer DEFAULT 0,
    max_orders_per_hour integer DEFAULT 4,
    current_orders integer DEFAULT 0,
    is_active boolean DEFAULT true,
    notes text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT staff_shifts_shift_type_check CHECK (((shift_type)::text = ANY ((ARRAY['morning'::character varying, 'evening'::character varying])::text[]))),
    CONSTRAINT staff_shifts_staff_count_check CHECK ((staff_count >= 0))
);


ALTER TABLE public.staff_shifts OWNER TO team_4;

--
-- Name: staff_shifts_id_seq; Type: SEQUENCE; Schema: public; Owner: team_4
--

CREATE SEQUENCE public.staff_shifts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.staff_shifts_id_seq OWNER TO team_4;

--
-- Name: staff_shifts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: team_4
--

ALTER SEQUENCE public.staff_shifts_id_seq OWNED BY public.staff_shifts.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: team_4
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username text NOT NULL,
    password text NOT NULL
);


ALTER TABLE public.users OWNER TO team_4;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: team_4
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO team_4;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: team_4
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: pizzas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pizzas ALTER COLUMN id SET DEFAULT nextval('public.pizzas_id_seq'::regclass);


--
-- Name: staff_shifts id; Type: DEFAULT; Schema: public; Owner: team_4
--

ALTER TABLE ONLY public.staff_shifts ALTER COLUMN id SET DEFAULT nextval('public.staff_shifts_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: team_4
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.orders (id, order_number, customer_name, customer_phone, customer_email, customer_address, small_quantity, medium_quantity, large_quantity, small_price, medium_price, large_price, small_total, medium_total, large_total, subtotal, tax_amount, delivery_fee, discount_amount, total_amount, status, special_instructions, estimated_delivery_time, actual_delivery_time, order_date, updated_at) FROM stdin;
1	PH-20260121-00001	sushant 	12345678	sushant.maharjan@apexcollege.edu.np	kanagawa	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	delivered		2026-01-21 03:41:41.244706	\N	2026-01-21 03:11:41.244706	2026-01-21 03:11:41.244706
40	PH-20260126044122879	fhgjk	12345678	k247036@kccollege.ac.jp	zdfxgchvj	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	delivered		2026-01-25 23:11:22.187129	\N	2026-01-25 22:41:22.187129	2026-01-25 22:41:22.187129
7	PH-20260126025358691	sdf	sdf	sushantmaharjan108@gmail.com	sdf	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	delivered	sdf	2026-01-25 21:23:58.066856	\N	2026-01-25 20:53:58.066856	2026-01-25 20:53:58.066856
6	PH-20260121-17689843683757	sushant 	12345678	k247036@kccollege.ac.jp	kanagawa	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	delivered		2026-01-21 04:02:48.148247	\N	2026-01-21 03:32:48.148247	2026-01-21 03:32:48.148247
5	PH-20260121-17689840154598	sushant 	12345678	k247036@kccollege.ac.jp	kanagawa	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	delivered		2026-01-21 03:56:55.669909	\N	2026-01-21 03:26:55.669909	2026-01-21 03:26:55.669909
3	PH-20260121-00003	sushant 	12345678	k247036@kccollege.ac.jp	kanagawa	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	cancelled		2026-01-21 03:43:49.020033	\N	2026-01-21 03:13:49.020033	2026-01-21 03:13:49.020033
2	PH-20260121-00002	sushant 	12345678	k247036@kccollege.ac.jp	kanagawa	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	cancelled		2026-01-21 03:42:06.359136	\N	2026-01-21 03:12:06.359136	2026-01-21 03:12:06.359136
42	PH-20260126075551654	asdf	asdf	sushant.maharjan@apexcollege.edu.np	asdf	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	delivered	adsf	2026-01-26 02:25:51.705538	\N	2026-01-26 01:55:51.705538	2026-01-26 01:55:51.705538
41	PH-20260126044525231	khunchyo	23456789	k247036@kccollege.ac.jp	yokohoma	5	5	5	800.00	1200.00	1500.00	4000.00	6000.00	7500.00	17500.00	1750.00	0.00	0.00	19250.00	delivered	not spicy	2026-01-25 23:15:25.453404	\N	2026-01-25 22:45:25.453404	2026-01-25 22:45:25.453404
44	PH-20260126084704823	mmmuuu	778899	7@gmail.com	yoko	0	0	1	800.00	1200.00	1500.00	0.00	0.00	1500.00	1500.00	150.00	300.00	0.00	1950.00	out_for_delivery	need discount	2026-01-26 03:17:04.716952	\N	2026-01-26 02:47:04.716952	2026-01-26 02:47:04.716952
43	PH-20260126083335965	sushant maharaj	0987654321	kiiki@gmail.com	asdkjf	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	delivered	no cheese please	2026-01-26 03:03:35.659706	\N	2026-01-26 02:33:35.659706	2026-01-26 02:33:35.659706
45	PH-20260127082001612	randi ko ban	123456789	rndi@gmail.com	qwertyu	0	1	0	800.00	1200.00	1500.00	0.00	1200.00	0.00	1200.00	120.00	300.00	0.00	1620.00	confirmed	remove the chees	2026-01-27 02:50:01.654444	\N	2026-01-27 02:20:01.654444	2026-01-27 02:20:01.654444
46	PH-20260127082528512	maharjan	56789	maharjan@gmail.com	yokohoma	1	0	0	800.00	1200.00	1500.00	800.00	0.00	0.00	800.00	80.00	300.00	0.00	1180.00	delivered	this is my last resort	2026-01-27 02:55:28.400459	\N	2026-01-27 02:25:28.400459	2026-01-27 02:25:28.400459
47	PH-20260127083026512	test	123	test@gmail.cim	test	1	0	0	800.00	1200.00	1500.00	800.00	0.00	0.00	800.00	80.00	300.00	0.00	1180.00	preparing	tst	2026-01-27 03:00:26.881731	\N	2026-01-27 02:30:26.881731	2026-01-27 02:30:26.881731
48	PH-20260127083132512	test2	12356	test2@gmail.com	test2	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	confirmed	test2	2026-01-27 03:01:32.782933	\N	2026-01-27 02:31:32.782933	2026-01-27 02:31:32.782933
49	PH-20260127083153400	test3	345345	test3@gmail.com	test3	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	preparing	test3	2026-01-27 03:01:53.006353	\N	2026-01-27 02:31:53.006353	2026-01-27 02:31:53.006353
50	PH-20260127084236730	test4	345678	test4@gmail.com	test4	1	0	0	800.00	1200.00	1500.00	800.00	0.00	0.00	800.00	80.00	300.00	0.00	1180.00	confirmed	test4	2026-01-27 03:12:36.825662	\N	2026-01-27 02:42:36.825662	2026-01-27 02:42:36.825662
51	PH-20260127084259529	test5	123	test5@gmail.com	test5	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	confirmed	test5	2026-01-27 03:12:59.335626	\N	2026-01-27 02:42:59.335626	2026-01-27 02:42:59.335626
52	PH-20260127084433639	test6	123	test6@gmail.com	test6	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	confirmed	test6	2026-01-27 03:14:33.012122	\N	2026-01-27 02:44:33.012122	2026-01-27 02:44:33.012122
53	PH-20260127084952771	test7	34567	test7@gmail.com	test7	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	confirmed	test7	2026-01-27 03:19:52.649448	\N	2026-01-27 02:49:52.649448	2026-01-27 02:49:52.649448
54	PH-20260127085007741	wer	234	23@gmail.com	esr	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	confirmed	sefd	2026-01-27 03:20:07.417625	\N	2026-01-27 02:50:07.417625	2026-01-27 02:50:07.417625
55	PH-20260127085538152	adfasd	adfga	a@gmail.com	asr	1	2	1	800.00	1200.00	1500.00	800.00	2400.00	1500.00	4700.00	470.00	0.00	0.00	5170.00	confirmed	wer	2026-01-27 03:25:38.829273	\N	2026-01-27 02:55:38.829273	2026-01-27 02:55:38.829273
\.


--
-- Data for Name: pizzas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pizzas (id, pizza_name, description, small_price, medium_price, large_price, small_size, medium_size, large_size, image_url, is_available, created_at, updated_at) FROM stdin;
1	Classic Pizza	Delicious pizza with tomato sauce, mozzarella cheese, and fresh basil	800.00	1200.00	1500.00	20cm	30cm	40cm	https://images.unsplash.com/photo-1601924638867-3ec62c7e5c79	t	2026-01-20 21:40:46.512213	2026-01-20 21:40:46.512213
2	Classic Pizza	Delicious pizza with tomato sauce, mozzarella cheese, and fresh basil	800.00	1200.00	1500.00	20cm	30cm	40cm	https://images.unsplash.com/photo-1601924638867-3ec62c7e5c79	t	2026-01-27 19:26:48.885885	2026-01-27 19:26:48.885885
\.


--
-- Data for Name: staff_shifts; Type: TABLE DATA; Schema: public; Owner: team_4
--

COPY public.staff_shifts (id, shift_date, shift_type, morning_start, morning_end, evening_start, evening_end, staff_count, max_orders_per_hour, current_orders, is_active, notes, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: team_4
--

COPY public.users (id, username, password) FROM stdin;
1	admin	$2y$12$DpHN0t4oXy5NtpiLfMf2seqkXK4xXl0xfC56dUxnr6N.76wJKGorG
\.


--
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.orders_id_seq', 55, true);


--
-- Name: orders_order_number_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.orders_order_number_seq', 3, true);


--
-- Name: pizzas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pizzas_id_seq', 2, true);


--
-- Name: staff_shifts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: team_4
--

SELECT pg_catalog.setval('public.staff_shifts_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: team_4
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: orders orders_order_number_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_order_number_key UNIQUE (order_number);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: pizzas pizzas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pizzas
    ADD CONSTRAINT pizzas_pkey PRIMARY KEY (id);


--
-- Name: staff_shifts staff_shifts_pkey; Type: CONSTRAINT; Schema: public; Owner: team_4
--

ALTER TABLE ONLY public.staff_shifts
    ADD CONSTRAINT staff_shifts_pkey PRIMARY KEY (id);


--
-- Name: staff_shifts staff_shifts_shift_date_shift_type_key; Type: CONSTRAINT; Schema: public; Owner: team_4
--

ALTER TABLE ONLY public.staff_shifts
    ADD CONSTRAINT staff_shifts_shift_date_shift_type_key UNIQUE (shift_date, shift_type);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: team_4
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: team_4
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: idx_orders_customer_phone; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orders_customer_phone ON public.orders USING btree (customer_phone);


--
-- Name: idx_orders_order_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orders_order_date ON public.orders USING btree (order_date);


--
-- Name: idx_orders_order_number; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orders_order_number ON public.orders USING btree (order_number);


--
-- Name: idx_orders_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orders_status ON public.orders USING btree (status);


--
-- Name: orders calculate_totals; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER calculate_totals BEFORE INSERT OR UPDATE ON public.orders FOR EACH ROW EXECUTE FUNCTION public.calculate_order_totals();


--
-- Name: orders set_order_number; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_order_number BEFORE INSERT ON public.orders FOR EACH ROW EXECUTE FUNCTION public.generate_order_number();


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT USAGE ON SCHEMA public TO team_4;


--
-- Name: TABLE orders; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.orders TO team_4;


--
-- Name: TABLE daily_sales; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.daily_sales TO team_4;


--
-- Name: TABLE order_summary; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.order_summary TO team_4;


--
-- Name: SEQUENCE orders_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,USAGE ON SEQUENCE public.orders_id_seq TO team_4;


--
-- Name: SEQUENCE orders_order_number_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,USAGE ON SEQUENCE public.orders_order_number_seq TO team_4;


--
-- Name: TABLE pizzas; Type: ACL; Schema: public; Owner: postgres
--

GRANT ALL ON TABLE public.pizzas TO team_4;


--
-- Name: SEQUENCE pizzas_id_seq; Type: ACL; Schema: public; Owner: postgres
--

GRANT SELECT,USAGE ON SEQUENCE public.pizzas_id_seq TO team_4;


--
-- PostgreSQL database dump complete
--

\unrestrict neXmBfYIpIofpLfYCa8P8fIjWcnkGKPLgzxnUEOU6KfFrlCbnt3jhzC4rYbtxse

