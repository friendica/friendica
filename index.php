<?php
/**
 * @file index.php
 * Friendica
 */

use Dice\Dice;
use Friendica\App;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar --no-dev install" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$diLibrary = new Dice();
$diLibrary = $diLibrary->addRules(include __DIR__ . '/static/dependencies.conf.php');

$a = new App($diLibrary, 'index', false);

$a->runFrontend();
