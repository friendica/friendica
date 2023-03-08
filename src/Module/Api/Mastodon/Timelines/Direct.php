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

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class Direct extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'max_id'   => 0,  // Return results older than id
			'since_id' => 0,  // Return results newer than id
			'min_id'   => 0,  // Return results immediately newer than id
			'limit'    => 20, // Maximum number of results to return. Defaults to 20.
		], $request);

		$params = ['order' => ['uri-id' => true], 'limit' => $request['limit']];

		$condition = ['uid' => $uid];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['min_id']]);

			$params['order'] = ['uri-id'];
		}

		if (!empty($uid)) {
			$condition = DBA::mergeConditions(
				$condition,
				["NOT `parent-author-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored`) AND `cid` = `parent-author-id`)", $uid]
			);
		}

		$mails = DBA::select('mail', ['id', 'uri-id'], $condition, $params);

		$statuses = [];

		while ($mail = DBA::fetch($mails)) {
			self::setBoundaries($mail['uri-id']);
			$statuses[] = DI::mstdnStatus()->createFromMailId($mail['id']);
		}

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		System::jsonExit($statuses);
	}
}
