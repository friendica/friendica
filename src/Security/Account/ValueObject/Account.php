<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\Account\ValueObject;

/**
 * A local account that may sign in.
 */
final readonly class Account
{
	public function __construct(
		public int $uid,
		public string $username,
		public string $nickname,
		public string $email,
		public string $language,
	) {}
}
