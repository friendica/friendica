<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\CacheItemEvent;
use PHPUnit\Framework\TestCase;

class CacheItemEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new CacheItemEvent(['id' => 1], '<p>html</p>', 'abc123');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new CacheItemEvent(['id' => 1], '<p>html</p>', 'abc123');

		$this->assertSame(CacheItemEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new CacheItemEvent(['id' => 1], '<p>html</p>', 'abc123');

		$this->assertSame(['id' => 1], $event->getItemArray());
		$this->assertSame('<p>html</p>', $event->getRenderedHtml());
		$this->assertSame('abc123', $event->getRenderedHash());

		$event->setRenderedHtml('<p>modified</p>');
		$event->setRenderedHash('def456');

		$this->assertSame('<p>modified</p>', $event->getRenderedHtml());
		$this->assertSame('def456', $event->getRenderedHash());
	}
}
