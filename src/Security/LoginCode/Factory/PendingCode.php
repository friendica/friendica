<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\LoginCode\Factory;

use Friendica\BaseFactory;
use Friendica\Security\LoginCode\ValueObject\PendingCode as PendingCodeObject;

class PendingCode extends BaseFactory
{
	/**
	 * @param array{nonce: string, hash: string} $entry
	 */
	public function createFromCacheEntry(int $uid, array $entry): PendingCodeObject
	{
		return new PendingCodeObject($uid, $entry['nonce'], $entry['hash']);
	}
}
