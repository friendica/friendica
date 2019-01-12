<?php

namespace Friendica\Test\src\Core\Console;

use Asika\SimpleConsole\Console;
use Friendica\Test\Util\Intercept;
use Friendica\Test\Util\Mocks\AppMockTrait;
use Friendica\Test\Util\Mocks\VFSTrait;
use PHPUnit\Framework\TestCase;

abstract class ConsoleTest extends TestCase
{
	use VFSTrait;
	use AppMockTrait;

	/**
	 * @var array The default argv for a Console Instance
	 */
	protected $consoleArgv = [ 'consoleTest.php' ];

	protected function setUp()
	{
		parent::setUp();

		if (!getenv('MYSQL_DATABASE')) {
			$this->markTestSkipped('Please set the MYSQL_* environment variables to your test database credentials.');
		}

		Intercept::setUp();

		$this->setUpVfsDir();
		$this->mockApp($this->root);
	}

	protected function tearDown()
	{
		\Mockery::close();

		parent::tearDown();
	}

	/**
	 * Dumps the execution of an console output to a string and returns it
	 *
	 * @param Console $console The current console instance
	 *
	 * @return string the output of the execution
	 */
	protected function dumpExecute($console)
	{
		Intercept::reset();
		$console->execute();
		$returnStr = Intercept::$cache;
		Intercept::reset();

		return $returnStr;
	}
}
