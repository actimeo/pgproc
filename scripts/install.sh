#! /bin/sh

# Create database
psql -c "create user $DBUSER password '$DBPASS'" -U postgres
psql -c "CREATE DATABASE $DBNAME WITH ENCODING='UTF8' owner=$DBUSER" -U postgres
PGPASSWORD=$DBPASS psql $DBNAME -c "CREATE SCHEMA pgcrypto AUTHORIZATION $DBUSER;"
psql $DBNAME -c "CREATE EXTENSION pgcrypto WITH SCHEMA pgcrypto;" -U postgres

TESTFILE=tests/tests.sql
# Install schemas
(echo 'BEGIN TRANSACTION; ' && cat $TESTFILE && echo 'COMMIT; ' ) |  PGPASSWORD=$DBPASS PGOPTIONS="--client-min-messages=warning" psql -v ON_ERROR_STOP=1 -q -h localhost -U $DBUSER $DBNAME

echo "<?php \$pg_host = 'localhost'; \$pg_user = '$DBUSER'; \$pg_pass = '$DBPASS'; \$pg_database = '$DBNAME';" > config.inc.php
