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

namespace Friendica\Core\Config\Model;

use Friendica\Database\Database;

/**
 * The Config model backend, which is using the general DB-model backend for configs
 */
class Config
{
	/** @var Database */
	protected $dba;

	/**
	 * @param Database $dba The database connection of this model
	 */
	public function __construct(Database $dba)
	{
		$this->dba = $dba;
	}

	/**
	 * Checks if the model is currently connected
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->dba->isConnected();
	}

	/**
	 * Loads all configuration values and returns the loaded category as an array.
	 *
	 * @param string|null $cat The category of the configuration values to load
	 *
	 * @return array The config array
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function load(string $cat = null)
	{
		$return = [];

		if (empty($cat)) {
			$configs = $this->dba->select('config', ['cat', 'v', 'k']);
		} else {
			$configs = $this->dba->select('config', ['cat', 'v', 'k'], ['cat' => $cat]);
		}

		while ($config = $this->dba->fetch($configs)) {

			$key   = $config['k'];
			$value = DbaUtils::toConfigValue($config['v']);

			// just save it in case it is set
			if (isset($value)) {
				$return[$config['cat']][$key] = $value;
			}
		}
		$this->dba->close($configs);

		return $return;
	}

	/**
	 * Get a particular, system-wide config variable out of the DB with the
	 * given category name ($cat) and a key ($key).
	 *
	 * Note: Boolean variables are defined as 0/1 in the database
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to query
	 *
	 * @return array|string|null Stored value or null if it does not exist
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function get(string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return null;
		}

		$config = $this->dba->selectFirst('config', ['v'], ['cat' => $cat, 'k' => $key]);
		if ($this->dba->isResult($config)) {
			$value = DbaUtils::toConfigValue($config['v']);

			// just return it in case it is set
			if (isset($value)) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Stores a config value ($value) in the category ($cat) under the key ($key).
	 *
	 * Note: Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param mixed  $value The value to store
	 *
	 * @return bool Operation success
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function set(string $cat, string $key, $value)
	{
		if (!$this->isConnected()) {
			return false;
		}

		// We store our setting values in a string variable.
		// So we have to do the conversion here so that the compare below works.
		// The exception are array values.
		$compare_value = (!is_array($value) ? (string)$value : $value);
		$stored_value  = $this->get($cat, $key);

		if (isset($stored_value) && ($stored_value === $compare_value)) {
			return true;
		}

		$dbvalue = DbaUtils::toDbValue($value);

		$result = $this->dba->update('config', ['v' => $dbvalue], ['cat' => $cat, 'k' => $key], true);

		return $result;
	}

	/**
	 * Removes the configured value from the database.
	 *
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool Operation success
	 *
	 * @throws \Exception In case DB calls are invalid
	 */
	public function delete(string $cat, string $key)
	{
		if (!$this->isConnected()) {
			return false;
		}

		return $this->dba->delete('config', ['cat' => $cat, 'k' => $key]);
	}
}
