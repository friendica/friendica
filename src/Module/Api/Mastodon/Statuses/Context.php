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

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Context extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$request = $this->getRequest([
			'max_id'   => 0,     // Return results older than this id
			'since_id' => 0,     // Return results newer than this id
			'min_id'   => 0,     // Return results immediately newer than this id
			'limit'    => 40,    // Maximum number of results to return. Defaults to 40.
			'show_all' => false, // shows posts for all users including blocked and ignored users
		], $request);

		$id = $this->parameters['id'];

		$parents  = [];
		$children = [];

		$parent = Post::selectFirst(['parent-uri-id'], ['uri-id' => $id]);
		if (DBA::isResult($parent)) {
			$params    = ['order' => ['uri-id' => true]];
			$condition = ['parent-uri-id' => $parent['parent-uri-id'], 'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]];

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

			if (!empty($uid) && !$request['show_all']) {
				$condition = DBA::mergeConditions(
					$condition,
					["NOT `author-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored`))", $uid]
				);
			}
	
			$posts = Post::selectPosts(['uri-id', 'thr-parent-id'], $condition, $params);
			while ($post = Post::fetch($posts)) {
				if ($post['uri-id'] == $post['thr-parent-id']) {
					continue;
				}
				self::setBoundaries($post['uri-id']);

				$parents[$post['uri-id']] = $post['thr-parent-id'];

				$children[$post['thr-parent-id']][] = $post['uri-id'];
			}
			DBA::close($posts);

			self::setLinkHeader();
		} else {
			$parent = DBA::selectFirst('mail', ['parent-uri-id'], ['uri-id' => $id, 'uid' => $uid]);
			if (DBA::isResult($parent)) {
				$posts = DBA::select('mail', ['uri-id', 'thr-parent-id'], ['parent-uri-id' => $parent['parent-uri-id']]);
				while ($post = DBA::fetch($posts)) {
					if ($post['uri-id'] == $post['thr-parent-id']) {
						continue;
					}
					$parents[$post['uri-id']] = $post['thr-parent-id'];

					$children[$post['thr-parent-id']][] = $post['uri-id'];
				}
				DBA::close($posts);
			} else {
				DI::mstdnError()->RecordNotFound();
			}
		}

		$statuses = ['ancestors' => [], 'descendants' => []];

		$ancestors = self::getParents($id, $parents);

		asort($ancestors);

		$display_quotes = self::appSupportsQuotes();

		foreach (array_slice($ancestors, 0, $request['limit']) as $ancestor) {
			$statuses['ancestors'][] = DI::mstdnStatus()->createFromUriId($ancestor, $uid, $display_quotes);
		}

		$descendants = self::getChildren($id, $children);

		asort($descendants);

		foreach (array_slice($descendants, 0, $request['limit']) as $descendant) {
			$statuses['descendants'][] = DI::mstdnStatus()->createFromUriId($descendant, $uid, $display_quotes);
		}

		System::jsonExit($statuses);
	}

	private static function getParents(int $id, array $parents, array $list = [])
	{
		if (!empty($parents[$id])) {
			$list[] = $parents[$id];

			$list = self::getParents($parents[$id], $parents, $list);
		}
		return $list;
	}

	private static function getChildren(int $id, array $children, array $list = [])
	{
		if (!empty($children[$id])) {
			foreach ($children[$id] as $child) {
				$list[] = $child;

				$list = self::getChildren($child, $children, $list);
			}
		}
		return $list;
	}
}
