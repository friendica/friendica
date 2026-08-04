<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\AccountRegisterEvent;
use PHPUnit\Framework\TestCase;

class AccountRegisterEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new AccountRegisterEvent(123);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new AccountRegisterEvent(123);

		$this->assertSame(AccountRegisterEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new AccountRegisterEvent(123);

		$this->assertSame(123, $event->getUserId());

		$event->setUserId(456);
		$this->assertSame(456, $event->getUserId());
	}
}
