<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

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
		if (!DBA::connected()) {
			$this->markTestSkipped('Could not connect to the database.');
		}

		return $this->createDefaultDBConnection(DBA::get_db(), getenv('MYSQL_DATABASE'));
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
