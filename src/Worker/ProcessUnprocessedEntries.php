<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Protocol\ActivityPub\Queue;

class ProcessUnprocessedEntries
{
	/**
	 * Process all unprocessed entries
	 *
	 * @return void
	 */
	public static function execute()
	{
		DI::logger()->info('Start processing unprocessed entries');
		Queue::processAll();
		DI::logger()->info('Successfully processed unprocessed entries');
	}
}
