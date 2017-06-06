<?php

namespace Friendica\Util;

/**
 * @file src/Util/Lock.php
 * @brief Functions for preventing parallel execution of functions
 *
 */

use Friendica\Core\Config;
use Memcache;
use dba;
use dbm;

/**
 * @brief This class contain Functions for preventing parallel execution of functions
 */
class Lock {
       /**
         * @brief Check for memcache and open a connection if configured
         *
         * @return object|boolean The memcache object - or "false" if not successful
         */
        public static function memcache() {
                if (!function_exists('memcache_connect')) {
                        return false;
                }

                if (!Config::get('system', 'memcache')) {
                        return false;
                }

                $memcache_host = Config::get('system', 'memcache_host', '127.0.0.1');
                $memcache_port = Config::get('system', 'memcache_port', 11211);

                $memcache = new Memcache;

                if (!$memcache->connect($memcache_host, $memcache_port)) {
                        return false;
                }

                return $memcache;
        }

	/**
	 * @brief Sets a lock for a given name
	 *
	 * @param string $fn_name Name of the lock
	 * @param integer $timeout Seconds until we give up
	 *
	 * @return boolean Was the lock successful?
	 */
	public static function set($fn_name, $timeout = 120) {
		$got_lock = false;
		$start = time();

		$memcache = self::memcache();
		if (is_object($memcache)) {
			$wait_sec = 0.2;
			$cachekey = get_app()->get_hostname().";lock:".$fn_name;

			do {
				$lock = $memcache->get($cachekey);

				if (!is_bool($lock)) {
					$pid = (int)$lock;

					// When the process id isn't used anymore, we can safely claim the lock for us.
					// Or we do want to lock something that was already locked by us.
					if (!posix_kill($pid, 0) OR ($pid == getmypid())) {
						$lock = false;
					}
				}
				if (is_bool($lock)) {
					$memcache->set($cachekey, getmypid(), MEMCACHE_COMPRESSED, 300);
					$got_lock = true;
				}
				if (!$got_lock AND ($timeout > 0)) {
					usleep($wait_sec * 1000000);
				}
			} while (!$got_lock AND ((time() - $start) < $timeout));

			return $got_lock;
		}

		$wait_sec = 2;

		do {
			dba::lock('locks');
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

			dba::unlock();

			if (!$got_lock AND ($timeout > 0)) {
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
		$memcache = self::memcache();
		if (is_object($memcache)) {
			$cachekey = get_app()->get_hostname().";lock:".$fn_name;
			$lock = $memcache->get($cachekey);

			if (!is_bool($lock)) {
				if ((int)$lock == getmypid()) {
					$memcache->delete($cachekey);
				}
			}
			return;
		}

		dba::update('locks', array('locked' => false, 'pid' => 0), array('name' => $fn_name, 'pid' => getmypid()));
		return;
	}

	/**
	 * @brief Removes all lock that were set by us
	 */
	public static function removeAll() {
		$memcache = self::memcache();
		if (is_object($memcache)) {
			// We cannot delete all cache entries, but this doesn't matter with memcache
			return;
		}

		dba::update('locks', array('locked' => false, 'pid' => 0), array('pid' => getmypid()));
		return;
	}
}
