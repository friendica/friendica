<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Statuses;

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
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
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
		$deleted  = [];

		$parent = Post::selectOriginal(['uri-id', 'parent-uri-id'], ['uri-id' => $id]);
		if (DBA::isResult($parent)) {
			$id        = $parent['uri-id'];
			$params    = ['order' => ['uri-id' => true]];
			$condition = ['parent-uri-id' => $parent['parent-uri-id'], 'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]];

			if (!empty($request['max_id'])) {
				$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $request['max_id']]);
			}

			if (!empty($request['since_id'])) {
				$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['since_id']]);
			}

			if (!empty($request['min_id'])) {
				$condition       = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['min_id']]);
				$params['order'] = ['uri-id'];
			}

			if (!empty($uid) && !$request['show_all']) {
				$condition = DBA::mergeConditions(
					$condition,
					["NOT `author-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored` OR `is-blocked`))", $uid]
				);
			}

			$posts = Post::selectPosts(['uri-id', 'thr-parent-id', 'deleted'], $condition, $params);
			while ($post = Post::fetch($posts)) {
				if ($post['uri-id'] == $post['thr-parent-id']) {
					continue;
				}
				self::setBoundaries($post['uri-id']);

				$parents[$post['uri-id']] = $post['thr-parent-id'];

				$children[$post['thr-parent-id']][] = $post['uri-id'];

				if ($post['deleted']) {
					$deleted[] = $post['uri-id'];
				}
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
				$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
			}
		}

		$statuses = ['ancestors' => [], 'descendants' => []];

		$ancestors = array_diff(self::getParents($id, $parents), $deleted);

		asort($ancestors);

		foreach (array_slice($ancestors, 0, $request['limit']) as $ancestor) {
			try {
				$statuses['ancestors'][] = DI::mstdnStatus()->createFromUriId($ancestor, $uid);
			} catch (\Throwable $th) {
				$this->logger->info('Post not fetchable', ['uri-id' => $ancestor, 'uid' => $uid, 'error' => $th]);
			}
		}

		$descendants = array_diff(self::getChildren($id, $children), $deleted);

		asort($descendants);

		foreach (array_slice($descendants, 0, $request['limit']) as $descendant) {
			try {
				$statuses['descendants'][] = DI::mstdnStatus()->createFromUriId($descendant, $uid);
			} catch (\Throwable $th) {
				$this->logger->info('Post not fetchable', ['uri-id' => $descendant, 'uid' => $uid, 'error' => $th]);
			}
		}

		$this->jsonExit($statuses);
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
