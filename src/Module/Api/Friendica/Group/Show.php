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

namespace Friendica\Module\Api\Friendica\Group;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * API endpoint: /api/friendica/group_show
 */
class Show extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid  = BaseApi::getCurrentUserID();
		$type = $this->getRequestValue($this->parameters, 'extension', 'json');

		// params
		$gid = $this->getRequestValue($request, 'gid', 0);

		// get data of the specified group id or all groups if not specified
		if ($gid != 0) {
			$groups = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid, 'id' => $gid]);

			// error message if specified gid is not in database
			if (!DBA::isResult($groups)) {
				throw new HTTPException\BadRequestException('gid not available');
			}
		} else {
			$groups = DBA::selectToArray('group', [], ['deleted' => false, 'uid' => $uid]);
		}

		// loop through all groups and retrieve all members for adding data in the user array
		$grps = [];
		foreach ($groups as $rr) {
			$members = Contact\Group::getById($rr['id']);
			$users   = [];

			if ($type == 'xml') {
				$user_element = 'users';
				$k            = 0;
				foreach ($members as $member) {
					$users[$k++.':user'] = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				}
			} else {
				$user_element = 'user';
				foreach ($members as $member) {
					$users[] = DI::twitterUser()->createFromContactId($member['contact-id'], $uid, true)->toArray();
				}
			}
			$grps[] = ['name' => $rr['name'], 'gid' => $rr['id'], $user_element => $users];
		}

		$this->response->exit('group_update', ['group' => $grps], $this->parameters['extension'] ?? null);
	}
}
