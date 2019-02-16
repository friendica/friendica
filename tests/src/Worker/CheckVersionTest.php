<?php

namespace Friendica\Test\Worker;

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\DBAMockTrait;
use Friendica\Test\Util\NetworkMockTrait;
use Friendica\Worker\CheckVersion;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class CheckVersionTest extends MockedTest
{
	use NetworkMockTrait;
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
	 * Test the Worker checkVersion with master branch
	 */
	public function testCheckVersionMaster()
	{
		$url = 'https://raw.githubusercontent.com/friendica/friendica/master/VERSION';

		$this->configMock
			->shouldReceive('get')
			->with('system', 'check_new_version_url', 'none')
			->andReturn('master')
			->once();
		$this->configMock
			->shouldReceive('set')
			->with('system', 'git_friendica_version', $url)
			->once();
		$this->mockNetworkFetchUrl($url, 1);
		$this->mockDbaEscape($url, $url, 1);

		$worker = new CheckVersion($this->app, $this->logger);
		$worker->execute();
	}

	/**
	 * Test the Worker checkVersion with develop branch
	 */
	public function testCheckVersionDevelop()
	{
		$url = 'https://raw.githubusercontent.com/friendica/friendica/develop/VERSION';

		$this->configMock
			->shouldReceive('get')
			->with('system', 'check_new_version_url', 'none')
			->andReturn('develop')
			->once();
		$this->configMock
			->shouldReceive('set')
			->with('system', 'git_friendica_version', $url)
			->once();
		$this->mockNetworkFetchUrl($url, 1);
		$this->mockDbaEscape($url, $url, 1);

		$worker = new CheckVersion($this->app, $this->logger);
		$worker->execute();
	}

	/**
	 * Test the Worker checkVersion with unknown branch
	 */
	public function testCheckVersionUnknown()
	{
		$url = 'https://raw.githubusercontent.com/friendica/friendica/develop/VERSION';

		$this->configMock
			->shouldReceive('get')
			->with('system', 'check_new_version_url', 'none')
			->andReturn('unknown')
			->once();

		$worker = new CheckVersion($this->app, $this->logger);
		$worker->execute();
	}
}
