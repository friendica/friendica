<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Trends;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Util\DateTimeFormat;

/**
 * @see https://docs.joinmastodon.org/methods/trends/#links
 */
class Links extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'limit'  => 10, // Maximum number of results to return. Defaults to 10.
			'offset' => 0, // Offset in set, Defaults to 0.
		], $request);

		$condition = ["EXISTS(SELECT `id` FROM `post-media` WHERE `post-media`.`uri-id` = `post-thread-view`.`uri-id` AND `type` = ? AND NOT `name` IS NULL AND NOT `description` IS NULL) AND NOT `private` AND `commented` > ? AND `created` > ?",
			Post\Media::HTML, DateTimeFormat::utc('now -1 day'), DateTimeFormat::utc('now -1 week')];
		$condition = DBA::mergeConditions($condition, ['network' => Protocol::FEDERATED]);

		$trending = [];
		$statuses = Post::selectPostThread(['uri-id', 'total-comments', 'total-actors'], $condition, ['limit' => [$request['offset'], $request['limit']], 'offset' => $request['offset'], 'order' => ['total-actors' => true]]);
		while ($status = Post::fetch($statuses)) {
			$history = [['day' => (string)time(), 'uses' => (string)$status['total-comments'], 'accounts' => (string)$status['total-actors']]];
			$link    = DI::mstdnCard()->createFromUriId($status['uri-id'], $history)->toArray();
			if ($link) {
				$trending[] = $link;
			}
		}
		DBA::close($statuses);

		if (!empty($trending)) {
			self::setLinkHeaderByOffsetLimit($request['offset'], $request['limit']);
		}

		$this->jsonExit($trending);
	}
}
