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

namespace Friendica\Module\Api\Twitter;

use Friendica\Core\Logger;
use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;

/**
 * Returns the most recent mentions.
 *
 * @see http://developer.twitter.com/doc/get/statuses/mentions
 */
class Favorites extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// in friendica starred item are private
		// return favorites only for self
		Logger::info(BaseApi::LOG_PREFIX . 'for {self}', ['module' => 'api', 'action' => 'favorites']);

		// params
		$since_id = $request['since_id'] ?? 0;
		$max_id   = $request['max_id']   ?? 0;
		$count    = $request['count']    ?? 20;
		$page     = $request['page']     ?? 1;

		$start = max(0, ($page - 1) * $count);

		$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `id` > ? AND `starred`",
			$uid, GRAVITY_PARENT, GRAVITY_COMMENT, $since_id];

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];

		if ($max_id > 0) {
			$condition[0] .= " AND `id` <= ?";
			$condition[] = $max_id;
		}

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
