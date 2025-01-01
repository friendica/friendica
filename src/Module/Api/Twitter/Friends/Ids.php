<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Friends;

use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Module\BaseApi;

/**
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/get-friends-ids
 */
class Ids extends ContactEndpoint
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		// Expected value for user_id parameter: public/user contact id
		$cid           = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), $uid);
		$cursor        = $this->getRequestValue($request, 'cursor', -1);
		$stringify_ids = $this->getRequestValue($request, 'stringify_ids', false);
		$count         = $this->getRequestValue($request, 'count', self::DEFAULT_COUNT, 1, self::MAX_COUNT);

		// Friendica-specific
		$since_id = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id   = $this->getRequestValue($request, 'max_id', 0, 0);
		$min_id   = $this->getRequestValue($request, 'min_id', 0, 0);

		if ($cid == Contact::getPublicIdByUserId($uid)) {
			$params = ['order' => ['pid' => true], 'limit' => $count];

			$condition = ['uid' => $uid, 'self' => false, 'pending' => false, 'rel' => [Contact::SHARING, Contact::FRIEND]];

			$total_count = (int)DBA::count('contact', $condition);

			if (!empty($max_id)) {
				$condition = DBA::mergeConditions($condition, ["`pid` < ?", $max_id]);
			}

			if (!empty($since_id)) {
				$condition = DBA::mergeConditions($condition, ["`pid` > ?", $since_id]);
			}

			if (!empty($min_id)) {
				$condition = DBA::mergeConditions($condition, ["`pid` > ?", $min_id]);

				$params['order'] = ['pid'];
			}

			$ids = [];

			foreach (Contact::selectAccountToArray(['pid'], $condition, $params) as $follower) {
				self::setBoundaries($follower['pid']);
				$ids[] = $follower['pid'];
			}
		} else {
			$params = ['order' => ['cid' => true], 'limit' => $count];

			$condition = ['relation-cid' => $cid, 'follows' => true];

			$total_count = (int)DBA::count('contact-relation', $condition);

			if (!empty($max_id)) {
				$condition = DBA::mergeConditions($condition, ["`cid` < ?", $max_id]);
			}

			if (!empty($since_id)) {
				$condition = DBA::mergeConditions($condition, ["`cid` > ?", $since_id]);
			}

			if (!empty($min_id)) {
				$condition = DBA::mergeConditions($condition, ["`cid` > ?", $min_id]);

				$params['order'] = ['cid'];
			}

			$ids = [];

			$followers = DBA::select('contact-relation', ['cid'], $condition, $params);
			while ($follower = DBA::fetch($followers)) {
				self::setBoundaries($follower['cid']);
				$ids[] = $follower['cid'];
			}
			DBA::close($followers);
		}

		if (!empty($min_id)) {
			$ids = array_reverse($ids);
		}

		$return = self::ids($ids, $total_count, $cursor, $count, $stringify_ids);

		self::setLinkHeader();

		$this->jsonExit($return);
	}
}
