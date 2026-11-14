<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$mail = DBA::selectFirst('mail', ['parent-uri', 'convid'], ['id' => $this->parameters['id'], 'uid' => $uid]);
		if (!$mail) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		DBA::delete('mail', ['parent-uri' => $mail['parent-uri'], 'uid' => $uid]);

		if (!empty($mail['convid']) && !DBA::exists('mail', ['convid' => $mail['convid']])) {
			DBA::delete('conv', ['id' => $mail['convid'], 'uid' => $uid]);
		}

		$this->earlyJsonExit([]);
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

		// Same grouping as in the message module: one conversation per parent URI.
		// The conversation id is the id of its newest message.
		$having = [];
		$args   = [$uid];

		if (!empty($request['max_id'])) {
			$having[] = '`last_id` < ?';
			$args[]   = (int) $request['max_id'];
		}

		if (!empty($request['since_id'])) {
			$having[] = '`last_id` > ?';
			$args[]   = (int) $request['since_id'];
		}

		if (!empty($request['min_id'])) {
			$having[] = '`last_id` > ?';
			$args[]   = (int) $request['min_id'];
		}

		$args[] = (int) $request['limit'];

		$convs = DBA::p('SELECT MAX(`id`) AS `last_id` FROM `mail` WHERE `uid` = ? GROUP BY `parent-uri`'
			. ($having ? ' HAVING ' . implode(' AND ', $having) : '')
			. (empty($request['min_id']) ? ' ORDER BY `last_id` DESC' : ' ORDER BY `last_id`')
			. ' LIMIT ?', ...$args);

		$conversations = [];

		try {
			while ($conv = DBA::fetch($convs)) {
				self::setBoundaries($conv['last_id']);
				$conversations[] = DI::mstdnConversation()->createFromMailId($uid, $conv['last_id']);
			}
		} catch (NotFoundException) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		DBA::close($convs);

		if (!empty($request['min_id'])) {
			$conversations = array_reverse($conversations);
		}

		$this->setPaginationLinkHeader();
		$this->earlyJsonExit($conversations);
	}
}
