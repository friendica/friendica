<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;

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

		$cid             = BaseApi::getContactIDForSearchterm($request['screen_name'] ?? '', $request['profileurl'] ?? '', $request['user_id'] ?? 0, $uid);
		$since_id        = $request['since_id'] ?? 0;
		$max_id          = $request['max_id']   ?? 0;
		$exclude_replies = !empty($request['exclude_replies']);
		$conversation_id = $request['conversation_id'] ?? 0;

		// pagination
		$count = $request['count'] ?? 20;
		$page  = $request['page']  ?? 1;

		$start = max(0, ($page - 1) * $count);

		$condition = ["(`uid` = ? OR (`uid` = ? AND NOT `global`)) AND `gravity` IN (?, ?) AND `id` > ? AND `author-id` = ?",
			0, $uid, GRAVITY_PARENT, GRAVITY_COMMENT, $since_id, $cid];

		if ($exclude_replies) {
			$condition[0] .= ' AND `gravity` = ?';
			$condition[] = GRAVITY_PARENT;
		}

		if ($conversation_id > 0) {
			$condition[0] .= " AND `parent` = ?";
			$condition[] = $conversation_id;
		}

		if ($max_id > 0) {
			$condition[0] .= " AND `id` <= ?";
			$condition[] = $max_id;
		}
		$params   = ['order' => ['id' => true], 'limit' => [$start, $count]];
		$statuses = Post::selectForUser($uid, [], $condition, $params);

		$include_entities = strtolower(($request['include_entities'] ?? 'false') == 'true');

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		$this->response->exit('statuses', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
