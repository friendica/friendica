<?php

namespace Friendica\Test\Core\Config\Cache;

use Friendica\App;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Cache\ConfigCacheLoader;
use Friendica\Core\Config\Cache\ConfigCacheSaver;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

class ConfigCacheSaverTest extends MockedTest
{
	use VFSTrait;

	/**
	 * @var App\Mode|MockInterface
	 */
	private $mode;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->mode = \Mockery::mock(App\Mode::class);
		$this->mode->shouldReceive('isInstall')->andReturn(true);
	}

	/**
	 * Test the saveToConfigFile() method with a local.config.php file
	 */
	public function testSaveToConfigFileLocal()
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.config.php';

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configCacheSaver = new ConfigCacheSaver($this->root->url());
		$configCacheLoader = new ConfigCacheLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);
		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('!<unset>!', $configCache->get('config', 'test_val'));

		$configCacheSaver->saveToConfigFile('config', 'admin_email', 'new@mail.it');
		$configCacheSaver->saveToConfigFile('config', 'test_val', 'Testing$!"$with@all.we can!');

		$newConfigCache = new ConfigCache();
		$configCacheLoader->loadConfigFiles($newConfigCache);
		$this->assertEquals('new@mail.it', $newConfigCache->get('config', 'admin_email'));
		$this->assertEquals('Testing$!"$with@all.we can!', $newConfigCache->get('config', 'test_val'));

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.config.php'));
		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.config.php.old'));
		$this->assertFalse($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.config.php.tmp'));

		$this->assertEquals(file_get_contents($file), file_get_contents($this->root->getChild('config' . DIRECTORY_SEPARATOR . 'local.config.php.old')->url()));
	}

	/**
	 * Test the saveToConfigFile() method with a local.ini.php file
	 */
	public function testSaveToConfigFileINI()
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.ini.php';

		vfsStream::newFile('local.ini.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configCacheSaver = new ConfigCacheSaver($this->root->url());
		$configCacheLoader = new ConfigCacheLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);
		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('!<unset>!', $configCache->get('config', 'test_val'));

		$configCacheSaver->saveToConfigFile('config', 'admin_email', 'new@mail.it');
		$configCacheSaver->saveToConfigFile('config', 'test_val', "Testing@with.all we can");

		$newConfigCache = new ConfigCache();
		$configCacheLoader->loadConfigFiles($newConfigCache);
		$this->assertEquals('new@mail.it', $newConfigCache->get('config', 'admin_email'));
		$this->assertEquals("Testing@with.all we can", $newConfigCache->get('config', 'test_val'));

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php'));
		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php.old'));
		$this->assertFalse($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.ini.php.tmp'));

		$this->assertEquals(file_get_contents($file), file_get_contents($this->root->getChild('config' . DIRECTORY_SEPARATOR . 'local.ini.old')->url()));
	}

	/**
	 * Test the saveToConfigFile() method with a .htconfig.php file
	 * @todo fix it after 2019.03 merge to develop
	 */
	public function testSaveToConfigFileHtconfig()
	{
		$this->markTestSkipped('Needs 2019.03 merge to develop first');
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'.htconfig.test.php';

		vfsStream::newFile('.htconfig.php')
			->at($this->root)
			->setContent(file_get_contents($file));
		$configCacheSaver = new ConfigCacheSaver($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheSaver->loadConfigFiles($configCache);
		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('!<unset>!', $configCache->get('config', 'test_val'));

		$configCacheSaver->saveToConfigFile('config', 'admin_email', 'new@mail.it');
		$configCacheSaver->saveToConfigFile('config', 'test_val', 'Testing$!"$with@all.we can!');

		$newConfigCache = new ConfigCache();
		$configCacheSaver->loadConfigFiles($newConfigCache);
		$this->assertEquals('new@mail.it', $newConfigCache->get('config', 'admin_email'));
		$this->assertEquals('Testing$!"$with@all.we can!', $newConfigCache->get('config', 'test_val'));

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . '.htconfig.php'));
		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . '.htconfig.php.old'));
		$this->assertFalse($this->root->hasChild('config' . DIRECTORY_SEPARATOR . '.htconfig.php.tmp'));

		$this->assertEquals(file_get_contents($file), file_get_contents($this->root->getChild('config' . DIRECTORY_SEPARATOR . '.htconfig.php.old')->url()));
	}
}
