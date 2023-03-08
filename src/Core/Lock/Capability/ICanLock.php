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

namespace Friendica\Core\Lock\Capability;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Lock\Exception\LockPersistenceException;

/**
 * Lock Interface
 */
interface ICanLock
{
	/**
	 * Checks, if a key is currently locked to a or my process
	 *
	 * @param string $key The name of the lock
	 */
	public function isLocked(string $key): bool;

	/**
	 *
	 * Acquires a lock for a given name
	 *
	 * @param string  $key     The Name of the lock
	 * @param integer $timeout Seconds until we give up
	 * @param integer $ttl     Seconds The lock lifespan, must be one of the Cache constants
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function acquire(string $key, int $timeout = 120, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Releases a lock if it was set by us
	 *
	 * @param string $key      The Name of the lock
	 * @param bool   $override Overrides the lock to get released
	 *
	 * @return bool Was the unlock successful?
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function release(string $key, bool $override = false): bool;

	/**
	 * Releases all lock that were set by us
	 *
	 * @param bool $override Override to release all locks
	 *
	 * @return bool Was the unlock of all locks successful?
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function releaseAll(bool $override = false): bool;

	/**
	 * Returns the name of the current lock
	 */
	public function getName(): string;

	/**
	 * Lists all locks
	 *
	 * @param string prefix optional a prefix to search
	 *
	 * @return string[] Empty if it isn't supported by the cache driver
	 *
	 * @throws LockPersistenceException In case the underlying persistence throws errors
	 */
	public function getLocks(string $prefix = ''): array;
}
