<?php

namespace Friendica\Test\src\Core\Cache;

use Friendica\Core\Cache\MemcachedCacheDriver;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\Mocks\AppMockTrait;
use Friendica\Test\Util\Mocks\VFSTrait;
use Friendica\Util\DateTimeFormat;

abstract class CacheTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;

	/**
	 * @var \Friendica\Core\Cache\ICacheDriver
	 */
	protected $instance;

	/**
	 * @var \Friendica\Core\Cache\IMemoryCacheDriver
	 */
	protected $cache;


	abstract protected function getInstance();

	protected function setUp()
	{
		parent::setUp();

		// Reusable App object
		$this->setUpVfsDir();
		$this->mockApp($this->root);
		$this->app->shouldReceive('getHostname')->andReturn('localhost');

		$this->instance = $this->getInstance();
		$this->instance->clear(false);
	}

	/**
	 * @small
	 */
	function testSimple() {
		$this->assertNull($this->instance->get('value1'));

		$value = 'foobar';
		$this->instance->set('value1', $value);
		$received = $this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value received from cache not equal to the original');

		$value = 'ipsum lorum';
		$this->instance->set('value1', $value);
		$received = $this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value not overwritten by second set');

		$value2 = 'foobar';
		$this->instance->set('value2', $value2);
		$received2 = $this->instance->get('value2');
		$this->assertEquals($value, $received, 'Value changed while setting other variable');
		$this->assertEquals($value2, $received2, 'Second value not equal to original');

		$this->assertNull($this->instance->get('not_set'), 'Unset value not equal to null');

		$this->assertTrue($this->instance->delete('value1'));
		$this->assertNull($this->instance->get('value1'));
	}

	/**
	 * @small
	 */
	function testClear() {
		$value = 'ipsum lorum';
		$this->instance->set('1_value1', $value . '1');
		$this->instance->set('1_value2', $value . '2');
		$this->instance->set('2_value1', $value . '3');
		$this->instance->set('3_value1', $value . '4');

		$this->assertEquals([
			'1_value1' => 'ipsum lorum1',
			'1_value2' => 'ipsum lorum2',
			'2_value1' => 'ipsum lorum3',
			'3_value1' => 'ipsum lorum4',
		], [
			'1_value1' => $this->instance->get('1_value1'),
			'1_value2' => $this->instance->get('1_value2'),
			'2_value1' => $this->instance->get('2_value1'),
			'3_value1' => $this->instance->get('3_value1'),
		]);

		$this->assertTrue($this->instance->clear());

		$this->assertEquals([
			'1_value1' => 'ipsum lorum1',
			'1_value2' => 'ipsum lorum2',
			'2_value1' => 'ipsum lorum3',
			'3_value1' => 'ipsum lorum4',
		], [
			'1_value1' => $this->instance->get('1_value1'),
			'1_value2' => $this->instance->get('1_value2'),
			'2_value1' => $this->instance->get('2_value1'),
			'3_value1' => $this->instance->get('3_value1'),
		]);

		$this->assertTrue($this->instance->clear(false));

		$this->assertEquals([
			'1_value1' => null,
			'1_value2' => null,
			'2_value3' => null,
			'3_value4' => null,
		], [
			'1_value1' => $this->instance->get('1_value1'),
			'1_value2' => $this->instance->get('1_value2'),
			'2_value3' => $this->instance->get('2_value3'),
			'3_value4' => $this->instance->get('3_value4'),
		]);
	}

	/**
	 * @medium
	 */
	function testTTL() {
		$this->markTestSkipped('taking too much time without mocking');

		$this->assertNull($this->instance->get('value1'));

		$value = 'foobar';
		$this->instance->set('value1', $value, 1);
		$received = $this->instance->get('value1');
		$this->assertEquals($value, $received, 'Value received from cache not equal to the original');

		sleep(2);

		$this->assertNull($this->instance->get('value1'));
	}

	/**
	 * @small
	 */
	function testDifferentTypesInCache() {
		// String test
		$value = "foobar";
		$this->instance->set('stringVal', $value);
		$received = $this->instance->get('stringVal');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));

		// Integer test
		$value = 1;
		$this->instance->set('intVal', $value);
		$received = $this->instance->get('intVal');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));

		// Boolean test
		$value = true;
		$this->instance->set('boolValTrue', $value);
		$received = $this->instance->get('boolValTrue');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));

		$value = false;
		$this->instance->set('boolValFalse', $value);
		$received = $this->instance->get('boolValFalse');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));

		// float
		$value = 4.6634234;
		$this->instance->set('decVal', $value);
		$received = $this->instance->get('decVal');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));

		// array
		$value = array('1', '2', '3', '4', '5');
		$this->instance->set('arrayVal', $value);
		$received = $this->instance->get('arrayVal');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));

		// object
		$value = new DateTimeFormat();
		$this->instance->set('objVal', $value);
		$received = $this->instance->get('objVal');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));

		// null
		$value = null;
		$this->instance->set('objVal', $value);
		$received = $this->instance->get('objVal');
		$this->assertEquals($value, $received, 'Value type changed from ' . gettype($value) . ' to ' . gettype($received));
	}

	/**
	 * @small
	 */
	public function testGetAllKeys() {
		if ($this->cache instanceof MemcachedCacheDriver) {
			$this->markTestSkipped('Memcached doesn\'t support getAllKeys anymore');
		}

		$this->assertTrue($this->instance->set('value1', 'test'));
		$this->assertTrue($this->instance->set('value2', 'test'));
		$this->assertTrue($this->instance->set('test_value3', 'test'));

		$list = $this->instance->getAllKeys();

		$this->assertContains('value1', $list);
		$this->assertContains('value2', $list);
		$this->assertContains('test_value3', $list);

		$list = $this->instance->getAllKeys('test');

		$this->assertContains('test_value3', $list);
	}
}
