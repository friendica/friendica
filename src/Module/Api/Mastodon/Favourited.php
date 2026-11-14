<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Verb;
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

		// The likes are selected directly from the table by the author, so that the index "author-id_uid" can be used.
		// Without this the optimizer can decide to walk through the activities of all users in the order of the sorting.
		$self = Contact::getPublicIdByUserId($uid);
		if (empty($self)) {
			$this->setPaginationLinkHeader();
			$this->earlyJsonExit([]);
		}

		$condition = ['author-id' => $self, 'uid' => $uid, 'gravity' => Item::GRAVITY_ACTIVITY, 'origin' => true, 'deleted' => false];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`thr-parent-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`thr-parent-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`thr-parent-id` > ?", $request['min_id']]);
		}

		// There is deliberately no ordering and no limit in the query: With them the optimizer prefers the index of
		// the sorting field and filters through the activities of all users. The own likes are few enough to sort them here.
		// The verb is checked here as well and not in the query, since the optimizer would then intersect the index of
		// the verb, which contains the likes of all users, with the index of the author.
		$vid_like = Verb::getID(Activity::LIKE);
		$uriIds   = [];
		foreach (DBA::selectToArray('post-user', ['thr-parent-id', 'vid'], $condition) as $row) {
			if ($row['vid'] == $vid_like) {
				$uriIds[$row['thr-parent-id']] = $row['thr-parent-id'];
			}
		}
		$uriIds = array_values($uriIds);
		if (empty($request['min_id'])) {
			rsort($uriIds);
		} else {
			sort($uriIds);
		}

		$statuses = [];
		foreach (array_slice($uriIds, 0, $request['limit']) as $uriId) {
			self::setBoundaries($uriId);
			try {
				$statuses[] = DI::mstdnStatus()->createFromUriId($uriId, $uid);
			} catch (\Exception $exception) {
				$this->logger->info('Post not fetchable', ['uri-id' => $uriId, 'uid' => $uid, 'exception' => $exception]);
			}
		}

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		$this->setPaginationLinkHeader();
		$this->earlyJsonExit($statuses);
	}
}
