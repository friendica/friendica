<?php

namespace Friendica\Core\Lock;

use dba;
use Friendica\Core\Cache;
use Friendica\Database\DBM;
use Friendica\Util\DateTimeFormat;

/**
 * Locking driver that stores the locks in the database
 */
class DatabaseLockDriver extends AbstractLockDriver
{
	/**
	 * (@inheritdoc)
	 */
	public function acquireLock($key, $timeout = 120, $ttl = Cache::FIVE_MINUTES)
	{
		$got_lock = false;
		$start = time();

		do {
			dba::lock('locks');
			$lock = dba::selectFirst('locks', ['locked', 'pid'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

			if (DBM::is_result($lock)) {
				if ($lock['locked']) {
					// We want to lock something that was already locked by us? So we got the lock.
					if ($lock['pid'] == getmypid()) {
						$got_lock = true;
					}
				}
				if (!$lock['locked']) {
					dba::update('locks', ['locked' => true, 'pid' => getmypid(), 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')], ['name' => $key]);
					$got_lock = true;
				}
			} else {
				dba::insert('locks', ['name' => $key, 'locked' => true, 'pid' => getmypid(), 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')]);
				$got_lock = true;
				$this->markAcquire($key);
			}

			dba::unlock();

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(100000, 2000000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseLock($key)
	{
		dba::delete('locks', ['name' => $key, 'pid' => getmypid()]);

		$this->markRelease($key);

		return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseAll()
	{
		dba::delete('locks', ['pid' => getmypid()]);

		$this->acquiredLocks = [];
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		$lock = dba::selectFirst('locks', ['locked'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

		if (DBM::is_result($lock)) {
			return $lock['locked'] !== false;
		} else {
			return false;
		}
	}
}
