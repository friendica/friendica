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

		if (empty($_REQUEST['q'])) {
			throw new BadRequestException('q parameter is required.');
		}

		$searchTerm = trim(rawurldecode($_REQUEST['q']));

		$data['status'] = [];

		$count = 15;

		$exclude_replies = !empty($_REQUEST['exclude_replies']);
		if (!empty($_REQUEST['rpp'])) {
			$count = $_REQUEST['rpp'];
		} elseif (!empty($_REQUEST['count'])) {
			$count = $_REQUEST['count'];
		}

		$since_id = $_REQUEST['since_id'] ?? 0;
		$max_id   = $_REQUEST['max_id']   ?? 0;
		$page     = $_REQUEST['page']     ?? 1;

		$start = max(0, ($page - 1) * $count);

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];
		if (preg_match('/^#(\w+)$/', $searchTerm, $matches) === 1 && isset($matches[1])) {
			$searchTerm = $matches[1];
			$condition  = ["`iid` > ? AND `name` = ? AND (NOT `private` OR (`private` AND `uid` = ?))", $since_id, $searchTerm, $uid];

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
				$condition['gravity'] = GRAVITY_PARENT;
			}

			$params['group_by'] = ['uri-id'];
		} else {
			$condition = ["`id` > ?
				" . ($exclude_replies ? " AND `gravity` = " . GRAVITY_PARENT : ' ') . "
				AND (`uid` = 0 OR (`uid` = ? AND NOT `global`))
				AND `body` LIKE CONCAT('%',?,'%')",
				$since_id, $uid, $_REQUEST['q']];
			if ($max_id > 0) {
				$condition[0] .= ' AND `id` <= ?';
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

		$include_entities = strtolower(($_REQUEST['include_entities'] ?? 'false') == 'true');

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		$this->response->exit('statuses', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
