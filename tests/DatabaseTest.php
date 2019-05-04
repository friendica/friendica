<?php
/**
 * DatabaseTest class.
 */

namespace Friendica\Test;

use Dice\Dice;
use Friendica\App;
use Friendica\Database\DBA;
use Friendica\Factory;
use Friendica\Util\BasePath;
use Friendica\Util\Config\ConfigFileLoader;
use Friendica\Util\Logger\VoidLogger;
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
	 * @var Dice
	 */
	protected $diLibrary;

	/**
	 * @var App
	 */
	protected $app;

	protected function setUp()
	{
		parent::setUp();

		$this->diLibrary = new Dice();
		$this->diLibrary  = $this->diLibrary->addRules(include __DIR__ . '/../static/dependencies.conf.php');

		$this->app = new App($this->diLibrary , 'index', false);
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
