#!/usr/bin/env php
<?php
/**
 * @file bin/worker.php
 * @brief Starts the background processing
 */

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Update;
use Friendica\Core\Worker;

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

require dirname(__DIR__) . '/vendor/autoload.php';

$diLibrary = new Dice();
$diLibrary = $diLibrary->addRules(include dirname(__DIR__ ) . '/static/dependencies.conf.php');

$a = $diLibrary->create(App::class, [], ['channel' => 'worker']);

// Check the database structure and possibly fixes it
Update::check($a->getBasePath(), true, $a->getMode());

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
