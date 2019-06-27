<?php

namespace Friendica\Test\src\Core\Config\Adapter;

use Friendica\Core\Config\Adapter\IConfigAdapter;
use Friendica\Core\Config\Adapter\PreloadConfigAdapter;

class PreloadConfigAdapterTest extends ConfigAdapterTest
{
	protected function getInstance()
	{
		return new PreloadConfigAdapter($this->dba);
	}

	/**
	 * {@inheritDoc}
	 */
	public function testLoadConfig()
	{
		$this->mockLoadAll();

		$result        = parent::testLoadConfig();
		/** @var IConfigAdapter $configAdapter */
		$configAdapter = $result['adapter'];

		// everything is loaded
		$this->assertConfig('config', $result['result'], $configAdapter);
		$this->assertConfig('system', $result['result'], $configAdapter);
		$this->assertConfig('other', $result['result'], $configAdapter);
	}

	/**
	 * {@inheritDoc}
	 */
	public function testLoadSystem()
	{
		$this->mockLoadAll();

		$result        = parent::testLoadConfig();
		/** @var IConfigAdapter $configAdapter */
		$configAdapter = $result['adapter'];

		// everything is loaded
		$this->assertConfig('config', $result['result'], $configAdapter);
		$this->assertConfig('system', $result['result'], $configAdapter);
		$this->assertConfig('other', $result['result'], $configAdapter);
	}

	/**
	 * {@inheritDoc}
	 */
	public function testLoadOther()
	{
		$this->mockLoadAll();

		$result = parent::testLoadOther();
		/** @var IConfigAdapter $configAdapter */
		$configAdapter = $result['adapter'];

		// everything is loaded
		$this->assertConfig('config', $result['result'], $configAdapter);
		$this->assertConfig('system', $result['result'], $configAdapter);
		$this->assertConfig('other', $result['result'], $configAdapter);

		// autoload everything
		$this->assertTrue($configAdapter->isLoaded('system', 'key1'));
		$this->assertTrue($configAdapter->isLoaded('system', 'key2'));
		$this->assertTrue($configAdapter->isLoaded('system', 'key3'));
		$this->assertTrue($configAdapter->isLoaded('config', 'key1'));
		$this->assertTrue($configAdapter->isLoaded('config', 'key4'));
	}


	/**
	 * {@inheritDoc}
	 */
	public function testGet()
	{
		$configAdapter = parent::testGet();

		// Still not loaded, because of missing "load"
		// Preload adapter can only load everything at all
		$this->assertFalse($configAdapter->isLoaded('system', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key2'));
		$this->assertFalse($configAdapter->isLoaded('system', 'key3'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
		$this->assertFalse($configAdapter->isLoaded('config', 'key4'));
	}

	/**
	 * {@inheritDoc}
	 */
	public function testSet()
	{
		$configAdapter = parent::testSet();

		// Still not loaded
		$this->assertFalse($configAdapter->isLoaded('config', 'key1'));
	}
}
