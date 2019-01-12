<?php
namespace Friendica\Test\Database;

use Friendica\Database\DBA;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Mocks\AppMockTrait;
use Friendica\Test\Util\Mocks\VFSTrait;
use Mockery\MockInterface;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DBATest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	/**
	 * @var \PDO|MockInterface The mocked PDO connection
	 */
	protected $mockConn;

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
	public function testExists()
	{
		$this->markTestSkipped('Currently no db connection is possible');

		$this->assertTrue(DBA::exists('config', []));
		$this->assertFalse(DBA::exists('notable', []));

		$this->assertTrue(DBA::exists('config', null));
		$this->assertFalse(DBA::exists('notable', null));

		$this->assertTrue(DBA::exists('config', ['k' => 'hostname']));
		$this->assertFalse(DBA::exists('config', ['k' => 'nonsense']));
	}
}
