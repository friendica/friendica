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

namespace Friendica\Module\Api\Friendica\Events;

use Friendica\Database\DBA;
use Friendica\Model\Event;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * API endpoint: /api/friendica/event_delete
 */


class Delete extends BaseApi
{
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'id' => 0
		], $request);

		// params

		// error if no id specified
		if ($request['id'] == 0) {
			throw new HTTPException\BadRequestException('id not specified');
		}

		// error message if specified id is not in database
		if (!DBA::exists('event', ['uid' => $uid, 'id' => $request['id']])) {
			throw new HTTPException\BadRequestException('id not available');
		}

		// delete event
		$eventid = $request['id'];
		Event::delete($eventid);

		$success = ['id' => $eventid, 'status' => 'deleted'];
		$this->response->exit('event_delete', ['$result' => $success], $this->parameters['extension'] ?? null);
	}
}
