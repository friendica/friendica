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
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GServer;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Util\Network;
use Friendica\Util\Strings;

class UpdateServerPeers
{
	/**
	 * Query the given server for their known peers
	 *
	 * @param string $gserver Server URL
	 * @return void
	 */
	public static function execute(string $url)
	{
		$ret = DI::httpClient()->get($url . '/api/v1/instance/peers', HttpClientAccept::JSON);
		if (!$ret->isSuccess() || empty($ret->getBody())) {
			Logger::info('Server is not reachable or does not offer the "peers" endpoint', ['url' => $url]);
			return;
		}

		$peers = json_decode($ret->getBody());
		if (empty($peers) || !is_array($peers)) {
			Logger::info('Server does not have any peers listed', ['url' => $url]);
			return;
		}

		Logger::info('Server peer update start', ['url' => $url]);

		$total = 0;
		$added = 0;
		foreach ($peers as $peer) {
			if (Network::isUrlBlocked('https://' . $peer)) {
				// Ignore blocked systems as soon as possible in the loop to avoid being slowed down by tar pits
				continue;
			}

			++$total;
			if (DBA::exists('gserver', ['nurl' => Strings::normaliseLink('http://' . $peer)])) {
				// We already know this server
				continue;
			}
			// This endpoint doesn't offer the schema. So we assume that it is HTTPS.
			GServer::add('https://' . $peer);
			++$added;
			Worker::coolDown();
		}
		Logger::info('Server peer update ended', ['total' => $total, 'added' => $added, 'url' => $url]);
	}
}
