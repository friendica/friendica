<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Trends;

use Friendica\DI;
use Friendica\Model\Tag;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/instance/trends/
 */
class Tags extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$request = $this->getRequest([
			'limit'           => 20, // Maximum number of results to return. Defaults to 20.
			'offset'          => 0, // Offset in set. Defaults to 0.
			'friendica_local' => false, // Whether to return local tag trends instead of global, defaults to false
		], $request);

		$trending = [];
		if ($request['friendica_local']) {
			$tags = Tag::getLocalTrendingHashtags(24, $request['limit'], $request['offset']);
		} else {
			$tags = Tag::getGlobalTrendingHashtags(24, $request['limit'], $request['offset']);
		}

		foreach ($tags as $tag) {
			$tag['name'] = $tag['term'];
			$history     = [['day' => (string)time(), 'uses' => (string)$tag['score'], 'accounts' => (string)$tag['authors']]];
			$hashtag     = new \Friendica\Object\Api\Mastodon\Tag(DI::baseUrl(), $tag, $history);
			$trending[]  = $hashtag->toArray();
		}

		if (!empty($trending)) {
			self::setLinkHeaderByOffsetLimit($request['offset'], $request['limit']);
		}

		$this->jsonExit($trending);
	}
}
