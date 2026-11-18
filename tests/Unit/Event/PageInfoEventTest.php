<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\PageInfoEvent;
use PHPUnit\Framework\TestCase;

class PageInfoEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new PageInfoEvent(['url' => 'https://example.com']);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PageInfoEvent(['url' => 'https://example.com']);

		$this->assertSame(PageInfoEvent::NAME, $event->getName());
	}

	public function testGetDataArray(): void
	{
		$event = new PageInfoEvent(['url' => 'https://example.com', 'type' => 'link']);

		$this->assertSame(['url' => 'https://example.com', 'type' => 'link'], $event->getDataArray());
	}

	public function testSetDataArray(): void
	{
		$event = new PageInfoEvent(['url' => 'https://example.com']);

		$event->setDataArray(['url' => 'https://example.com', 'type' => 'photo']);

		$this->assertSame(['url' => 'https://example.com', 'type' => 'photo'], $event->getDataArray());
	}
}
