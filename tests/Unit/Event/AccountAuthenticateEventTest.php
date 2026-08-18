<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\AccountAuthenticateEvent;
use PHPUnit\Framework\TestCase;

class AccountAuthenticateEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new AccountAuthenticateEvent('user', 'pass');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new AccountAuthenticateEvent('user', 'pass');

		$this->assertSame(AccountAuthenticateEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new AccountAuthenticateEvent('user', 'pass');

		$this->assertSame('user', $event->getUsername());
		$this->assertSame('pass', $event->getPassword());
		$this->assertFalse($event->isAuthenticated());
		$this->assertNull($event->getUserRecordArray());

		$event->setAuthenticated(true);
		$this->assertTrue($event->isAuthenticated());

		$record = ['uid' => 42];
		$event->setUserRecordArray($record);
		$this->assertSame($record, $event->getUserRecordArray());
	}
}
