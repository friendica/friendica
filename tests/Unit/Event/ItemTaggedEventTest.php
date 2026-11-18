<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\ItemTaggedEvent;
use PHPUnit\Framework\TestCase;

class ItemTaggedEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new ItemTaggedEvent(['id' => 1], ['id' => 2]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new ItemTaggedEvent(['id' => 1], ['id' => 2]);

		$this->assertSame(ItemTaggedEvent::NAME, $event->getName());
	}

	public function testGetters(): void
	{
		$event = new ItemTaggedEvent(['id' => 1], ['id' => 2]);

		$this->assertSame(['id' => 1], $event->getItemArray());
		$this->assertSame(['id' => 2], $event->getUserArray());
	}
}
