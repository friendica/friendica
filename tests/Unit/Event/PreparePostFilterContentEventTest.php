<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\PreparePostFilterContentEvent;
use PHPUnit\Framework\TestCase;

class PreparePostFilterContentEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new PreparePostFilterContentEvent(['id' => 1], 0, []);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new PreparePostFilterContentEvent(['id' => 1], 0, []);

		$this->assertSame(PreparePostFilterContentEvent::NAME, $event->getName());
	}

	public function testGettersAndSetters(): void
	{
		$event = new PreparePostFilterContentEvent(['id' => 1], 42, ['reason1']);

		$this->assertSame(['id' => 1], $event->getItemArray());
		$this->assertSame(42, $event->getUserId());
		$this->assertSame(['reason1'], $event->getFilterReasons());

		$event->setFilterReasons(['reason2']);

		$this->assertSame(['reason2'], $event->getFilterReasons());
	}
}
