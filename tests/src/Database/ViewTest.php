<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Database;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\Database\View;
use Friendica\DI;
use Friendica\Test\DatabaseTestCase;
use Friendica\Test\Util\Database\StaticDatabaseRecordingStatements;

class ViewTest extends DatabaseTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabaseRecordingStatements::class, 'shared' => true]);
		DI::init($dice);

		StaticDatabaseRecordingStatements::$statements = [];
	}

	public function testCreateReplacesExistingViewsWithoutDroppingThem(): void
	{
		View::create(false, true);

		$definition = DI::viewDefinition()->load(true)->getAll();
		$statements = StaticDatabaseRecordingStatements::$statements;

		foreach ($statements as $statement) {
			self::assertStringStartsNotWith('DROP VIEW', $statement);
		}

		foreach (array_keys($definition) as $name) {
			$creates = array_filter($statements, fn (string $statement): bool => str_starts_with($statement, sprintf('CREATE OR REPLACE VIEW `%s` ', $name)));
			self::assertCount(1, $creates, $name);
		}
	}

	public function testCreateWithoutActionExecutesNothing(): void
	{
		$this->expectOutputRegex('/CREATE OR REPLACE VIEW `post-view` /');

		View::create(true, false);

		self::assertEmpty(StaticDatabaseRecordingStatements::$statements);
	}
}
