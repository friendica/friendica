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
 * Interface for caches
 */
interface ICanCache
{
	/**
	 * Lists all cache keys
	 *
	 * @param string|null prefix optional a prefix to search
	 *
	 * @return array Empty if it isn't supported by the cache driver
	 */
	public function getAllKeys(?string $prefix = null): array;

	/**
	 * Fetches cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function get(string $key);

	/**
	 * Stores data in the cache identified by the key. The input $value can have multiple formats.
	 *
	 * @param string  $key   The cache key
	 * @param mixed   $value The value to store
	 * @param integer $ttl   The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function set(string $key, $value, int $ttl = Duration::FIVE_MINUTES): bool;

	/**
	 * Delete a key from the cache
	 *
	 * @param string $key The cache key
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function delete(string $key): bool;

	/**
	 * Remove outdated data from the cache
	 *
	 * @param boolean $outdated just remove outdated values
	 *
	 * @return bool
	 *
	 * @throws CachePersistenceException In case the underlying cache driver has errors during persistence
	 */
	public function clear(bool $outdated = true): bool;

	/**
	 * Returns the name of the current cache
	 *
	 * @return string
	 */
	public function getName(): string;
}
