<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\BbcodeToHtmlStartEvent;
use PHPUnit\Framework\TestCase;

class BbcodeToHtmlStartEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new BbcodeToHtmlStartEvent('[b]Hello[/b]');

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new BbcodeToHtmlStartEvent('[b]Hello[/b]');

		$this->assertSame(BbcodeToHtmlStartEvent::NAME, $event->getName());
	}

	public function testGetBbcode2html(): void
	{
		$event = new BbcodeToHtmlStartEvent('[b]Hello[/b]');

		$this->assertSame('[b]Hello[/b]', $event->getBbcode2html());
	}

	public function testSetBbcode2html(): void
	{
		$event = new BbcodeToHtmlStartEvent('');

		$event->setBbcode2html('[i]Hi[/i]');

		$this->assertSame('[i]Hi[/i]', $event->getBbcode2html());
	}
}
