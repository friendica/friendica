<?php

namespace Friendica\Test\src\Core\Config\Adapter;

use Friendica\Core\Config\Adapter\IConfigAdapter;
use Friendica\Core\Config\Adapter\JITConfigAdapter;

class JITConfigAdapterTest extends ConfigAdapterTest
{
	protected function getInstance()
	{
		return new JITConfigAdapter($this->dba);
	}

	/**
	 * {@inheritDoc}
	 */
	public function testLoadConfig()
	{
		$this->mockLoad('config');

		$result = parent::testLoadConfig();
		/** @var IConfigAdapter $configAdapter */
		$configAdapter = $result['adapter'];

		$this->assertConfig('config', $result['result'], $configAdapter);

		// no autoload system - negative check
		$this->assertFalse($configAdapter->isLoaded('system', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key2'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key3'));
	}

	/**
	 * {@inheritDoc}
	 */
	public function testLoadSystem()
	{
		$result = parent::testLoadSystem();
		/** @var IConfigAdapter $configAdapter */
		$configAdapter = $result['adapter'];

		// forbidden autoload system
		$this->assertEmpty($result['result']);
		$this->assertFalse($configAdapter->isLoaded('system', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key2'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key3'));

		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));
	}

	/**
	 * {@inheritDoc}
	 */
	public function testLoadOther()
	{
		$this->mockLoad('other');

		$result = parent::testLoadOther();
		/** @var IConfigAdapter $configAdapter */
		$configAdapter = $result['adapter'];

		$this->assertConfig('other', $result['result'], $configAdapter);

		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));
	}

	/**
	 * Test the get method of a JIT Config adapter with different arguments
	 */
	public function testGet()
	{
		// mock system check (one DB call)
		$this->mockGet('system', 'key1', 'value1');
		$this->mockGet('system', 'key2', 'value2');
		$this->mockGet('system', 'key3', 'value3');

		// mock config check (one DB call)
		$this->mockGet('config', 'key1', 'value1a');
		$this->mockGet('config', 'key4', 'value4');

		// mock invalid value
		$this->mockGet('invalid', 'invalid', 1423);

		$configAdapter = $this->getInstance();

		$this->assertFalse($configAdapter->isLoaded('system', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key2'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key3'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));

		// Test system
		$this->assertEquals('value1', $configAdapter->get('system', 'key1'));
		$this->assertEquals('value2', $configAdapter->get('system', 'key2'));
		$this->assertEquals('value3', $configAdapter->get('system', 'key3'));

		// test config
		$this->assertEquals('value1a', $configAdapter->get('config', 'key1'));
		$this->assertEquals('value4', $configAdapter->get('config', 'key4'));

		// test invalid
		$this->assertEmpty($configAdapter->get('invalid', 'invalid'));

		$this->assertTrue($configAdapter->isLoaded('system', 'key1'));
		$this->assertTrue($configAdapter->isLoaded('system', 'key2'));
		$this->assertTrue($configAdapter->isLoaded('system', 'key3'));
		$this->assertTrue($configAdapter->isLoaded('config', 'key1'));
		$this->assertTrue($configAdapter->isLoaded('config', 'key4'));
	}

	/**
	 * Test the GET method without DB connection
	 */
	public function testGetDisconnectDB()
	{
		// fire the first connected check to reset it (because of the setup)
		$this->assertTrue($this->dba->connected());

		$this->dba->shouldReceive('connected')->andReturn(false);

		$configAdapter = $this->getInstance();

		$this->assertEmpty($configAdapter->get('system', 'key1'));
	}
}
