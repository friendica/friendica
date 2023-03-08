<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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
		self::assertTrue(DBStructure::existsTable('user'));
		self::assertFalse(DBStructure::existsTable('notatable'));

		self::assertTrue(DBStructure::existsColumn('user', ['uid']));
		self::assertFalse(DBStructure::existsColumn('user', ['nonsense']));
		self::assertFalse(DBStructure::existsColumn('user', ['uid', 'nonsense']));
	}

	/**
	 * @small
	 */
	public function testRename() {
		$fromColumn = 'email';
		$toColumn = 'email_key';
		$fromType = 'varchar(255) NOT NULL DEFAULT \'\' COMMENT \'the users email address\'';
		$toType = 'varchar(255) NOT NULL DEFAULT \'\' COMMENT \'Adapted column\'';

		self::assertTrue(DBStructure::rename('user', [ $fromColumn => [ $toColumn, $toType ]]));
		self::assertTrue(DBStructure::existsColumn('user', [ $toColumn ]));
		self::assertFalse(DBStructure::existsColumn('user', [ $fromColumn ]));

		self::assertTrue(DBStructure::rename('user', [ $toColumn => [ $fromColumn, $fromType ]]));
		self::assertTrue(DBStructure::existsColumn('user', [ $fromColumn ]));
		self::assertFalse(DBStructure::existsColumn('user', [ $toColumn ]));
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
