<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Database;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Test\DatabaseTestCase;
use Friendica\Test\Util\Database\StaticDatabase;

class DBStructureTest extends DatabaseTestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);
	}

	public function testExists(): void
	{
		self::assertTrue(DBStructure::existsTable('user'));
		self::assertFalse(DBStructure::existsTable('nonexistent'));

		self::assertTrue(DBStructure::existsColumn('user', ['uid']));
		self::assertFalse(DBStructure::existsColumn('user', ['nonsense']));
		self::assertFalse(DBStructure::existsColumn('user', ['uid', 'nonsense']));
	}

	public function testRename(): void
	{
		$fromColumn = 'email';
		$toColumn   = 'email_key';
		$fromType   = 'varchar(255) NOT NULL DEFAULT \'\' COMMENT \'the users email address\'';
		$toType     = 'varchar(255) NOT NULL DEFAULT \'\' COMMENT \'Adapted column\'';

		self::assertTrue(DBStructure::rename('user', [ $fromColumn => [ $toColumn, $toType ]]));
		self::assertTrue(DBStructure::existsColumn('user', [ $toColumn ]));
		self::assertFalse(DBStructure::existsColumn('user', [ $fromColumn ]));

		self::assertTrue(DBStructure::rename('user', [ $toColumn => [ $fromColumn, $fromType ]]));
		self::assertTrue(DBStructure::existsColumn('user', [ $fromColumn ]));
		self::assertFalse(DBStructure::existsColumn('user', [ $toColumn ]));
	}

	public function testChangePrimaryKey(): void
	{
		static::markTestSkipped('rename primary key with autoincrement and foreign key support necessary first');
		$oldID = 'client_id'; // @phpstan-ignore deadCode.unreachable (skipped test)
		$newID = 'pw';

		self::assertTrue(DBStructure::rename('clients', [ $newID ], DBStructure::RENAME_PRIMARY_KEY));
		self::assertTrue(DBStructure::rename('clients', [ $oldID ], DBStructure::RENAME_PRIMARY_KEY));
	}
}
