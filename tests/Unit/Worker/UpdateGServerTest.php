<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Worker;

use Dice\Dice;
use Friendica\DI;
use Friendica\Worker\UpdateGServer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class UpdateGServerTest extends TestCase
{
	/**
	 * A stored server URL with a control character makes `new Uri()` throw a MalformedUriException.
	 * `add()` must drop the task instead of aborting the calling worker (UpdateGServers) at that row.
	 */
	public function testAddDropsServerUrlThatCannotBeParsed(): void
	{
		$dice = $this->createMock(Dice::class);
		$dice->method('create')->willReturnCallback(fn (string $name): object => match ($name) {
			LoggerInterface::class => new NullLogger(),
			default                => throw new \LogicException('Unexpected class requested: ' . $name),
		});
		DI::init($dice, true);

		$this->assertSame(0, UpdateGServer::add(0, "https://merveill\x04s.town"));
	}
}
