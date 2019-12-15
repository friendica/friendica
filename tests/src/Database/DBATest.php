<?php
namespace Friendica\Test\src\Database;

use Dice\Dice;
use Friendica\Core\Config;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Test\DatabaseTest;
use Friendica\Test\Util\Database\StaticDatabase;

class DBATest extends DatabaseTest
{
	public function setUp()
	{
		parent::setUp();

		$dice = (new Dice())
			->addRules(include __DIR__ . '/../../../static/dependencies.config.php')
			->addRule(Database::class, ['instanceOf' => StaticDatabase::class, 'shared' => true]);
		DI::init($dice);

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
