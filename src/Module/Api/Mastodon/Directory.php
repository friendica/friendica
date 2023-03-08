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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/instance/directory/
 */
class Directory extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/instance/directory/
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'offset' => 0,        // How many accounts to skip before returning results. Default 0.
			'limit'  => 40,       // How many accounts to load. Default 40.
			'order'  => 'active', // active to sort by most recently posted statuses (default) or new to sort by most recently created profiles.
			'local'  => false,    // Only return local accounts.
		], $request);

		Logger::info('directory', ['offset' => $request['offset'], 'limit' => $request['limit'], 'order' => $request['order'], 'local' => $request['local']]);

		if ($request['local']) {
			$table = 'owner-view';
			$condition = ['net-publish' => true];
		} else {
			$table = 'contact';
			$condition = ['uid' => 0, 'hidden' => false, 'network' => Protocol::FEDERATED];
		}

		$params = ['limit' => [$request['offset'], $request['limit']],
			'order' => [($request['order'] == 'active') ? 'last-item' : 'created' => true]];

		$accounts = [];
		$contacts = DBA::select($table, ['id', 'uid'], $condition, $params);
		while ($contact = DBA::fetch($contacts)) {
			$accounts[] = DI::mstdnAccount()->createFromContactId($contact['id'], $contact['uid']);
		}
		DBA::close($contacts);

		System::jsonExit($accounts);
	}
}
