<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\HtmlToBbcodeEndEvent;
use PHPUnit\Framework\TestCase;

class HtmlToBbcodeEndEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new HtmlToBbcodeEndEvent('<b>Hello</b>');

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new HtmlToBbcodeEndEvent('<b>Hello</b>');

		$this->assertSame(HtmlToBbcodeEndEvent::NAME, $event->getName());
	}

	public function testGetHtml2bbcode(): void
	{
		$event = new HtmlToBbcodeEndEvent('<b>Hello</b>');

		$this->assertSame('<b>Hello</b>', $event->getHtml2bbcode());
	}

	public function testSetHtml2bbcode(): void
	{
		$event = new HtmlToBbcodeEndEvent('');

		$event->setHtml2bbcode('[i]Hi[/i]');

		$this->assertSame('[i]Hi[/i]', $event->getHtml2bbcode());
	}
}
