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

namespace Friendica\Core\PConfig;

use Friendica\Core\BasePConfig;
use Friendica\Model;

/**
 * This class implements the Just-In-Time configuration, which will cache
 * user config values in a cache, once they are retrieved.
 *
 * Default Configuration type.
 * Provides the best performance for pages loading few configuration variables.
 */
class JitPConfig extends BasePConfig
{
	/**
	 * @var array Array of already loaded db values (even if there was no value)
	 */
	private $db_loaded;

	/**
	 * @param Cache                $configCache The configuration cache
	 * @param Model\Config\PConfig $configModel The configuration model
	 */
	public function __construct(Cache $configCache, Model\Config\PConfig $configModel)
	{
		parent::__construct($configCache, $configModel);
		$this->db_loaded = [];
	}

	/**
	 * {@inheritDoc}
	 *
	 */
	public function load(int $uid, string $cat = 'config')
	{
		// If not connected or no uid, do nothing
		if (!$uid || !$this->configModel->isConnected()) {
			return;
		}

		$config = $this->configModel->load($uid, $cat);

		if (!empty($config[$cat])) {
			foreach ($config[$cat] as $key => $value) {
				$this->db_loaded[$uid][$cat][$key] = true;
			}
		}

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

		// if the value isn't loaded or refresh is needed, load it to the cache
		if ($this->configModel->isConnected() &&
		    (empty($this->db_loaded[$uid][$cat][$key]) ||
		     $refresh)) {

			$dbvalue = $this->configModel->get($uid, $cat, $key);

			if (isset($dbvalue)) {
				$this->configCache->set($uid, $cat, $key, $dbvalue);
				unset($dbvalue);
			}

			$this->db_loaded[$uid][$cat][$key] = true;
		}

		// use the config cache for return
		$result = $this->configCache->get($uid, $cat, $key);

		return (isset($result)) ? $result : $default_value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(int $uid, string $cat, string $key, $value)
	{
		if (!$uid) {
			return false;
		}

		// set the cache first
		$cached = $this->configCache->set($uid, $cat, $key, $value);

		// If there is no connected adapter, we're finished
		if (!$this->configModel->isConnected()) {
			return $cached;
		}

		$stored = $this->configModel->set($uid, $cat, $key, $value);

		$this->db_loaded[$uid][$cat][$key] = $stored;

		return $cached && $stored;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete(int $uid, string $cat, string $key)
	{
		if (!$uid) {
			return false;
		}

		$cacheRemoved = $this->configCache->delete($uid, $cat, $key);

		if (isset($this->db_loaded[$uid][$cat][$key])) {
			unset($this->db_loaded[$uid][$cat][$key]);
		}

		if (!$this->configModel->isConnected()) {
			return $cacheRemoved;
		}

		$storeRemoved = $this->configModel->delete($uid, $cat, $key);

		return $cacheRemoved || $storeRemoved;
	}
}
