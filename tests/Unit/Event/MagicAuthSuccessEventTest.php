<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\MagicAuthSuccessEvent;
use PHPUnit\Framework\TestCase;

class MagicAuthSuccessEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new MagicAuthSuccessEvent(['id' => 1], 'test');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new MagicAuthSuccessEvent(['id' => 1], 'test');

		$this->assertSame(MagicAuthSuccessEvent::NAME, $event->getName());
	}

	public function testGetVisitorArrayReturnsVisitorArray(): void
	{
		$visitor = ['id' => 42, 'name' => 'TestVisitor'];
		$event   = new MagicAuthSuccessEvent($visitor, 'test');

		$this->assertSame($visitor, $event->getVisitorArray());
	}

	public function testSetVisitorArrayUpdatesVisitorArray(): void
	{
		$event = new MagicAuthSuccessEvent(['id' => 1], 'test');

		$newVisitor = ['id' => 99, 'name' => 'Changed'];
		$event->setVisitorArray($newVisitor);

		$this->assertSame($newVisitor, $event->getVisitorArray());
	}

	public function testGetUrlReturnsUrl(): void
	{
		$event = new MagicAuthSuccessEvent(['id' => 1], 'foo=bar&baz=qux');

		$this->assertSame('foo=bar&baz=qux', $event->getUrl());
	}
}
