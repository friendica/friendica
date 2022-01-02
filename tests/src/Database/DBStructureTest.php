<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Database;

use Dice\Dice;
use Friendica\Database\Database;
use Friendica\Database\DBStructure;
use Friendica\DI;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;

class DBStructureTest extends DatabaseTest
{
	protected function setUp(): void
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);
	}

	/**
	 * @small
	 */
	public function testExists() {
		self::assertTrue(DBStructure::existsTable('config'));

		self::assertFalse(DBStructure::existsTable('notatable'));

		self::assertTrue(DBStructure::existsColumn('config', ['k']));
		self::assertFalse(DBStructure::existsColumn('config', ['nonsense']));
		self::assertFalse(DBStructure::existsColumn('config', ['k', 'nonsense']));
	}

	/**
	 * @small
	 */
	public function testRename() {
		$fromColumn = 'k';
		$toColumn = 'key';
		$fromType = 'varbinary(255) not null';
		$toType = 'varbinary(255) not null comment \'Test To Type\'';

		self::assertTrue(DBStructure::rename('config', [ $fromColumn => [ $toColumn, $toType ]]));
		self::assertTrue(DBStructure::existsColumn('config', [ $toColumn ]));
		self::assertFalse(DBStructure::existsColumn('config', [ $fromColumn ]));

		self::assertTrue(DBStructure::rename('config', [ $toColumn => [ $fromColumn, $fromType ]]));
		self::assertTrue(DBStructure::existsColumn('config', [ $fromColumn ]));
		self::assertFalse(DBStructure::existsColumn('config', [ $toColumn ]));
	}

	/**
	 * @small
	 */
	public function testChangePrimaryKey() {
		static::markTestSkipped('rename primary key with autoincrement and foreign key support necessary first');
		$oldID = 'client_id';
		$newID = 'pw';

		self::assertTrue(DBStructure::rename('clients', [ $newID ], DBStructure::RENAME_PRIMARY_KEY));
		self::assertTrue(DBStructure::rename('clients', [ $oldID ], DBStructure::RENAME_PRIMARY_KEY));
	}
}
