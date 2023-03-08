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

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Contact;
use Friendica\Model\Post;

/**
 * Returns the most recent mentions.
 *
 * @see http://developer.twitter.com/doc/get/statuses/mentions
 */
class Mentions extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// get last network messages

		// params
		$count            = $this->getRequestValue($request, 'count', 20, 1, 100);
		$page             = $this->getRequestValue($request, 'page', 1, 1);
		$since_id         = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id           = $this->getRequestValue($request, 'max_id', 0, 0);
		$include_entities = $this->getRequestValue($request, 'include_entities', false);

		$start = max(0, ($page - 1) * $count);

		$query = "`gravity` IN (?, ?) AND `uri-id` IN
			(SELECT `uri-id` FROM `post-user-notification` WHERE `uid` = ? AND `notification-type` & ? != 0 ORDER BY `uri-id`)
			AND (`uid` = 0 OR (`uid` = ? AND NOT `global`)) AND `uri-id` > ?";

		$condition = [
			Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT,
			$uid,
			Post\UserNotification::TYPE_EXPLICIT_TAGGED | Post\UserNotification::TYPE_IMPLICIT_TAGGED |
			Post\UserNotification::TYPE_THREAD_COMMENT | Post\UserNotification::TYPE_DIRECT_COMMENT |
			Post\UserNotification::TYPE_DIRECT_THREAD_COMMENT,
			$uid, $since_id,
		];

		if ($max_id > 0) {
			$query .= " AND `uri-id` <= ?";
			$condition[] = $max_id;
		}

		array_unshift($condition, $query);

		$params   = ['order' => ['uri-id' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectForUser($uid, [], $condition, $params);

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		$this->response->exit('statuses', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
