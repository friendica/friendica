<?php

namespace Friendica\Test\Database;

use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Database\DBStructure;
use Friendica\Test\DatabaseTest;

class DBStructureTest extends DatabaseTest
{
	public function setUp()
	{
		parent::setUp();

		// Reusable App object
		$this->app = BaseObject::getApp();

		// Default config
		Config::set('config', 'hostname', 'localhost');
		Config::set('system', 'throttle_limit_day', 100);
		Config::set('system', 'throttle_limit_week', 100);
		Config::set('system', 'throttle_limit_month', 100);
		Config::set('system', 'theme', 'system_theme');
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
