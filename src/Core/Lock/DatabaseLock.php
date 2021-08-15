<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Core\Lock;

use Friendica\Core\BaseLock;
use Friendica\Core\Cache\Duration;
use Friendica\Database\Database;
use Friendica\Util\DateTimeFormat;

/**
 * Locking driver that stores the locks in the database
 */
class DatabaseLock extends BaseLock
{
	/**
	 * The current ID of the process
	 *
	 * @var int
	 */
	private $pid;

	/**
	 * @var Database The database connection of Friendica
	 */
	private $dba;

	/**
	 * The id of the current host
	 *
	 * @var int
	 */
	private $hostId;

	/**
	 * @param null|int $pid The Id of the current process (null means determine automatically)
	 */
	public function __construct(Database $dba, int $hostId, $pid = null)
	{
		$this->dba    = $dba;
		$this->hostId = $hostId;
		$this->pid    = isset($pid) ? $pid : getmypid();
	}

	/**
	 * (@inheritdoc)
	 */
	public function acquire($key, $timeout = 120, $ttl = Duration::FIVE_MINUTES)
	{
		$got_lock = false;
		$start    = time();

		do {
			$this->dba->lock('locks');
			$lock = $this->dba->selectFirst('locks', ['locked', 'host-id', 'pid'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

			if ($this->dba->isResult($lock)) {
				if ($lock['locked']) {
					// We want to lock something that was already locked by us? So we got the lock.
					if ($lock['pid'] == $this->pid && $lock['host-id'] == $this->hostId) {
						$got_lock = true;
					}
				}
				if (!$lock['locked']) {
					$this->dba->update('locks', ['locked' => true, 'host-id' => $this->hostId, 'pid' => $this->pid, 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')], ['name' => $key]);
					$got_lock = true;
				}
			} else {
				$this->dba->insert('locks', ['name' => $key, 'locked' => true, 'host-id' => $this->hostId, 'pid' => $this->pid, 'expires' => DateTimeFormat::utc('now + ' . $ttl . 'seconds')]);
				$got_lock = true;
				$this->markAcquire($key);
			}

			$this->dba->unlock();

			if (!$got_lock && ($timeout > 0)) {
				usleep(rand(100000, 2000000));
			}
		} while (!$got_lock && ((time() - $start) < $timeout));

		return $got_lock;
	}

	/**
	 * (@inheritdoc)
	 */
	public function release($key, $override = false)
	{
		if ($override) {
			$where = ['name' => $key];
		} else {
			$where = ['name' => $key, 'host-id' => $this->hostId, 'pid' => $this->pid];
		}

		if ($this->dba->exists('locks', $where)) {
			$return = $this->dba->delete('locks', $where);
		} else {
			$return = false;
		}

		$this->markRelease($key);

		return $return;
	}

	/**
	 * (@inheritdoc)
	 */
	public function releaseAll($override = false)
	{
		$success = parent::releaseAll($override);

		if ($override) {
			$where = ['1 = 1'];
		} else {
			$where = ['host-id' => $this->hostId, 'pid' => $this->pid];
		}
		$return = $this->dba->delete('locks', $where);

		$this->acquiredLocks = [];

		return $return && $success;
	}

	/**
	 * (@inheritdoc)
	 */
	public function isLocked($key)
	{
		$lock = $this->dba->selectFirst('locks', ['locked'], ['`name` = ? AND `expires` >= ?', $key, DateTimeFormat::utcNow()]);

		if ($this->dba->isResult($lock)) {
			return $lock['locked'] !== false;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return Type::DATABASE;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getLocks(string $prefix = '')
	{
		if (empty($prefix)) {
			$where = ['`expires` >= ?', DateTimeFormat::utcNow()];
		} else {
			$where = ['`expires` >= ? AND `name` LIKE CONCAT(?, \'%\')', DateTimeFormat::utcNow(), $prefix];
		}

		$stmt = $this->dba->select('locks', ['name'], $where);

		$keys = [];
		while ($key = $this->dba->fetch($stmt)) {
			array_push($keys, $key['name']);
		}
		$this->dba->close($stmt);

		return $keys;
	}
}
