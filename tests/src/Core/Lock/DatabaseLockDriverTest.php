<?php

namespace Friendica\Test\src\Core\Lock;

use dba;
use Friendica\Core\Lock\DatabaseLockDriver;
use Friendica\Database\DBStructure;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

class DatabaseLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		return new DatabaseLockDriver();
	}

	public function tearDown()
	{
		dba::delete('locks', [ 'id > 0']);
		parent::tearDown();
	}
}
