<?php

namespace Friendica\Core\Lock;

use Friendica\Core\Cache;
use Friendica\Database\DBA;
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
			DBA::lock('locks');
			$lock = DBA::selectFirst('locks', ['locked', 'pid'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

			if (DBA::isResult($lock)) {
				if ($lock['locked']) {
					// We want to lock something that was already locked by us? So we got the lock.
					if ($lock['pid'] == getmypid()) {
						$got_lock = true;
					}
				}
				if (!$lock['locked']) {
					DBA::update('locks', ['locked' => true, 'pid' => getmypid(), 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')], ['name' => $key]);
					$got_lock = true;
				}
			} else {
				DBA::insert('locks', ['name' => $key, 'locked' => true, 'pid' => getmypid(), 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')]);
				$got_lock = true;
				$this->markAcquire($key);
			}

			DBA::unlock();

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
		DBA::delete('locks', ['name' => $key, 'pid' => getmypid()]);

		$this->markRelease($key);

		return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseAll()
	{
		DBA::delete('locks', ['pid' => getmypid()]);

		$this->acquiredLocks = [];
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		$lock = DBA::selectFirst('locks', ['locked'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

		if (DBA::isResult($lock)) {
			return $lock['locked'] !== false;
		} else {
			return false;
		}
	}
}
