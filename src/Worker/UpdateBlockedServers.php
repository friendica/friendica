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
use Friendica\Database\DBA;
use Friendica\Model\GServer;
use Friendica\Util\Network;

class UpdateBlockedServers
{
	/**
	 * Updates the server blocked status
	 */
	public static function execute()
	{
		Logger::debug('Update blocked servers - start');
		$gservers = DBA::select('gserver', ['id', 'url', 'blocked']);
		while ($gserver = DBA::fetch($gservers)) {
			$blocked = Network::isUrlBlocked($gserver['url']);
			if (!is_null($gserver['blocked']) && ($blocked == $gserver['blocked'])) {
				continue;
			}

			if ($blocked) {
				GServer::setBlockedById($gserver['id']);
			} else {
				GServer::setUnblockedById($gserver['id']);
			}
		}
		DBA::close($gservers);
		Logger::debug('Update blocked servers - done');
	}
}
