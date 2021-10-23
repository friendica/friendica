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

use Friendica\Core\Cache\Enum\Duration;

/**
 * Lock Interface
 */
interface ILock
{
	/**
	 * Checks, if a key is currently locked to a or my process
	 *
	 * @param string $key The name of the lock
	 *
	 * @return bool
	 */
	public function isLocked($key);

	/**
	 *
	 * Acquires a lock for a given name
	 *
	 * @param string  $key     The Name of the lock
	 * @param integer $timeout Seconds until we give up
	 * @param integer $ttl     Seconds The lock lifespan, must be one of the Cache constants
	 *
	 * @return boolean Was the lock successful?
	 */
	public function acquire($key, $timeout = 120, $ttl = Duration::FIVE_MINUTES);

	/**
	 * Releases a lock if it was set by us
	 *
	 * @param string $key      The Name of the lock
	 * @param bool   $override Overrides the lock to get released
	 *
	 * @return boolean Was the unlock successful?
	 */
	public function release($key, $override = false);

	/**
	 * Releases all lock that were set by us
	 *
	 * @param bool $override Override to release all locks
	 *
	 * @return boolean Was the unlock of all locks successful?
	 */
	public function releaseAll($override = false);

	/**
	 * Returns the name of the current lock
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Lists all locks
	 *
	 * @param string prefix optional a prefix to search
	 *
	 * @return array Empty if it isn't supported by the cache driver
	 */
	public function getLocks(string $prefix = '');
}
