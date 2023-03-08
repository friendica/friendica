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
use Friendica\Model\Group;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * API endpoint: /api/friendica/group/delete
 */
class Delete extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'gid'  => 0,
			'name' => ''
		], $request);

		// params

		// error if no gid specified
		if ($request['gid'] == 0 || $request['name'] == '') {
			throw new BadRequestException('gid or name not specified');
		}

		// error message if specified gid is not in database
		if (!DBA::exists('group', ['uid' => $uid, 'id' => $request['gid']])) {
			throw new BadRequestException('gid not available');
		}

		// error message if specified gid is not in database
		if (!DBA::exists('group', ['uid' => $uid, 'id' => $request['gid'], 'name' => $request['name']])) {
			throw new BadRequestException('wrong group name');
		}

		// delete group
		$gid = Group::getIdByName($uid, $request['name']);
		if (empty($request['gid'])) {
			throw new BadRequestException('other API error');
		}

		$ret = Group::remove($gid);

		if ($ret) {
			// return success
			$success = ['success' => $ret, 'gid' => $request['gid'], 'name' => $request['name'], 'status' => 'deleted', 'wrong users' => []];
			$this->response->exit('group_delete', ['$result' => $success], $this->parameters['extension'] ?? null);
		} else {
			throw new BadRequestException('other API error');
		}
	}
}
