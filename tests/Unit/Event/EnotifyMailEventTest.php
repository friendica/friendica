<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\EnotifyMailEvent;
use PHPUnit\Framework\TestCase;

class EnotifyMailEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new EnotifyMailEvent(['uid' => 42]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new EnotifyMailEvent(['uid' => 42]);

		$this->assertSame(EnotifyMailEvent::NAME, $event->getName());
	}

	public function testGetDataArray(): void
	{
		$data = [
			'preamble'     => 'Preamble',
			'type'         => 1,
			'parent'       => 7,
			'source_name'  => 'Name',
			'source_link'  => 'https://example.com',
			'source_photo' => 'https://example.com/photo',
			'uid'          => 42,
			'hsitelink'    => 'https://example.com/home',
			'tsitelink'    => 'https://example.com/site',
			'itemlink'     => 'https://example.com/item',
			'title'        => 'Title',
			'body'         => 'Body',
			'subject'      => 'Subject',
			'headers'      => ['Message-ID' => ['<abc@example.com>']],
		];

		$event = new EnotifyMailEvent($data);

		$this->assertSame($data, $event->getDataArray());
	}

	public function testSetDataArray(): void
	{
		$event = new EnotifyMailEvent(['uid' => 42]);

		$data = [
			'uid'     => 42,
			'subject' => 'Changed subject',
		];

		$event->setDataArray($data);

		$this->assertSame($data, $event->getDataArray());
	}
}
