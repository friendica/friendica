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

namespace Friendica\Worker;

use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Nodeinfo as ModelNodeInfo;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;

class NodeInfo
{
	public static function execute()
	{
		Logger::info('start');
		ModelNodeInfo::update();
		// Now trying to register
		$url = 'http://the-federation.info/register/' . DI::baseUrl()->getHost();
		Logger::debug('Check registering url', ['url' => $url]);
		$ret = DI::httpClient()->fetch($url, HttpClientAccept::HTML);
		Logger::debug('Check registering answer', ['answer' => $ret]);
		Logger::info('end');
	}
}
