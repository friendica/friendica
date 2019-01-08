#!/usr/bin/env php
<?php

include_once dirname(__DIR__) . '/boot.php';

use Friendica\Container;

$settings = require dirname(__DIR__) . '/../src/settings.php';
$settings['settings']['channel'] = 'console';
$settings['settings']['logfile'] = 'php://stdout';
$settings['settings']['loglevel'] = \Psr\Log\LogLevel::INFO;

$container = new Container($settings);

require dirname(__DIR__) . '/../src/dependencies.php';

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new Friendica\App(dirname(__DIR__), $container);
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
