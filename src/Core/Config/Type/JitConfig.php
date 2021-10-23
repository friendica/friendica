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

namespace Friendica\Core\Config\Type;

use Friendica\Core\Config\Cache\Cache;
use Friendica\Core\Config\Model\Config;

/**
 * This class implements the Just-In-Time configuration, which will cache
 * config values in a cache, once they are retrieved.
 *
 * Default Configuration type.
 * Provides the best performance for pages loading few configuration variables.
 */
class JitConfig extends BaseConfig
{
	/**
	 * @var array Array of already loaded db values (even if there was no value)
	 */
	private $db_loaded;

	/**
	 * @param Cache                               $configCache The configuration cache (based on the config-files)
	 * @param Config $configModel The configuration model
	 */
	public function __construct(Cache $configCache, Config $configModel)
	{
		parent::__construct($configCache, $configModel);
		$this->db_loaded = [];

		$this->load();
	}

	/**
	 * {@inheritDoc}
	 *
	 */
	public function load(string $cat = 'config')
	{
		// If not connected, do nothing
		if (!$this->configModel->isConnected()) {
			return;
		}

		$config = $this->configModel->load($cat);

		if (!empty($config[$cat])) {
			foreach ($config[$cat] as $key => $value) {
				$this->db_loaded[$cat][$key] = true;
			}
		}

		// load the whole category out of the DB into the cache
		$this->configCache->load($config, Cache::SOURCE_DB);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $cat, string $key, $default_value = null, bool $refresh = false)
	{
		// if the value isn't loaded or refresh is needed, load it to the cache
		if ($this->configModel->isConnected() &&
		    (empty($this->db_loaded[$cat][$key]) ||
		     $refresh)) {

			$dbvalue = $this->configModel->get($cat, $key);

			if (isset($dbvalue)) {
				$this->configCache->set($cat, $key, $dbvalue, Cache::SOURCE_DB);
				unset($dbvalue);
			}

			$this->db_loaded[$cat][$key] = true;
		}

		// use the config cache for return
		$result = $this->configCache->get($cat, $key);

		return (isset($result)) ? $result : $default_value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(string $cat, string $key, $value)
	{
		// set the cache first
		$cached = $this->configCache->set($cat, $key, $value, Cache::SOURCE_DB);

		// If there is no connected adapter, we're finished
		if (!$this->configModel->isConnected()) {
			return $cached;
		}

		$stored = $this->configModel->set($cat, $key, $value);

		$this->db_loaded[$cat][$key] = $stored;

		return $cached && $stored;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(string $cat, string $key)
	{
		$cacheRemoved = $this->configCache->delete($cat, $key);

		if (isset($this->db_loaded[$cat][$key])) {
			unset($this->db_loaded[$cat][$key]);
		}

		if (!$this->configModel->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configModel->delete($cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
