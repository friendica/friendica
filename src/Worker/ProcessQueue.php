<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Protocol\ActivityPub\Queue;

class ProcessQueue
{
	/**
	 * Process queue entry
	 *
	 * @param int $id queue id
	 *
	 * @return void
	 */
	public static function execute(int $id)
	{
		DI::logger()->info('Start processing queue entry', ['id' => $id]);
		$result = Queue::process($id);
		DI::logger()->info('Successfully processed queue entry', ['result' => $result, 'id' => $id]);
	}
}
