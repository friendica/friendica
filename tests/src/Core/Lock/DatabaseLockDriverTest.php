<?php

namespace Friendica\Test\src\Core\Lock;

use Friendica\Core\Lock\DatabaseLockDriver;
use Friendica\Database\DBA;

class DatabaseLockDriverTest extends LockTest
{
	protected function getInstance()
	{
		return new DatabaseLockDriver();
	}

	public function tearDown()
	{
		DBA::delete('locks', [ 'id > 0']);
		parent::tearDown();
	}
}
