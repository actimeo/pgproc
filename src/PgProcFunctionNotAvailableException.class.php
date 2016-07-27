<?php

namespace actimeo\pgproc;

class PgProcFunctionNotAvailableException extends \Exception {
  function __construct ($msg) { parent::__construct ($msg); }
}
