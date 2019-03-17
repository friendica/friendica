<?php

namespace Friendica\Test\Core\Config\Cache;

use Friendica\App;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Config\Cache\ConfigCacheManager;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

class ConfigCacheManagerTest extends MockedTest
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
	 * Test the loadConfigFiles() method with default values
	 */
	public function testLoadConfigFiles()
	{
		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);

		$this->assertEquals($this->root->url(), $configCache->get('system', 'basepath'));
	}

	/**
	 * Test the loadConfigFiles() method with a wrong local.config.php
	 * @expectedException \Exception
	 * @expectedExceptionMessageRegExp /Error loading config file \w+/
	 */
	public function testLoadConfigWrong()
	{
		$this->delConfigFile('local.config.php');

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent('<?php return true;');

		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);
	}

	/**
	 * Test the loadConfigFiles() method with a local.config.php file
	 */
	public function testLoadConfigFilesLocal()
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

		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);

		$this->assertEquals('testhost', $configCache->get('database', 'hostname'));
		$this->assertEquals('testuser', $configCache->get('database', 'username'));
		$this->assertEquals('testpw', $configCache->get('database', 'password'));
		$this->assertEquals('testdb', $configCache->get('database', 'database'));

		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('Friendica Social Network', $configCache->get('config', 'sitename'));
	}

	/**
	 * Test the loadConfigFile() method with a local.ini.php file
	 */
	public function testLoadConfigFilesINI()
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

		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);

		$this->assertEquals('testhost', $configCache->get('database', 'hostname'));
		$this->assertEquals('testuser', $configCache->get('database', 'username'));
		$this->assertEquals('testpw', $configCache->get('database', 'password'));
		$this->assertEquals('testdb', $configCache->get('database', 'database'));

		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
	}

	/**
	 * Test the loadConfigFile() method with a .htconfig.php file
	 */
	public function testLoadConfigFilesHtconfig()
	{
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

		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);

		$this->assertEquals('testhost', $configCache->get('database', 'hostname'));
		$this->assertEquals('testuser', $configCache->get('database', 'username'));
		$this->assertEquals('testpw', $configCache->get('database', 'password'));
		$this->assertEquals('testdb', $configCache->get('database', 'database'));

		$this->assertEquals('/var/run/friendica.pid', $configCache->get('system', 'pidfile'));
		$this->assertEquals('Europe/Berlin', $configCache->get('system', 'default_timezone'));
		$this->assertEquals('fr', $configCache->get('system', 'language'));
	}

	public function testLoadAddonConfig()
	{
		$structure = [
			'addon' => [
				'test' => [
					'config' => [],
				],
			],
		];

		vfsStream::create($structure, $this->root);

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.config.php';

		vfsStream::newFile('test.config.php')
			->at($this->root->getChild('addon')->getChild('test')->getChild('config'))
			->setContent(file_get_contents($file));

		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);

		$conf = $configCacheLoader->loadAddonConfig('test');

		$this->assertEquals('testhost', $conf['database']['hostname']);
		$this->assertEquals('testuser', $conf['database']['username']);
		$this->assertEquals('testpw', $conf['database']['password']);
		$this->assertEquals('testdb', $conf['database']['database']);

		$this->assertEquals('admin@test.it', $conf['config']['admin_email']);
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

		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);
		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('!<unset>!', $configCache->get('config', 'test_val'));

		$configCacheLoader->saveToConfigFile('config', 'admin_email', 'new@mail.it');
		$configCacheLoader->saveToConfigFile('config', 'test_val', 'Testing$!"$with@all.we can!');

		$newConfigCache = new ConfigCache();
		$configCacheLoader->loadConfigFiles($newConfigCache);
		$this->assertEquals('new@mail.it', $newConfigCache->get('config', 'admin_email'));
		$this->assertEquals('Testing$!"$with@all.we can!', $newConfigCache->get('config', 'test_val'));
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
		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);
		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('!<unset>!', $configCache->get('config', 'test_val'));

		$configCacheLoader->saveToConfigFile('config', 'admin_email', 'new@mail.it');
		$configCacheLoader->saveToConfigFile('config', 'test_val', "Testing@with.all we can");

		$newConfigCache = new ConfigCache();
		$configCacheLoader->loadConfigFiles($newConfigCache);
		$this->assertEquals('new@mail.it', $newConfigCache->get('config', 'admin_email'));
		$this->assertEquals("Testing@with.all we can", $newConfigCache->get('config', 'test_val'));
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
		$configCacheLoader = new ConfigCacheManager($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configCacheLoader->loadConfigFiles($configCache);
		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('!<unset>!', $configCache->get('config', 'test_val'));

		$configCacheLoader->saveToConfigFile('config', 'admin_email', 'new@mail.it');
		$configCacheLoader->saveToConfigFile('config', 'test_val', 'Testing$!"$with@all.we can!');

		$newConfigCache = new ConfigCache();
		$configCacheLoader->loadConfigFiles($newConfigCache);
		$this->assertEquals('new@mail.it', $newConfigCache->get('config', 'admin_email'));
		$this->assertEquals('Testing$!"$with@all.we can!', $newConfigCache->get('config', 'test_val'));
	}
}
