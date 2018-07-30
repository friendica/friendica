#!/usr/bin/env php
<?php
/**
 * @file bin/worker.php
 * @brief Starts the background processing
 */

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Core\L10n;

// Get options
$shortopts = 'sn';
$longopts = ['spawn', 'no_cron'];
$options = getopt($shortopts, $longopts);

// Ensure that worker.php is executed from the base path of the installation
if (!file_exists("boot.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/") {
		$directory = $_SERVER["PWD"]."/".$directory;
	}
	$directory = realpath($directory."/..");

	chdir($directory);
}

require_once "boot.php";

$a = new App(dirname(__DIR__));

Config::load();

$lang = L10n::getBrowserLanguage();
L10n::loadTranslationTable($lang);

// Check the database structure and possibly fixes it
check_db(true);

// Quit when in maintenance
if (Config::get('system', 'maintenance', false, true)) {
	return;
}

$a->set_baseurl(Config::get('system', 'url'));

Addon::loadHooks();

$spawn = array_key_exists('s', $options) || array_key_exists('spawn', $options);

if ($spawn) {
	Worker::spawnWorker();
	killme();
}

$run_cron = !array_key_exists('n', $options) && !array_key_exists('no_cron', $options);

Worker::processQueue($run_cron);

Worker::unclaimProcess();

Worker::endProcess();

killme();
