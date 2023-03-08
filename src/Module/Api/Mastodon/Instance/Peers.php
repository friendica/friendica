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

namespace Friendica\Module\Api\Mastodon\Instance;

use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\GServer;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Util\Network;

/**
 * Undocumented API endpoint that is implemented by both Mastodon and Pleroma
 */
class Peers extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$return = [];

		// We only select for Friendica and ActivityPub servers, since it is expected to only deliver AP compatible systems here.
		$instances = DBA::select('gserver', ['url'], ["`network` in (?, ?) AND NOT `blocked` AND NOT `failed` AND NOT `detection-method` IN (?, ?, ?, ?)",
			Protocol::DFRN, Protocol::ACTIVITYPUB,
			GServer::DETECT_MANUAL, GServer::DETECT_HEADER, GServer::DETECT_BODY, GServer::DETECT_HOST_META]);
		while ($instance = DBA::fetch($instances)) {
			$urldata = parse_url($instance['url']);
			unset($urldata['scheme']);
			$return[] = ltrim(Network::unparseURL($urldata), '/');
		}
		DBA::close($instances);

		System::jsonExit($return);
	}
}
