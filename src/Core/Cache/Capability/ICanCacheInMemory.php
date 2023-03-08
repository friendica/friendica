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

namespace Friendica\Core\Cache\Capability;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Exception\CachePersistenceException;

/**
 * This interface defines methods for Memory-Caches only
 */
interface ICanCacheInMemory extends ICanCache
{
	/**
	 * Sets a value if it's not already stored
	 *
	 * @param string $key   The cache key
	 * @param mixed  $value The old value we know from the cache
	 * @param int    $ttl   The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function add(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Compares if the old value is set and sets the new value
	 *
	 * @param string $key      The cache key
	 * @param mixed  $oldValue The old value we know from the cache
	 * @param mixed  $newValue The new value we want to set
	 * @param int    $ttl      The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function compareSet(string $key, $oldValue, $newValue, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Compares if the old value is set and removes it
	 *
	 * @param string $key   The cache key
	 * @param mixed  $value The old value we know and want to delete
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function compareDelete(string $key, $value): bool;
}
