<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

class DBAStub
{
	public static $connected = true;
}

/**
 * Trait to mock the DBA connection status
 */
trait DBAMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Database\DBA
	 */
	private $dbaMock;

	/**
	 * Check if the mock interface
	 */
	private function checkMock()
	{
		if (!isset($this->dbaMock)) {
			$this->dbaMock = \Mockery::namedMock('Friendica\Database\DBA', 'Friendica\Test\Util\Mocks\DBAStub');
		}
	}

	/**
	 * Mocking DBA::connect()
	 *
	 * @param bool $return True, if the connect was successful, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockConnect($return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('connect')
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::connected()
	 *
	 * @param bool $return True, if the DB is connected, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockConnected($return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('connected')
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::fetchFirst()
	 *
	 * @param string $arg The argument of fetchFirst
	 * @param bool $return True, if the DB is connected, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockFetchFirst($arg, $return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('fetchFirst')
			->with($arg)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::select()
	 *
	 * @param string $tableName The name of the table
	 * @param array $select The Select Array (Default is [])
	 * @param array $where The Where Array (Default is [])
	 * @param object $return The array to return (Default is [])
	 * @param null|int $times How often the method will get used
	 */
	public function mockSelect($tableName, $select = [], $where = [], $return = null, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('select')
			->with($tableName, $select, $where)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::selectFirst()
	 *
	 * @param string $tableName The name of the table
	 * @param array $select The Select Array (Default is [])
	 * @param array $where The Where Array (Default is [])
	 * @param array $return The array to return (Default is [])
	 * @param null|int $times How often the method will get used
	 */
	public function mockSelectFirst($tableName, $select = [], $where = [], $return = [], $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('selectFirst')
			->with($tableName, $select, $where)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::isResult()
	 *
	 * @param mixed $record The record to test
	 * @param bool $return True, if the DB is connected, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockIsResult($record, $return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('isResult')
			->with($record)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::delete()
	 *
	 * @param string $tableName The name of the table
	 * @param array $where The Where Array (Default is [])
	 * @param boolean $return The return value (default is true)
	 * @param null|int $times How often the method will get used
	 */
	public function mockDelete($tableName, $where = [], $return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('delete')
			->with($tableName, $where)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::update()
	 *
	 * @param string $expTableName The name of the table
	 * @param array $expFields The Fields Array
	 * @param array $expCondition The Condition Array
	 * @param array $expOld_fields The Old Fieldnames (Default is [])
	 * @param bool $return true if the update was successful
	 * @param null|int $times How often the method will get used
	 */
	public function mockUpdate($expTableName, $expFields, $expCondition, $expOld_fields = [], $return = true, $times = null)
	{
		$this->checkMock();

		$closure = function ($tableName, $fields, $condition, $old_fields = []) use ($expTableName, $expFields, $expCondition, $expOld_fields) {
			return
				$tableName == $expTableName &&
				$fields == $expFields &&
				$condition == $expCondition &&
				$old_fields == $expOld_fields;
		};

		$this->dbaMock
			->shouldReceive('update')
			->withArgs($closure)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::exists()
	 *
	 * @param string $expTableName The name of the table
	 * @param array $expCondition The Condition Array
	 * @param bool $return true if the update was successful
	 * @param null|int $times How often the method will get used
	 */
	public function mockExists($expTableName, $expCondition, $return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('exists')
			->with($expTableName, $expCondition)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::isResult()
	 *
	 * @param object $record The record to test
	 * @param array $return The array to return
	 * @param null|int $times How often the method will get used
	 */
	public function mockToArray($record = null, $return = [], $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('toArray')
			->with($record)
			->times($times)
			->andReturn($return);
	}


	/**
	 * Mocking DBA::p()
	 *
	 * @param string $sql The SQL statement
	 * @param object $return The object to return
	 * @param null|int $times How often the method will get used
	 */
	public function mockP($sql = null, $return = null, $times = null)
	{
		$this->checkMock();

		if (!isset($sql)) {
			$this->dbaMock
				->shouldReceive('p')
				->times($times)
				->andReturn($return);
		} else {
			$this->dbaMock
				->shouldReceive('p')
				->with($sql)
				->times($times)
				->andReturn($return);
		}
	}

	public function mockEscape($stmt, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('escape')
			->times($times)
			->with($stmt)
			->andReturn($stmt);
	}

	public function mockCleanQuery($stmt, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('cleanQuery')
			->times($times)
			->with($stmt)
			->andReturn($stmt);
	}

	public function mockAnyValueFallback($stmt, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('anyValueFallback')
			->times($times)
			->with($stmt)
			->andReturn($stmt);
	}

	public function mockColumnCount($stmt, $columns = 0, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('columnCount')
			->times($times)
			->with($stmt)
			->andReturn($columns);
	}

	public function mockCount($table, $condition = [], $return = 0, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('count')
			->with($table, $condition)
			->times($times)
			->andReturn($return);
	}
}
