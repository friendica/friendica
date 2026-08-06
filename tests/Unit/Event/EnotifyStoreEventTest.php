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
		$event = new EnotifyStoreEvent(['uid' => 42]);

		$this->assertInstanceOf(NamedEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = new EnotifyStoreEvent(['uid' => 42]);

		$this->assertSame(EnotifyStoreEvent::NAME, $event->getName());
	}

	public function testGetDataArray(): void
	{
		$data = [
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
		];

		$event = new EnotifyStoreEvent($data);

		$this->assertSame($data, $event->getDataArray());
	}

	public function testSetDataArray(): void
	{
		$event = new EnotifyStoreEvent(['uid' => 42]);

		$data = [
			'uid'  => 42,
			'link' => 'https://example.com/changed',
		];

		$event->setDataArray($data);

		$this->assertSame($data, $event->getDataArray());
	}
}
