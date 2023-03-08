#!/usr/bin/env php
<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * Run the worker from a daemon.
 *
 * This script was taken from http://php.net/manual/en/function.pcntl-fork.php
 */
if (php_sapi_name() !== 'cli') {
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
	exit();
}

use Dice\Dice;
use Friendica\App\Mode;
use Friendica\Core\Logger;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

// Get options
$shortopts = 'f';
$longopts = ['foreground'];
$options = getopt($shortopts, $longopts);

// Ensure that daemon.php is executed from the base path of the installation
if (!file_exists('index.php') && (sizeof($_SERVER['argv']) != 0)) {
	$directory = dirname($_SERVER['argv'][0]);

	if (substr($directory, 0, 1) != '/') {
		$directory = $_SERVER['PWD'] . '/' . $directory;
	}
	$directory = realpath($directory . '/..');

	chdir($directory);
}

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
$dice = $dice->addRule(LoggerInterface::class,['constructParams' => ['daemon']]);

DI::init($dice);
\Friendica\Core\Logger\Handler\ErrorHandler::register($dice->create(\Psr\Log\LoggerInterface::class));

if (DI::mode()->isInstall()) {
	die("Friendica isn't properly installed yet.\n");
}

DI::mode()->setExecutor(Mode::DAEMON);

DI::config()->reload();

if (empty(DI::config()->get('system', 'pidfile'))) {
	die(<<<TXT
Please set system.pidfile in config/local.config.php. For example:
    
    'system' => [ 
        'pidfile' => '/path/to/daemon.pid',
    ],
TXT
    );
}

$pidfile = DI::config()->get('system', 'pidfile');

if (in_array('start', $_SERVER['argv'])) {
	$mode = 'start';
}

if (in_array('stop', $_SERVER['argv'])) {
	$mode = 'stop';
}

if (in_array('status', $_SERVER['argv'])) {
	$mode = 'status';
}

$foreground = array_key_exists('f', $options) || array_key_exists('foreground', $options);

if (!isset($mode)) {
	die("Please use either 'start', 'stop' or 'status'.\n");
}

if (empty($_SERVER['argv'][0])) {
	die("Unexpected script behaviour. This message should never occur.\n");
}

$pid = null;

if (is_readable($pidfile)) {
	$pid = intval(file_get_contents($pidfile));
}

if (empty($pid) && in_array($mode, ['stop', 'status'])) {
	DI::keyValue()->set('worker_daemon_mode', false);
	die("Pidfile wasn't found. Is the daemon running?\n");
}

if ($mode == 'status') {
	if (posix_kill($pid, 0)) {
		die("Daemon process $pid is running.\n");
	}

	unlink($pidfile);

	DI::keyValue()->set('worker_daemon_mode', false);
	die("Daemon process $pid isn't running.\n");
}

if ($mode == 'stop') {
	posix_kill($pid, SIGTERM);

	unlink($pidfile);

	Logger::notice('Worker daemon process was killed', ['pid' => $pid]);

	DI::keyValue()->set('worker_daemon_mode', false);
	die("Worker daemon process $pid was killed.\n");
}

if (!empty($pid) && posix_kill($pid, 0)) {
	die("Daemon process $pid is already running.\n");
}

Logger::notice('Starting worker daemon.', ['pid' => $pid]);

if (!$foreground) {
	echo "Starting worker daemon.\n";

	DBA::disconnect();

	// Fork a daemon process
	$pid = pcntl_fork();
	if ($pid == -1) {
		echo "Daemon couldn't be forked.\n";
		Logger::warning('Could not fork daemon');
		exit(1);
	} elseif ($pid) {
		// The parent process continues here
		echo 'Child process started with pid ' . $pid . ".\n";
		Logger::notice('Child process started', ['pid' => $pid]);
		file_put_contents($pidfile, $pid);
		exit(0);
	}

	// We now are in the child process
	register_shutdown_function('shutdown');

	// Make the child the main process, detach it from the terminal
	if (posix_setsid() < 0) {
		return;
	}

	// Closing all existing connections with the outside
	fclose(STDIN);

	// And now connect the database again
	DBA::connect();
}

DI::keyValue()->set('worker_daemon_mode', true);

// Just to be sure that this script really runs endlessly
set_time_limit(0);

$wait_interval = intval(DI::config()->get('system', 'cron_interval', 5)) * 60;

$do_cron = true;
$last_cron = 0;

// Now running as a daemon.
while (true) {
	// Check the database structure and possibly fixes it
	Update::check(DI::basePath(), true);

	if (!$do_cron && ($last_cron + $wait_interval) < time()) {
		Logger::info('Forcing cron worker call.', ['pid' => $pid]);
		$do_cron = true;
	}

	if ($do_cron || (!DI::system()->isMaxLoadReached() && Worker::entriesExists() && Worker::isReady())) {
		Worker::spawnWorker($do_cron);
	} else {
		Logger::info('Cool down for 5 seconds', ['pid' => $pid]);
		sleep(5);
	}

	if ($do_cron) {
		// We force a reconnect of the database connection.
		// This is done to ensure that the connection don't get lost over time.
		DBA::reconnect();

		$last_cron = time();
	}

	$start = time();
	Logger::info('Sleeping', ['pid' => $pid, 'until' => gmdate(DateTimeFormat::MYSQL, $start + $wait_interval)]);

	do {
		$seconds = (time() - $start);

		// logarithmic wait time calculation.
		// Background: After jobs had been started, they often fork many workers.
		// To not waste too much time, the sleep period increases.
		$arg = (($seconds + 1) / ($wait_interval / 9)) + 1;
		$sleep = min(1000000, round(log10($arg) * 1000000, 0));
		usleep($sleep);

		$pid = pcntl_waitpid(-1, $status, WNOHANG);
		if ($pid > 0) {
			Logger::info('Children quit via pcntl_waitpid', ['pid' => $pid, 'status' => $status]);
		}

		$timeout = ($seconds >= $wait_interval);
	} while (!$timeout && !Worker\IPC::JobsExists());

	if ($timeout) {
		$do_cron = true;
		Logger::info('Woke up after $wait_interval seconds.', ['pid' => $pid, 'sleep' => $wait_interval]);
	} else {
		$do_cron = false;
		Logger::info('Worker jobs are calling to be forked.', ['pid' => $pid]);
	}
}

function shutdown() {
	posix_kill(posix_getpid(), SIGHUP);
}
