<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\NotFoundException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/conversations/
 */
class Conversations extends BaseApi
{
	protected function delete(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (!empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		DBA::delete('conv', ['id' => $this->parameters['id'], 'uid' => $uid]);
		DBA::delete('mail', ['convid' => $this->parameters['id'], 'uid' => $uid]);

		$this->jsonExit([]);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'limit'    => 20, // Maximum number of results. Defaults to 20. Max 40.
			'max_id'   => 0,  // Return results older than this ID. Use HTTP Link header to paginate.
			'since_id' => 0,  // Return results newer than this ID. Use HTTP Link header to paginate.
			'min_id'   => 0,  // Return results immediately newer than this ID. Use HTTP Link header to paginate.
		], $request);

		$params = ['order' => ['id' => true], 'limit' => $request['limit']];

		$condition = ['uid' => $uid];

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

		$convs = DBA::select('conv', ['id'], $condition, $params);

		$conversations = [];

		try {
			while ($conv = DBA::fetch($convs)) {
				self::setBoundaries($conv['id']);
				$conversations[] = DI::mstdnConversation()->createFromConvId($conv['id']);
			}
		} catch (NotFoundException) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		DBA::close($convs);

		if (!empty($request['min_id'])) {
			$conversations = array_reverse($conversations);
		}

		self::setLinkHeader();
		$this->jsonExit($conversations);
	}
}
