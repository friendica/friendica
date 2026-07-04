<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Worker\Repository;

use Friendica\Core\Worker\Factory;
use Friendica\Core\Worker\Repository;
use Friendica\DI;
use Friendica\Test\FixtureTestCase;
use Psr\Log\NullLogger;

class ProcessTest extends FixtureTestCase
{
	public function testStandardProcess(): void
	{
		$factory    = new Factory\Process(new NullLogger());
		$repository = new Repository\Process(DI::dba(), new NullLogger(), $factory, []);

		$entityNew = $repository->create(getmypid(), 'test');

		self::assertEquals(getmypid(), $entityNew->pid);
		self::assertEquals('test', $entityNew->command);
		self::assertLessThanOrEqual(new \DateTime('now', new \DateTimeZone('UTC')), $entityNew->created);
		self::assertEquals(php_uname('n'), $entityNew->hostname);
	}

	public function testHostnameEnv(): void
	{
		$factory    = new Factory\Process(new NullLogger());
		$repository = new Repository\Process(DI::dba(), new NullLogger(), $factory, [Repository\Process::NODE_ENV => 'test_node']);

		$entityNew = $repository->create(getmypid(), 'test');

		self::assertEquals(getmypid(), $entityNew->pid);
		self::assertEquals('test', $entityNew->command);
		self::assertLessThanOrEqual(new \DateTime('now', new \DateTimeZone('UTC')), $entityNew->created);
		self::assertEquals('test_node', $entityNew->hostname);
	}
}
