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

namespace Friendica\Core\PConfig\Repository;

use Friendica\App\Mode;
use Friendica\Core\PConfig\Util\ValueConversion;
use Friendica\Core\PConfig\Exception\PConfigPersistenceException;
use Friendica\Database\Database;

/**
 * The Config model backend for users, which is using the general DB-model backend for user-configs
 */
class PConfig
{
	protected static $table_name = 'pconfig';

	/** @var Database */
	protected $db;
	/** @var Mode */
	protected $mode;

	public function __construct(Database $db, Mode $mode)
	{
		$this->db   = $db;
		$this->mode = $mode;
	}

	/**
	 * Checks if the model is currently connected
	 *
	 * @return bool
	 */
	public function isConnected(): bool
	{
		return $this->db->isConnected() & !$this->mode->isInstall();
	}

	/**
	 * Loads all configuration values and returns the loaded category as an array.
	 *
	 * @param int         $uid The id of the user to load
	 * @param string|null $cat The category of the configuration values to load
	 *
	 * @return string[][] The config array
	 *
	 * @throws PConfigPersistenceException In case the persistence layer throws errors
	 */
	public function load(int $uid, ?string $cat = null): array
	{
		$return = [];

		try {
			if (empty($cat)) {
				$configs = $this->db->select(static::$table_name, ['cat', 'v', 'k'], ['uid' => $uid]);
			} else {
				$configs = $this->db->select(static::$table_name, ['cat', 'v', 'k'], ['cat' => $cat, 'uid' => $uid]);
			}

			while ($config = $this->db->fetch($configs)) {
				$key   = $config['k'];
				$value = ValueConversion::toConfigValue($config['v']);

				// just save it in case it is set
				if (isset($value)) {
					$return[$config['cat']][$key] = $value;
				}
			}
		} catch (\Exception $exception) {
			throw new PConfigPersistenceException(sprintf('Cannot load config category %s for user %d', $cat, $uid), $exception);
		} finally {
			$this->db->close($configs);
		}

		return $return;
	}

	/**
	 * Get a particular user config variable out of the DB with the
	 * given category name ($cat) and a key ($key).
	 *
	 * Note: Boolean variables are defined as 0/1 in the database
	 *
	 * @param int         $uid The id of the user to load
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to query
	 *
	 * @return array|string|null Stored value or null if it does not exist
	 *
	 * @throws PConfigPersistenceException In case the persistence layer throws errors
	 */
	public function get(int $uid, string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return null;
		}

		try {
			$config = $this->db->selectFirst('pconfig', ['v'], ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
			if ($this->db->isResult($config)) {
				$value = ValueConversion::toConfigValue($config['v']);

				// just return it in case it is set
				if (isset($value)) {
					return $value;
				}
			}
		} catch (\Exception $exception) {
			throw new PConfigPersistenceException(sprintf('Cannot get config value for category %s, key %s and user %d', $cat, $key, $uid), $exception);
		}

		return null;
	}

	/**
	 * Stores a config value ($value) in the category ($cat) under the key ($key) for a
	 * given user ($uid).
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param int    $uid   The id of the user to load
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 *
	 * @throws PConfigPersistenceException In case the persistence layer throws errors
	 */
	public function set(int $uid, string $cat, string $key, $value): bool
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = (!is_array($value) ? (string)$value : $value);
		$stored_value  = $this->get($uid, $cat, $key);

		if (isset($stored_value) && ($stored_value === $compare_value)) {
			return true;
		}

		try {
			$dbValue = ValueConversion::toDbValue($value);
			return $this->db->update(static::$table_name, ['v' => $dbValue], ['uid' => $uid, 'cat' => $cat, 'k' => $key], true);
		} catch (\Exception $exception) {
			throw new PConfigPersistenceException(sprintf('Cannot set config value for category %s, key %s and user %d', $cat, $key, $uid), $exception);
		}
	}

	/**
	 * Removes the configured value of the given user.
	 *
	 * @param int    $uid The id of the user to load
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool Operation success
	 *
	 * @throws PConfigPersistenceException In case the persistence layer throws errors
	 */
	public function delete(int $uid, string $cat, string $key): bool
	{
		if (!$this->isConnected()) {
			return false;
		}

		try {
			return $this->db->delete('pconfig', ['uid' => $uid, 'cat' => $cat, 'k' => $key]);
		} catch (\Exception $exception) {
			throw new PConfigPersistenceException(sprintf('Cannot delete config value for category %s, key %s and user %d', $cat, $key, $uid), $exception);
		}
	}
}
