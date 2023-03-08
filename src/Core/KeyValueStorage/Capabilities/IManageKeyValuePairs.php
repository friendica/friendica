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

namespace Friendica\Core\KeyValueStorage\Capabilities;

use Friendica\Core\KeyValueStorage\Exceptions\KeyValueStoragePersistenceException;

/**
 * Interface for Friendica specific Key-Value pair storage
 */
interface IManageKeyValuePairs extends \ArrayAccess
{
	/**
	 * Get a particular value from the KeyValue Storage
	 *
	 * @param string  $key           The key to query
	 *
	 * @return mixed Stored value or null if it does not exist
	 *
	 * @throws KeyValueStoragePersistenceException In case the persistence layer throws errors
	 *
	 */
	public function get(string $key);

	/**
	 * Sets a value for a given key
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @throws KeyValueStoragePersistenceException In case the persistence layer throws errors
	 */
	public function set(string $key, $value): void;

	/**
	 * Deletes the given key.
	 *
	 * @param string $key    The configuration key to delete
	 *
	 * @throws KeyValueStoragePersistenceException In case the persistence layer throws errors
	 *
	 */
	public function delete(string $key): void;
}
