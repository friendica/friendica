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

namespace Friendica\Core\PConfig\Type;

use Friendica\Core\PConfig\Repository;
use Friendica\Core\PConfig\ValueObject;

/**
 * This class implements the preload configuration, which will cache
 * all user config values per call in a cache.
 *
 * Minimizes the number of database queries to retrieve configuration values at the cost of memory.
 */
class PreloadPConfig extends AbstractPConfigValues
{
	/** @var array */
	private $config_loaded;

	/**
	 * @param ValueObject\Cache  $configCache The configuration cache
	 * @param Repository\PConfig $configRepo  The configuration model
	 */
	public function __construct(ValueObject\Cache $configCache, Repository\PConfig $configRepo)
	{
		parent::__construct($configCache, $configRepo);
		$this->config_loaded = [];
	}

	/**
	 * {@inheritDoc}
	 *
	 * This loads all config values everytime load is called
	 *
	 */
	public function load(int $uid, string $cat = 'config'): array
	{
		// Don't load the whole configuration twice or with invalid uid
		if (!$uid || !empty($this->config_loaded[$uid])) {
			return [];
		}

		// If not connected, do nothing
		if (!$this->configModel->isConnected()) {
			return [];
		}

		$config                    = $this->configModel->load($uid);
		$this->config_loaded[$uid] = true;

		// load the whole category out of the DB into the cache
		$this->configCache->load($uid, $config);

		return $config;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(int $uid, string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		if (!$uid) {
			return $default_value;
		}

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		} elseif ($refresh) {
			if ($this->configModel->isConnected()) {
				$config = $this->configModel->get($uid, $cat, $key);
				if (isset($config)) {
					$this->configCache->set($uid, $cat, $key, $config);
				}
			}
		}

		// use the config cache for return
		$result = $this->configCache->get($uid, $cat, $key);

		return (isset($result)) ? $result : $default_value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(int $uid, string $cat, string $key, $value): bool
	{
		if (!$uid) {
			return false;
		}

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		}

		// set the cache first
		$cached = $this->configCache->set($uid, $cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configModel->isConnected()) {
			return $cached;
		}

		$stored = $this->configModel->set($uid, $cat, $key, $value);

		return $cached && $stored;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(int $uid, string $cat, string $key): bool
	{
		if (!$uid) {
			return false;
		}

		if (empty($this->config_loaded[$uid])) {
			$this->load($uid);
		}

		$cacheRemoved = $this->configCache->delete($uid, $cat, $key);

		if (!$this->configModel->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configModel->delete($uid, $cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
