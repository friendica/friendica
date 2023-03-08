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

namespace Friendica\Core\Lock\Type;

use Friendica\Core\Lock\Capability\ICanLock;

/**
 * Basic class for Locking with common functions (local acquired locks, releaseAll, ..)
 */
abstract class AbstractLock implements ICanLock
{
	/**
	 * @var array The local acquired locks
	 */
	protected $acquiredLocks = [];

	/**
	 * Check if we've locally acquired a lock
	 *
	 * @param string key The Name of the lock
	 *
	 * @return bool      Returns true if the lock is set
	 */
	protected function hasAcquiredLock(string $key): bool
	{
		return isset($this->acquireLock[$key]) && $this->acquiredLocks[$key] === true;
	}

	/**
	 * Mark a locally acquired lock
	 *
	 * @param string $key The Name of the lock
	 */
	protected function markAcquire(string $key)
	{
		$this->acquiredLocks[$key] = true;
	}

	/**
	 * Mark a release of a locally acquired lock
	 *
	 * @param string $key The Name of the lock
	 */
	protected function markRelease(string $key)
	{
		unset($this->acquiredLocks[$key]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function releaseAll(bool $override = false): bool
	{
		$return = true;

		foreach ($this->acquiredLocks as $acquiredLock => $hasLock) {
			if (!$this->release($acquiredLock, $override)) {
				$return = false;
			}
		}

		return $return;
	}
}
