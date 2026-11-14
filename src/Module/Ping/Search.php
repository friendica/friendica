<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Ping;

use Friendica\DI;
use Friendica\Module\Search\Index;

/**
 * Number of posts that are newer than the ones shown on the search page
 */
class Search extends Index
{
	protected function rawContent(array $request = [])
	{
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid || !isset($request['p'])) {
			$this->earlyHttpExit('');
		}

		$count = count($this->getNewUriIds($request, $uid, 100));

		$this->earlyHttpExit($count < 100 ? (string) $count : '99+');
	}
}
