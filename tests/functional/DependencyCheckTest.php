<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\functional;

use Dice\Dice;
use Friendica\App;
use Friendica\Core\Cache\ICache;
use Friendica\Core\Cache\IMemoryCache;
use Friendica\Core\Config\Cache\Cache;
use Friendica\Core\Config\IConfig;
use Friendica\Core\Lock\ILock;
use Friendica\Database\Database;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\BasePath;
use Friendica\Core\Config\Cache\ConfigFileLoader;
use Friendica\Util\Profiler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DependencyCheckTest extends TestCase
{
	use VFSTrait;

	/**
	 * @var Dice
	 */
	private $dice;

	protected function setUp() : void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->dice = (new Dice())
			->addRules(include __DIR__ . '/../../static/dependencies.config.php');
	}

	/**
	 * Test the creation of the BasePath
	 */
	public function testBasePath()
	{
		/** @var BasePath $basePath */
		$basePath = $this->dice->create(BasePath::class, [$this->root->url()]);

		self::assertInstanceOf(BasePath::class, $basePath);
		self::assertEquals($this->root->url(), $basePath->getPath());
	}

	/**
	 * Test the initial config cache
	 * Should not need any other files
	 */
	public function testConfigFileLoader()
	{
		/** @var ConfigFileLoader $configFileLoader */
		$configFileLoader = $this->dice->create(ConfigFileLoader::class);

		self::assertInstanceOf(ConfigFileLoader::class, $configFileLoader);

		$configCache = new Cache();
		$configFileLoader->setupCache($configCache);

		self::assertNotEmpty($configCache->getAll());
		self::assertArrayHasKey('database', $configCache->getAll());
		self::assertArrayHasKey('system', $configCache->getAll());
	}

	/**
	 * Test the construction of a profiler class with DI
	 */
	public function testProfiler()
	{
		/** @var Profiler $profiler */
		$profiler = $this->dice->create(Profiler::class);

		self::assertInstanceOf(Profiler::class, $profiler);

		$configCache = new Cache([
			'system' => [
				'profiler' => true,
			],
			'rendertime' => [
				'callstack' => true,
			]
		]);

		// create new DI-library because of shared instance rule (so the Profiler wouldn't get created twice)
		$this->dice = new Dice();
		$profiler = $this->dice->create(Profiler::class, [$configCache]);

		self::assertInstanceOf(Profiler::class, $profiler);
		self::assertTrue($profiler->isRendertime());
	}

	public function testDatabase()
	{
		// PDO needs to be disabled for PHP 7.2, see https://jira.mariadb.org/browse/MDEV-24121
		if (version_compare(PHP_VERSION, '7.3') < 0) {
			$configCache = $this->dice->create(Cache::class);
			$configCache->set('database', 'disable_pdo', true);
		}

		/** @var Database $database */
		$database = $this->dice->create(Database::class);

		self::assertInstanceOf(Database::class, $database);
		self::assertContains($database->getDriver(), [Database::PDO, Database::MYSQLI], 'The driver returns an unexpected value');
		self::assertNotNull($database->getConnection(), 'There is no database connection');

		$result = $database->p("SELECT 1");
		self::assertEquals('', $database->errorMessage(), 'There had been a database error message');
		self::assertEquals(0, $database->errorNo(), 'There had been a database error number');

		self::assertTrue($database->connected(), 'The database is not connected');
	}

	public function testAppMode()
	{
		// PDO needs to be disabled for PHP 7.2, see https://jira.mariadb.org/browse/MDEV-24121
		if (version_compare(PHP_VERSION, '7.3') < 0) {
			$configCache = $this->dice->create(Cache::class);
			$configCache->set('database', 'disable_pdo', true);
		}

		/** @var App\Mode $mode */
		$mode = $this->dice->create(App\Mode::class);

		self::assertInstanceOf(App\Mode::class, $mode);

		self::assertTrue($mode->has(App\Mode::LOCALCONFIGPRESENT), 'No local config present');
		self::assertTrue($mode->has(App\Mode::DBAVAILABLE), 'Database is not available');
		self::assertTrue($mode->has(App\Mode::DBCONFIGAVAILABLE), 'Database config is not available');
		self::assertTrue($mode->has(App\Mode::MAINTENANCEDISABLED), 'In maintenance mode');

		self::assertTrue($mode->isNormal(), 'Not in normal mode');
	}

	public function testConfiguration()
	{
		/** @var IConfig $config */
		$config = $this->dice->create(IConfig::class);

		self::assertInstanceOf(IConfig::class, $config);

		self::assertNotEmpty($config->get('database', 'username'));
	}

	public function testLogger()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->dice->create(LoggerInterface::class, ['test']);

		self::assertInstanceOf(LoggerInterface::class, $logger);
	}

	public function testDevLogger()
	{
		/** @var IConfig $config */
		$config = $this->dice->create(IConfig::class);
		$config->set('system', 'dlogfile', $this->root->url() . '/friendica.log');

		/** @var LoggerInterface $logger */
		$logger = $this->dice->create('$devLogger', ['dev']);

		self::assertInstanceOf(LoggerInterface::class, $logger);
	}

	public function testCache()
	{
		/** @var ICache $cache */
		$cache = $this->dice->create(ICache::class);

		self::assertInstanceOf(ICache::class, $cache);
	}

	public function testMemoryCache()
	{
		/** @var IMemoryCache $cache */
		$cache = $this->dice->create(IMemoryCache::class);

		// We need to check "just" ICache, because the default Cache is DB-Cache, which isn't a memorycache
		self::assertInstanceOf(ICache::class, $cache);
	}

	public function testLock()
	{
		/** @var ILock $cache */
		$lock = $this->dice->create(ILock::class);

		self::assertInstanceOf(ILock::class, $lock);
	}
}
