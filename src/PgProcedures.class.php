<?php

namespace actimeo\pgproc;

class PgProcedures {

  // PG connection parameters
  private $server;
  private $user;
  private $password;
  private $db;
  private $port;

  public $handler; // PG connection handler

  private $pgtypes; // Store already read pg_types 

  // Format under which timestamp, date and time values will be returned
  public $timestamp_return_format;
  public $date_return_format;
  public $time_return_format;

  public $timestamp_args_format;
  public $date_args_format;
  public $time_args_format;

  public function __construct ($server, $user, $password, $db, $port = '5432') {
    $this->server = $server;
    $this->user = $user;
    $this->password = $password;
    $this->db = $db;
    $this->port = $port;
    $this->connect ();
    
    $this->pgtypes = array ();

    $this->timestamp_return_format = "d/m/Y H:i:s";
    $this->date_return_format = "d/m/Y";
    $this->time_return_format = "H:i:s";

    $this->timestamp_arg_format = '%d/%m/%Y %H:%M:%S';
    $this->date_arg_format = '%d/%m/%Y';
    $this->time_arg_format = '%H:%M:%S';
  }

  public function __destruct () {
    $this->disconnect ();
  }

  public function __call ($func, $args) {
    $schema_public = new PgSchema ($this, 'public');
    return $schema_public->__call ($func, $args);
  }

  public function __get ($schema_name) {
    return new PgSchema ($this, $schema_name);
  }

  public function set_timestamp_return_format ($timestamp_return_format) {
    $this->timestamp_return_format = $timestamp_return_format;
  }
  
  public function set_date_return_format ($date_return_format) {
    $this->date_return_format = $date_return_format;
  }
  
  public function set_time_return_format ($time_return_format) {
    $this->time_return_format = $time_return_format;
  }
  
  public function set_timestamp_arg_format ($timestamp_arg_format) {
    $this->timestamp_arg_format = $timestamp_arg_format;
  }
  
  public function set_date_arg_format ($date_arg_format) {
    $this->date_arg_format = $date_arg_format;
  }
  
  public function set_time_arg_format ($time_arg_format) {
    $this->time_arg_format = $time_arg_format;
  }

  public function get_client_encoding () {
    return pg_client_encoding ($this->handler);
  }
  
  public function set_client_encoding ($encoding) {
    return pg_set_client_encoding ($this->handler, $encoding);
  }
  
  public static function order ($attribute, $direction = 'ASC') {
    return array ('order' => array ($attribute => $direction));
  }

  public static function limit ($number, $offset = 0) {
    return array ('limit' => array ($number => $offset));
  }

  public static function distinct () {
    return array ('distinct' => true);
  }

  public static function count () {
    return array ('count' => true);
  }

  // Transactions
  public function startTransaction () {
    $this->pgproc_query ('START TRANSACTION');
  }

  public function commit () {
    $this->pgproc_query ('COMMIT');
  }

  public function rollback () {
    $this->pgproc_query ('ROLLBACK');    
  }
  
  /***********
   * PRIVATE *
   ***********/
  private function connect () {
    $connectionString = "host=".$this->server." port=".$this->port." dbname=".$this->db." user=".$this->user." password=".$this->password;
    $this->handler = pg_connect ($connectionString);
  }

  private function disconnect () {
    if ($this->handler)
      pg_close ($this->handler);
  }
  
  
  private function pgproc_query ($q) {
    $ret = pg_query ($this->handler, $q);
  }
}
