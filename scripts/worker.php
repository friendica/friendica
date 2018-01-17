#!/usr/bin/env php
<?php
/**
 * @file scripts/worker.php
 * @brief Starts the background processing
 */

use Friendica\App;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\Worker;

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
require_once "include/dba.php";

$a = new App(dirname(__DIR__));

require_once ".htconfig.php";
dba::connect($db_host, $db_user, $db_pass, $db_data);
unset($db_host, $db_user, $db_pass, $db_data);

Config::load();

// Check the database structure and possibly fixes it
check_db(true);

// Quit when in maintenance
if (Config::get('system', 'maintenance', true)) {
	return;
}

$a->set_baseurl(Config::get('system', 'url'));

Addon::loadHooks();

$spawn = (($_SERVER["argc"] == 2) && ($_SERVER["argv"][1] == "spawn"));

if ($spawn) {
	Worker::spawnWorker();
	killme();
}

$run_cron = (($_SERVER["argc"] <= 1) || ($_SERVER["argv"][1] != "no_cron"));

Worker::processQueue($run_cron);

Worker::unclaimProcess();

Worker::endProcess();

killme();

