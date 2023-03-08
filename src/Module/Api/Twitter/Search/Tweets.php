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

namespace Friendica\Module\Api\Twitter\Search;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * Returns statuses that match a specified query.
 *
 * @see https://developer.twitter.com/en/docs/tweets/search/api-reference/get-search-tweets
 */
class Tweets extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		if (empty($request['q'])) {
			throw new BadRequestException('q parameter is required.');
		}

		$searchTerm = trim(rawurldecode($request['q']));

		$data['status'] = [];

		$count            = $this->getRequestValue($request, 'count', 20, 1, 100);
		$count            = $this->getRequestValue($request, 'rpp', $count);
		$since_id         = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id           = $this->getRequestValue($request, 'max_id', 0, 0);
		$page             = $this->getRequestValue($request, 'page', 1, 1);
		$include_entities = $this->getRequestValue($request, 'include_entities', false);
		$exclude_replies  = $this->getRequestValue($request, 'exclude_replies', false);

		$start = max(0, ($page - 1) * $count);

		$params = ['order' => ['uri-id' => true], 'limit' => [$start, $count]];
		if (preg_match('/^#(\w+)$/', $searchTerm, $matches) === 1 && isset($matches[1])) {
			$searchTerm = $matches[1];
			$condition  = ["`uri-id` > ? AND `name` = ? AND (NOT `private` OR (`private` AND `uid` = ?))", $since_id, $searchTerm, $uid];

			$tags   = DBA::select('tag-search-view', ['uri-id'], $condition);
			$uriids = [];
			while ($tag = DBA::fetch($tags)) {
				$uriids[] = $tag['uri-id'];
			}
			DBA::close($tags);

			if (empty($uriids)) {
				$this->response->exit('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
				return;
			}

			$condition = ['uri-id' => $uriids];
			if ($exclude_replies) {
				$condition['gravity'] = Item::GRAVITY_PARENT;
			}

			$params['group_by'] = ['uri-id'];
		} else {
			$condition = ["`uri-id` > ?
				" . ($exclude_replies ? " AND `gravity` = " . Item::GRAVITY_PARENT : ' ') . "
				AND (`uid` = 0 OR (`uid` = ? AND NOT `global`))
				AND `body` LIKE CONCAT('%',?,'%')",
				$since_id, $uid, $_REQUEST['q']];
			if ($max_id > 0) {
				$condition[0] .= ' AND `uri-id` <= ?';
				$condition[] = $max_id;
			}
		}

		$statuses = [];

		if (parse_url($searchTerm, PHP_URL_SCHEME) != '') {
			$id = Item::fetchByLink($searchTerm, $uid);
			if (!$id) {
				// Public post
				$id = Item::fetchByLink($searchTerm);
			}

			if (!empty($id)) {
				$statuses = Post::select([], ['id' => $id]);
			}
		}

		$statuses = $statuses ?: Post::selectForUser($uid, [], $condition, $params);

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		$this->response->exit('statuses', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
