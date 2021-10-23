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

namespace Friendica\Core\Cache;

use Friendica\Core\Cache\Enum\Duration;

/**
 * Cache Interface
 */
interface ICache
{
	/**
	 * Lists all cache keys
	 *
	 * @param string prefix optional a prefix to search
	 *
	 * @return array Empty if it isn't supported by the cache driver
	 */
	public function getAllKeys($prefix = null);

	/**
	 * Fetches cached data according to the key
	 *
	 * @param string $key The key to the cached data
	 *
	 * @return mixed Cached $value or "null" if not found
	 */
	public function get($key);

	/**
	 * Stores data in the cache identified by the key. The input $value can have multiple formats.
	 *
	 * @param string  $key      The cache key
	 * @param mixed   $value    The value to store
	 * @param integer $ttl      The cache lifespan, must be one of the Cache constants
	 *
	 * @return bool
	 */
	public function set($key, $value, $ttl = Duration::FIVE_MINUTES);

	/**
	 * Delete a key from the cache
	 *
	 * @param string $key      The cache key
	 *
	 * @return bool
	 */
	public function delete($key);

	/**
	 * Remove outdated data from the cache
	 * @param  boolean $outdated just remove outdated values
	 *
	 * @return bool
	 */
	public function clear($outdated = true);

	/**
	 * Returns the name of the current cache
	 *
	 * @return string
	 */
	public function getName();
}
