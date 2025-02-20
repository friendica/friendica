<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\Event;
use Friendica\Event\EventDispatcher;
use Friendica\Event\NamedEvent;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventDispatcherTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$eventDispatcher = new EventDispatcher();

		$this->assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);
	}

	public function testDispatchANamedEventUsesNameAsEventName(): void
	{
		$eventDispatcher = new EventDispatcher();

		$eventDispatcher->addListener('test', function (NamedEvent $event) {
			$this->assertSame('test', $event->getName());
		});

		$eventDispatcher->dispatch(new Event('test'));
	}
}
