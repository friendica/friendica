<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ContactPhotoMenuEvent;
use PHPUnit\Framework\TestCase;

class ContactPhotoMenuEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new ContactPhotoMenuEvent(['id' => 1], []);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ContactPhotoMenuEvent(['id' => 1], []);

		$this->assertSame(ContactPhotoMenuEvent::NAME, $event->getName());
	}

	public function testGetContact(): void
	{
		$event = new ContactPhotoMenuEvent(['id' => 1], []);

		$this->assertSame(['id' => 1], $event->getContact());
	}

	public function testGetMenu(): void
	{
		$event = new ContactPhotoMenuEvent([], ['profile' => ['View Profile', 'https://example.com', true]]);

		$this->assertSame(['profile' => ['View Profile', 'https://example.com', true]], $event->getMenu());
	}

	public function testSetMenu(): void
	{
		$event = new ContactPhotoMenuEvent([], []);

		$event->setMenu(['profile' => ['View Profile', 'https://example.com', true]]);

		$this->assertSame(['profile' => ['View Profile', 'https://example.com', true]], $event->getMenu());
	}
}
