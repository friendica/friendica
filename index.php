<?php
/**
 * @file index.php
 * Friendica
 */

use Friendica\App;

require_once 'boot.php';

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new App(__DIR__, false);

$a->runFrontend();
