<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Core\Event;

use Friendica\Core\Event\EventDispatcher;
use Friendica\Core\Event\NamedEvent;
use Friendica\Event\InitEvent;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventDispatcherTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$eventDispatcher = new EventDispatcher();

		$this->assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testDispatchANamedEventUsesNameAsEventName(): void
	{
		$eventDispatcher = new EventDispatcher();

		$eventDispatcher->addListener('friendica.init', function (NamedEvent $event): void {
			$this->assertSame(InitEvent::NAME, $event->getName());
		});

		$eventDispatcher->dispatch(new InitEvent());
	}
}
