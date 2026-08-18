<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Event;

use Friendica\Core\Event\AbstractEvent;
use Friendica\Event\GetSiteInfoEvent;
use PHPUnit\Framework\TestCase;

class GetSiteInfoEventTest extends TestCase
{
	private function createEvent(): GetSiteInfoEvent
	{
		return new GetSiteInfoEvent([
			'url'  => 'https://example.org',
			'type' => 'link',
		]);
	}

	public function testImplementationOfAbstractEvent(): void
	{
		$event = $this->createEvent();

		$this->assertInstanceOf(AbstractEvent::class, $event); // @phpstan-ignore method.alreadyNarrowedType
	}

	public function testGetNameReturnsName(): void
	{
		$event = $this->createEvent();

		$this->assertSame(GetSiteInfoEvent::NAME, $event->getName());
	}

	public function testGetSiteInfoArrayReturnsValue(): void
	{
		$event = $this->createEvent();

		$this->assertSame([
			'url'  => 'https://example.org',
			'type' => 'link',
		], $event->getSiteInfoArray());
	}

	public function testSetSiteInfoArray(): void
	{
		$event = $this->createEvent();

		$event->setSiteInfoArray([
			'url'   => 'https://example.org',
			'type'  => 'photo',
			'title' => 'Example',
		]);

		$this->assertSame([
			'url'   => 'https://example.org',
			'type'  => 'photo',
			'title' => 'Example',
		], $event->getSiteInfoArray());
	}
}
