<?php
namespace actimeo\pgproc;

class PgSchema {
  private $base;
  private $name;

  function __construct ($base, $name) {
    $this->base = $base;
    $this->name = $name;
  }

  public function __call ($method, $args) {
    if (substr ($method, 0, 1) != '_') {
      $pargs = end ($args);
      $limit = NULL;
      $orders = array ();
      $distinct = false;
      $count = false;
      $nargstodel = 0;
      while (1) {
	if ($this->is_order_arg ($pargs)) {
	  $orders[] = $pargs['order'];
	} else if ($this->is_limit_arg ($pargs)) {
	  $limit = $pargs['limit'];
	} else if ($this->is_distinct_arg ($pargs)) {
	  $distinct = true;
	} else if ($this->is_count_arg ($pargs)) {
	  $count = true;
	} else
	  break;
	$pargs = prev ($args);
	$nargstodel++;
      }
      reset ($args);
      for ($i=0; $i<$nargstodel; $i++)
	array_pop ($args);

      // Search the argument and return types for this function
      list ($schema, $argtypeSchemas, $argtypes, $rettype, $retset) = $this->search_pg_proc ($method, $args);
      if (!is_array ($argtypes) || !strlen ($schema)) {
	// Function not found
	throw new PgProcFunctionNotAvailableException ('Function '.$this->name.'.'.$method.' not available');
      } 
      // Create the SQL string to call the function
      $query = "SELECT ";
      if ($distinct)
	$query .= 'DISTINCT ';
      if ($count)
	$query .= "COUNT(*) ";
      else 
	$query .= "* ";
      $query .= "FROM ".$schema.".".$method." (  ";
      foreach ($argtypes as $i => $argtype) {
	$value = $args[$i];
	if ($value === NULL)
	  $sqlvalue = 'null';
	else {
	  $sqlvalue = $this->escape_value ($argtypeSchemas[$i], $argtype, $value);
	}
	$query .= $sqlvalue.", ";
      }
      $query = substr ($query, 0, -2);
      $query .= ")";

      if (!empty ($orders)) {
	$orders = array_reverse ($orders);
	$orderstr = ' ORDER BY ';
	foreach ($orders as $order) {
	  foreach ($order as $k => $v) {
	    $orderstr .= $k." ".$v.", ";
	  }
	}
	$orderstr = substr ($orderstr, 0, -2);
	$query .= $orderstr;
      }

      if ($limit != NULL) {
	foreach ($limit as $k => $v) {
	  $query .= ' LIMIT '.$k;
	  if ($v)
	    $query .= ' OFFSET '.$v;
	}
      }
      
      // Prepare the return value depending on the return type
      if ($count)  {
	if ($res = $this->pgproc_query ($query)) { 
	  $row = pg_fetch_array ($res);
	  return intval($row['count']);
	}

      } else if (is_array ($rettype)) { // Composite type
	if ($res = $this->pgproc_query ($query)) { 

	  if ($retset) { // SETOF
	    $retsetvalue = array ();
	    while ($row = pg_fetch_array ($res)) {
	      $ret = array ();
	      foreach ($rettype as $name => $subtype) {
		$ret[$name] = $this->cast_value ($subtype, $row[$name]);
	      }
	      $retsetvalue[] = $ret;
	    }
	    if (empty ($retsetvalue))
	      return NULL;
	    else
	      return $retsetvalue;

	  } else { // no SETOF
	    if ($row = pg_fetch_array ($res)) {
	      $ret = array ();
	      foreach ($rettype as $name => $subtype) {
		$ret[$name] = $this->cast_value ($subtype, $row[$name]);
	      }
	      return $ret;
	    }
	  }
	}
	
      } else { // Scalar type

	if ($res = $this->pgproc_query ($query)) { 
	  
	  if ($retset) { // SETOF
	    $retsetvalue = array ();
	    while ($row = pg_fetch_array ($res)) {
	      $retsetvalue[] = $this->cast_value ($rettype, $row[$method]);
	    }
	    if (empty ($retsetvalue))
	      return NULL;
	    else
	      return $retsetvalue;

	  } else { // no SETOF
	    if ($row = pg_fetch_array ($res)) {
	      return $this->cast_value ($rettype, $row[$method]);
	    }
	  }

	}
      }
    } else {
      throw new PgProcFunctionNotAvailableException ('Function not available');
    }
  }

  /* PRIVATE */
  private static function is_order_arg ($arg) {
    return (is_array ($arg) && isset ($arg['order']));
  }

  private static function is_distinct_arg ($arg) {
    return (is_array ($arg) && isset ($arg['distinct']));
  }

  private static function is_count_arg ($arg) {
    return (is_array ($arg) && isset ($arg['count']));
  }

  private static function is_limit_arg ($arg) {
    return (is_array ($arg) && isset ($arg['limit']));
  }

  /**
   * Search method by name and number of args
   * Returns: The types of the arguments
   */
  private function search_pg_proc ($method, $args) {
    $argtypenames = array ();
    $argtypeschemas = array ();
    $nargs = count ($args);

    $query = "SELECT * FROM pgprocedures.search_function ('".$this->name."', '$method', $nargs)";
    
    $rettypename = null;

    if ($res = $this->pgproc_query ($query)) {
      if ($row = pg_fetch_array ($res)) {
	$schema = $row['proc_nspname'];
	$argtypes = $row['proargtypes'];
	$rettype = $row['prorettype'];

	// Get the arguments types
	$argtypeslist = explode (' ', $argtypes);
	foreach ($argtypeslist as $argtype) {
	  if (!strlen (trim ($argtype)))
	    continue;

	  list($argtypeschemas[], $argtypenames[]) = $this->get_pgtype_and_schema ($argtype);
	}

	if (/*$row['ret_nspname'] == 'pg_catalog' &&*/ (in_array($row['ret_typtype'], array('b', 'p', 'e')))) { // scalar type
	  $rettypename = $row['ret_typname'];
	  
	} else if ($row['ret_typtype'] == 'c') { // composite type
	  $query3 = "select attname, typname FROM pg_attribute INNER JOIN pg_type ON pg_attribute.atttypid = pg_type.oid WHERE pg_attribute.attrelid = (select oid FROM pg_class where relname = '".$row['ret_typname']."') AND attnum > 0 ORDER BY attnum";
	  if ($res3 = $this->pgproc_query ($query3)) {
	    $rettypename = array();
	    while ($row3 = pg_fetch_array ($res3)) {
	      $rettypename[$row3['attname']] =  $row3['typname'];
	    }
	  }
	}

      }
    }
    if (count ($argtypenames) == $nargs)
      return array ($schema, $argtypeschemas, $argtypenames, $rettypename, ($row['proretset'] == 't'));
    else
      return NULL;
  }

  public function cast_value ($rettype, $value) {
    if (substr ($rettype, 0, 1) == '_') {
      if ($value === null)
	return null;
      $v = substr ($value, 1, -1);
      if ($v === '')
	return array ();
      
      $ret = explode (',', $v);
      foreach ($ret as &$r) {
	$r = $this->cast_value (substr ($rettype, 1), $r);
      }
      return $ret;
    }
    
    switch ($rettype) {
    case 'oid':
    case 'xid':
    case 'int2':
    case 'int4':
    case 'int8':
      return intval($value);
      break;	  

    case 'name':
    case 'text':
    case 'varchar':
    case 'bpchar':
    case 'char':
    case 'aclitem':
      return $value;
      break;	  

    case 'float4':
    case 'float8':
    case 'numeric':
      return floatval($value);
      break;	  
      
    case 'interval':
      if (substr ($value, 0, 3) == '00:')
	return substr ($value, 3);
      else
	return $value;
      break;
      
    case 'timestamp':
    case 'timestamptz':
      if (strlen ($value)) {
	$timestamp = strtotime ($value);
	return date ($this->base->timestamp_return_format, $timestamp);
      } else
	return NULL;
      break;
      
    case 'date':
      //      echo $value."\n";
      if (strlen ($value)) {
	if ($value === 'infinity')
	  return null;
	else if ($value === '-infinity')
	  return null;
	$timestamp = strtotime ($value);
	return date ($this->base->date_return_format, $timestamp);
      } else {
	return NULL;
      }
      break;
      
    case 'time';
    case 'timetz':
      if (strlen ($value)) {
	$timestamp = strtotime ($value);
	return date ($this->base->time_return_format, $timestamp);
      } else
	return NULL;

      break;
      
    case 'bool':
      return ($value == 't');
      break;
      
    case '': // void
    case 'void':
      return;

    case 'oidvector':
      if (strlen ($value))
	return explode (' ', $value);
      else 
	return;

    case 'enumtype':
    case 'user_right':
    case 'entity':
    case 'topics':
    case 'personview_element_type':
    case 'mainview_element_type':
    case 'param':
    case 'param_type':
    case 'typ':
      // user-defined enum types
      return $value;
      
    default: 
      echo "Unknown type $rettype\n";
    }
  }

  private function escape_value ($typeSchema, $type, $value) {
    if (substr ($type, 0, 1) == '_' && is_array ($value)) {
      $sqlvalue = 'ARRAY[';
      foreach ($value as $subvalue) {
	$sqlvalue .= $this->escape_value ($typeSchema, substr ($type, 1), $subvalue) . ", "; // TODO replace , by pg_type.typdelim
      }      
      if (count ($value) > 0)     
	$sqlvalue = substr ($sqlvalue, 0, -2);
      $sqlvalue .= ']' . '::' . $typeSchema . '.' . substr($type, 1).'[]';
      return $sqlvalue;
    }
    switch ($type) {
    case 'int2':
    case 'int4':
    case 'int8':
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = intval ($value);
      break;

    case 'numeric':
    case 'float4':
    case 'float8':
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = floatval ($value);
      break;
      
    case 'name':
    case 'text':
    case 'varchar':
    case 'bpchar':
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = "'".pg_escape_string ($this->base->handler, $value)."'";
      break;

    case 'bool':
      $sqlvalue = $value ? 'true' : 'false';
      break;

    case 'timestamp':
    case 'timestamptz':
      $parts = strptime ($value, $this->base->timestamp_arg_format);
      if ($parts) {
	$timestamp = mktime($parts['tm_hour'], $parts['tm_min'], $parts['tm_sec'], 
			    $parts['tm_mon']+1, $parts['tm_mday'], $parts['tm_year']+1900);
	$sqlvalue = "'".date ('Y-m-d H:i:s', $timestamp)."'";
      } else {
	$sqlvalue = 'null';
      }
      break;

    case 'date':
      $parts = strptime ($value, $this->base->date_arg_format);
      if ($parts) {
	$timestamp = mktime($parts['tm_hour'], $parts['tm_min'], $parts['tm_sec'], 
			    $parts['tm_mon']+1, $parts['tm_mday'], $parts['tm_year']+1900);
	$sqlvalue = "'".date ('Y-m-d', $timestamp)."'";
      } else {
	$sqlvalue = 'null';
      }
      break;

    case 'time';
    case 'timetz':
      $parts = strptime ($value, $this->base->time_arg_format);
      if ($parts) {
	$timestamp = mktime($parts['tm_hour'], $parts['tm_min'], $parts['tm_sec']);
	$sqlvalue = "'".date ('H:i:s', $timestamp)."'";
      } else {
	$sqlvalue = 'null';
      }
      break;

    default:
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = "'".pg_escape_string ($this->base->handler, $value)."'";
    }
    return $sqlvalue;
  }

  public function get_pgtype_and_schema ($oid) {
    if (isset ($this->pgtypesWithSchema[$oid]))
      return $this->pgtypesWithSchema[$oid];
    else {
      $query2 = "SELECT nspname, typname FROM pg_type INNER JOIN pg_namespace on pg_type.typnamespace = pg_namespace.oid WHERE pg_type.oid=".$oid;
      if ($res2 = $this->pgproc_query ($query2)) {
	if ($row2 = pg_fetch_array ($res2)) {
	  $ret = array($row2['nspname'], $row2['typname']);
	  $this->pgtypesWithSchema[$oid] = $ret;
	  return $ret;
	}
      }
    }
  }

  private function pgproc_query ($q) {
    try {
      $ret = pg_query ($this->base->handler, $q);
      if ($ret === false)
      	throw new PgProcException (pg_last_error($this->base->handler));
      return $ret;
    } catch (\Exception $e) {
      throw new PgProcException (pg_last_error($this->base->handler));
    }      
  }
}
