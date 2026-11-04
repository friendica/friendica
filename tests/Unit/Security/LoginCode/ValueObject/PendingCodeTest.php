<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security\LoginCode\ValueObject;

use Friendica\Security\LoginCode\ValueObject\Ticket;
use PHPUnit\Framework\TestCase;

final class PendingCodeTest extends TestCase
{
	private const UID  = 42;
	private const CODE = '123456';

	public function testCodeIsBoundToAccountNonceAndValue(): void
	{
		$code = (new Ticket(self::UID, 'nonce'))->pendingCodeFor(self::CODE);

		self::assertTrue($code->equals((new Ticket(self::UID, 'nonce'))->pendingCodeFor(self::CODE)));
		self::assertFalse($code->equals((new Ticket(self::UID, 'other'))->pendingCodeFor(self::CODE)));
		self::assertFalse($code->equals((new Ticket(self::UID + 1, 'nonce'))->pendingCodeFor(self::CODE)));
		self::assertFalse($code->equals((new Ticket(self::UID, 'nonce'))->pendingCodeFor('654321')));
	}

	public function testOnlyTheHashIsKept(): void
	{
		$code = (new Ticket(self::UID, 'nonce'))->pendingCodeFor(self::CODE);

		self::assertStringNotContainsString(self::CODE, serialize($code));
	}
}
