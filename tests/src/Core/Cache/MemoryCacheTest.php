<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\IMemoryCacheDriver;

abstract class MemoryCacheTest extends CacheTest
{
	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	protected $instance;

	function setUp()
	{
		parent::setUp();
		if (!($this->instance instanceof IMemoryCacheDriver)) {
			throw new \Exception('MemoryCacheTest unsupported');
		}
	}

	function testCompareSet() {
		$this->assertNull($this->instance->get('value1'));

		$value = 'foobar';
		$this->instance->add('value1', $value);
		$received = $this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value received from cache not equal to the original');

		$newValue = 'ipsum lorum';
		$this->instance->compareSet('value1', $value, $newValue);
		$received = $this->instance->get('value1');
		$this->assertEquals($newValue, $received, 'Value not overwritten by compareSet');
	}

	function testNegativeCompareSet() {
		$this->assertNull($this->instance->get('value1'));

		$value = 'foobar';
		$this->instance->add('value1', $value);
		$received = $this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value received from cache not equal to the original');

		$newValue = 'ipsum lorum';
		$this->instance->compareSet('value1', 'wrong', $newValue);
		$received = $this->instance->get('value1');
		$this->assertNotEquals($newValue, $received, 'Value was wrongly overwritten by compareSet');
		$this->assertEquals($value, $received, 'Value was wrongly overwritten by any other value');
	}

	function testCompareDelete() {
		$this->assertNull($this->instance->get('value1'));

		$value = 'foobar';
		$this->instance->add('value1', $value);
		$received = $this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value received from cache not equal to the original');
		$this->instance->compareDelete('value1', $value);
		$this->assertNull($this->instance->get('value1'), 'Value was not deleted by compareDelete');
	}

	function testNegativeCompareDelete() {
		$this->assertNull($this->instance->get('value1'));

		$value = 'foobar';
		$this->instance->add('value1', $value);
		$received = $this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value received from cache not equal to the original');
		$this->instance->compareDelete('value1', 'wrong');
		$this->assertNotNull($this->instance->get('value1'), 'Value was wrongly compareDeleted');

		$this->instance->compareDelete('value1', $value);
		$this->assertNull($this->instance->get('value1'), 'Value was wrongly NOT deleted by compareDelete');
	}

	function testAdd() {
		$this->assertNull($this->instance->get('value1'));

		$value = 'foobar';
		$this->instance->add('value1', $value);

		$newValue = 'ipsum lorum';
		$this->instance->add('value1', $newValue);
		$received = $this->instance->get('value1');
		$this->assertNotEquals($newValue, $received, 'Value was wrongly overwritten by add');
		$this->assertEquals($value, $received, 'Value was wrongly overwritten by any other value');

		$this->instance->delete('value1');
		$this->instance->add('value1', $newValue);
		$received = $this->instance->get('value1');
		$this->assertEquals($newValue, $received, 'Value was not overwritten by add');
		$this->assertNotEquals($value, $received, 'Value was not overwritten by any other value');
	}
}