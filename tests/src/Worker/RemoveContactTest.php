<?php

namespace Friendica\Test\Worker;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\DBAMockTrait;
use Friendica\Worker\RemoveContact;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

/**
 * @todo Remove annotation when 'DBA' isn't static
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RemoveContactTest extends MockedTest
{
	use DBAMockTrait;

	/**
	 * @var Config\ConfigCache|MockInterface
	 */
	private $configMock;

	/**
	 * @var LoggerInterface|MockInterface
	 */
	private $logger;

	/**
	 * @var App|MockInterface
	 */
	private $app;

	protected function setUp()
	{
		parent::setUp();

		$this->app = \Mockery::mock('Friendica\App');

		$this->configMock = \Mockery::mock('Friendica\Core\Config\ConfigCache');
		Config::init($this->configMock);

		$this->logger = \Mockery::mock('Psr\Log\LoggerInterface');
		$this->logger->shouldReceive('info')->with(\Mockery::any(), \Mockery::any());
		$this->logger->shouldReceive('info')->with(\Mockery::any());
		$this->logger->shouldReceive('debug')->with(\Mockery::any(), \Mockery::any());
		$this->logger->shouldReceive('debug')->with(\Mockery::any());
	}

	/**
	 * Test the Worker checkVersion with wrong arguments
	 */
	public function testWrongArguments()
	{
		$this->logger->shouldReceive('alert')
			->with('Invoked with wrong parameters', ['count' => 0, 'parameter' => []])
			->once();

		$worker = new RemoveContact($this->app, $this->logger);
		$worker->execute([]);
	}
}
