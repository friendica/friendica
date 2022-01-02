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

namespace Friendica\Module\Api\Twitter\Blocks;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Module\BaseApi;

/**
 * @see https://developer.twitter.com/en/docs/twitter-api/v1/accounts-and-users/mute-block-report-users/api-reference/get-blocks-ids
 */
class Ids extends ContactEndpoint
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// Expected value for user_id parameter: public/user contact id
		$cursor        = filter_input(INPUT_GET, 'cursor'       , FILTER_VALIDATE_INT, ['options' => ['default' => -1]]);
		$stringify_ids = filter_input(INPUT_GET, 'stringify_ids', FILTER_VALIDATE_BOOLEAN, ['options' => ['default' => false]]);
		$count         = filter_input(INPUT_GET, 'count'        , FILTER_VALIDATE_INT, ['options' => [
			'default'   => self::DEFAULT_COUNT,
			'min_range' => 1,
			'max_range' => self::MAX_COUNT,
		]]);
		// Friendica-specific
		$since_id = filter_input(INPUT_GET, 'since_id', FILTER_VALIDATE_INT);
		$max_id   = filter_input(INPUT_GET, 'max_id'  , FILTER_VALIDATE_INT);
		$min_id   = filter_input(INPUT_GET, 'min_id'  , FILTER_VALIDATE_INT);

		$params = ['order' => ['cid' => true], 'limit' => $count];

		$condition = ['uid' => $uid, 'blocked' => true];

		$total_count = (int)DBA::count('user-contact', $condition);

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`cid` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`cid` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`cid` > ?", $min_id]);

			$params['order'] = ['cid'];
		}

		$ids = [];

		$contacts = DBA::select('user-contact', ['cid'], $condition, $params);
		while ($contact = DBA::fetch($contacts)) {
			self::setBoundaries($contact['cid']);
			$ids[] = $contact['cid'];
		}
		DBA::close($contacts);

		if (!empty($min_id)) {
			$ids = array_reverse($ids);
		}

		$return = self::ids($ids, $total_count, $cursor, $count, $stringify_ids);

		self::setLinkHeader();

		System::jsonExit($return);
	}
}
