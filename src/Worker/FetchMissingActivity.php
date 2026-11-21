<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\ActivityPub\Fetch;
use Friendica\Protocol\ActivityPub\Queue;
use Friendica\Protocol\ActivityPub\Receiver;

class FetchMissingActivity
{
	public const WORKER_DEFER_LIMIT = 5;

	public const CACHEKEY_FAILED = 'FetchMissingActivity:failed:';

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
		$cachekey = self::CACHEKEY_FAILED . hash('sha256', $url);
		if (DI::distributedCache()->get($cachekey)) {
			DI::logger()->info('Activity could not be fetched recently, skip it', ['url' => $url]);
			Queue::deleteByReplyToId($url);
			return;
		}
		$result = ActivityPub\Processor::fetchMissingActivity($url, $child, $relay_actor, $completion);
		if ($result) {
			DI::logger()->info('Successfully fetched missing activity', ['url' => $url]);
		} elseif (is_null($result)) {
			DI::logger()->info('Permament error, activity could not be fetched', ['url' => $url]);
			DI::distributedCache()->set($cachekey, true, Duration::DAY);
			Queue::deleteByReplyToId($url);
		} elseif (!Worker::defer(self::WORKER_DEFER_LIMIT)) {
			// Waiting children are kept, Queue::processAll() restarts the fetch for them
			DI::logger()->info('Defer limit reached, activity could not be fetched', ['url' => $url]);
		} else {
			DI::logger()->info('Fetching deferred', ['url' => $url]);
		}
	}

	public static function add($run_parameters, string $url, array $child = [], string $relay_actor = '', int $completion = Receiver::COMPLETION_MANUAL): int
	{
		if (Fetch::hasWorker($url)) {
			DI::logger()->notice('Fetching is already added as worker task.', ['url' => $url]);
			return 0;
		}

		DI::logger()->debug('Fetch Missing Activity', ['url' => $url]);
		$wid = Worker::add($run_parameters, 'FetchMissingActivity', $url, $child, $relay_actor, $completion);
		Fetch::setWorkerId($url, $wid);
		return $wid;
	}
}
