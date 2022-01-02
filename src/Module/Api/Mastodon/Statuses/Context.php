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

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
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
			'limit'    => 40, // Maximum number of results to return. Defaults to 40.
		], $request);

		$id = $this->parameters['id'];

		$parents  = [];
		$children = [];

		$parent = Post::selectFirst(['parent-uri-id'], ['uri-id' => $id]);
		if (DBA::isResult($parent)) {
			$posts = Post::selectPosts(['uri-id', 'thr-parent-id'],
				['parent-uri-id' => $parent['parent-uri-id'], 'gravity' => [GRAVITY_PARENT, GRAVITY_COMMENT]]);
			while ($post = Post::fetch($posts)) {
				if ($post['uri-id'] == $post['thr-parent-id']) {
					continue;
				}
				$parents[$post['uri-id']] = $post['thr-parent-id'];

				$children[$post['thr-parent-id']][] = $post['uri-id'];
			}
			DBA::close($posts);
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

		foreach (array_slice($ancestors, 0, $request['limit']) as $ancestor) {
			$statuses['ancestors'][] = DI::mstdnStatus()->createFromUriId($ancestor, $uid);;
		}

		$descendants = self::getChildren($id, $children);

		asort($descendants);

		foreach (array_slice($descendants, 0, $request['limit']) as $descendant) {
			$statuses['descendants'][] = DI::mstdnStatus()->createFromUriId($descendant, $uid);
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
