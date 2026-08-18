<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\NamedEvent;
use Friendica\Event\EnotifyStoreEvent;
use PHPUnit\Framework\TestCase;

class EnotifyStoreEventTest extends TestCase
{
	public function testImplementationOfNamedEvent(): void
	{
		$event = new EnotifyStoreEvent($this->createData());

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new EnotifyStoreEvent($this->createData());

		$this->assertSame(EnotifyStoreEvent::NAME, $event->getName());
	}

	public function testGetDataArray(): void
	{
		$data = $this->createData();

		$event = new EnotifyStoreEvent($data);

		$this->assertSame($data, $event->getDataArray());
	}

	public function testSetDataArray(): void
	{
		$event = new EnotifyStoreEvent($this->createData());

		$data              = $this->createData();
		$data['name']      = 'Changed name';
		$data['msg']       = 'Changed message';
		$data['link']      = 'https://example.com/changed';
		$data['seen']      = true;
		$data['msg_cache'] = 'Changed message cache';

		$event->setDataArray($data);

		$this->assertSame($data, $event->getDataArray());
	}

	/** @return array{type: int, name: string, url: string, photo: string, msg: ?string, uid: int, link: string, iid: ?int, parent: ?int, seen: bool, verb: string, otype: string, name_cache: ?string, msg_cache: ?string, uri-id: ?int, parent-uri-id: ?int, date: string} */
	private function createData(): array
	{
		return [
			'type'          => 1,
			'name'          => 'Name',
			'url'           => 'https://example.com',
			'photo'         => 'https://example.com/photo',
			'msg'           => 'Message',
			'uid'           => 42,
			'link'          => 'https://example.com/link',
			'iid'           => 7,
			'parent'        => 0,
			'seen'          => false,
			'verb'          => 'verb',
			'otype'         => 'item',
			'name_cache'    => 'Name cache',
			'msg_cache'     => 'Message cache',
			'uri-id'        => 99,
			'parent-uri-id' => 98,
			'date'          => '2026-08-10 12:00:00',
		];
	}
}
