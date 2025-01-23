<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ActivityPub\Queue;
use Friendica\Protocol\ActivityPub\Receiver;

class FetchMissingActivity
{
	const WORKER_DEFER_LIMIT = 5;

	/**
	 * Fetch missing activities
	 * @param string $url Contact URL
	 *
	 * @return void
	 */
	public static function execute(string $url, array $child = [], string $relay_actor = '', int $completion = Receiver::COMPLETION_MANUAL)
	{
		DI::logger()->info('Start fetching missing activity', ['url' => $url]);
		if (ActivityPub\Processor::alreadyKnown($url, $child['id'] ?? '')) {
			DI::logger()->info('Activity is already known.', ['url' => $url]);
			return;
		}
		$result = ActivityPub\Processor::fetchMissingActivity($url, $child, $relay_actor, $completion);
		if ($result) {
			DI::logger()->info('Successfully fetched missing activity', ['url' => $url]);
		} elseif (is_null($result)) {
			DI::logger()->info('Permament error, activity could not be fetched', ['url' => $url]);
		} elseif (!Worker::defer(self::WORKER_DEFER_LIMIT)) {
			DI::logger()->info('Defer limit reached, activity could not be fetched', ['url' => $url]);

			// recursively delete all entries that belong to this worker task
			$queue = DI::appHelper()->getQueue();
			if (!empty($queue['id'])) {
				Queue::deleteByWorkerId($queue['id']);
			}
		} else {
			DI::logger()->info('Fetching deferred', ['url' => $url]);
		}
	}
}
