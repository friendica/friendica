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

namespace Friendica\Module\Api\Twitter\Users;

use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Return user objects
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-lookup
 */
class Lookup extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$users = [];

		if (!empty($request['user_id'])) {
			foreach (explode(',', $request['user_id']) as $cid) {
				if (!empty($cid) && is_numeric($cid)) {
					$users[] = DI::twitterUser()->createFromContactId((int)$cid, $uid, false)->toArray();
				}
			}
		}

		if (empty($users)) {
			throw new NotFoundException();
		}

		$this->response->exit('users', ['user' => $users], $this->parameters['extension'] ?? null);
	}
}
