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

namespace Friendica\Module\Api\Mastodon\Lists;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Group;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/lists/#accounts-in-a-list
 *
 * Currently the output will be unordered since we use public contact ids in the api and not user contact ids.
 */
class Accounts extends BaseApi
{
	protected function delete(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);

		$request = $this->getRequest([
			'account_ids' => [], // Array of account IDs to remove from the list
		], $request);

		if (empty($request['account_ids']) || empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		return Group::removeMembers($this->parameters['id'], $request['account_ids']);
	}

	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);

		$request = $this->getRequest([
			'account_ids' =>  [], // Array of account IDs to add to the list
		], $request);

		if (empty($request['account_ids']) || empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		Group::addMembers($this->parameters['id'], $request['account_ids']);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$id = $this->parameters['id'];
		if (!DBA::exists('group', ['id' => $id, 'uid' => $uid])) {
			DI::mstdnError()->RecordNotFound();
		}

		$request = $this->getRequest([
			'max_id'   => 0,  // Return results older than this id
			'since_id' => 0,  // Return results newer than this id
			'min_id'   => 0,  // Return results immediately newer than id
			'limit'    => 40, // Maximum number of results. Defaults to 40. Max 40. Set to 0 in order to get all accounts without pagination.
		], $request);

		$params = ['order' => ['contact-id' => true]];

		if ($request['limit'] != 0) {
			$params['limit'] = min($request['limit'], 40);
		}

		$condition = ['gid' => $id];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`contact-id` > ?", $request['min_id']]);

			$params['order'] = ['contact-id'];
		}

		$accounts = [];

		$members = DBA::select('group_member', ['contact-id'], $condition, $params);
		while ($member = DBA::fetch($members)) {
			self::setBoundaries($member['contact-id']);
			try {
				$accounts[] = DI::mstdnAccount()->createFromContactId($member['contact-id'], $uid);
			} catch (\Exception $exception) {
			}
		}
		DBA::close($members);

		if (!empty($request['min_id'])) {
			$accounts = array_reverse($accounts);
		}

		self::setLinkHeader();
		System::jsonExit($accounts);
	}
}
