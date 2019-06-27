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
	 * @expectedException Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function testGet()
	{
		$configAdapter = $this->getInstance();

		$configAdapter->get('system', 'key1');
	}
}
