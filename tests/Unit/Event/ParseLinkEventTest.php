<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\ParseLinkEvent;
use PHPUnit\Framework\TestCase;

class ParseLinkEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new ParseLinkEvent('url', 'json');

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ParseLinkEvent('url', 'json');

		$this->assertSame(ParseLinkEvent::NAME, $event->getName());
	}

	public function testGetUrl(): void
	{
		$event = new ParseLinkEvent('https://friendica.example', 'json');

		$this->assertSame('https://friendica.example', $event->getUrl());
	}

	public function testGetFormat(): void
	{
		$event = new ParseLinkEvent('https://friendica.example', 'json');

		$this->assertSame('json', $event->getFormat());
	}

	public function testGetText(): void
	{
		$event = new ParseLinkEvent('https://friendica.example', 'json');

		$this->assertNull($event->getText());
	}

	public function testSetText(): void
	{
		$event = new ParseLinkEvent('https://friendica.example', 'json');

		$event->setText('Some text');

		$this->assertSame('Some text', $event->getText());
	}
}
