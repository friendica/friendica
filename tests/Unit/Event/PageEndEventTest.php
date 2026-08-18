<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\PageEndEvent;
use PHPUnit\Framework\TestCase;

class PageEndEventTest extends TestCase
{
	private function createEvent(): PageEndEvent
	{
		return new PageEndEvent('<html>');
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(PageEndEvent::NAME, $event->getName());
	}

	public function testGetHtmlReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame('<html>', $event->getHtml());
	}

	public function testSetHtml(): void
	{
		$event = $this->createEvent();

		$event->setHtml('<changed>');

		$this->assertSame('<changed>', $event->getHtml());
	}
}
