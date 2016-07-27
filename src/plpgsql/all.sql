--  Copyright © 2014 ELOL
--    Written by Philippe Martin (contact@elol.fr)
-- --------------------------------------------------------------------------------
--     This file is part of pgprocedures.

--     pgprocedures is free software: you can redistribute it and/or modify
--     it under the terms of the GNU General Public License as published by
--     the Free Software Foundation, either version 3 of the License, or
--     (at your option) any later version.

--     pgprocedures is distributed in the hope that it will be useful,
--     but WITHOUT ANY WARRANTY; without even the implied warranty of
--     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
--     GNU General Public License for more details.

--     You should have received a copy of the GNU General Public License
--     along with pgprocedures.  If not, see <http://www.gnu.org/licenses/>.

DROP FUNCTION IF EXISTS pgprocedures.search_function(prm_schema character varying, prm_method character varying, prm_nargs integer);
DROP TYPE IF EXISTS pgprocedures.search_function;

CREATE TYPE pgprocedures.search_function AS (
       	proc_nspname name,
        proargtypes oidvector,
	prorettype oid,
	ret_typtype character(1),
	ret_typname name,
	ret_nspname name,
	proretset boolean
);

CREATE FUNCTION pgprocedures.search_function(prm_schema character varying, prm_method character varying, prm_nargs integer)
  RETURNS pgprocedures.search_function AS
$BODY$
DECLARE
      ret pgprocedures.search_function;
BEGIN
      SELECT
          pg_namespace_proc.nspname,
          proargtypes,
          prorettype,
          pg_type_ret.typtype,
          pg_type_ret.typname,
          pg_namespace_ret.nspname,
          proretset
      INTO ret
      FROM pg_proc
          INNER JOIN pg_type pg_type_ret ON pg_type_ret.oid = pg_proc.prorettype
          INNER JOIN pg_namespace pg_namespace_ret ON pg_namespace_ret.oid = pg_type_ret.typnamespace
          INNER JOIN pg_namespace pg_namespace_proc ON pg_namespace_proc.oid = pg_proc.pronamespace
      WHERE pg_namespace_proc.nspname = prm_schema AND proname = prm_method AND pronargs = prm_nargs;
      RETURN ret;
END;
$BODY$
LANGUAGE plpgsql;


DROP FUNCTION IF EXISTS pgprocedures.search_arguments(prm_schema character varying, prm_function character varying);
DROP TYPE IF EXISTS pgprocedures.search_arguments;
CREATE TYPE pgprocedures.search_arguments AS (
    argnames varchar[],
    argtypes oidvector
);

CREATE FUNCTION pgprocedures.search_arguments(prm_schema character varying, prm_function character varying)
  RETURNS SETOF pgprocedures.search_arguments AS
$BODY$
DECLARE
 row pgprocedures.search_arguments;
BEGIN
 FOR row IN
   SELECT proargnames, proargtypes
    FROM pg_proc
    INNER JOIN pg_namespace pg_namespace_proc ON pg_namespace_proc.oid = pg_proc.pronamespace
     WHERE pg_namespace_proc.nspname = prm_schema AND proname = prm_function
     ORDER BY pronargs DESC
 LOOP
   RETURN NEXT row;
 END LOOP;
END;
$BODY$
LANGUAGE plpgsql;
