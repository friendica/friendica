<?php
namespace Friendica\Test\Database;

use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Database\DBA;
use Friendica\Test\DatabaseTest;

class DBATest extends DatabaseTest
{
	public function setUp()
	{
		parent::setUp();

		// Reusable App object
		$this->app = BaseObject::getApp();
		$this->app->getLogger()->enableTest();

		// Default config
		Config::set('config', 'hostname', 'localhost');
		Config::set('system', 'throttle_limit_day', 100);
		Config::set('system', 'throttle_limit_week', 100);
		Config::set('system', 'throttle_limit_month', 100);
		Config::set('system', 'theme', 'system_theme');
	}

	/**
	 * @small
	 */
	public function testExists() {

		$this->assertTrue(DBA::exists('config', []));
		$this->assertFalse(DBA::exists('notable', []));

		$this->assertTrue(DBA::exists('config', null));
		$this->assertFalse(DBA::exists('notable', null));

		$this->assertTrue(DBA::exists('config', ['k' => 'hostname']));
		$this->assertFalse(DBA::exists('config', ['k' => 'nonsense']));
	}
}
