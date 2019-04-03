<?php

namespace Friendica\Test\src\Util\Config;

use Friendica\App;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Config\ConfigFileLoader;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

class ConfigFileLoaderTest extends MockedTest
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
		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configFileLoader->setupCache($configCache);

		$this->assertEquals($this->root->url(), $configCache->get('system', 'basepath'));
	}

	/**
	 * Test the loadConfigFiles() method with a wrong local.config.php
	 */
	public function testLoadConfigWrong()
	{
		$this->delConfigFile('local.config.php');

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent('<?php return true;');

		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configFileLoader->setupCache($configCache);

		$this->assertEmpty($configCache->get('database', 'hostname'));
		$this->assertEmpty($configCache->get('database', 'username'));
		$this->assertEmpty($configCache->get('database', 'password'));
		$this->assertEmpty($configCache->get('database', 'database'));
		$this->assertEmpty($configCache->get('config', 'admin_email'));
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
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.config.php';

		vfsStream::newFile('local.config.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configFileLoader->setupCache($configCache);

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
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.ini.php';

		vfsStream::newFile('local.ini.php')
			->at($this->root->getChild('config'))
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configFileLoader->setupCache($configCache);

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
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'.htconfig.php';

		vfsStream::newFile('.htconfig.php')
			->at($this->root)
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();

		$configFileLoader->setupCache($configCache);

		$this->assertEquals('testhost', $configCache->get('database', 'hostname'));
		$this->assertEquals('testuser', $configCache->get('database', 'username'));
		$this->assertEquals('testpw', $configCache->get('database', 'password'));
		$this->assertEquals('testdb', $configCache->get('database', 'database'));
		$this->assertEquals('anotherCharset', $configCache->get('database', 'charset'));

		$this->assertEquals('/var/run/friendica.pid', $configCache->get('system', 'pidfile'));
		$this->assertEquals('Europe/Berlin', $configCache->get('system', 'default_timezone'));
		$this->assertEquals('fr', $configCache->get('system', 'language'));

		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('Friendly admin', $configCache->get('config', 'admin_nickname'));

		$this->assertEquals('/another/php', $configCache->get('config', 'php_path'));
		$this->assertEquals('999', $configCache->get('config', 'max_import_size'));
		$this->assertEquals('666', $configCache->get('system', 'maximagesize'));

		$this->assertEquals('quattro,vier,duepuntozero', $configCache->get('system', 'allowed_themes'));
		$this->assertEquals('1', $configCache->get('system', 'no_regfullname'));
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
			'datasets' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			'local.config.php';

		vfsStream::newFile('test.config.php')
			->at($this->root->getChild('addon')->getChild('test')->getChild('config'))
			->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);

		$conf = $configFileLoader->loadAddonConfig('test');

		$this->assertEquals('testhost', $conf['database']['hostname']);
		$this->assertEquals('testuser', $conf['database']['username']);
		$this->assertEquals('testpw', $conf['database']['password']);
		$this->assertEquals('testdb', $conf['database']['database']);

		$this->assertEquals('admin@test.it', $conf['config']['admin_email']);
	}
}
