<?php

namespace Friendica\Core\Lock;
use Friendica\Core\Cache;

/**
 * Lock Driver Interface
 *
 * @author Philipp Holzer <admin@philipp.info>
 */
interface ILockDriver
{
	/**
	 * Checks, if a key is currently locked to a or my process
	 *
	 * @param string $key 		The name of the lock
	 * @return bool
	 */
	public function isLocked($key);

	/**
	 *
	 * Acquires a lock for a given name
	 *
	 * @param string  $key      The Name of the lock
	 * @param integer $timeout  Seconds until we give up
	 * @param integer $ttl      Seconds The lock lifespan, must be one of the Cache constants
	 *
	 * @return boolean Was the lock successful?
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache::FIVE_MINUTES);

	/**
	 * Releases a lock if it was set by us
	 *
	 * @param string $key The Name of the lock
	 *
	 * @return void
	 */
	public function releaseLock($key);

	/**
	 * Releases all lock that were set by us
	 *
	 * @return void
	 */
	public function releaseAll();
}
