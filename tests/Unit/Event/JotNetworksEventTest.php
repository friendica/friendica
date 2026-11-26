<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\JotNetworksEvent;
use PHPUnit\Framework\TestCase;

class JotNetworksEventTest extends TestCase
{
	public function testImplementationOfAbstractEvent(): void
	{
		$event = new JotNetworksEvent([]);

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new JotNetworksEvent([]);

		$this->assertSame(JotNetworksEvent::NAME, $event->getName());
	}

	public function testGetJotnetsFields(): void
	{
		$event = new JotNetworksEvent([['type' => 'checkbox']]);

		$this->assertSame([['type' => 'checkbox']], $event->getJotnetsFields());
	}

	public function testSetJotnetsFields(): void
	{
		$event = new JotNetworksEvent([]);

		$event->setJotnetsFields([['type' => 'checkbox']]);

		$this->assertSame([['type' => 'checkbox']], $event->getJotnetsFields());
	}
}
