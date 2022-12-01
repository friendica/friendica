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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/followed_tags/
 */
class FollowedTags extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'max_id'   => 0,
			'since_id' => 0,
			'min_id'   => 0,
			'limit'    => 100, // Maximum number of results to return. Defaults to 100. Paginate using the HTTP Link header.
		], $request);

		$params = ['order' => ['id' => true], 'limit' => $request['limit']];

		$condition = ["`uid` = ? AND `term` LIKE ?", $uid, '#%'];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $request['min_id']]);

			$params['order'] = ['id'];
		}

		$return = [];

		$saved_searches = DBA::select('search', ['id', 'term'], $condition);
		while ($saved_search = DBA::fetch($saved_searches)) {
			self::setBoundaries($saved_search['id']);

			$hashtag  = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, ['name' => ltrim($saved_search['term'], '#')], [], true);
			$return[] = $hashtag->toArray();
		}

		DBA::close($saved_searches);

		if (!empty($request['min_id'])) {
			$return = array_reverse($return);
		}

		self::setLinkHeader();
		System::jsonExit($return);
	}
}
