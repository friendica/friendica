<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/followed_tags/
 */
class FollowedTags extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'max_id'   => 0,
			'since_id' => 0,
			'min_id'   => 0,
			'limit'    => 100, // Maximum number of results to return. Defaults to 100. Paginate using the HTTP Link header.
		], $request);

		$params = ['order' => ['id' => true], 'limit' => $request['limit']];

		$condition = ["`uid` = ? AND `term` LIKE ?", $uid, '#%'];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $request['min_id']]);

			$params['order'] = ['id'];
		}

		$return = [];

		$saved_searches = DBA::select('search', ['id', 'term'], $condition);
		while ($saved_search = DBA::fetch($saved_searches)) {
			self::setBoundaries($saved_search['id']);

			$hashtag  = new \Friendica\Object\Api\Mastodon\Tag($this->baseUrl, ['name' => ltrim((string) $saved_search['term'], '#')], [], true);
			$return[] = $hashtag->toArray();
		}

		DBA::close($saved_searches);

		if (!empty($request['min_id'])) {
			$return = array_reverse($return);
		}

		self::setLinkHeader();
		$this->jsonExit($return);
	}
}
