<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\AccountRemoveEvent;
use PHPUnit\Framework\TestCase;

class AccountRemoveEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new AccountRemoveEvent(['uid' => 1]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new AccountRemoveEvent(['uid' => 1]);

		$this->assertSame(AccountRemoveEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new AccountRemoveEvent(['uid' => 1]);

		$this->assertSame(['uid' => 1], $event->getUserArray());

		$event->setUserArray(['uid' => 2]);
		$this->assertSame(['uid' => 2], $event->getUserArray());
	}
}
