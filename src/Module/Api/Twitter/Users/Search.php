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

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * Search a public user account.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-users-search
 */
class Search extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$userlist = [];

		if (!empty($request['q'])) {
			$contacts = Contact::selectToArray(
				['id'],
				[
					'`uid` = 0 AND (`name` = ? OR `nick` = ? OR `url` = ? OR `addr` = ?)',
					$request['q'],
					$request['q'],
					$request['q'],
					$request['q'],
				]
			);

			if (DBA::isResult($contacts)) {
				$k = 0;
				foreach ($contacts as $contact) {
					$user_info = DI::twitterUser()->createFromContactId($contact['id'], $uid, false)->toArray();

					$userlist[] = $user_info;
				}
				$userlist = ['users' => $userlist];
			} else {
				throw new NotFoundException('User ' . $request['q'] . ' not found.');
			}
		} else {
			throw new BadRequestException('No search term specified.');
		}

		$this->response->exit('users', $userlist, $this->parameters['extension'] ?? null);
	}
}
