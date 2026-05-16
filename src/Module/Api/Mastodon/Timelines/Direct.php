<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class Direct extends BaseApi
{
	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'max_id'   => 0,  // Return results older than id
			'since_id' => 0,  // Return results newer than id
			'min_id'   => 0,  // Return results immediately newer than id
			'limit'    => 20, // Maximum number of results to return. Defaults to 20.
		], $request);

		$params = ['order' => ['uri-id' => true], 'limit' => $request['limit']];

		$condition = ['uid' => $uid];

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

		if (!empty($uid)) {
			$condition = DBA::mergeConditions(
				$condition,
				["NOT `parent-author-id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored` OR `is-blocked`) AND `cid` = `parent-author-id`)", $uid],
			);
		}

		$mails = DBA::select('mail', ['id', 'uri-id'], $condition, $params);

		$statuses = [];

		try {
			while ($mail = DBA::fetch($mails)) {
				self::setBoundaries($mail['uri-id']);
				$statuses[] = DI::mstdnStatus()->createFromMailId($mail['id']);
			}
		} catch (NotFoundException) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		self::setLinkHeader();
		$this->jsonExit($statuses);
	}
}
