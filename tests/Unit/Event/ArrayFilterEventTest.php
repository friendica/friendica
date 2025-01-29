<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\ArrayFilterEvent;
use Friendica\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class ArrayFilterEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new ArrayFilterEvent('test', []);

		$this->assertInstanceOf(NamedEvent::class, $event);
	}

	public static function getPublicConstants(): array
	{
		return [
			[ArrayFilterEvent::APP_MENU, 'friendica.data.app_menu'],
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
		$event = new ArrayFilterEvent('test', []);

		$this->assertSame('test', $event->getName());
	}

	public function testGetArrayReturnsCorrectString(): void
	{
		$data = ['original'];

		$event = new ArrayFilterEvent('test', $data);

		$this->assertSame($data, $event->getArray());
	}

	public function testSetArrayUpdatesHtml(): void
	{
		$event = new ArrayFilterEvent('test', ['original']);

		$expected = ['updated'];

		$event->setArray($expected);

		$this->assertSame($expected, $event->getArray());
	}
}
