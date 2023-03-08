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

namespace Friendica\Core\Worker;

use Friendica\App\Mode;
use Friendica\Core\Logger;
use Friendica\DI;

/**
 * Contains the class for the worker background job processing
 */
class Daemon
{
	private static $mode = null;

	/**
	 * Checks if the worker is running in the daemon mode.
	 *
	 * @return boolean
	 */
	public static function isMode()
	{
		if (!is_null(self::$mode)) {
			return self::$mode;
		}

		if (DI::mode()->getExecutor() == Mode::DAEMON) {
			return true;
		}

		$daemon_mode = DI::keyValue()->get('worker_daemon_mode') ?? false;
		if ($daemon_mode) {
			return $daemon_mode;
		}

		if (!function_exists('pcntl_fork')) {
			self::$mode = false;
			return false;
		}

		$pidfile = DI::config()->get('system', 'pidfile');
		if (empty($pidfile)) {
			// No pid file, no daemon
			self::$mode = false;
			return false;
		}

		if (!is_readable($pidfile)) {
			// No pid file. We assume that the daemon had been intentionally stopped.
			self::$mode = false;
			return false;
		}

		$pid     = intval(file_get_contents($pidfile));
		$running = posix_kill($pid, 0);

		self::$mode = $running;
		return $running;
	}

	/**
	 * Test if the daemon is running. If not, it will be started
	 *
	 * @return void
	 */
	public static function checkState()
	{
		if (!DI::config()->get('system', 'daemon_watchdog', false)) {
			return;
		}

		if (!DI::mode()->isNormal()) {
			return;
		}

		// Check every minute if the daemon is running
		if ((DI::keyValue()->get('last_daemon_check') ?? 0) + 60 > time()) {
			return;
		}

		DI::keyValue()->set('last_daemon_check', time());

		$pidfile = DI::config()->get('system', 'pidfile');
		if (empty($pidfile)) {
			// No pid file, no daemon
			return;
		}

		if (!is_readable($pidfile)) {
			// No pid file. We assume that the daemon had been intentionally stopped.
			return;
		}

		$pid = intval(file_get_contents($pidfile));
		if (posix_kill($pid, 0)) {
			Logger::info('Daemon process is running', ['pid' => $pid]);
			return;
		}

		Logger::warning('Daemon process is not running', ['pid' => $pid]);

		self::spawn();
	}

	/**
	 * Spawn a new daemon process
	 *
	 * @return void
	 */
	private static function spawn()
	{
		Logger::notice('Starting new daemon process');
		DI::system()->run('bin/daemon.php', ['start']);
		Logger::notice('New daemon process started');
	}
}
