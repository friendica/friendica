#!/usr/bin/env php
<?php
/**
 * @file bin/worker.php
 * @brief Starts the background processing
 */
use Friendica\App;
use Friendica\Container;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Core\Update;

// Get options
$shortopts = 'sn';
$longopts = ['spawn', 'no_cron'];
$options = getopt($shortopts, $longopts);

// Ensure that worker.php is executed from the base path of the installation
if (!file_exists("boot.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != '/') {
		$directory = $_SERVER["PWD"] . '/' . $directory;
	}
	$directory = realpath($directory . '/..');

	chdir($directory);
}

require_once "boot.php";

$settings = require 'src/settings.php';
$settings['settings']['channel'] = 'worker';

$container = new Container($settings);

require '/src/dependencies.php';

// We assume that the index.php is called by a frontend process
// The value is set to "true" by default in App
$a = new App(dirname(__DIR__), $container);

// Check the database structure and possibly fixes it
Update::check(true);

// Quit when in maintenance
if (!$a->getMode()->has(App\Mode::MAINTENANCEDISABLED)) {
	return;
}

$a->setBaseURL(Config::get('system', 'url'));

$spawn = array_key_exists('s', $options) || array_key_exists('spawn', $options);

if ($spawn) {
	Worker::spawnWorker();
	exit();
}

$run_cron = !array_key_exists('n', $options) && !array_key_exists('no_cron', $options);

Worker::processQueue($run_cron);

Worker::unclaimProcess();

Worker::endProcess();
