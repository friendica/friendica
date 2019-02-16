<?php

namespace Friendica\Test\Util;

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

	private function checkMock()
	{
		if (!isset($this->dbaMock)) {
			$this->dbaMock = \Mockery::namedMock('Friendica\Database\DBA', 'Friendica\Test\Util\DBAStub');
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
	 * Mocking each DBA::fetch() call of an statement
	 *
	 * @param array $stmt The result statement (array)
	 * @param null|int $times How often the method will get used
	 */
	public function mockFetchLoop($stmt = [], $times = null)
	{
		$this->checkMock();

		foreach ($stmt as $item) {
			$this->dbaMock
				->shouldReceive('fetch')
				->times($times)
				->andReturn($item);
		}

		// The last mock call of a fetch (=> breaking the loop)
		$this->dbaMock
			->shouldReceive('fetch')
			->times($times)
			->andReturn(false);
	}

	/**
	 * Mocking DBA::close()
	 *
	 * @param array $return The return per fetch
	 * @param null|int $times How often the method will get used
	 */
	public function mockDbaClose($return = [], $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('close')
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
	 * Mocking DBA::delete()
	 *
	 * @param string $tableName The name of the table
	 * @param array $where The Where Array (Default is [])
	 * @param bool $return The array to return (Default is true)
	 * @param null|int $times How often the method will get used
	 */
	public function mockDBADelete($tableName, $where = [], $return = true, $times = null)
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
	public function mockDBAUpdate($expTableName, $expFields, $expCondition, $expOld_fields = [], $return = true, $times = null)
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
	 * Mocking DBA::insert()
	 *
	 * @param string $expTableName    The name of the table
	 * @param array  $expParam        The Parameters Array
	 * @param bool   $expOnDuplUpdate Update on a duplicated entry
	 * @param bool   $return          True if the insert was successful
	 * @param null|int $times How often the method will get used
	 */
	public function mockDBAInsert($expTableName, $expParam, $expOnDuplUpdate = false, $return = true, $times = null)
	{
		$this->checkMock();

		$closure = function ($tableName, $param, $on_duplicate_update = false) use ($expTableName, $expParam, $expOnDuplUpdate) {
			return $tableName            == $expTableName
				&& $param                == $expParam
				&& $on_duplicate_update  == $expOnDuplUpdate;

		};

		$this->dbaMock
			->shouldReceive('insert')
			->withArgs($closure)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::selectFirst()
	 *
	 * @param string $expTableName The name of the table
	 * @param array $expSelect The Select Array (Default is [])
	 * @param array $expWhere The Where Array (Default is [])
	 * @param array $return The array to return (Default is [])
	 * @param null|int $times How often the method will get used
	 */
	public function mockSelectFirst($expTableName, $expSelect = [], $expWhere = [], $return = [], $times = null)
	{
		$this->checkMock();

		$closure = function ($tableName, $select = [], $where = []) use ($expTableName, $expSelect, $expWhere) {
			return $tableName === $expTableName
				&& $select === $expSelect
				&& $where === $expWhere;
		};

		$this->dbaMock
			->shouldReceive('selectFirst')
			->withArgs($closure)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::isResult()
	 *
	 * @param object $record The record to test
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

	/**
	 * Mocking DBA::lock()
	 *
	 * @param string $table The table to lock
	 * @param bool $return True, if the lock is set successful
	 * @param null|int $times How often the method will get used
	 */
	public function mockDbaLock($table, $return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('lock')
			->with($table)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBA::unlock()
	 *
	 * @param bool $return True, if the lock is set successful
	 * @param null|int $times How often the method will get used
	 */
	public function mockDbaUnlock($return = true, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('unlock')
			->times($times)
			->andReturn($return);
	}

	public function mockDbaEscape($input, $output = null, $times = null)
	{
		$this->checkMock();

		$this->dbaMock
			->shouldReceive('escape')
			->with($input)
			->andReturn((isset($output) ? $output : $input))
			->times($times);
	}
}
