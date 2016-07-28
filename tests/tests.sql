CREATE SCHEMA pgtests;

DROP TABLE IF EXISTS pgtests.content;
CREATE TABLE pgtests.content (
  cnt_id serial PRIMARY KEY,
  cnt_name text NOT NULL
);

DROP TYPE IF EXISTS pgtests.enumtype CASCADE;
CREATE TYPE pgtests.enumtype AS ENUM ('val1', 'val2', 'val3');

CREATE OR REPLACE FUNCTION pgtests.test_returns_integer()
RETURNS integer
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 42;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_integer_as_string()
RETURNS character varying
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT '42'::varchar;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_string()
RETURNS character varying
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 'hello'::varchar;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_numeric()
RETURNS numeric
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 3.14159::numeric;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_real()
RETURNS real
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 3.14::real;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_bool_true()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT true;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_bool_false()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT false;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_date()
RETURNS date
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT CURRENT_TIMESTAMP::date;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_infinity_date()
RETURNS date
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 'infinity'::date;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_minus_infinity_date()
RETURNS date
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT '-infinity'::date;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_64bits_date()
RETURNS date
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT '2040-01-01'::date;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_timestamp()
RETURNS timestamp
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT CURRENT_TIMESTAMP::timestamp;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_time()
RETURNS time
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT CURRENT_TIMESTAMP::time;
$$;

DROP FUNCTION IF EXISTS pgtests.test_returns_composite();
DROP FUNCTION IF EXISTS pgtests.test_returns_setof_composite();
DROP TYPE IF EXISTS pgtests.composite1;
CREATE TYPE pgtests.composite1 AS (
  a integer,
  b varchar
);

CREATE FUNCTION pgtests.test_returns_composite()
RETURNS pgtests.composite1
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT (1, 'hello')::pgtests.composite1;
$$;

CREATE FUNCTION pgtests.test_returns_setof_composite()
RETURNS SETOF pgtests.composite1
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT (1, 'hello')::pgtests.composite1
  UNION SELECT (2, 'bye')::pgtests.composite1;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_enum() 
RETURNS pgtests.enumtype 
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 'val1'::pgtests.enumtype;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_enum_array() 
RETURNS pgtests.enumtype[] 
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT ARRAY['val1', 'val2']::pgtests.enumtype[];
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_null_enum_array() 
RETURNS pgtests.enumtype[] 
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT NULL::pgtests.enumtype[];
$$;

CREATE OR REPLACE FUNCTION pgtests._hidden_function()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT true;
$$;

CREATE OR REPLACE FUNCTION pgtests.function_in_tests_schema()
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT true;
$$;

CREATE OR REPLACE FUNCTION pgtests.function_raising_exception()
RETURNS boolean
LANGUAGE PLPGSQL
IMMUTABLE
AS $$
BEGIN
  RAISE EXCEPTION '"a particular exception message"';
  SELECT true;
END;
$$;

-- test arguments
CREATE OR REPLACE FUNCTION pgtests.test_returns_incremented_integer(n integer)
RETURNS integer
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1 + 1;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_incremented_numeric(n numeric)
RETURNS numeric
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1 + 1.5;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_incremented_real(n real)
RETURNS real
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT ($1 + 1.42)::real;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_cat_string(s varchar)
RETURNS varchar
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1 || '.';
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_same_bool(b boolean)
RETURNS boolean
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_same_date(d date)
RETURNS date
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_same_timestamp(t timestamp)
RETURNS timestamp
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_same_time(t time)
RETURNS time
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT $1;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_integer_array_arg(list integer[]) 
RETURNS SETOF integer
LANGUAGE plpgsql
IMMUTABLE
AS $$
DECLARE 
  i integer;
BEGIN
  FOREACH i IN ARRAY list LOOP
    RETURN NEXT i;
  END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_varchar_array_arg(list varchar[]) 
RETURNS SETOF varchar
LANGUAGE plpgsql
IMMUTABLE
AS $$
DECLARE 
  i varchar;
BEGIN
  FOREACH i IN ARRAY list LOOP
    RETURN NEXT i;
  END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_enum_arg(enumval pgtests.enumtype) 
RETURNS pgtests.enumtype
LANGUAGE plpgsql
IMMUTABLE
AS $$
BEGIN
  RETURN enumval;
END;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_enum_array_arg(list pgtests.enumtype[]) 
RETURNS SETOF pgtests.enumtype
LANGUAGE plpgsql
IMMUTABLE
AS $$
DECLARE 
  i varchar;
BEGIN
  FOREACH i IN ARRAY list LOOP
    RETURN NEXT i;
  END LOOP;
END;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_accented_string()
RETURNS character varying
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT 'héllo'::varchar;
$$;

CREATE OR REPLACE FUNCTION pgtests.test_returns_empty_array()
RETURNS integer[]
LANGUAGE SQL
IMMUTABLE
AS $$
  SELECT '{}'::integer[];
$$;

CREATE OR REPLACE FUNCTION pgtests.content_add(prm_name text) 
RETURNS integer
LANGUAGE plpgsql
VOLATILE
AS $$
DECLARE
  ret integer;
BEGIN
  INSERT INTO pgtests.content (cnt_name) VALUES (prm_name)
    RETURNING cnt_id INTO ret;
  RETURN ret;
END;
$$;

CREATE OR REPLACE FUNCTION pgtests.content_get(prm_id integer)
RETURNS pgtests.content
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
  ret pgtests.content;
BEGIN
  SELECT * INTO ret FROM pgtests.content WHERE cnt_id = prm_id;
  RETURN ret;
END;
$$;
