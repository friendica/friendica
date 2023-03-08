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
use Friendica\Core\PConfig\ValueObject\Cache;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;

/**
 * This class is responsible for the user-specific configuration values in Friendica
 * The values are set through the Config-DB-Table (per Config-DB-model @see Repository\PConfig)
 *
 * The configuration cache (@see Cache) is used for temporary caching of database calls. This will
 * increase the performance.
 */
abstract class AbstractPConfigValues implements IManagePersonalConfigValues
{
	/**
	 * @var Cache
	 */
	protected $configCache;

	/**
	 * @var Repository\PConfig
	 */
	protected $configModel;

	/**
	 * @param Cache              $configCache The configuration cache
	 * @param Repository\PConfig $configRepo  The configuration model
	 */
	public function __construct(Cache $configCache, Repository\PConfig $configRepo)
	{
		$this->configCache = $configCache;
		$this->configModel = $configRepo;
	}

	/**
	 * Returns the Config Cache
	 *
	 * @return Cache
	 */
	public function getCache(): Cache
	{
		return $this->configCache;
	}
}
