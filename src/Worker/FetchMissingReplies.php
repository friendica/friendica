<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Protocol\ActivityPub\Processor;

class FetchMissingReplies
{
	/**
	 * Fetch missing replies for a given post.
	 * @param int   $uriId Uri-Id of a post
	 * @param array $child Child activity that initiated the thread completion
	 *
	 * @return void
	 */
	public static function execute(int $uriId, array $child = [])
	{
		DI::logger()->info('Start fetching missing replies', ['url' => $uriId]);
		Processor::fetchRepliesByUriId($uriId, $child);
		DI::logger()->info('Fetched missing replies', ['url' => $uriId]);
	}
}
