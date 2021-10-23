<?php

namespace Friendica\Test\src\Model;

use Friendica\Core\Config\Factory\ConfigFactory;
use Friendica\Model\Process;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Profiler;
use Psr\Log\NullLogger;

class ProcessTest extends DatabaseTest
{
	use VFSTrait;

	/** @var StaticDatabase */
	private $dba;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$logger = new NullLogger();

		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('startRecording');
		$profiler->shouldReceive('stopRecording');
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		// load real config to avoid mocking every config-entry which is related to the Database class
		$configFactory = new ConfigFactory();
		$loader        = (new ConfigFactory())->createConfigFileLoader($this->root->url(), []);
		$configCache   = $configFactory->createCache($loader);

		$this->dba = new StaticDatabase($configCache, $profiler, $logger);
	}

	public function testInsertDelete()
	{
		$process = new Process($this->dba);

		self::assertEquals(0, $this->dba->count('process'));
		$process->insert('test', 1);
		$process->insert('test2', 2);
		$process->insert('test3', 3);

		self::assertEquals(3, $this->dba->count('process'));

		self::assertEquals([
			['command' => 'test']
		], $this->dba->selectToArray('process', ['command'], ['pid' => 1]));

		$process->deleteByPid(1);

		self::assertEmpty($this->dba->selectToArray('process', ['command'], ['pid' => 1]));

		self::assertEquals(2, $this->dba->count('process'));
	}

	public function testDoubleInsert()
	{
		$process = new Process($this->dba);

		$process->insert('test', 1);

		// double insert doesn't work
		$process->insert('test23', 1);

		self::assertEquals([['command' => 'test']], $this->dba->selectToArray('process', ['command'], ['pid' => 1]));
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testWrongDelete()
	{
		$process = new Process($this->dba);

		// Just ignore wrong deletes, no execution is thrown
		$process->deleteByPid(-1);
	}
}
