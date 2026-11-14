<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\DateTimeFormat;

class DiscoverGroup
{
	// Number of posts that are fetched from the outbox
	private const POSTS = 5;

	/**
	 * Adds a group and fetches its latest posts
	 *
	 * @param string $url Profile URL of the group
	 */
	public static function execute(string $url): void
	{
		$cid = Contact::getIdForURL($url);
		if (!$cid) {
			DI::logger()->info('Group could not be added', ['url' => $url]);
			return;
		}

		$account = Contact::getAccountById($cid, ['contact-type', 'ap-outbox', 'platform', 'last-item']);
		if (empty($account['ap-outbox']) || ((int) $account['contact-type'] !== Contact::TYPE_COMMUNITY)) {
			DI::logger()->info('No group with an outbox', ['url' => $url, 'cid' => $cid, 'contact-type' => $account['contact-type'] ?? null]);
			return;
		}

		// Posts of followed or recently polled groups are already there
		if ($account['last-item'] > DateTimeFormat::utc('now - 1 day')) {
			DI::logger()->debug('Group is up to date', ['url' => $url, 'cid' => $cid, 'last-item' => $account['last-item']]);
			return;
		}

		// NodeBB sorts its outbox ascending, so the latest activities are on the last page
		ActivityPub::fetchOutbox($account['ap-outbox'], 0, self::POSTS, $account['platform'] === 'nodebb');

		DI::logger()->info('Fetched group posts', ['url' => $url, 'cid' => $cid]);
	}
}
