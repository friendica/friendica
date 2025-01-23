<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\ActivityPub;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * This class handles the fetching of posts
 */
class Fetch
{
	public static function add(string $url): int
	{
		DBA::insert('fetch-entry', ['url' => $url, 'created' => DateTimeFormat::utcNow()], Database::INSERT_IGNORE);

		$fetch = DBA::selectFirst('fetch-entry', ['id'], ['url' => $url]);
		DI::logger()->debug('Added fetch entry', ['url' => $url, 'fetch' => $fetch]);
		return $fetch['id'] ?? 0;
	}

	/**
	 * Set the worker id for the queue entry
	 *
	 * @return void
	 */
	public static function setWorkerId(string $url, int $wid)
	{
		if (empty($url) || empty($wid)) {
			return;
		}

		DBA::update('fetch-entry', ['wid' => $wid], ['url' => $url]);
		DI::logger()->debug('Worker id set', ['url' => $url, 'wid' => $wid]);
	}

	/**
	 * Check if there is an assigned worker task
	 */
	public static function hasWorker(string $url): bool
	{
		$fetch = DBA::selectFirst('fetch-entry', ['id', 'wid'], ['url' => $url]);
		if (empty($fetch['id'])) {
			DI::logger()->debug('No entry found for url', ['url' => $url]);
			return false;
		}

		// We don't have a workerqueue id yet. So most likely is isn't assigned yet.
		// To avoid the ramping up of another fetch request we simply claim that there is a waiting worker.
		if (!empty($fetch['id']) && empty($fetch['wid'])) {
			DI::logger()->debug('Entry without worker found for url', ['url' => $url]);
			return true;
		}

		return DBA::exists('workerqueue', ['id' => $fetch['wid'], 'done' => false]);
	}
}
