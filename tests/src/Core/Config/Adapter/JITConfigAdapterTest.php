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
	 * {@inheritDoc}
	 */
	public function testGet()
	{
		$configAdapter = parent::testGet();

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

	/**
	 * {@inheritDoc}
	 */
	public function testSet()
	{
		$configAdapter = parent::testSet();

		$this->assertTrue($configAdapter->isLoaded('config', 'key1'));
	}
}
