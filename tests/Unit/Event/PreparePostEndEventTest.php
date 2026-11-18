<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\PreparePostEndEvent;
use PHPUnit\Framework\TestCase;

class PreparePostEndEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new PreparePostEndEvent(['id' => 1], '<p>test</p>');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PreparePostEndEvent(['id' => 1], '<p>test</p>');

		$this->assertSame(PreparePostEndEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new PreparePostEndEvent(['id' => 1], '<p>test</p>');

		$this->assertSame(['id' => 1], $event->getItemArray());
		$this->assertSame('<p>test</p>', $event->getHtml());

		$event->setHtml('<p>modified</p>');

		$this->assertSame('<p>modified</p>', $event->getHtml());
	}
}
