<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Update;

use Friendica\Core\System;
use Friendica\Module\Notifications\Stream;

/**
 * The notifications that are newer than the ones shown on the page, used by the automatic update
 */
class Notifications extends Stream
{
	protected function rawContent(array $request = [])
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid || !isset($request['p']) || !isset($request['item'])) {
			System::exit();
		}

		// Only the first page is updated
		if (!empty($request['max_id']) || !empty($request['min_id'])) {
			System::htmlUpdateExit('');
		}

		$list = $this->renderList($uid, (int) ($request['first_id'] ?? 0) ?: null, null);

		System::htmlUpdateExit($list['html']);
	}
}
