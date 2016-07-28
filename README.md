[![Build Status](https://travis-ci.org/actimeo/pgproc.svg?branch=master)](https://travis-ci.org/actimeo/pgproc)
[![Coverage Status](https://coveralls.io/repos/actimeo/pgproc/badge.svg?branch=master&service=github)](https://coveralls.io/github/actimeo/pgproc?branch=master)

Install
==
 - Install with composer:

```bash
$ composer require actimeo/pgproc
```

 -  Create a database

 - Import necessary code in your database:
```bash
$ cat vendor/actimeo/pgproc/src/sql/pgprocedures.sql | psql -h host -U login -W dbname
$ cat vendor/actimeo/pgproc/src/plpgsql/all.sql | psql -h host -U login -W dbname
```

Usage
==

 - Write some procedures in your database, for example:

```bash
$ cat vendor/actimeo/pgproc/tests/tests.sql | psql -h host -U login -W dbname
```

```php
<?php
require 'config.inc.php';
require 'vendor/autoload.php';

use \actimeo\pgproc\PgProcedures;

// Instanciate the class with the arguments necessary to connect to the database
$base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database);

// Call a stored procedure: just use the name of the stored procedure itself, with its corresponding arguments
// Depending on the return type of the stored procedure, the $ret variable will be 
// - a simple variable (for, for example, RETURNS INTEGER), 
$ret = $base->pgtests->test_returns_integer ();
echo $ret."\n";

// - an array (for, for example, RETURNS SETOF INTEGER), 
$ret = $base->pgtests->test_integer_array_arg(array(1, 2, 3));
print_r($ret); 

// - a map (for, for example, RETURNS a_composite_type) 
$ret = $base->pgtests->test_returns_composite();
print_r($ret); 

// - or an array of maps (for, for example, RETURNS SETOF a_composite_type).
$ret = $base->pgtests->test_returns_setof_composite();
print_r($ret); 

// You can order the result:
$ret = $base->pgtests->test_integer_array_arg(array(3, 1, 2), 
					      PgProcedures::order('test_integer_array_arg', 'DESC'));
print_r($ret);

// Limit the result:
$ret = $base->pgtests->test_integer_array_arg(array(3, 1, 2), 
					      PgProcedures::limit(2));
print_r($ret);

// ... with an offset:
$ret = $base->pgtests->test_integer_array_arg(array(3, 1, 2), 
					      PgProcedures::limit(2, 1));
print_r($ret);

// Get DISTINCT values from the result:
$ret = $base->pgtests->test_integer_array_arg(array(3, 1, 2, 3, 1, 3), 
					      PgProcedures::distinct());
print_r($ret);

// Get COUNT(*) from the result:
$ret = $base->pgtests->test_integer_array_arg(array(3, 1, 2, 3, 1, 3), 
					      PgProcedures::count());
echo $ret."\n";

// You can also use these three functions for transactions:
$base->startTransaction ();
$base->commit ();
$base->rollback ();

// If the name of the stored procedure is contained in a string, you can call your function like this:
$functionName = 'test_returns_incremented_integer';
$n = 41;
$ret = $base->pgtests->__call ($functionName, array($n));
echo $ret."\n";

// By default, dates are returned with the format "d/m/Y", and times with the format "H:i:s" 
// (see the documentationfor the PHP __ date __ function for more information about date formats).
// You can change these formats with the following functions:
$base->set_timestamp_return_format ('Y-m-d h:i:s A');
$ret = $base->pgtests->test_returns_timestamp();
echo $ret."\n";

$base->set_timestamp_return_format ('d/m/Y H:i:s');
$ret = $base->pgtests->test_returns_timestamp();
echo $ret."\n";

$base->set_date_return_format ('Y-m-d');
$ret = $base->pgtests->test_returns_date();
echo $ret."\n";

$base->set_date_return_format ('d/m/Y');
$ret = $base->pgtests->test_returns_date();
echo $ret."\n";

$base->set_time_return_format ('h:i:s A');
$ret = $base->pgtests->test_returns_time();
echo $ret."\n";

$base->set_time_return_format ('H:i:s');
$ret = $base->pgtests->test_returns_time();
echo $ret."\n";

// By default, when you pass dates and times as arguments to a function, 
// you have to use the formats '%d/%m/%Y' and '%H:%M:%S'
// (see the documentation for the PHP __ strftime __ function for more information about this format).
// You can change these formats with the following functions: 
$base->set_timestamp_arg_format ('%Y-%m-%d %H:%M:%S %p');
$base->set_date_arg_format ('%Y-%m-%d');
$base->set_time_arg_format ('%H:%M:%S %p');

// You can get the character set used in the database to store the text with the following function:
$ret = $base->get_client_encoding ();
echo $ret."\n";

// By default, text will be returned using the server character set. You can enable automatic character set conversion 
// between server and client specifying with the following function the character set in which you want the text to be retrieved:
$base->set_client_encoding ('LATIN1');

// Stored procedures prefixed by an underscore (_) are not accessible through this class.

```