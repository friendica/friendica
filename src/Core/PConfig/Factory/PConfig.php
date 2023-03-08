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

namespace Friendica\Core\PConfig\Factory;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\PConfig\Repository;
use Friendica\Core\PConfig\Type;
use Friendica\Core\PConfig\ValueObject;

class PConfig
{
	/**
	 * @param IManageConfigValues $config       The config
	 * @param ValueObject\Cache   $pConfigCache The personal config cache
	 * @param Repository\PConfig  $configRepo   The configuration model
	 *
	 * @return IManagePersonalConfigValues
	 */
	public function create(IManageConfigValues $config, ValueObject\Cache $pConfigCache, Repository\PConfig $configRepo): IManagePersonalConfigValues
	{
		if ($config->get('system', 'config_adapter') === 'preload') {
			$configuration = new Type\PreloadPConfig($pConfigCache, $configRepo);
		} else {
			$configuration = new Type\JitPConfig($pConfigCache, $configRepo);
		}

		return $configuration;
	}
}
