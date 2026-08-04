<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\InitEvent;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
	public function testAbstractEventIsAbstract(): void
	{
		$reflection = new \ReflectionClass(AbstractEvent::class);

		$this->assertTrue($reflection->isAbstract());
	}

	public static function getPublicConstants(): array
	{
		return [
			[InitEvent::NAME, 'friendica.init'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getPublicConstants')]
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}
}
