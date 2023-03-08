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

use Friendica\App\Router;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/scheduled_statuses/
 */
class ScheduledStatuses extends BaseApi
{
	public function put(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$this->response->unsupported(Router::PUT, $request);
	}

	protected function delete(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		if (!DBA::exists('delayed-post', ['id' => $this->parameters['id'], 'uid' => $uid])) {
			DI::mstdnError()->RecordNotFound();
		}

		Post\Delayed::deleteById($this->parameters['id']);

		System::jsonExit([]);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (isset($this->parameters['id'])) {
			System::jsonExit(DI::mstdnScheduledStatus()->createFromDelayedPostId($this->parameters['id'], $uid)->toArray());
		}

		$request = $this->getRequest([
			'limit'           => 20, // Max number of results to return. Defaults to 20.
			'max_id'          => 0,  // Return results older than ID
			'since_id'        => 0,  // Return results newer than ID
			'min_id'          => 0,  // Return results immediately newer than ID
		], $request);

		$params = ['order' => ['id' => true], 'limit' => $request['limit']];

		$condition = ["`uid` = ? AND NOT `wid` IS NULL", $uid];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['min_id']]);
			$params['order'] = ['uri-id'];
		}

		$posts = DBA::select('delayed-post', ['id'], $condition, $params);

		$statuses = [];
		while ($post = DBA::fetch($posts)) {
			self::setBoundaries($post['id']);
			$statuses[] = DI::mstdnScheduledStatus()->createFromDelayedPostId($post['id'], $uid);
		}
		DBA::close($posts);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		System::jsonExit($statuses);
	}
}
