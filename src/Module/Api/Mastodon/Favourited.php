<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/favourites/
 */
class Favourited extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'limit'      => 20,    // Maximum number of results to return. Defaults to 20.
			'max_id'     => 0,     // Return results older than id
			'since_id'   => 0,     // Return results newer than this ID. Use HTTP Link header to paginate.
			'min_id'     => 0,     // Return results immediately newer than id
			'with_muted' => false, // Pleroma extension: return activities by muted (not by blocked!) users.
		], $request);

		$params = ['order' => ['thr-parent-id' => true], 'limit' => $request['limit']];

		$condition = ['gravity' => Item::GRAVITY_ACTIVITY, 'origin' => true, 'verb' => Activity::LIKE, 'uid' => $uid];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`thr-parent-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`thr-parent-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`thr-parent-id` > ?", $request['min_id']]);

			$params['order'] = ['thr-parent-id'];
		}

		$items = Post::selectForUser($uid, ['thr-parent-id'], $condition, $params);

		$display_quotes = self::appSupportsQuotes();

		$statuses = [];
		while ($item = Post::fetch($items)) {
			self::setBoundaries($item['thr-parent-id']);
			try {
				$statuses[] = DI::mstdnStatus()->createFromUriId($item['thr-parent-id'], $uid, $display_quotes);
			} catch (\Exception $exception) {
				$this->logger->info('Post not fetchable', ['uri-id' => $item['thr-parent-id'], 'uid' => $uid, 'exception' => $exception]);
			}
		}
		DBA::close($items);

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		$this->jsonExit($statuses);
	}
}
