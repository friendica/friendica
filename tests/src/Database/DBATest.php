<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Database;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Test\DatabaseTestCase;
use Friendica\Test\Util\Database\StaticDatabase;

class DBATest extends DatabaseTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);

		// Default config
		DI::config()->set('config', 'hostname', 'localhost');
		DI::config()->set('system', 'throttle_limit_day', 100);
		DI::config()->set('system', 'throttle_limit_week', 100);
		DI::config()->set('system', 'throttle_limit_month', 100);
		DI::config()->set('system', 'theme', 'system_theme');
	}

	public function testExists(): void
	{
		self::assertTrue(DBA::exists('user', []));
		self::assertFalse(DBA::exists('notable', []));
	}
}
