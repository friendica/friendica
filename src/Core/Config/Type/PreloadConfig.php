<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Core\Config\Type;

use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\Config\Repository\Config;

/**
 * This class implements the preload configuration, which will cache
 * all config values per call in a cache.
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 */
class PreloadConfig extends AbstractConfig
{
	/** @var bool */
	private $config_loaded;

	/**
	 * @param Cache  $configCache The configuration cache (based on the config-files)
	 * @param Config $configRepo  The configuration model
	 */
	public function __construct(Cache $configCache, Config $configRepo)
	{
		parent::__construct($configCache, $configRepo);
		$this->config_loaded = false;

		$this->load();
	}

	/**
	 * {@inheritDoc}
	 *
	 * This loads all config values everytime load is called
	 */
	public function load(string $cat = 'config')
	{
		// Don't load the whole configuration twice
		if ($this->config_loaded) {
			return;
		}

		// If not connected, do nothing
		if (!$this->configRepo->isConnected()) {
			return;
		}

		$config              = $this->configRepo->load();
		$this->config_loaded = true;

		// load the whole category out of the DB into the cache
		$this->configCache->load($config, Cache::SOURCE_DB);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		if ($refresh) {
			if ($this->configRepo->isConnected()) {
				$config = $this->configRepo->get($cat, $key);
				if (isset($config)) {
					$this->configCache->set($cat, $key, $config, Cache::SOURCE_DB);
				}
			}
		}

		// use the config cache for return
		$result = $this->configCache->get($cat, $key);

		return (isset($result)) ? $result : $default_value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(string $cat, string $key, $value): bool
	{
		if (!$this->config_loaded) {
			$this->load();
		}

		// set the cache first
		$cached = $this->configCache->set($cat, $key, $value, Cache::SOURCE_DB);

		// If there is no connected adapter, we're finished
		if (!$this->configRepo->isConnected()) {
			return $cached;
		}

		$stored = $this->configRepo->set($cat, $key, $value);

		return $cached && $stored;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(string $cat, string $key): bool
	{
		if ($this->config_loaded) {
			$this->load();
		}

		$cacheRemoved = $this->configCache->delete($cat, $key);

		if (!$this->configRepo->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configRepo->delete($cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
