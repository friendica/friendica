<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\BbcodeToMarkdownEndEvent;
use PHPUnit\Framework\TestCase;

class BbcodeToMarkdownEndEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new BbcodeToMarkdownEndEvent('**Hello**');

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new BbcodeToMarkdownEndEvent('**Hello**');

		$this->assertSame(BbcodeToMarkdownEndEvent::NAME, $event->getName());
	}

	public function testGetBbcode2markdown(): void
	{
		$event = new BbcodeToMarkdownEndEvent('**Hello**');

		$this->assertSame('**Hello**', $event->getBbcode2markdown());
	}

	public function testSetBbcode2markdown(): void
	{
		$event = new BbcodeToMarkdownEndEvent('');

		$event->setBbcode2markdown('*Hi*');

		$this->assertSame('*Hi*', $event->getBbcode2markdown());
	}
}
