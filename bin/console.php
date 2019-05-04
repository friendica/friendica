#!/usr/bin/env php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = new \Dice\Dice();
$dice = $dice->addRules(include dirname(__DIR__) . '/static/dependencies.conf.php');

(new Friendica\Core\Console($dice, $argv))->execute();
