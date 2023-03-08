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

namespace Friendica\Util;

/**
 * Pidfile class
 */
class PidFile
{
	/**
	 * Read the pid from a given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean|string PID or "false" if not existent
	 */
	private static function pidFromFile(string $file)
	{
		if (!file_exists($file)) {
			return false;
		}

		return trim(@file_get_contents($file));
	}

	/**
	 * Is there a running process with the given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean Is it running?
	 */
	public static function isRunningProcess(string $file): bool
	{
		$pid = self::pidFromFile($file);

		if (!$pid) {
			return false;
		}

		// Is the process running?
		$running = posix_kill($pid, 0);

		// If not, then we will kill the stale file
		if (!$running) {
			self::delete($file);
		}
		return $running;
	}

	/**
	 * Kills a process from a given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean Was it killed successfully?
	 */
	public static function killProcess(string $file): bool
	{
		$pid = self::pidFromFile($file);

		// We don't have a process id? then we quit
		if (!$pid) {
			return false;
		}

		// We now kill the process
		$killed = posix_kill($pid, SIGTERM);

		// If we killed the process successfully, we can remove the pidfile
		if ($killed) {
			self::delete($file);
		}
		return $killed;
	}

	/**
	 * Creates a pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean|string PID or "false" if not created
	 */
	public static function create(string $file)
	{
		$pid = self::pidFromFile($file);

		// We have a process id? then we quit
		if ($pid) {
			return false;
		}

		$pid = getmypid();
		file_put_contents($file, $pid);

		// Now we check if everything is okay
		return self::pidFromFile($file);
	}

	/**
	 * Deletes a given pid file
	 *
	 * @param string $file Filename of pid file
	 *
	 * @return boolean Is it running?
	 */
	public static function delete(string $file): bool
	{
		return @unlink($file);
	}
}
