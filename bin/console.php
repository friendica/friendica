#!/usr/bin/env php
<?php

include_once dirname(__DIR__) . '/boot.php';

use Friendica\Core\LoggerFactory;

$logger = LoggerFactory::create('console');

$a = new Friendica\App(dirname(__DIR__), $logger);
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
