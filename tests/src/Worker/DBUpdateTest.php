<?php

namespace Friendica\Test\Worker;

use Friendica\App;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\UpdateMockTrait;
use Friendica\Worker\DBUpdate;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class DBUpdateTest extends MockedTest
{
	use UpdateMockTrait;

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
		$this->app->shouldReceive('getBasePath')->andReturn('/temp');

		$this->logger = \Mockery::mock('Psr\Log\LoggerInterface');
		$this->logger->shouldReceive('info')->with(\Mockery::any(), \Mockery::any());
		$this->logger->shouldReceive('info')->with(\Mockery::any());
		$this->logger->shouldReceive('debug')->with(\Mockery::any(), \Mockery::any());
		$this->logger->shouldReceive('debug')->with(\Mockery::any());
	}

	/**
	 * Test the Worker DbUpdate
	 */
	public function testNormal()
	{
		$this->mockUpdateRun($this->app->getBasePath(), $this->logger);

		$worker = new DBUpdate($this->app, $this->logger);
		$worker->execute();
	}
}
