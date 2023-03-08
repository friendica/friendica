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

namespace Friendica\Core\PConfig\ValueObject;

use ParagonIE\HiddenString\HiddenString;

/**
 * The Friendica config cache for users
 */
class Cache
{
	/**
	 * @var array
	 */
	private $config = [];

	/**
	 * @var bool
	 */
	private $hidePasswordOutput;

	/**
	 * @param bool $hidePasswordOutput True, if cache variables should take extra care of password values
	 */
	public function __construct(bool $hidePasswordOutput = true)
	{
		$this->hidePasswordOutput = $hidePasswordOutput;
	}

	/**
	 * Tries to load the specified configuration array into the user specific config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * @param int   $uid
	 * @param array $config
	 */
	public function load(int $uid, array $config)
	{
		if (!is_int($uid)) {
			return;
		}

		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (isset($config[$category]) && is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					$value = $config[$category][$key];
					if (isset($value)) {
						$this->set($uid, $category, $key, $value);
					}
				}
			}
		}
	}

	/**
	 * Retrieves a value from the user config cache
	 *
	 * @param int    $uid User Id
	 * @param string $cat Config category
	 * @param string|null $key Config key
	 *
	 * @return null|mixed The value of the config entry or null if not set
	 */
	public function get(int $uid, string $cat, ?string $key = null)
	{
		if (!is_int($uid)) {
			return null;
		}

		if (isset($this->config[$uid][$cat][$key])) {
			return $this->config[$uid][$cat][$key];
		} elseif (!isset($key) && isset($this->config[$uid][$cat])) {
			return $this->config[$uid][$cat];
		} else {
			return null;
		}
	}

	/**
	 * Sets a value in the user config cache
	 *
	 * Accepts raw output from the pconfig table
	 *
	 * @param int    $uid   User Id
	 * @param string $cat   Config category
	 * @param string $key   Config key
	 * @param mixed  $value Value to set
	 *
	 * @return bool Set successful
	 */
	public function set(int $uid, string $cat, string $key, $value): bool
	{
		if (!is_int($uid)) {
			return false;
		}

		if (!isset($this->config[$uid]) || !is_array($this->config[$uid])) {
			$this->config[$uid] = [];
		}

		if (!isset($this->config[$uid][$cat])) {
			$this->config[$uid][$cat] = [];
		}

		if ($this->hidePasswordOutput &&
			$key == 'password' &&
			!empty($value) && is_string($value)) {
			$this->config[$uid][$cat][$key] = new HiddenString((string)$value);
		} else {
			$this->config[$uid][$cat][$key] = $value;
		}


		return true;
	}

	/**
	 * Deletes a value from the user config cache
	 *
	 * @param int    $uid User Id
	 * @param string $cat Config category
	 * @param string $key Config key
	 *
	 * @return bool true, if deleted
	 */
	public function delete(int $uid, string $cat, string $key): bool
	{
		if (!is_int($uid)) {
			return false;
		}

		if (isset($this->config[$uid][$cat][$key])) {
			unset($this->config[$uid][$cat][$key]);
			if (count($this->config[$uid][$cat]) == 0) {
				unset($this->config[$uid][$cat]);
				if (count($this->config[$uid]) == 0) {
					unset($this->config[$uid]);
				}
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Returns the whole configuration
	 *
	 * @return string[][] The configuration
	 */
	public function getAll(): array
	{
		return $this->config;
	}

	/**
	 * Returns an array with missing categories/Keys
	 *
	 * @param string[][] $config The array to check
	 *
	 * @return string[][]
	 */
	public function keyDiff(array $config): array
	{
		$return = [];

		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					if (!isset($this->config[$category][$key])) {
						$return[$category][$key] = $config[$category][$key];
					}
				}
			}
		}

		return $return;
	}
}
