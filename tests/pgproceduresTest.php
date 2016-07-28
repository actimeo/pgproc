<?php
require 'src/PgProcedures.class.php';
require 'src/PgProcException.class.php';
require 'src/PgProcFunctionNotAvailableException.class.php';
require 'src/PgSchema.class.php';
require_once 'config.inc.php';

use \actimeo\pgproc\PgProcedures;
use \actimeo\pgproc\PgProcFunctionNotAvailableException;
use \actimeo\pgproc\PgProcException;

class pgproceduresTest extends PHPUnit_Framework_TestCase {
  
  protected function setUp() {

    // Get connection params
    global $pg_host, $pg_user, $pg_pass, $pg_database;
    $this->pgHost = $pg_host;
    $this->pgUser = $pg_user;
    $this->pgPass = $pg_pass;
    $this->pgDatabase = $pg_database;
    $this->assertNotNull($this->pgHost);
    $this->assertNotNull($this->pgUser);
    $this->assertNotNull($this->pgPass);
    $this->assertNotNull($this->pgDatabase);
    
    // Create object
    $this->base = new PgProcedures ($this->pgHost, $this->pgUser, $this->pgPass, $this->pgDatabase);
    $this->assertNotNull($this->base);    
    $this->base->startTransaction();
  }

  protected function tearDown() {
    $this->base->commit();
    unset($this->base);
  }
  
  /*********
   * TESTS *
   *********/

  /*
   * Return values
   */
  public function testReturnsInteger() {
    $res = $this->base->pgtests->test_returns_integer();
    $this->assertSame($res, 42);
  }

  public function testReturnsIntegerAsString() {
    $res = $this->base->pgtests->test_returns_integer_as_string();
    $this->assertSame($res, '42');
  }

  public function testReturnsString() {
    $res = $this->base->pgtests->test_returns_string();
    $this->assertSame($res, 'hello');
  }
  
  public function testReturnsNumeric() {
    $res = $this->base->pgtests->test_returns_numeric();
    $this->assertEquals($res, 3.14159, '', 0.00001);
  }
  
  public function testReturnsReal() {
    $res = $this->base->pgtests->test_returns_real();
    $this->assertEquals($res, 3.14, '', 0.00001);
  }

  public function testReturnsBoolTrue() {
    $res = $this->base->pgtests->test_returns_bool_true();
    $this->assertSame($res, true);
  }

  public function testReturnsBoolFalse() {
    $res = $this->base->pgtests->test_returns_bool_false();
    $this->assertSame($res, false);
  }

  public function testReturnsDate() {
    $this->base->set_date_return_format("d/m/Y");
    $res = $this->base->pgtests->test_returns_date();
    $this->assertRegExp('|^\d\d/\d\d/\d\d\d\d$|', $res);

    $this->base->set_date_return_format("Y-m-d");
    $res = $this->base->pgtests->test_returns_date();
    $this->assertRegExp('|^\d\d\d\d-\d\d-\d\d$|', $res);
  }
  
  public function testReturnsInfinityDate() {
    $res = $this->base->pgtests->test_returns_infinity_date();
    $this->assertNull($res);
  }
  
  public function testReturnsMinusInfinityDate() {
    $res = $this->base->pgtests->test_returns_minus_infinity_date();
    $this->assertNull($res);
  }
  
  public function testReturns64Date() {
    $this->base->set_date_return_format("d/m/Y");
    $res = $this->base->pgtests->test_returns_64bits_date();
  }

  public function testReturnsTimestamp() {
    $this->base->set_timestamp_return_format("d/m/Y H:i:s");
    $res = $this->base->pgtests->test_returns_timestamp();
    $this->assertRegExp('|^\d\d/\d\d/\d\d\d\d \d\d:\d\d:\d\d$|', $res);

    $this->base->set_timestamp_return_format("Y-m-d H:i");
    $res = $this->base->pgtests->test_returns_timestamp();
    $this->assertRegExp('|^\d\d\d\d-\d\d-\d\d \d\d:\d\d$|', $res);
  }

  public function testReturnsTime() {
    $this->base->set_time_return_format("H:i:s");
    $res = $this->base->pgtests->test_returns_time();
    $this->assertRegExp('|^\d\d:\d\d:\d\d$|', $res);

    $this->base->set_time_return_format("H:i");
    $res = $this->base->pgtests->test_returns_time();
    $this->assertRegExp('|^\d\d:\d\d$|', $res);
  }

  public function testReturnsComposite() {
    $res = $this->base->pgtests->test_returns_composite();
    $this->assertSame(array('a'=> 1, 'b'=> 'hello'), $res);
  }

  public function testReturnsSetofComposite() {
    $res = $this->base->pgtests->test_returns_setof_composite();
    $this->assertSame(array (array('a'=> 1, 'b'=> 'hello'), array('a'=> 2, 'b'=> 'bye')), $res);
  }


  public function testReturnsEnum() {
    $res = $this->base->pgtests->test_returns_enum();
    $this->assertSame('val1', $res);
  }

  public function testReturnsEnumArray() {
    $res = $this->base->pgtests->test_returns_enum_array();
    $this->assertSame(array('val1', 'val2'), $res);
  }

  public function testReturnsEnumArrayAsNull() {
    $res = $this->base->pgtests->test_returns_null_enum_array();
    $this->assertSame(null, $res);
  }

  /**
   * Not found function
   * @expectedException \actimeo\pgproc\PgProcFunctionNotAvailableException
   */
  public function testNotFoundFunction() {
    $res = $this->base->pgtests->not_found_function();
  }

  /**
   * Not found hidden (prefixed with _) function
   * @expectedException \actimeo\pgproc\PgProcFunctionNotAvailableException
   */
  public function testNotFoundHiddenFunction() {
    $res = $this->base->pgtests->_hidden_function();
  }

  /**
   * Function in right schema
   */
  public function testRightSchema() {
    $res = $this->base->pgtests->function_in_tests_schema();
    $this->assertTrue($res);
  }

  /**
   * Function in another schema
   * @expectedException \actimeo\pgproc\PgProcFunctionNotAvailableException
   */
  public function testWrongSchema() {
    $res = $this->base->otherschema->function_in_tests_schema();
  }

  /**
   * @expectedException \actimeo\pgproc\PgProcException
   */
  public function testFunctionRaisingException() {
    $res = $this->base->pgtests->function_raising_exception();
  }

  /*
   * Input arguments 
   */
  public function testIncrementedInteger() {
    $n = 4;
    $res = $this->base->pgtests->test_returns_incremented_integer($n);
    $this->assertSame($n + 1, $res);
  }

  public function testIncrementedNumeric() {
    $n = 3.14;
    $res = $this->base->pgtests->test_returns_incremented_numeric($n);
    $this->assertEquals($n + 1.5, $res, '', 0.00001);
  }

  public function testIncrementedReal() {
    $n = 1.414;
    $res = $this->base->pgtests->test_returns_incremented_real($n);
    $this->assertEquals($n + 1.42, $res, '', 0.00001);
  }

  public function testCatString() {
    $s = 'hello';
    $res = $this->base->pgtests->test_returns_cat_string($s);
    $this->assertSame($s . '.', $res);
  }

  public function testSameBool() {
    $res = $this->base->pgtests->test_returns_same_bool(true);
    $this->assertSame($res, true);

    $res = $this->base->pgtests->test_returns_same_bool(false);
    $this->assertSame($res, false);
  }

  public function testSameDate() {
    $this->base->set_date_return_format("d/m/Y");
    $this->base->set_date_arg_format("%Y-%m-%d");
    $res = $this->base->pgtests->test_returns_same_date('2015-05-04');
    $this->assertSame($res, '04/05/2015');

    $this->base->set_date_return_format("Y-m-d");
    $this->base->set_date_arg_format("%d/%m/%Y");
    $res = $this->base->pgtests->test_returns_same_date('07/11/2015');
    $this->assertSame($res, '2015-11-07');
  }

  public function testSameTimestamp() {
    $this->base->set_timestamp_return_format("d/m/Y H:i:s");
    $this->base->set_timestamp_arg_format("%Y-%m-%d %l:%M %p");
    $res = $this->base->pgtests->test_returns_same_timestamp('2015-05-04 02:25 PM');
    $this->assertSame($res, '04/05/2015 14:25:00');

    $this->base->set_timestamp_return_format("Y-m-d h:i:s A");
    $this->base->set_timestamp_arg_format("%d/%m/%Y %H:%M");
    $res = $this->base->pgtests->test_returns_same_timestamp('04/05/2015 14:25');
    $this->assertSame($res, '2015-05-04 02:25:00 PM');
  }

  public function testSameTime() {
    $this->base->set_time_return_format("H:i:s");
    $this->base->set_time_arg_format("%l:%M %p");
    $res = $this->base->pgtests->test_returns_same_time('02:25 PM');
    $this->assertSame($res, '14:25:00');

    $this->base->set_time_return_format("h:i:s A");
    $this->base->set_time_arg_format("%H:%M");
    $res = $this->base->pgtests->test_returns_same_time('14:25');
    $this->assertSame($res, '02:25:00 PM');
  }

  public function testIntegerArrayArg() {
    $in = array (1, 2, 3, 4);
    $out = $this->base->pgtests->test_integer_array_arg($in);
    $this->assertSame($out, $in);
  }

  public function testVarcharArrayArg() {
    $in = array ('a', 'b', 'c');
    $out = $this->base->pgtests->test_varchar_array_arg($in);
    $this->assertSame($out, $in);
  }

  /*
   * count 
   */
  public function testCount() {
    $in = array (1, 2, 3, 4);
    $out = $this->base->pgtests->test_integer_array_arg($in, PgProcedures::count());
    $this->assertSame($out, count($in));
  }

  /*
   * order
   */
  public function testOrder() {
    $in = array (1, 3, 2, 4);
    $out = $this->base->pgtests->test_integer_array_arg($in, 
						      PgProcedures::order('test_integer_array_arg', 'DESC'));
    $this->assertSame(array(4, 3, 2, 1), $out);

    $out = $this->base->pgtests->test_integer_array_arg($in, 
						      PgProcedures::order('test_integer_array_arg', 'ASC'));
    $this->assertSame(array(1, 2, 3, 4), $out);
  }

  /*
   * limit
   */
  public function testLimit() {
    $in = array (1, 3, 2, 4);
    $out = $this->base->pgtests->test_integer_array_arg($in, 
						      PgProcedures::limit(2));
    $this->assertSame(array(1, 3), $out);
  }

  /*
   * limit offset 
   */
  public function testLimitOffset() {
    $in = array (1, 3, 2, 4);
    $out = $this->base->pgtests->test_integer_array_arg($in, 
						      PgProcedures::limit(2, 1));
    $this->assertSame(array(3, 2), $out);
  }

  /*
   * distinct / order
   */
  public function testDistinct() {
    $in = array (1, 3, 2, 3, 4);
    $out = $this->base->pgtests->test_integer_array_arg($in, 
						      PgProcedures::distinct(),
						      PgProcedures::order('test_integer_array_arg'));
    $this->assertSame(array(1, 2, 3, 4), $out);
  }

  /*
   * client encoding 
   */
  public function testGetClientEncoding() {
    $enc = $this->base->get_client_encoding();
    $this->assertEquals($enc, 'UTF8');
    
    $utf8string = $this->base->pgtests->test_returns_accented_string();
    $this->assertEquals($utf8string, 'héllo'); // Takes care this current file is utf-8 encoded
  }

  public function testSetClientEncoding() {
    $enc = $this->base->get_client_encoding();
    $this->assertEquals($enc, 'UTF8');
    $this->base->set_client_encoding('ISO-8859-1');
    $isoString = $this->base->pgtests->test_returns_accented_string();
    $this->base->set_client_encoding($enc);
    $this->assertEquals($isoString, utf8_decode('héllo')); // Takes care this current file is utf-8 encoded
  }

  public function testEnumArg() {
    $val = $this->base->pgtests->test_enum_arg('val1');
    $this->assertEquals('val1', $val); // Takes care this current file is utf-8 encoded
  }

  public function testEnumArrayArg() {
    $val = $this->base->pgtests->test_enum_array_arg(array('val1', 'val2'));
    $this->assertEquals($val, array('val1', 'val2')); // Takes care this current file is utf-8 encoded
  }

  public function testEnumEmptyArrayArg() {
    $val = $this->base->pgtests->test_enum_array_arg(array());
    $this->assertEquals($val, null); // Takes care this current file is utf-8 encoded
  }

  public function testReturnsEmptyArray() {
    $val = $this->base->pgtests->test_returns_empty_array();
  }

  public function testContentAdd() {
    $name1 = 'a name';
    $name2 = 'another name';
    $id1 = $this->base->pgtests->content_add($name1);
    $id2 = $this->base->pgtests->content_add($name2);
    $this->assertEquals($id1 + 1, $id2);
  }

  public function testRollback() {
    $name = 'a name';
    $id = $this->base->pgtests->content_add($name);
    $this->base->rollback();
    $cnt = $this->base->pgtests->content_get($id);
    $this->assertEquals($cnt['cnt_id'], 0);
  }

  public function testPublicFunction() {
    $ret = $this->base->pgtests_get_one();
    $this->assertEquals(1, $ret);
  }

  public function testExecuteSQL() {
    $ret = $this->base->execute_sql('SELECT 1');
    $this->assertEquals('1', $ret[0][0]);
  }

  /**
     @expectedException \actimeo\pgproc\PgProcException
  */
  public function testExecuteSQLerror() {
    $ret = $this->base->execute_sql('SELECT a');
    print_r($ret);
  }
}
