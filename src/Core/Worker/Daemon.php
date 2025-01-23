<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Worker;

use Friendica\App\Mode;
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
			DI::logger()->info('Daemon process is running', ['pid' => $pid]);
			return;
		}

		DI::logger()->warning('Daemon process is not running', ['pid' => $pid]);

		self::spawn();
	}

	/**
	 * Spawn a new daemon process
	 *
	 * @return void
	 */
	private static function spawn()
	{
		DI::logger()->notice('Starting new daemon process');
		DI::system()->run('bin/console.php', ['start']);
		DI::logger()->notice('New daemon process started');
	}
}
