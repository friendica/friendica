<?php

declare(strict_types=1);

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

class ExpireRenderedContent
{
	/**
	 * Delete rendered content that hasn't been accessed for 30 days
	 */
	public static function execute(): void
	{
		DBA::delete('rendered-content', ["`last-access` < ?", DateTimeFormat::utc('now - 30 days', 'Y-m-d')]);
	}
}
