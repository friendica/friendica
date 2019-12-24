<?php

namespace Friendica\Test\src\Model\Entity;

use Friendica\Model\Entity\BaseEntity;
use Friendica\Model\Entity\Introduction;
use Friendica\Test\MockedTest;

class BaseEntityTest extends MockedTest
{
	/**
	 * Test if creating an entity without an id fails
	 *
	 * @expectedException \Friendica\Network\HTTPException\InternalServerErrorException
	 * @expectedExceptionMessage data are incomplete, id is missing.
	 */
	public function testInstanceWithoutId()
	{
		new Introduction([]);
	}

	/**
	 * Test if cloned entities behave different
	 */
	public function testClonedVersion()
	{
		$intro = new Introduction(['id' => 1]);
		$this->assertInstanceOf(BaseEntity::class, $intro);

		$count = BaseEntity::getEntityCount();
		$this->assertGreaterThan(0, $count);

		$intro2 = clone $intro;
		$count2 = BaseEntity::getEntityCount();

		$this->assertGreaterThan($count, $count2);
		$this->assertNotEquals($intro, $intro2);
		$this->assertEquals($intro->id, $intro2->id);
	}

	/**
	 * Test if entity-override during "fromEntity" call is supported
	 */
	public function testClonedWithEntityFromMethod()
	{
		$intro = new Introduction(['id' => 1]);
		$this->assertInstanceOf(BaseEntity::class, $intro);

		$count = BaseEntity::getEntityCount();
		$this->assertGreaterThan(0, $count);

		$intro2 = Introduction::fromEntity($intro, ['id' => 2, 'fid' => 'newValue']);
		$count2 = BaseEntity::getEntityCount();

		$this->assertGreaterThan($count, $count2);
		$this->assertNotEquals($intro, $intro2);
		$this->assertNotEquals($intro->id, $intro2->id);
		$this->assertEquals(1, $intro->id);
		$this->assertObjectNotHasAttribute('fid', $intro);
		$this->assertEquals(2, $intro2->id);
		$this->assertEquals('newValue', $intro2->fid);
	}

	/**
	 * Test if setting entity-attributes fails correctly
	 *
	 * @expectedException \Friendica\Network\HTTPException\InternalServerErrorException
	 * @expectedExceptionMessage Entity set is not allowed.
	 */
	public function testInvalidSet()
	{
		$intro = new Introduction(['id' => 2]);
		$intro->id = 1;
	}
}
