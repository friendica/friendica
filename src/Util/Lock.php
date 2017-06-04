<?php

namespace Friendica\Util;

/**
 * @file src/Util/Lock.php
 * @brief Functions for preventing parallel execution of functions
 *
 */

use dba;
use dbm;

/**
 * @brief This class contain Functions for preventing parallel execution of functions
 */
class Lock {

	/**
	 * @brief Sets a lock for a given name
	 *
	 * @param string $fn_name Name of the lock
	 * @param integer $timeout Seconds until we give up
	 * @param integer $wait_sec Time between to lock attempts
	 *
	 * @return boolean Was the lock successful?
	 */
	public static function set($fn_name, $timeout = 30, $wait_sec = 2) {
		if ($wait_sec == 0) {
			$wait_sec = 2;
		}

		$got_lock = false;
		$start = time();

		do {
			dba::p("LOCK TABLE `locks` WRITE");
			$lock = dba::select('locks', array('locked', 'pid'), array('name' => $fn_name), array('limit' => 1));

			if (dbm::is_result($lock)) {
				if ($lock['locked']) {
					// When the process id isn't used anymore, we can safely claim the lock for us.
					if (!posix_kill($lock['pid'], 0)) {
						$lock['locked'] = false;
					}
					// We want to lock something that was already locked by us? So we got the lock.
					if ($lock['pid'] == getmypid()) {
						$got_lock = true;
					}
				}
				if (!$lock['locked']) {
					dba::update('locks', array('locked' => true, 'pid' => getmypid()), array('name' => $fn_name));
					$got_lock = true;
				}
			} elseif (!dbm::is_result($lock)) {
				dba::insert('locks', array('name' => $fn_name, 'locked' => true, 'pid' => getmypid()));
				$got_lock = true;
			}

			dba::p("UNLOCK TABLES");

			if (!$got_lock) {
				sleep($wait_sec);
			}
		} while (!$got_lock AND ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * @brief Removes a lock if it was set by us
	 *
	 * @param string $fn_name Name of the lock
	 */
	public static function remove($fn_name) {
		dba::update('locks', array('locked' => false, 'pid' => 0), array('name' => $fn_name, 'pid' => getmypid()));
		return;
	}
}
