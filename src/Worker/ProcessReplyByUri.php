<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Protocol\ActivityPub\Queue;

class ProcessReplyByUri
{
	/**
	 * Process queued replies
	 *
	 * @param string $uri post url
	 *
	 * @return void
	 */
	public static function execute(string $uri)
	{
		DI::logger()->info('Start processing queued replies', ['url' => $uri]);
		$count = Queue::processReplyByUri($uri);
		DI::logger()->info('Successfully processed queued replies', ['count' => $count, 'url' => $uri]);
	}
}
