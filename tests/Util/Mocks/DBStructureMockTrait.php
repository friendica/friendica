<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

/**
 * Trait to mock the DBStructure connection status
 */
trait DBStructureMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Database\DBStructure
	 */
	private $dbStructure;

	/**
	 * Mocking DBStructure::update()
	 *
	 * @param array $args The arguments for the update call
	 * @param bool $return True, if the connect was successful, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockUpdate($args = [], $return = true, $times = null)
	{
		if (!isset($this->dbStructure)) {
			$this->dbStructure = \Mockery::mock('alias:Friendica\Database\DBStructure');
		}

		$this->dbStructure
			->shouldReceive('update')
			->withArgs($args)
			->times($times)
			->andReturn($return);
	}

	/**
	 * Mocking DBStructure::existsTable()
	 *
	 * @param string $tableName The name of the table to check
	 * @param bool $return True, if the connect was successful, otherwise false
	 * @param null|int $times How often the method will get used
	 */
	public function mockExistsTable($tableName, $return = true, $times = null)
	{
		if (!isset($this->dbStructure)) {
			$this->dbStructure = \Mockery::mock('alias:Friendica\Database\DBStructure');
		}

		$this->dbStructure
			->shouldReceive('existsTable')
			->with($tableName)
			->times($times)
			->andReturn($return);
	}
}
