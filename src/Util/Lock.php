<?php
/**
 * @file src/Util/Lock.php
 */
namespace Friendica\Util;

/**
 * @file src/Util/Lock.php
 * @brief Functions for preventing parallel execution of functions
 */

use Friendica\Core\Config;
use Friendica\Database\DBM;
use Memcache;
use dba;

require_once 'include/dba.php';

/**
 * @brief This class contain Functions for preventing parallel execution of functions
 */
class Lock
{
	private static $semaphore = [];

	/**
	 * @brief Check for memcache and open a connection if configured
	 *
	 * @return object|boolean The memcache object - or "false" if not successful
	 */
	private static function connectMemcache()
	{
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
	 * @brief Creates a semaphore key
	 *
	 * @param string $fn_name Name of the lock
	 *
	 * @return ressource the semaphore key
	 */
	private static function semaphoreKey($fn_name)
	{
		$temp = get_temppath();

		$file = $temp.'/'.$fn_name.'.sem';

		if (!file_exists($file)) {
			file_put_contents($file, $fn_name);
		}

		return ftok($file, 'f');
	}

	/**
	 * @brief Sets a lock for a given name
	 *
	 * @param string  $fn_name Name of the lock
	 * @param integer $timeout Seconds until we give up
	 *
	 * @return boolean Was the lock successful?
	 */
	public static function set($fn_name, $timeout = 120)
	{
		$got_lock = false;
		$start = time();

		// The second parameter for "sem_acquire" doesn't exist before 5.6.1
		if (function_exists('sem_get') && version_compare(PHP_VERSION, '5.6.1', '>=')) {
			self::$semaphore[$fn_name] = sem_get(self::semaphoreKey($fn_name));
			if (self::$semaphore[$fn_name]) {
				return sem_acquire(self::$semaphore[$fn_name], ($timeout == 0));
			}
		}

		$memcache = self::connectMemcache();
		if (is_object($memcache)) {
			$cachekey = get_app()->get_hostname().";lock:".$fn_name;

			do {
				// We only lock to be sure that nothing happens at exactly the same time
				dba::lock('locks');
				$lock = $memcache->get($cachekey);

				if (!is_bool($lock)) {
					$pid = (int)$lock;

					// When the process id isn't used anymore, we can safely claim the lock for us.
					// Or we do want to lock something that was already locked by us.
					if (!posix_kill($pid, 0) || ($pid == getmypid())) {
						$lock = false;
					}
				}
				if (is_bool($lock)) {
					$memcache->set($cachekey, getmypid(), MEMCACHE_COMPRESSED, 300);
					$got_lock = true;
				}

				dba::unlock();

				if (!$got_lock && ($timeout > 0)) {
					usleep(rand(10000, 200000));
				}
			} while (!$got_lock && ((time() - $start) < $timeout));

			return $got_lock;
		}

		do {
			dba::lock('locks');
			$lock = dba::selectFirst('locks', ['locked', 'pid'], ['name' => $fn_name]);

			if (DBM::isResult($lock)) {
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
					dba::update('locks', ['locked' => true, 'pid' => getmypid()], ['name' => $fn_name]);
					$got_lock = true;
				}
			} elseif (!DBM::isResult($lock)) {
				dba::insert('locks', ['name' => $fn_name, 'locked' => true, 'pid' => getmypid()]);
				$got_lock = true;
			}

			dba::unlock();

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(100000, 2000000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * @brief Removes a lock if it was set by us
	 *
	 * @param string $fn_name Name of the lock
	 * @return mixed
	 */
	public static function remove($fn_name)
	{
		if (function_exists('sem_get') && version_compare(PHP_VERSION, '5.6.1', '>=')) {
			if (empty(self::$semaphore[$fn_name])) {
				return false;
			} else {
				$success = @sem_release(self::$semaphore[$fn_name]);
				unset(self::$semaphore[$fn_name]);
				return $success;
			}
		}

		$memcache = self::connectMemcache();
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

		dba::update('locks', ['locked' => false, 'pid' => 0], ['name' => $fn_name, 'pid' => getmypid()]);
		return;
	}

	/**
	 * @brief Removes all lock that were set by us
	 * @return void
	 */
	public static function removeAll()
	{
		$memcache = self::connectMemcache();
		if (is_object($memcache)) {
			// We cannot delete all cache entries, but this doesn't matter with memcache
			return;
		}

		dba::update('locks', ['locked' => false, 'pid' => 0], ['pid' => getmypid()]);
		return;
	}
}
