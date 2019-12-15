#!/usr/bin/env php
<?php
/**
 * @file bin/daemon.php
 * @brief Run the worker from a daemon.
 *
 * This script was taken from http://php.net/manual/en/function.pcntl-fork.php
 */

use Dice\Dice;
use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Psr\Log\LoggerInterface;

// Get options
$shortopts = 'f';
$longopts = ['foreground'];
$options = getopt($shortopts, $longopts);

// Ensure that daemon.php is executed from the base path of the installation
if (!file_exists("boot.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/") {
		$directory = $_SERVER["PWD"] . "/" . $directory;
	}
	$directory = realpath($directory . "/..");

	chdir($directory);
}

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
$dice = $dice->addRule(LoggerInterface::class,['constructParams' => ['daemon']]);

DI::init($dice);
$a = DI::app();

if (DI::mode()->isInstall()) {
	die("Friendica isn't properly installed yet.\n");
}

Config::load();

if (empty(Config::get('system', 'pidfile'))) {
	die(<<<TXT
Please set system.pidfile in config/local.config.php. For example:
    
    'system' => [ 
        'pidfile' => '/path/to/daemon.pid',
    ],
TXT
    );
}

$pidfile = Config::get('system', 'pidfile');

if (in_array("start", $_SERVER["argv"])) {
	$mode = "start";
}

if (in_array("stop", $_SERVER["argv"])) {
	$mode = "stop";
}

if (in_array("status", $_SERVER["argv"])) {
	$mode = "status";
}

$foreground = array_key_exists('f', $options) || array_key_exists('foreground', $options);

if (!isset($mode)) {
	die("Please use either 'start', 'stop' or 'status'.\n");
}

if (empty($_SERVER["argv"][0])) {
	die("Unexpected script behaviour. This message should never occur.\n");
}

$pid = null;

if (is_readable($pidfile)) {
	$pid = intval(file_get_contents($pidfile));
}

if (empty($pid) && in_array($mode, ["stop", "status"])) {
	Config::set('system', 'worker_daemon_mode', false);
	die("Pidfile wasn't found. Is the daemon running?\n");
}

if ($mode == "status") {
	if (posix_kill($pid, 0)) {
		die("Daemon process $pid is running.\n");
	}

	unlink($pidfile);

	Config::set('system', 'worker_daemon_mode', false);
	die("Daemon process $pid isn't running.\n");
}

if ($mode == "stop") {
	posix_kill($pid, SIGTERM);

	unlink($pidfile);

	Logger::notice("Worker daemon process was killed", ["pid" => $pid]);

	Config::set('system', 'worker_daemon_mode', false);
	die("Worker daemon process $pid was killed.\n");
}

if (!empty($pid) && posix_kill($pid, 0)) {
	die("Daemon process $pid is already running.\n");
}

Logger::notice('Starting worker daemon.', ["pid" => $pid]);

if (!$foreground) {
	echo "Starting worker daemon.\n";

	// Switch over to daemon mode.
	if ($pid = pcntl_fork()) {
		return;     // Parent
	}

	fclose(STDIN);  // Close all of the standard

	// Enabling this seem to block a running php process with 100% CPU usage when there is an outpout
	// fclose(STDOUT); // file descriptors as we
	// fclose(STDERR); // are running as a daemon.

	DBA::disconnect();

	register_shutdown_function('shutdown');

	if (posix_setsid() < 0) {
		return;
	}

	if ($pid = pcntl_fork()) {
		return;     // Parent
	}

	$pid = getmypid();
	file_put_contents($pidfile, $pid);

	// We lose the database connection upon forking
	DBA::reconnect();
}

Config::set('system', 'worker_daemon_mode', true);

// Just to be sure that this script really runs endlessly
set_time_limit(0);

$wait_interval = intval(Config::get('system', 'cron_interval', 5)) * 60;

$do_cron = true;
$last_cron = 0;

// Now running as a daemon.
while (true) {
	if (!$do_cron && ($last_cron + $wait_interval) < time()) {
		Logger::info('Forcing cron worker call.', ["pid" => $pid]);
		$do_cron = true;
	}

	Worker::spawnWorker($do_cron);

	if ($do_cron) {
		// We force a reconnect of the database connection.
		// This is done to ensure that the connection don't get lost over time.
		DBA::reconnect();

		$last_cron = time();
	}

	Logger::info("Sleeping", ["pid" => $pid]);
	$start = time();
	do {
		$seconds = (time() - $start);

		// logarithmic wait time calculation.
		// Background: After jobs had been started, they often fork many workers.
		// To not waste too much time, the sleep period increases.
		$arg = (($seconds + 1) / ($wait_interval / 9)) + 1;
		$sleep = round(log10($arg) * 1000000, 0);
		usleep($sleep);

		$timeout = ($seconds >= $wait_interval);
	} while (!$timeout && !Worker::IPCJobsExists());

	if ($timeout) {
		$do_cron = true;
		Logger::info("Woke up after $wait_interval seconds.", ["pid" => $pid, 'sleep' => $wait_interval]);
	} else {
		$do_cron = false;
		Logger::info("Worker jobs are calling to be forked.", ["pid" => $pid]);
	}
}

function shutdown() {
	posix_kill(posix_getpid(), SIGHUP);
}
