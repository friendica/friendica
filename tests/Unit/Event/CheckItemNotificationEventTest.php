<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\CheckItemNotificationEvent;
use PHPUnit\Framework\TestCase;

class CheckItemNotificationEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new CheckItemNotificationEvent(1, ['https://example.com']);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new CheckItemNotificationEvent(1, ['https://example.com']);

		$this->assertSame(CheckItemNotificationEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new CheckItemNotificationEvent(1, ['https://example.com']);

		$this->assertSame(1, $event->getUserId());
		$this->assertSame(['https://example.com'], $event->getProfilesArray());

		$event->setProfilesArray(['https://example.com', 'https://example.org']);

		$this->assertSame(['https://example.com', 'https://example.org'], $event->getProfilesArray());
	}
}
