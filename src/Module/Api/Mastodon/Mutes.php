<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/mutes/
 */
class Mutes extends BaseApi
{
	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'max_id'   => 0,  // Return results older than this id
			'since_id' => 0,  // Return results newer than this id
			'min_id'   => 0,  // Return results immediately newer than id
			'limit'    => 40, // Maximum number of results. Defaults to 40.
		], $request);

		$params = ['order' => ['cid' => true], 'limit' => $request['limit']];

		$condition = ['ignored' => true, 'uid' => $uid];

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`cid` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`cid` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`cid` > ?", $request['min_id']]);

			$params['order'] = ['cid'];
		}

		$followers = DBA::select('user-contact', ['cid'], $condition, $params);
		$accounts  = [];
		while ($follower = DBA::fetch($followers)) {
			self::setBoundaries($follower['cid']);
			$accounts[] = DI::mstdnAccount()->createFromContactId($follower['cid'], $uid);
		}
		DBA::close($followers);

		if (!empty($request['min_id'])) {
			$accounts = array_reverse($accounts);
		}

		self::setLinkHeader();
		$this->jsonExit($accounts);
	}
}
