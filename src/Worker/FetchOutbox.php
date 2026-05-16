<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Protocol\ActivityPub;

class FetchOutbox
{
	/**
	 * Fetch posts for a given contact..
	 * @param int $cid Contact ID
	 * @param int $uid User ID
	 *
	 * @return void
	 */
	public static function execute(int $cid, int $uid)
	{
		DI::logger()->info('Start fetching posts for a given contact', ['cid' => $cid, 'uid' => $uid]);
		$account = Contact::getAccountById($cid, ['ap-outbox']);
		if (!isset($account['ap-outbox'])) {
			DI::logger()->info('No outbox found for the given contact', ['cid' => $cid, 'uid' => $uid]);
			return;
		}
		ActivityPub::fetchOutbox($account['ap-outbox'], $uid, DI::config()->get('system', 'max_feed_items'));
		DI::logger()->info('Fetched posts for a given contact', ['cid' => $cid, 'uid' => $uid]);
	}
}
