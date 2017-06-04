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


// Provide some ability to lock a PHP function so that multiple processes
// can't run the function concurrently

	public static function set($fn_name, $wait_sec = 2, $timeout = 30) {
		if ($wait_sec == 0) {
			$wait_sec = 2;
		}

		$got_lock = false;
		$start = time();

		do {
			dba:p("LOCK TABLE `locks` WRITE");
			$lock = dba::select('locks', array('locked'), array('name' => $fn_name), array('limit' => 1));

			if ((dbm::is_result($lock)) AND !$lock['locked']) {
				dba::update('locks', array('locked' => true), array('name' => $fn_name));
				$got_lock = true;
			} elseif (!dbm::is_result($lock)) {
				dbm::insert('locks', array('name' => $fn_name, 'locked' => true));
				$got_lock = true;
			}

			dbm::p("UNLOCK TABLES");

			if (!$got_lock) {
				sleep($wait_sec);
			}
		} while (!$got_lock AND ((time() - $start) < $timeout));

		logger('lock_function: function ' . $fn_name . ' with blocking = ' . $block . ' got_lock = ' . $got_lock . ' time = ' . (time() - $start), LOGGER_DEBUG);

		return $got_lock;
	}

	public static function remove($fn_name) {
		dba::update('locks', array('locked' => false), array('name' => $fn_name));

		logger('unlock_function: released lock for function ' . $fn_name, LOGGER_DEBUG);

		return;
	}
}
