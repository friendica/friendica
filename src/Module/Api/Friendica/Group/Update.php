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
use Friendica\Model\Contact;
use Friendica\Model\Group;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * API endpoint: /api/friendica/group_update
 */
class Update extends BaseApi
{
	protected function post(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// params
		$gid   = $this->getRequestValue($request, 'gid', 0);
		$name  = $this->getRequestValue($request, 'name', '');
		$json  = json_decode($request['json'], true);
		$users = $json['user'];

		// error if no name specified
		if (!$name) {
			throw new BadRequestException('group name not specified');
		}

		// error if no gid specified
		if (!$gid) {
			throw new BadRequestException('gid not specified');
		}

		// remove members
		$members = Contact\Group::getById($gid);
		foreach ($members as $member) {
			$cid = $member['id'];
			foreach ($users as $user) {
				$found = $user['cid'] == $cid;
			}
			if (!isset($found) || !$found) {
				$gid = Group::getIdByName($uid, $name);
				Group::removeMember($gid, $cid);
			}
		}

		// add members
		$erroraddinguser = false;
		$errorusers      = [];
		foreach ($users as $user) {
			$cid = $user['cid'];

			if (DBA::exists('contact', ['id' => $cid, 'uid' => $uid])) {
				Group::addMember($gid, $cid);
			} else {
				$erroraddinguser = true;
				$errorusers[]    = $cid;
			}
		}

		// return success message incl. missing users in array
		$status  = ($erroraddinguser ? 'missing user' : 'ok');
		$success = ['success' => true, 'gid' => $gid, 'name' => $name, 'status' => $status, 'wrong users' => $errorusers];
		$this->response->exit('group_update', ['$result' => $success], $this->parameters['extension'] ?? null);
	}
}
