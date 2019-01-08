<?php
/**
 * @file index.php
 * Friendica
 */

use Friendica\App;
use Friendica\Container;

require_once 'boot.php';

$settings = require __DIR__ . '/src/settings.php';
$settings['settings']['channel']  = 'index';

$container = new Container($settings);

require __DIR__ . '/src/dependencies.php';

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new App(__DIR__, $container, false);

$a->runFrontend();
