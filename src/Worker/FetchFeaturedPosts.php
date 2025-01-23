<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Protocol\ActivityPub;

class FetchFeaturedPosts
{
	/**
	 * Fetch featured posts from a contact with the given URL
	 * @param string $url Contact URL
	 */
	public static function execute(string $url)
	{
		DI::logger()->info('Start fetching featured posts', ['url' => $url]);
		ActivityPub\Processor::fetchFeaturedPosts($url);
		DI::logger()->info('Finished fetching featured posts', ['url' => $url]);
	}
}
