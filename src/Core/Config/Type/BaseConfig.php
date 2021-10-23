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
use Friendica\Core\Config\IConfig;
use Friendica\Model;

/**
 * This class is responsible for all system-wide configuration values in Friendica
 * There are two types of storage
 * - The Config-Files    (loaded into the FileCache @see ConfigCache)
 * - The Config-DB-Table (per Config-DB-model @see Model\Config\Config)
 */
abstract class BaseConfig implements IConfig
{
	/**
	 * @var Cache
	 */
	protected $configCache;

	/**
	 * @var \Friendica\Core\Config\Model\Config
	 */
	protected $configModel;

	/**
	 * @param Cache                               $configCache The configuration cache (based on the config-files)
	 * @param \Friendica\Core\Config\Model\Config $configModel The configuration model
	 */
	public function __construct(Cache $configCache, \Friendica\Core\Config\Model\Config $configModel)
	{
		$this->configCache = $configCache;
		$this->configModel = $configModel;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCache()
	{
		return $this->configCache;
	}
}
