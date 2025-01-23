<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Protocol;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Protocol\Feed;
use Friendica\Util\DateTimeFormat;

/**
 * Poll contacts for unreceived messages
 */
class PollContacts
{
	public static function execute()
	{
		$abandon_days = intval(DI::config()->get('system', 'account_abandon_days'));
		if ($abandon_days < 1) {
			$abandon_days = 0;
		}

		$condition = ['network' => [Protocol::FEED, Protocol::MAIL], 'self' => false, 'blocked' => false, 'archive' => false];

		if (!empty($abandon_days)) {
			$condition = DBA::mergeConditions(
				$condition,
				["`uid` != ? AND `uid` IN (SELECT `uid` FROM `user` WHERE `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `last-activity` > ?)", 0, DateTimeFormat::utc('now - ' . $abandon_days . ' days')]
			);
		} else {
			$condition = DBA::mergeConditions(
				$condition,
				["`uid` != ? AND `uid` IN (SELECT `uid` FROM `user` WHERE `verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired`)", 0]
			);
		}

		$contacts = DBA::select('contact', ['id', 'nick', 'name', 'network', 'archive', 'last-update', 'priority', 'rating'], $condition);
		if (!DBA::isResult($contacts)) {
			return;
		}

		while ($contact = DBA::fetch($contacts)) {
			$interval = Feed::getPollInterval($contact);
			if ($interval == 0) {
				continue;
			}

			$now         = DateTimeFormat::utcNow();
			$next_update = DateTimeFormat::utc($contact['last-update'] . ' + ' . $interval . ' minute');

			if ($now < $next_update) {
				DI::logger()->debug('No update', ['cid' => $contact['id'], 'interval' => $interval, 'next' => $next_update, 'now' => $now]);
				continue;
			}

			if ((($contact['network'] == Protocol::FEED) && ($contact['priority'] <= 3)) || ($contact['network'] == Protocol::MAIL)) {
				$priority = Worker::PRIORITY_MEDIUM;
			} elseif ($contact['archive']) {
				$priority = Worker::PRIORITY_NEGLIGIBLE;
			} else {
				$priority = Worker::PRIORITY_LOW;
			}

			DI::logger()->notice("Polling " . $contact["network"] . " " . $contact["id"] . " " . $contact['priority'] . " " . $contact["nick"] . " " . $contact["name"]);

			Worker::add(['priority' => $priority, 'dont_fork' => true, 'force_priority' => true], 'OnePoll', (int)$contact['id']);
			Worker::coolDown();
		}
		DBA::close($contacts);
	}
}
