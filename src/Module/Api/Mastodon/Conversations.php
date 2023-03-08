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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/conversations/
 */
class Conversations extends BaseApi
{
	protected function delete(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (!empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		DBA::delete('conv', ['id' => $this->parameters['id'], 'uid' => $uid]);
		DBA::delete('mail', ['convid' => $this->parameters['id'], 'uid' => $uid]);

		System::jsonExit([]);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'limit'    => 20, // Maximum number of results. Defaults to 20. Max 40.
			'max_id'   => 0,  // Return results older than this ID. Use HTTP Link header to paginate.
			'since_id' => 0,  // Return results newer than this ID. Use HTTP Link header to paginate.
			'min_id'   => 0,  // Return results immediately newer than this ID. Use HTTP Link header to paginate.
		], $request);

		$params = ['order' => ['id' => true], 'limit' => $request['limit']];

		$condition = ['uid' => $uid];

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

		$convs = DBA::select('conv', ['id'], $condition, $params);

		$conversations = [];

		while ($conv = DBA::fetch($convs)) {
			self::setBoundaries($conv['id']);
			$conversations[] = DI::mstdnConversation()->createFromConvId($conv['id']);
		}

		DBA::close($convs);

		if (!empty($request['min_id'])) {
			$conversations = array_reverse($conversations);
		}

		self::setLinkHeader();
		System::jsonExit($conversations);
	}
}
