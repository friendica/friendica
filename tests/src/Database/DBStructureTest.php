<?php

namespace Friendica\Test\Database;

use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Mocks\AppMockTrait;
use Friendica\Test\Util\Mocks\VFSTrait;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DBStructureTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	public function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->mockApp($this->root);
		$this->app->shouldReceive('getConfigValue')
			->withArgs(['system', 'db_callstack'])
			->andReturn(false);
		$this->app->shouldReceive('getConfigValue')
			->withArgs(['system', 'db_log'])
			->andReturn(false);

		DBA::connect(getenv('MYSQL_HOST'),
			getenv('MYSQL_USERNAME'),
			getenv('MYSQL_PASSWORD'),
			getenv('MYSQL_DATABASE'));

		if (!DBA::connected()) {
			$this->markTestSkipped('Could not connect to the database.');
		}
	}

	/**
	 * @small
	 */
	public function testExists() {
		$this->assertTrue(DBStructure::existsTable('config'));

		$this->assertFalse(DBStructure::existsTable('notatable'));

		$this->assertTrue(DBStructure::existsColumn('config', ['k']));
		$this->assertFalse(DBStructure::existsColumn('config', ['nonsense']));
		$this->assertFalse(DBStructure::existsColumn('config', ['k', 'nonsense']));
	}

	/**
	 * @small
	 */
	public function testRename() {
		$fromColumn = 'k';
		$toColumn = 'key';
		$fromType = 'varbinary(255) not null';
		$toType = 'varbinary(255) not null comment \'Test To Type\'';

		$this->assertTrue(DBStructure::rename('config', [ $fromColumn => [ $toColumn, $toType ]]));
		$this->assertTrue(DBStructure::existsColumn('config', [ $toColumn ]));
		$this->assertFalse(DBStructure::existsColumn('config', [ $fromColumn ]));

		$this->assertTrue(DBStructure::rename('config', [ $toColumn => [ $fromColumn, $fromType ]]));
		$this->assertTrue(DBStructure::existsColumn('config', [ $fromColumn ]));
		$this->assertFalse(DBStructure::existsColumn('config', [ $toColumn ]));
	}

	/**
	 * @small
	 */
	public function testChangePrimaryKey() {
		$oldID = 'client_id';
		$newID = 'pw';

		$this->assertTrue(DBStructure::rename('clients', [ $newID ], DBStructure::RENAME_PRIMARY_KEY));
		$this->assertTrue(DBStructure::rename('clients', [ $oldID ], DBStructure::RENAME_PRIMARY_KEY));
	}
}
