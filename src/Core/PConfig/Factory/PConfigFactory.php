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

namespace Friendica\Core\PConfig\Factory;

use Friendica\Core\Config\Cache\Cache;
use Friendica\Core\PConfig\IPConfig;
use Friendica\Core\PConfig\Model\PConfig as PConfigModel;
use Friendica\Core\PConfig\Type;

class PConfigFactory
{
	/**
	 * @param Cache                               $configCache  The config cache
	 * @param \Friendica\Core\PConfig\Cache\Cache $pConfigCache The personal config cache
	 * @param PConfigModel                        $configModel  The configuration model
	 *
	 * @return IPConfig
	 */
	public function create(Cache $configCache, \Friendica\Core\PConfig\Cache\Cache $pConfigCache, PConfigModel $configModel)
	{
		if ($configCache->get('system', 'config_adapter') === 'preload') {
			$configuration = new Type\PreloadPConfig($pConfigCache, $configModel);
		} else {
			$configuration = new Type\JitPConfig($pConfigCache, $configModel);
		}

		return $configuration;
	}
}
