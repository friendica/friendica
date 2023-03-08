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

namespace Friendica\Module\Api\Twitter\Followers;

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Module\BaseApi;

/**
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-followers-list
 */
class Lists extends ContactEndpoint
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// Expected value for user_id parameter: public/user contact id
		$cid                   = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), $uid);
		$cursor                = $this->getRequestValue($request, 'cursor', -1);
		$skip_status           = $this->getRequestValue($request, 'skip_status', false);
		$include_user_entities = $this->getRequestValue($request, 'include_user_entities', false);
		$count                 = $this->getRequestValue($request, 'count', self::DEFAULT_COUNT, 1, self::MAX_COUNT);

		// Friendica-specific
		$since_id = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id   = $this->getRequestValue($request, 'max_id', 0, 0);
		$min_id   = $this->getRequestValue($request, 'min_id', 0, 0);

		if ($cid == Contact::getPublicIdByUserId($uid)) {
			$params = ['order' => ['pid' => true], 'limit' => $count];

			$condition = ['uid' => $uid, 'self' => false, 'pending' => false, 'rel' => [Contact::FOLLOWER, Contact::FRIEND]];

			$total_count = (int)DBA::count('contact', $condition);

			if (!empty($max_id)) {
				$condition = DBA::mergeConditions($condition, ["`pid` < ?", $max_id]);
			}

			if (!empty($since_id)) {
				$condition = DBA::mergeConditions($condition, ["`pid` > ?", $since_id]);
			}

			if (!empty($min_id)) {
				$condition = DBA::mergeConditions($condition, ["`pid` > ?", $min_id]);

				$params['order'] = ['pid'];
			}

			$ids = [];

			foreach (Contact::selectAccountToArray(['pid'], $condition, $params) as $follower) {
				self::setBoundaries($follower['pid']);
				$ids[] = $follower['pid'];
			}
		} else {
			$params = ['order' => ['relation-cid' => true], 'limit' => $count];

			$condition = ['cid' => $cid, 'follows' => true];

			$total_count = (int)DBA::count('contact-relation', $condition);

			if (!empty($max_id)) {
				$condition = DBA::mergeConditions($condition, ["`relation-cid` < ?", $max_id]);
			}

			if (!empty($since_id)) {
				$condition = DBA::mergeConditions($condition, ["`relation-cid` > ?", $since_id]);
			}

			if (!empty($min_id)) {
				$condition = DBA::mergeConditions($condition, ["`relation-cid` > ?", $min_id]);

				$params['order'] = ['relation-cid'];
			}

			$ids = [];

			$followers = DBA::select('contact-relation', ['relation-cid'], $condition, $params);
			while ($follower = DBA::fetch($followers)) {
				self::setBoundaries($follower['relation-cid']);
				$ids[] = $follower['relation-cid'];
			}
			DBA::close($followers);
		}

		if (!empty($min_id)) {
			$ids = array_reverse($ids);
		}

		$return = self::list($ids, $total_count, $uid, $cursor, $count, $skip_status, $include_user_entities);

		$this->response->setHeader(self::getLinkHeader());

		$this->response->exit('lists', ['lists' => $return]);
	}
}
