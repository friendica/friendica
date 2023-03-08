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

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Object\Api\Mastodon\TimelineOrderByTypes;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class ListTimeline extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$request = $this->getRequest([
			'max_id'          => null,  // Return results older than id
			'since_id'        => null,  // Return results newer than id
			'min_id'          => null,  // Return results immediately newer than id
			'limit'           => 20,    // Maximum number of results to return. Defaults to 20.Return results older than this ID.
			'with_muted'      => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'only_media'      => false, // Show only statuses with media attached? Defaults to false.
			'local'           => false, // Show only local statuses? Defaults to false.
			'remote'          => false, // Show only remote statuses? Defaults to false.
			'exclude_replies' => false, // Don't show comments
			'friendica_order' => TimelineOrderByTypes::ID, // Sort order options (defaults to ID)
		], $request);

		$condition = ["`uid` = ? AND `gravity` IN (?, ?) AND `contact-id` IN (SELECT `contact-id` FROM `group_member` WHERE `gid` = ?)",
			$uid, Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT, $this->parameters['id']];

		$condition = $this->addPagingConditions($request, $condition);
		$params = $this->buildOrderAndLimitParams($request);

		if ($request['only_media']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-media` WHERE `type` IN (?, ?, ?))",
				Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO]);
		}

		if ($request['exclude_replies']) {
			$condition = DBA::mergeConditions($condition, ['gravity' => Item::GRAVITY_PARENT]);
		}

		if ($request['local']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)"]);
		}

		if ($request['remote']) {
			$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin` AND `post-user`.`uri-id` = `post-user-view`.`uri-id`)"]);
		}

		$items = Post::selectForUser($uid, ['uri-id'], $condition, $params);

		$display_quotes = self::appSupportsQuotes();

		$statuses = [];
		while ($item = Post::fetch($items)) {
			try {
				$status =  DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, $display_quotes);
				$this->updateBoundaries($status, $item, $request['friendica_order']);
				$statuses[] = $status;
			} catch (\Throwable $th) {
				Logger::info('Post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'error' => $th]);
			}
		}
		DBA::close($items);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		System::jsonExit($statuses);
	}
}
