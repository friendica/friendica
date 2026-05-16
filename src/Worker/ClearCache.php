<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\DateTimeFormat;

/**
 * Clear cache entries
 */
class ClearCache
{
	public static function execute()
	{
		// clear old cache
		DI::cache()->clear();

		// Delete the cached "parsed_url" entries that are expired
		DBA::delete('parsed_url', ["`expires` < ?", DateTimeFormat::utcNow()]);
	}
}
