#!/usr/bin/env php
<?php

include_once dirname(__DIR__) . '/boot.php';

$a = new Friendica\App(dirname(__DIR__));
\Friendica\BaseObject::setApp($a);

(new Friendica\Core\Console($argv))->execute();
