<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Event\HtmlFilterEvent;
use Friendica\Core\Event\NamedEvent;
use PHPUnit\Framework\TestCase;

class HtmlFilterEventTest extends TestCase
{
	public function testImplementationOfInstances(): void
	{
		$event = new HtmlFilterEvent('test', 'original');

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public static function getPublicConstants(): array
	{
		return [
			[HtmlFilterEvent::CONTACT_BLOCK_END, 'friendica.html.contact_block_end'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('getPublicConstants')]
	public function testPublicConstantsAreAvailable($value, $expected): void
	{
		$this->assertSame($expected, $value);
	}

	public function testGetNameReturnsName(): void
	{
		$event = new HtmlFilterEvent('test', '');

		$this->assertSame('test', $event->getName());
	}

	public function testGetHtmlReturnsCorrectString(): void
	{
		$data = 'original';

		$event = new HtmlFilterEvent('test', $data);

		$this->assertSame($data, $event->getHtml());
	}

	public function testSetHtmlUpdatesHtml(): void
	{
		$event = new HtmlFilterEvent('test', 'original');

		$expected = 'updated';

		$event->setHtml($expected);

		$this->assertSame($expected, $event->getHtml());
	}
}
