<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use Friendica\App;
use Friendica\BaseObject;
use Friendica\Core\Config;
use Friendica\Database\DBA;
use PHPUnit\DbUnit\DataSet\YamlDataSet;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit_Extensions_Database_DB_IDatabaseConnection;

/**
 * Abstract class used by tests that need a database.
 */
abstract class DatabaseTest extends TestCase
{
	use TestCaseTrait;

	/**
	 * @var App The Friendica App
	 */
	protected $app;

	protected function setUp()
	{
		require_once __DIR__.'/../boot.php';

		// Reusable App object
		$this->app = BaseObject::getApp();

		Config::set('system', 'url', 'http://localhost');
		Config::set('system', 'hostname', 'localhost');
		Config::set('system', 'worker_dont_fork', true);
	}

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
