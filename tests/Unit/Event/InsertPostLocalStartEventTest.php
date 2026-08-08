<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\InsertPostLocalStartEvent;
use PHPUnit\Framework\TestCase;

class InsertPostLocalStartEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new InsertPostLocalStartEvent(['uid' => 1]);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new InsertPostLocalStartEvent(['uid' => 1]);

		$this->assertSame(InsertPostLocalStartEvent::NAME, $event->getName());
	}

	public function testGetSetRequestArray(): void
	{
		$event = new InsertPostLocalStartEvent(['uid' => 1]);

		$this->assertSame(['uid' => 1], $event->getRequestArray());

		$event->setRequestArray(['uid' => 2]);

		$this->assertSame(['uid' => 2], $event->getRequestArray());
	}
}
