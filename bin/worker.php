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
 * Starts the background processing
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

use Dice\Dice;
use Friendica\App;
use Friendica\App\Mode;
use Friendica\Core\Update;
use Friendica\Core\Worker;
use Friendica\DI;
use Psr\Log\LoggerInterface;

// Get options
$shortopts = 'sn';
$longopts = ['spawn', 'no_cron'];
$options = getopt($shortopts, $longopts);

// Ensure that worker.php is executed from the base path of the installation
if (!file_exists("index.php") && (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != '/') {
		$directory = $_SERVER["PWD"] . '/' . $directory;
	}
	$directory = realpath($directory . '/..');

	chdir($directory);
}

require dirname(__DIR__) . '/vendor/autoload.php';

$dice = (new Dice())->addRules(include __DIR__ . '/../static/dependencies.config.php');
$dice = $dice->addRule(LoggerInterface::class,['constructParams' => ['worker']]);

DI::init($dice);
\Friendica\Core\Logger\Handler\ErrorHandler::register($dice->create(\Psr\Log\LoggerInterface::class));

DI::mode()->setExecutor(Mode::WORKER);

// Check the database structure and possibly fixes it
Update::check(DI::basePath(), true);

// Quit when in maintenance
if (!DI::mode()->has(App\Mode::MAINTENANCEDISABLED)) {
	return;
}

$spawn = array_key_exists('s', $options) || array_key_exists('spawn', $options);

if ($spawn) {
	Worker::spawnWorker();
	exit();
}

$run_cron = !array_key_exists('n', $options) && !array_key_exists('no_cron', $options);

$process = DI::process()->create(getmypid(), basename(__FILE__));

Worker::processQueue($run_cron, $process);

Worker::unclaimProcess($process);

DI::process()->delete($process);
