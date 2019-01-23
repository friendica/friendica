<?php

namespace Friendica\Test;

use Friendica\Core\Logger;
use Friendica\Test\Util\Mocks\AppMockTrait;
use Friendica\Test\Util\Mocks\VFSTrait;
use Friendica\Util\LoggerFactory;
use PHPUnit\Framework\TestCase;

/**
 * This class verifies each mock after each call
 */
abstract class MockedTest extends TestCase
{
	use VFSTrait;
	use AppMockTrait;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->mockApp($this->root);

		$this->mockConfigGet('system', 'url', 'http://localhost');
		$this->mockConfigGet('system', 'hostname', 'localhost');
		$this->mockConfigGet('system', 'worker_dont_fork', true);

		// Default config
		$this->mockConfigGet('config', 'hostname', 'localhost');
		$this->mockConfigGet('system', 'throttle_limit_day', 100);
		$this->mockConfigGet('system', 'throttle_limit_week', 100);
		$this->mockConfigGet('system', 'throttle_limit_month', 100);
		$this->mockConfigGet('system', 'theme', 'system_theme');

		/// @todo not needed anymore with new Logging 2019.03
		$this->mockConfigGet('system', 'debugging', false);
		$this->mockConfigGet('system', 'logfile', 'friendica.log');
		$this->mockConfigGet('system', 'loglevel', '0');
		$this->mockConfigGet('system', 'dlogfile', null);

		$logger = LoggerFactory::create('test');
		LoggerFactory::enableTest($logger);
		Logger::setLogger($logger);
	}

	protected function tearDown()
	{
		\Mockery::close();

		parent::tearDown();
	}
}
