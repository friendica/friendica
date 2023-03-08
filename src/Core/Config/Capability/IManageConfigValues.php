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

namespace Friendica\Core\Config\Capability;

use Friendica\Core\Config\Exception\ConfigPersistenceException;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;

/**
 * Interface for accessing system-wide configurations
 */
interface IManageConfigValues
{
	/**
	 * Reloads all configuration values from the persistence layer
	 *
	 * All configuration values of the system are stored in the cache.
	 *
	 * @return void
	 *
	 * @throws ConfigPersistenceException In case the persistence layer throws errors
	 */
	public function reload();

	/**
	 * Get a particular user's config variable given the category name
	 * ($cat) and a $key.
	 *
	 * Get a particular config value from the given category ($cat)
	 *
	 * @param string  $cat           The category of the configuration value
	 * @param ?string $key           The configuration key to query (if null, the whole array at the category will get returned)
	 * @param mixed   $default_value Deprecated, use `Config->get($cat, $key, null, $refresh) ?? $default_value` instead
	 *
	 * @return mixed Stored value or null if it does not exist
	 *
	 * @throws ConfigPersistenceException In case the persistence layer throws errors
	 *
	 */
	public function get(string $cat, string $key = null, $default_value = null);

	/**
	 * Sets a configuration value for system config
	 *
	 * Stores a config value ($value) in the category ($cat) under the key ($key)
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to set
	 * @param mixed  $value  The value to store
	 *
	 * @return bool Operation success
	 *
	 * @throws ConfigPersistenceException In case the persistence layer throws errors
	 */
	public function set(string $cat, string $key, $value): bool;

	/**
	 * Creates a transactional config value store, which is used to set a bunch of values at once
	 *
	 * It relies on the current instance, so after save(), the values of this config class will get altered at once too.
	 *
	 * @return ISetConfigValuesTransactionally
	 */
	public function beginTransaction(): ISetConfigValuesTransactionally;

	/**
	 * Deletes the given key from the system configuration.
	 *
	 * Removes the configured value from the stored cache in the cache and removes it from the database.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key    The configuration key to delete
	 *
	 * @return bool
	 *
	 * @throws ConfigPersistenceException In case the persistence layer throws errors
	 *
	 */
	public function delete(string $cat, string $key): bool;

	/**
	 * Returns the Config Cache
	 *
	 * @return Cache
	 */
	public function getCache(): Cache;
}
