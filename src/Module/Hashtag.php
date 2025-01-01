<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Database\DBA;
use Friendica\Util\Strings;

/**
 * Hashtag module.
 */
class Hashtag extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$result = [];

		if (empty($request['t'])) {
			$this->jsonExit($result);
		}

		$taglist = DBA::select(
			'tag',
			['name'],
			["`name` LIKE ?", Strings::escapeHtml($request['t']) . "%"],
			['order' => ['name'], 'limit' => 100]
		);
		while ($tag = DBA::fetch($taglist)) {
			$result[] = ['text' => $tag['name']];
		}
		DBA::close($taglist);

		$this->jsonExit($result);
	}
}
