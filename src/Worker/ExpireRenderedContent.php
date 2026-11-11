<?php

declare(strict_types=1);

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Worker;

use Friendica\DI;
use Friendica\Util\DateTimeFormat;

class ExpireRenderedContent
{
	/**
	 * Number of days after which rendered content that hasn't been accessed is deleted
	 */
	public const EXPIRE_DAYS = 30;

	/**
	 * Delete rendered content that hasn't been accessed for EXPIRE_DAYS days
	 */
	public static function execute(): void
	{
		DI::renderedContentRepository()->deleteNotAccessedSince(DateTimeFormat::utc('now - ' . self::EXPIRE_DAYS . ' days', 'Y-m-d'));
	}
}
