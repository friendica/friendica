<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/bookmarks/
 */
class Bookmarks extends BaseApi
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
			'since_id'   => 0,     // Return results newer than id
			'min_id'     => 0,     // Return results immediately newer than id
			'with_muted' => false, // Pleroma extension: return activities by muted (not by blocked!) users.
		], $request);

		$params = ['order' => ['uri-id' => true], 'limit' => $request['limit']];

		$condition = ['starred' => true, 'uid' => $uid];

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

		$items = Post::selectThreadForUser($uid, ['uri-id'], $condition, $params);

		$display_quotes = self::appSupportsQuotes();

		$statuses = [];
		while ($item = Post::fetch($items)) {
			self::setBoundaries($item['uri-id']);
			try {
				$statuses[] = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid, $display_quotes);
			} catch (\Exception $exception) {
				$this->logger->info('Post not fetchable', ['uri-id' => $item['uri-id'], 'uid' => $uid, 'exception' => $exception]);
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
