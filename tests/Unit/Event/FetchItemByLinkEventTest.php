<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\FetchItemByLinkEvent;
use PHPUnit\Framework\TestCase;

class FetchItemByLinkEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new FetchItemByLinkEvent('https://example.com/post', 1, null);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new FetchItemByLinkEvent('https://example.com/post', 1, null);

		$this->assertSame(FetchItemByLinkEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new FetchItemByLinkEvent('https://example.com/post', 1, null);

		$this->assertSame('https://example.com/post', $event->getUri());
		$this->assertSame(1, $event->getUserId());
		$this->assertNull($event->getItemId());

		$event->setItemId(42);

		$this->assertSame(42, $event->getItemId());
	}
}
