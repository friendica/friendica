<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use Friendica\App;
use Friendica\Core\Config\Cache;
use Friendica\Database\DBA;
use Friendica\Factory;
use Friendica\Util\BasePath;
use Friendica\Util\Profiler;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

require_once __DIR__ . '/../boot.php';

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTest extends MockedTest
{
	use TestCaseTrait;

	/**
	 * Get database connection.
	 *
	 * This function is executed before each test in order to get a database connection that can be used by tests.
	 * If no prior connection is available, it tries to create one using the USER, PASS and DB environment variables.
	 *
	 * If it could not connect to the database, the test is skipped.
	 *
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 * @see https://phpunit.de/manual/5.7/en/database.html
	 */
	protected function getConnection()
	{
		if (!getenv('MYSQL_DATABASE')) {
			$this->markTestSkipped('Please set the MYSQL_* environment variables to your test database credentials.');
		}

		$basepath = BasePath::create(dirname(__DIR__));
		$mode = new App\Mode($basepath);
		$configLoader = new Cache\ConfigCacheLoader($basepath, $mode);
		$config = Factory\ConfigFactory::createCache($configLoader);

		$profiler = \Mockery::mock(Profiler::class);

		DBA::connect(
			$config,
			$profiler,
			getenv('MYSQL_HOST'),
			getenv('MYSQL_USERNAME'),
			getenv('MYSQL_PASSWORD'),
			getenv('MYSQL_DATABASE'));

		if (!DBA::connected()) {
			$this->markTestSkipped('Could not connect to the database.');
		}

		return $this->createDefaultDBConnection(DBA::getConnection(), getenv('MYSQL_DATABASE'));
	}

	/**
	 * Get dataset to populate the database with.
	 * @return YamlDataSet
	 * @see https://phpunit.de/manual/5.7/en/database.html
	 */
	protected function getDataSet()
	{
		return new YamlDataSet(__DIR__ . '/datasets/api.yml');
	}
}
