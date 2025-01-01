<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Protocol;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Feed as ProtocolFeed;

/**
 * Provides public Atom feeds
 *
 * Currently supported:
 * - /feed/[nickname]/ => posts
 * - /feed/[nickname]/posts => posts
 * - /feed/[nickname]/comments => comments
 * - /feed/[nickname]/replies => comments
 * - /feed/[nickname]/activity => activity
 *
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class Feed extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$nick = $this->parameters['nickname'] ?? '';
		$type = $this->parameters['type']     ?? null;
		switch ($type) {
			case 'posts':
			case 'comments':
			case 'activity':
				// Correct type names, no change needed
				break;
			case 'replies':
				$type = 'comments';
				break;
			default:
				$type = 'posts';
		}

		$last_update = $this->getRequestValue($request, 'last_update', '');

		$owner = User::getOwnerDataByNick($nick);
		if (!$owner || $owner['account_expired'] || $owner['account_removed']) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if ($owner['blocked']) {
			throw new HTTPException\UnauthorizedException($this->t('Access to this profile has been restricted.'));
		}

		Item::incrementOutbound(Protocol::FEED);

		$feed = ProtocolFeed::atom($owner, $last_update, 10, $type);

		$this->httpExit($feed, Response::TYPE_ATOM);
	}
}
