<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\LoginCode\ValueObject;

/**
 * A code that was sent to an account and wasn't used yet.
 *
 * Only the keyed hash of the code is kept, never the code itself.
 */
final readonly class PendingCode
{
	public function __construct(
		public int $uid,
		public string $nonce,
		public string $hash,
	) {}

	/**
	 * Compares in constant time, so the hash doesn't leak through the response time.
	 */
	public function equals(self $other): bool
	{
		return $this->uid === $other->uid
			&& hash_equals($this->nonce, $other->nonce)
			&& hash_equals($this->hash, $other->hash);
	}
}
