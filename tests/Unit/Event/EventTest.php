<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\Event;
use Friendica\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new Event('test');

		$this->assertInstanceOf(NamedEvent::class, $event);
	}

	public static function getPublicConstants(): array
	{
		return [
			[Event::INIT, 'friendica.init'],
		];
	}

	/**
	 * @dataProvider getPublicConstants
	 */
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new Event('test');

		$this->assertSame('test', $event->getName());
	}
}
