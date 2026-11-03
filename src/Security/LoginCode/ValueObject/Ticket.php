<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\LoginCode\ValueObject;

/**
 * Identifies one code request and is kept in the session of the requesting browser.
 *
 * A code is only accepted together with the nonce of the ticket it was issued for.
 */
final readonly class Ticket
{
	/** Used for requests that don't belong to an account that may sign in with a code */
	public const UNKNOWN_ACCOUNT = -1;

	public function __construct(
		public int $uid,
		public string $nonce,
	) {}
}
