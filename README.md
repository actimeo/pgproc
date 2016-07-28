[![Coverage Status](https://coveralls.io/repos/actimeo/pgproc/badge.svg?branch=master&service=github)](https://coveralls.io/github/actimeo/pgproc?branch=master)

Install
==
 - Install from composer:

```bash
$ composer require actimeo/pgproc
```

 -  Create a database

 - Import some code in your database:
```bash
$ cat vendor/actimeo/pgproc/src/sql/pgprocedures.sql | psql -h host -U login -W dbname
$ cat vendor/actimeo/pgproc/src/plpgsql/all.sql	     | psql -h host -U login -W dbname
```

 - Write some procedures in your database,	for example:

```bash
$  cat vendor/actimeo/pgproc/tests/tests.sql | psql -h host -U login -W dbname
```
