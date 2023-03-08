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

namespace Friendica\Module\ActivityPub;

use Friendica\Core\System;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;

/**
 * ActivityPub Outbox
 */
class Outbox extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['nickname'])) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$owner = User::getOwnerDataByNick($this->parameters['nickname']);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$uid  = self::getCurrentUserID();
		$page = $request['page'] ?? null;

		if (empty($page) && empty($request['max_id']) && !empty($uid)) {
			$page = 1;
		}

		$outbox = ActivityPub\ClientToServer::getOutbox($owner, $uid, $page, $request['max_id'] ?? null, HTTPSignature::getSigner('', $_SERVER));

		System::jsonExit($outbox, 'application/activity+json');
	}

	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid      = self::getCurrentUserID();
		$postdata = Network::postdata();

		if (empty($postdata) || empty($this->parameters['nickname'])) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$owner = User::getOwnerDataByNick($this->parameters['nickname']);
		if (empty($owner)) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}
		if ($owner['uid'] != $uid) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		$activity = json_decode($postdata, true);
		if (empty($activity)) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		System::jsonExit(ActivityPub\ClientToServer::processActivity($activity, $uid, self::getCurrentApplication() ?? []));
	}
}
