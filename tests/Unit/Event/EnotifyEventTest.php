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
		$event = new EnotifyEvent($this->createData());

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new EnotifyEvent($this->createData());

		$this->assertSame(EnotifyEvent::NAME, $event->getName());
	}

	public function testGetDataArray(): void
	{
		$data = $this->createData();

		$event = new EnotifyEvent($data);

		$this->assertSame($data, $event->getDataArray());
	}

	public function testSetDataArray(): void
	{
		$event = new EnotifyEvent($this->createData());

		$data              = $this->createData();
		$data['subject']   = 'Changed subject';
		$data['preamble']  = 'Changed preamble';
		$data['epreamble'] = 'Changed epreamble';
		$data['body']      = 'Changed body';

		$event->setDataArray($data);

		$this->assertSame($data, $event->getDataArray());
	}

	/** @return array{params: array<string, mixed>, subject: string, preamble: string, epreamble: string, body: string, sitelink: string, tsitelink: string, hsitelink: string, itemlink: string} */
	private function createData(): array
	{
		return [
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
	}
}
