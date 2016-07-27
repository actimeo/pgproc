<?php

namespace actimeo\pgproc;

class PgProcException extends \Exception {
  function __construct ($msg) { parent::__construct ($msg); }
}
