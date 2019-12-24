<?php

namespace Friendica\Test\src\Model;

use Friendica\BaseModel;
use Friendica\Database\Database;
use Friendica\Model\Entity\BaseEntity;
use Friendica\Model\Introduction;
use Friendica\Test\MockedTest;
use Mockery\MockInterface;
use Psr\Log\NullLogger;

class IntroductionTest extends MockedTest
{
	/** @var Database|MockInterface */
	private $dbaMock;

	protected function setUp()
	{
		parent::setUp();

		$this->dbaMock = \Mockery::mock(Database::class);
	}

	public function testInstance()
	{
		$intro = new Introduction($this->dbaMock, new NullLogger());
		$this->assertInstanceOf(Introduction::class, $intro);
		$this->assertInstanceOf(BaseModel::class, $intro);
	}

	public function testDelete()
	{
		$this->dbaMock->shouldReceive('delete')
		              ->with('intro', ['id' => 1])
		              ->andReturn(true)
		              ->once();

		$intro = new Introduction($this->dbaMock, new NullLogger());
		$this->assertInstanceOf(Introduction::class, $intro);
		$this->assertInstanceOf(BaseModel::class, $intro);

		$entity = new \Friendica\Model\Entity\Introduction(['id' => 1]);

		$intro->delete($entity);

		$this->assertNull($entity);
	}

	/**
	 * Test an invalid fetch and the resulting exception
	 *
	 * @expectedException \Friendica\Network\HTTPException\NotFoundException
	 * @expectedExceptionMessageRegExp /\w+ record not found./
	 */
	public function testFetchInvalid()
	{
		$this->dbaMock->shouldReceive('selectFirst')
		              ->with('intro', [], ['id' => 1])
		              ->andReturn(false)
		              ->once();

		$intro = new Introduction($this->dbaMock, new NullLogger());
		$this->assertInstanceOf(Introduction::class, $intro);
		$this->assertInstanceOf(BaseModel::class, $intro);

		$intro->fetch(['id' => 1]);
	}

	/**
	 * Test a fetch with all fields
	 */
	public function testFetchFull()
	{
		$this->dbaMock->shouldReceive('selectFirst')
		              ->with('intro', [], ['id' => 1])
		              ->andReturn(['id' => 1, 'fid' => 2])
		              ->once();

		$intro = new Introduction($this->dbaMock, new NullLogger());
		$this->assertInstanceOf(Introduction::class, $intro);
		$this->assertInstanceOf(BaseModel::class, $intro);

		$introEntity = $intro->fetch(['id' => 1]);

		$this->assertInstanceOf(\Friendica\Model\Entity\Introduction::class, $introEntity);
		$this->assertInstanceOf(BaseEntity::class, $introEntity);

		$this->assertEquals(1, $introEntity->id);
		$this->assertEquals(2, $introEntity->fid);
	}

	/**
	 * Test a fetch with custom fields
	 */
	public function testFetchCustom()
	{
		$this->dbaMock->shouldReceive('selectFirst')
		              ->with('intro', [0 => 'id', 1 => 'fid'], ['id' => 1])
		              ->andReturn(['id' => 1, 'fid' => 2])
		              ->once();

		$intro = new Introduction($this->dbaMock, new NullLogger());
		$this->assertInstanceOf(Introduction::class, $intro);
		$this->assertInstanceOf(BaseModel::class, $intro);

		$introEntity = $intro->fetch(['id' => 1], ['id', 'fid']);

		$this->assertInstanceOf(\Friendica\Model\Entity\Introduction::class, $introEntity);
		$this->assertInstanceOf(BaseEntity::class, $introEntity);

		$this->assertEquals(1, $introEntity->id);
		$this->assertEquals(2, $introEntity->fid);
	}

	/**
	 * Test if the ignore function works (even for the current entity)
	 */
	public function testIgnore()
	{
		$this->dbaMock->shouldReceive('update')
		              ->with('intro', ['ignored' => true], ['id' => 1])
		              ->andReturn(true)
		              ->once();

		$intro = new Introduction($this->dbaMock, new NullLogger());
		$this->assertInstanceOf(Introduction::class, $intro);
		$this->assertInstanceOf(BaseModel::class, $intro);

		$introEntity = new \Friendica\Model\Entity\Introduction(['id' => 1, 'ignored' => false]);
		$this->assertFalse($introEntity->ignored);

		$intro->ignore($introEntity);

		$this->assertTrue($introEntity->ignored);
	}
}
