<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security\LoginCode\ValueObject;

use Friendica\Security\LoginCode\Factory\PendingCode as PendingCodeFactory;
use Friendica\Security\LoginCode\ValueObject\Ticket;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class PendingCodeTest extends TestCase
{
	private const UID  = 42;
	private const CODE = '123456';

	public function testCodeIsBoundToAccountNonceAndValue(): void
	{
		$factory = new PendingCodeFactory(new NullLogger());
		$code    = $factory->createForTicket(new Ticket(self::UID, 'nonce'), self::CODE);

		self::assertTrue($code->equals($factory->createForTicket(new Ticket(self::UID, 'nonce'), self::CODE)));
		self::assertFalse($code->equals($factory->createForTicket(new Ticket(self::UID, 'other'), self::CODE)));
		self::assertFalse($code->equals($factory->createForTicket(new Ticket(self::UID + 1, 'nonce'), self::CODE)));
		self::assertFalse($code->equals($factory->createForTicket(new Ticket(self::UID, 'nonce'), '654321')));
	}

	public function testOnlyTheHashIsKept(): void
	{
		$code = (new PendingCodeFactory(new NullLogger()))->createForTicket(new Ticket(self::UID, 'nonce'), self::CODE);

		self::assertStringNotContainsString(self::CODE, serialize($code));
	}
}
