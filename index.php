<?php
/**
 * @file index.php
 * Friendica
 */

use Friendica\App;
use Friendica\Core\LoggerFactory;

require_once 'boot.php';

$logger = LoggerFactory::create('index');

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new App(__DIR__, $logger, false);

$a->runFrontend();
