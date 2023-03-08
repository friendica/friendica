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

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * Returns the most recent statuses posted by the user.
 *
 * @see https://developer.twitter.com/en/docs/tweets/timelines/api-reference/get-statuses-user_timeline
 */
class UserTimeline extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		Logger::info('api_statuses_user_timeline', ['api_user' => $uid, '_REQUEST' => $request]);

		$cid              = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), $uid);
		$count            = $this->getRequestValue($request, 'count', 20, 1, 100);
		$page             = $this->getRequestValue($request, 'page', 1, 1);
		$since_id         = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id           = $this->getRequestValue($request, 'max_id', 0, 0);
		$exclude_replies  = $this->getRequestValue($request, 'exclude_replies', false);
		$conversation_id  = $this->getRequestValue($request, 'conversation_id', 0, 0);
		$include_entities = $this->getRequestValue($request, 'include_entities', false);

		$start = max(0, ($page - 1) * $count);

		$condition = ["(`uid` = ? OR (`uid` = ? AND NOT `global`)) AND `gravity` IN (?, ?) AND `uri-id` > ? AND `author-id` = ?",
			0, $uid, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT, $since_id, $cid];

		if ($exclude_replies) {
			$condition[0] .= ' AND `gravity` = ?';
			$condition[] = Item::GRAVITY_PARENT;
		}

		if ($conversation_id > 0) {
			$condition[0] .= " AND `parent-uri-id` = ?";
			$condition[] = $conversation_id;
		}

		if ($max_id > 0) {
			$condition[0] .= " AND `uri-id` <= ?";
			$condition[] = $max_id;
		}
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
