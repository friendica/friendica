<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Test\Util\Intercept;
use PHPUnit\Framework\TestCase;

abstract class ConsoleTest extends TestCase
{
	/**
	 * @var MultiUseConsole Extension of the basic Friendica Console for testing purpose
	 */
	private $console;
	/**
	 * @var App The Friendica App
	 */
	protected $app;

	protected $stdout;

	protected function setUp()
	{
		parent::setUp();

		Intercept::setUp();

		if (!getenv('MYSQL_DATABASE')) {
			$this->markTestSkipped('Please set the MYSQL_* environment variables to your test database credentials.');
		}

		// Reusable App object
		$this->app = BaseObject::getApp();
		$this->console = new MultiUseConsole();
	}

	public function execute($args) {
		Intercept::reset();
		$this->console->reset();
		$this->console->parseTestArgv($args);
		$this->console->execute();

		$returnStr = Intercept::$cache;
		Intercept::reset();
		return $returnStr;
	}
}
