<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\InsertPostRemoteEndEvent;
use PHPUnit\Framework\TestCase;

class InsertPostRemoteEndEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new InsertPostRemoteEndEvent(['id' => 1]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new InsertPostRemoteEndEvent(['id' => 1]);

		$this->assertSame(InsertPostRemoteEndEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new InsertPostRemoteEndEvent(['id' => 1]);

		$this->assertSame(['id' => 1], $event->getItemArray());

		$event->setItemArray(['id' => 2]);
		$this->assertSame(['id' => 2], $event->getItemArray());
	}
}
