<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\PreparePostEvent;
use PHPUnit\Framework\TestCase;

class PreparePostEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new PreparePostEvent(['id' => 1], '<p>test</p>', false, []);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PreparePostEvent(['id' => 1], '<p>test</p>', false, []);

		$this->assertSame(PreparePostEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new PreparePostEvent(['id' => 1], '<p>test</p>', true, ['reason1']);

		$this->assertSame(['id' => 1], $event->getItemArray());
		$this->assertSame('<p>test</p>', $event->getHtml());
		$this->assertTrue($event->isPreview());
		$this->assertSame(['reason1'], $event->getFilterReasons());

		$event->setHtml('<p>modified</p>');

		$this->assertSame('<p>modified</p>', $event->getHtml());
	}
}
