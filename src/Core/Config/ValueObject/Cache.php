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

namespace Friendica\Core\Config\ValueObject;

use Friendica\Core\Config\Util\ConfigFileManager;
use ParagonIE\HiddenString\HiddenString;

/**
 * The Friendica config cache for the application
 * Initial, all *.config.php files are loaded into this cache with the
 * ConfigFileManager ( @see ConfigFileManager )
 */
class Cache
{
	/** @var int Indicates that the cache entry is a default value - Lowest Priority */
	const SOURCE_STATIC = 0;
	/** @var int Indicates that the cache entry is set by file - Low Priority */
	const SOURCE_FILE = 1;
	/** @var int Indicates that the cache entry is manually set by the application (per admin page/console) - Middle Priority */
	const SOURCE_DATA = 2;
	/** @var int Indicates that the cache entry is set by a server environment variable - High Priority */
	const SOURCE_ENV = 3;
	/** @var int Indicates that the cache entry is fixed and must not be changed */
	const SOURCE_FIX = 5;

	/** @var int Default value for a config source */
	const SOURCE_DEFAULT = self::SOURCE_FILE;

	/**
	 * @var array
	 */
	private $config = [];

	/**
	 * @var int[][]
	 */
	private $source = [];

	/**
	 * @var bool[][]
	 */
	private $delConfig = [];

	/**
	 * @var bool
	 */
	private $hidePasswordOutput;

	/**
	 * @param array $config             A initial config array
	 * @param bool  $hidePasswordOutput True, if cache variables should take extra care of password values
	 * @param int   $source             Sets a source of the initial config values
	 */
	public function __construct(array $config = [], bool $hidePasswordOutput = true, int $source = self::SOURCE_DEFAULT)
	{
		$this->hidePasswordOutput = $hidePasswordOutput;
		$this->load($config, $source);
	}

	/**
	 * Tries to load the specified configuration array into the config array.
	 * Doesn't overwrite previously set values by default to prevent default config files to supersede DB Config.
	 *
	 * @param array $config
	 * @param int   $source Indicates the source of the config entry
	 */
	public function load(array $config, int $source = self::SOURCE_DEFAULT)
	{
		$categories = array_keys($config);

		foreach ($categories as $category) {
			if (is_array($config[$category])) {
				$keys = array_keys($config[$category]);

				foreach ($keys as $key) {
					$value = $config[$category][$key];
					if (isset($value)) {
						$this->set($category, $key, $value, $source);
					}
				}
			}
		}
	}

	/**
	 * Gets a value from the config cache.
	 *
	 * @param string      $cat Config category
	 * @param string|null $key Config key
	 *
	 * @return null|mixed Returns the value of the Config entry or null if not set
	 */
	public function get(string $cat, ?string $key = null)
	{
		if (isset($this->config[$cat][$key])) {
			return $this->config[$cat][$key];
		} elseif (!isset($key) && isset($this->config[$cat])) {
			return $this->config[$cat];
		} else {
			return null;
		}
	}

	/**
	 * Returns the source value of the current, cached config value
	 *
	 * @param string $cat Config category
	 * @param string $key Config key
	 *
	 * @return int
	 */
	public function getSource(string $cat, string $key): int
	{
		return $this->source[$cat][$key] ?? -1;
	}

	/**
	 * Returns the whole config array based on the given source type
	 *
	 * @param int $source Indicates the source of the config entry
	 *
	 * @return array The config array part of the given source
	 */
	public function getDataBySource(int $source): array
	{
		$data = [];

		$categories = array_keys($this->source);

		foreach ($categories as $category) {
			if (is_array($this->source[$category])) {
				$keys = array_keys($this->source[$category]);

				foreach ($keys as $key) {
					if ($this->source[$category][$key] === $source) {
						$data[$category][$key] = $this->config[$category][$key];
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Sets a value in the config cache. Accepts raw output from the config table
	 *
	 * @param string $cat    Config category
	 * @param string $key    Config key
	 * @param mixed  $value  Value to set
	 * @param int    $source The source of the current config key
	 *
	 * @return bool True, if the value is set
	 */
	public function set(string $cat, string $key, $value, int $source = self::SOURCE_DEFAULT): bool
	{
		if (!isset($this->config[$cat])) {
			$this->config[$cat] = [];
			$this->source[$cat] = [];
		}

		if (isset($this->source[$cat][$key]) &&
			$source < $this->source[$cat][$key]) {
			return false;
		}

		if ($this->hidePasswordOutput &&
			$key == 'password' &&
			is_string($value)) {
			$this->config[$cat][$key] = new HiddenString((string)$value);
		} else if (is_string($value)) {
			$this->config[$cat][$key] = self::toConfigValue($value);
		} else {
			$this->config[$cat][$key] = $value;
		}

		$this->source[$cat][$key] = $source;

		return true;
	}

	/**
	 * Formats a DB value to a config value
	 * - null   = The db-value isn't set
	 * - bool   = The db-value is either '0' or '1'
	 * - array  = The db-value is a serialized array
	 * - string = The db-value is a string
	 *
	 * Keep in mind that there aren't any numeric/integer config values in the database
	 *
	 * @param string|null $value
	 *
	 * @return mixed
	 */
	public static function toConfigValue(?string $value)
	{
		if (!isset($value)) {
			return null;
		}

		if (preg_match("|^a:[0-9]+:{.*}$|s", $value)) {
			return unserialize($value);
		} else {
			return $value;
		}
	}

	/**
	 * Deletes a value from the config cache.
	 *
	 * @param string $cat Config category
	 * @param string $key Config key
	 *
	 * @return bool true, if deleted
	 */
	public function delete(string $cat, string $key): bool
	{
		if (isset($this->config[$cat][$key])) {
			unset($this->config[$cat][$key]);
			unset($this->source[$cat][$key]);
			$this->delConfig[$cat][$key] = true;
			if (count($this->config[$cat]) == 0) {
				unset($this->config[$cat]);
				unset($this->source[$cat]);
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

	/**
	 * Merges a new Cache into the existing one and returns the merged Cache
	 *
	 * @param Cache $cache The cache, which should get merged into this Cache
	 *
	 * @return Cache The merged Cache
	 */
	public function merge(Cache $cache): Cache
	{
		$newConfig = $this->config;
		$newSource = $this->source;

		$categories = array_keys($cache->config);

		foreach ($categories as $category) {
			if (is_array($cache->config[$category])) {
				$keys = array_keys($cache->config[$category]);

				if (is_null($newConfig[$category] ?? null)) {
					$newConfig[$category] = [];
					$newSource[$category] = [];
				}

				foreach ($keys as $key) {
					$newConfig[$category][$key] = $cache->config[$category][$key];
					$newSource[$category][$key] = $cache->source[$category][$key];
				}
			} else {
				$newConfig[$category] = $cache->config[$category];
				$newSource[$category] = $cache->source[$category];
			}
		}

		$delCategories = array_keys($cache->delConfig);

		foreach ($delCategories as $category) {
			if (is_array($cache->delConfig[$category])) {
				$keys = array_keys($cache->delConfig[$category]);

				foreach ($keys as $key) {
					unset($newConfig[$category][$key]);
					unset($newSource[$category][$key]);
				}
			}
		}

		$newCache = new Cache();
		$newCache->config = $newConfig;
		$newCache->source = $newSource;

		return $newCache;
	}
}
