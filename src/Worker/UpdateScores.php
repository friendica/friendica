<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact\Relation;
use Friendica\Model\Post;

/**
 * Update the interaction scores
 */
class UpdateScores
{
	public static function execute($param = '', $hook_function = '')
	{
		DI::logger()->notice('Start score update');

		$users = DBA::select('user', ['uid'], ["`verified` AND NOT `blocked` AND NOT `account_removed` AND NOT `account_expired` AND `uid` > ?", 0]);
		while ($user = DBA::fetch($users)) {
			Relation::calculateInteractionScore($user['uid']);
		}
		DBA::close($users);

		DI::logger()->notice('Score update done');

		Post\Engagement::expire();

		return;
	}
}
