<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\NetworkToNameEvent;
use PHPUnit\Framework\TestCase;

class NetworkToNameEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new NetworkToNameEvent([]);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new NetworkToNameEvent([]);

		$this->assertSame(NetworkToNameEvent::NAME, $event->getName());
	}

	public function testGetNetworks(): void
	{
		$event = new NetworkToNameEvent(['dfrn' => 'DFRN']);

		$this->assertSame(['dfrn' => 'DFRN'], $event->getNetworks());
	}

	public function testSetNetworks(): void
	{
		$event = new NetworkToNameEvent([]);

		$event->setNetworks(['dfrn' => 'DFRN']);

		$this->assertSame(['dfrn' => 'DFRN'], $event->getNetworks());
	}
}
