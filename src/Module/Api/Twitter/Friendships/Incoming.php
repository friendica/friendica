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

namespace Friendica\Module\Api\Twitter\Friendships;

use Friendica\Database\DBA;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Module\BaseApi;

/**
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/follow-search-get-users/api-reference/get-friendships-incoming
 */
class Incoming extends ContactEndpoint
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// Expected value for user_id parameter: public/user contact id
		$cursor        = $this->getRequestValue($request, 'cursor', -1);
		$stringify_ids = $this->getRequestValue($request, 'stringify_ids', false);
		$count         = $this->getRequestValue($request, 'count', self::DEFAULT_COUNT, 1, self::MAX_COUNT);

		// Friendica-specific
		$since_id = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id   = $this->getRequestValue($request, 'max_id', 0, 0);
		$min_id   = $this->getRequestValue($request, 'min_id', 0, 0);

		$params = ['order' => ['contact-id' => true], 'limit' => $count];

		$condition = ["`uid` = ? AND NOT `blocked` AND NOT `ignore` AND `contact-id` != 0 AND (`suggest-cid` = 0 OR `suggest-cid` IS NULL)", $uid];

		$total_count = (int)DBA::count('intro', $condition);

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` > ?", $min_id]);

			$params['order'] = ['contact-id'];
		}

		$ids = [];

		$contacts = DBA::select('intro', ['contact-id'], $condition, $params);
		while ($contact = DBA::fetch($contacts)) {
			self::setBoundaries($contact['contact-id']);
			$ids[] = $contact['contact-id'];
		}
		DBA::close($contacts);

		if (!empty($min_id)) {
			$ids = array_reverse($ids);
		}

		$return = self::ids($ids, $total_count, $cursor, $count, $stringify_ids);

		$this->response->setHeader(self::getLinkHeader());

		$this->response->exit('incoming', ['incoming' => $return]);
	}
}
