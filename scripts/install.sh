#! /bin/sh

# Create database
psql -p $DBPORT -c "create user $DBUSER password '$DBPASS'" -U postgres
psql -p $DBPORT -c "CREATE DATABASE $DBNAME WITH ENCODING='UTF8' owner=$DBUSER" -U postgres

FILES="src/sql/pgprocedures.sql src/plpgsql/all.sql tests/tests.sql"
# Install schemas
(echo 'BEGIN TRANSACTION; ' && cat $FILES && echo 'COMMIT; ' ) |  PGPASSWORD=$DBPASS PGOPTIONS="--client-min-messages=warning" psql -p $DBPORT -v ON_ERROR_STOP=1 -q -h localhost -U $DBUSER $DBNAME

echo "<?php \$pg_host = 'localhost'; \$pg_user = '$DBUSER'; \$pg_pass = '$DBPASS'; \$pg_database = '$DBNAME'; \$pg_port = '$DBPORT';" > config.inc.php
