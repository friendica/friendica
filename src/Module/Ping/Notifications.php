<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Ping;

use Friendica\Module\Notifications\Stream;

/**
 * Number of notifications that are newer than the ones shown on the page
 */
class Notifications extends Stream
{
	protected function rawContent(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid || !isset($request['p']) || !isset($request['item'])) {
			$this->earlyHttpExit('');
		}

		// Only the first page is updated
		if (!empty($request['max_id']) || !empty($request['min_id'])) {
			$this->earlyHttpExit('');
		}

		$count = $this->notification->count($this->getCondition($uid, (int) ($request['first_id'] ?? 0) ?: null));

		$this->earlyHttpExit($count < 100 ? (string) $count : '99+');
	}
}
