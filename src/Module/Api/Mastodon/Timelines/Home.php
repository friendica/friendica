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

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class Home extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'max_id'          => 0,     // Return results older than id
			'since_id'        => 0,     // Return results newer than id
			'min_id'          => 0,     // Return results immediately newer than id
			'limit'           => 20,    // Maximum number of results to return. Defaults to 20.
			'local'           => false, // Return only local statuses?
			'with_muted'      => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'only_media'      => false, // Show only statuses with media attached? Defaults to false.
			'remote'          => false, // Show only remote statuses? Defaults to false.
			'exclude_replies' => false, // Don't show comments
		], $request);

		$params = ['order' => ['uri-id' => true], 'limit' => $request['limit']];

		$condition = ['gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT], 'uid' => $uid];

		if ($request['local']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)"]);
		}

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

		if ($request['only_media']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-media` WHERE `type` IN (?, ?, ?))",
				Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO]);
		}

		if ($request['remote']) {
			$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)"]);
		}

		if ($request['exclude_replies']) {
			$condition = DBA::mergeConditions($condition, ['gravity' => GRAVITY_PARENT]);
		}

		$items = Post::selectForUser($uid, ['uri-id'], $condition, $params);

		$statuses = [];
		while ($item = Post::fetch($items)) {
			self::setBoundaries($item['uri-id']);
			$statuses[] = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid);
		}
		DBA::close($items);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		System::jsonExit($statuses);
	}
}
