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
		$event = new EnotifyMailEvent($this->createData());

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new EnotifyMailEvent($this->createData());

		$this->assertSame(EnotifyMailEvent::NAME, $event->getName());
	}

	public function testGetDataArray(): void
	{
		$data = $this->createData();

		$event = new EnotifyMailEvent($data);

		$this->assertSame($data, $event->getDataArray());
	}

	public function testSetDataArray(): void
	{
		$event = new EnotifyMailEvent($this->createData());

		$data                 = $this->createData();
		$data['preamble']     = 'Changed preamble';
		$data['type']         = 2;
		$data['parent']       = 8;
		$data['source_name']  = 'Changed name';
		$data['source_link']  = 'https://example.com/changed';
		$data['source_photo'] = 'https://example.com/changed-photo';
		$data['title']        = 'Changed title';
		$data['body']         = 'Changed body';
		$data['subject']      = 'Changed subject';
		$data['headers']      = ['Message-ID' => ['<changed@example.com>']];

		$event->setDataArray($data);

		$this->assertSame($data, $event->getDataArray());
	}

	/** @return array{preamble: string, type: int, parent: int, source_name: ?string, source_link: ?string, source_photo: ?string, uid: int, hsitelink: string, tsitelink: string, itemlink: string, title: string, body: string, subject: string, headers: array<string, array<string>>} */
	private function createData(): array
	{
		return [
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
	}
}
