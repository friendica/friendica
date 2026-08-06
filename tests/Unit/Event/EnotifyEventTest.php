<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\EnotifyEvent;
use PHPUnit\Framework\TestCase;

class EnotifyEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new EnotifyEvent(['params' => []]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new EnotifyEvent(['params' => []]);

		$this->assertSame(EnotifyEvent::NAME, $event->getName());
	}

	public function testGetDataArray(): void
	{
		$data = [
			'params'    => ['uid' => 42],
			'subject'   => 'Subject',
			'preamble'  => 'Preamble',
			'epreamble' => 'Epreamble',
			'body'      => 'Body',
			'sitelink'  => 'https://example.com',
			'tsitelink' => 'https://example.com/site',
			'hsitelink' => 'https://example.com/home',
			'itemlink'  => 'https://example.com/item',
		];

		$event = new EnotifyEvent($data);

		$this->assertSame($data, $event->getDataArray());
	}

	public function testSetDataArray(): void
	{
		$event = new EnotifyEvent(['params' => ['uid' => 42]]);

		$data = [
			'params'    => ['uid' => 42],
			'subject'   => 'Changed subject',
			'preamble'  => 'Changed preamble',
			'epreamble' => 'Changed epreamble',
			'body'      => 'Changed body',
			'sitelink'  => 'https://example.com',
			'tsitelink' => 'https://example.com/site',
			'hsitelink' => 'https://example.com/home',
			'itemlink'  => 'https://example.com/item',
		];

		$event->setDataArray($data);

		$this->assertSame($data, $event->getDataArray());
	}
}
