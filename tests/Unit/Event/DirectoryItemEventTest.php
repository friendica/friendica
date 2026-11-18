<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\DirectoryItemEvent;
use PHPUnit\Framework\TestCase;

class DirectoryItemEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new DirectoryItemEvent(['id' => 1], ['id' => 2, 'name' => 'test']);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new DirectoryItemEvent(['id' => 1], ['id' => 2, 'name' => 'test']);

		$this->assertSame(DirectoryItemEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new DirectoryItemEvent(['id' => 1], ['id' => 2, 'name' => 'test']);

		$this->assertSame(['id' => 1], $event->getContactArray());
		$this->assertSame(['id' => 2, 'name' => 'test'], $event->getEntryArray());

		$event->setEntryArray(['id' => 3, 'name' => 'test2']);

		$this->assertSame(['id' => 3, 'name' => 'test2'], $event->getEntryArray());
	}
}
