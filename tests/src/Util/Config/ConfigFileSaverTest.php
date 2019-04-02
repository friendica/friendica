<?php

namespace Friendica\Test\src\Util\Config;

use Friendica\App;
use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Config\ConfigFileLoader;
use Friendica\Util\Config\ConfigFileSaver;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

class ConfigFileSaverTest extends MockedTest
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

	public function dataConfigFiles()
	{
		return [
			'config' => [
				'fileName' => 'local.config.php',
				'filePath' => dirname(__DIR__) . DIRECTORY_SEPARATOR .
					'..' . DIRECTORY_SEPARATOR .
					'..' . DIRECTORY_SEPARATOR .
					'datasets' . DIRECTORY_SEPARATOR .
					'config',
				'relativePath' => 'config',
			],
			'ini' => [
				'fileName' => 'local.ini.php',
				'filePath' => dirname(__DIR__) . DIRECTORY_SEPARATOR .
					'..' . DIRECTORY_SEPARATOR .
					'..' . DIRECTORY_SEPARATOR .
					'datasets' . DIRECTORY_SEPARATOR .
					'config',
				'relativePath' => 'config',
			],
			'htconfig' => [
				'fileName' => '.htconfig.php',
				'filePath' => dirname(__DIR__) . DIRECTORY_SEPARATOR .
					'..' . DIRECTORY_SEPARATOR .
					'..' . DIRECTORY_SEPARATOR .
					'datasets' . DIRECTORY_SEPARATOR .
					'config',
				'relativePath' => '',
			],
		];
	}

	/**
	 * Test the saveToConfigFile() method
	 * @dataProvider dataConfigFiles
	 */
	public function testSaveToConfig($fileName, $filePath, $relativePath)
	{
		$this->delConfigFile('local.config.php');

		if (empty($relativePath)) {
			$root = $this->root;
			$relativeFullName = $fileName;
		} else {
			$root = $this->root->getChild($relativePath);
			$relativeFullName = $relativePath . DIRECTORY_SEPARATOR . $fileName;
		}

		vfsStream::newFile($fileName)
			->at($root)
			->setContent(file_get_contents($filePath . DIRECTORY_SEPARATOR . $fileName));

		// The parse_ini_string() method doesn't support every escaped character by definition
		if (stristr($fileName, '.ini.')) {
			$escapeString = 'Testingwith@all.we can';
		} else {
			$escapeString = 'Testingwith@all\'.we can?!, \$';
		}

		$configFileSaver = new ConfigFileSaver($this->root->url());
		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();
		$configFileLoader->setupCache($configCache);

		$this->assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		$this->assertEquals('frio', $configCache->get('system', 'theme'));
		$this->assertEquals(true, $configCache->get('system', 'no_regfullname'));
		$this->assertEquals(\Friendica\Module\Register::OPEN, $configCache->get('config', 'register_policy'));
		$this->assertEquals(2.5, $configCache->get('system', 'numeric'));
		$this->assertEquals('quattro,vier,duepuntozero', $configCache->get('system', 'allowed_themes'));
		$this->assertNull($configCache->get('config', 'test_val'));
		$this->assertNull($configCache->get('system', 'test_val2'));

		// update values (system and config value)
		$configFileSaver->addConfigValue('config', 'admin_email', 'new@mail.it');
		$configFileSaver->addConfigValue('system', 'theme', 'vier');

		// insert values (system and config value)
		$configFileSaver->addConfigValue('config', 'test_val', $escapeString);
		$configFileSaver->addConfigValue('system', 'test_val2', 'TestIt First');

		// overwrite value
		$configFileSaver->addConfigValue('system', 'test_val2', 'TestIt Now');

		// new category
        $configFileSaver->addConfigValue('newCat', 'test_val3', 'TestIt Again');
        $configFileSaver->addConfigValue('newCat', 'test_val4', 'TestIt Fourth');

        // replace types
		$configFileSaver->addConfigValue('config', 'register_policy', \Friendica\Module\Register::APPROVE);
		$configFileSaver->addConfigValue('system', 'no_regfullname', false);
		$configFileSaver->addConfigValue('system', 'numeric', 6.78);
		$configFileSaver->addConfigValue('system', 'allowed_themes', ['quattro',$escapeString]);

		// save it
		$this->assertTrue($configFileSaver->saveToConfigFile());

		$newConfigCache = new ConfigCache();
		$configFileLoader->setupCache($newConfigCache);

		// update values (system and config value)
		$this->assertEquals('new@mail.it', $newConfigCache->get('config', 'admin_email'));
		$this->assertEquals('vier', $newConfigCache->get('system', 'theme'));

		// insert/overwritten values (system and config value)
		$this->assertEquals($escapeString, $newConfigCache->get('config', 'test_val'));
		$this->assertEquals('TestIt Now', $newConfigCache->get('system', 'test_val2'));

		// new categories
		$this->assertEquals('TestIt Again', $newConfigCache->get('newCat', 'test_val3'));
		$this->assertEquals('TestIt Fourth', $newConfigCache->get('newCat', 'test_val4'));

		// check types
		$this->assertEquals(\Friendica\Module\Register::APPROVE, $newConfigCache->get('config', 'register_policy'));
		$this->assertEquals(false, $newConfigCache->get('system', 'no_regfullname'));
		$this->assertEquals(6.78, $newConfigCache->get('system', 'numeric'));
		$this->assertEquals('quattro,' . $escapeString, $newConfigCache->get('system', 'allowed_themes'));

		$this->assertTrue($this->root->hasChild($relativeFullName));
		$this->assertTrue($this->root->hasChild($relativeFullName . '.old'));
		$this->assertFalse($this->root->hasChild($relativeFullName . '.tmp'));

		$this->assertEquals(file_get_contents($filePath . DIRECTORY_SEPARATOR . $fileName), file_get_contents($this->root->getChild($relativeFullName . '.old')->url()));
	}

	/**
	 * Test the saveToConfigFile() method without permissions
	 * @dataProvider dataConfigFiles
	 */
	public function testNoPermission($fileName, $filePath, $relativePath)
	{
		$this->delConfigFile('local.config.php');

		if (empty($relativePath)) {
			$root = $this->root;
		} else {
			$root = $this->root->getChild($relativePath);
		}

		$root->chmod(000);

		vfsStream::newFile($fileName)
			->at($root)
			->setContent(file_get_contents($filePath . DIRECTORY_SEPARATOR . $fileName));

		$configFileSaver = new ConfigFileSaver($this->root->url());

		$configFileSaver->addConfigValue('system', 'test_val2', 'TestIt Now');

		// wrong mod, so return false if nothing to write
		$this->assertFalse($configFileSaver->saveToConfigFile());
	}

	/**
	 * Test the saveToConfigFile() method with nothing to do
	 * @dataProvider dataConfigFiles
	 */
	public function testNothingToDo($fileName, $filePath, $relativePath)
	{
		$this->delConfigFile('local.config.php');

		if (empty($relativePath)) {
			$root = $this->root;
			$relativeFullName = $fileName;
		} else {
			$root = $this->root->getChild($relativePath);
			$relativeFullName = $relativePath . DIRECTORY_SEPARATOR . $fileName;
		}

		vfsStream::newFile($fileName)
			->at($root)
			->setContent(file_get_contents($filePath . DIRECTORY_SEPARATOR . $fileName));

		$configFileSaver = new ConfigFileSaver($this->root->url());
		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();
		$configFileLoader->setupCache($configCache);

		// save nothing
		$this->assertTrue($configFileSaver->saveToConfigFile());

		$this->assertTrue($this->root->hasChild($relativeFullName));
		$this->assertFalse($this->root->hasChild($relativeFullName . '.old'));
		$this->assertFalse($this->root->hasChild($relativeFullName . '.tmp'));

		$this->assertEquals(file_get_contents($filePath . DIRECTORY_SEPARATOR . $fileName), file_get_contents($this->root->getChild($relativeFullName)->url()));
	}

	/**
	 * Test the saveToConfigFile() method doesn't accidentally alter data
	 * @dataProvider dataConfigFiles
	 */
	public function testNoDataAltering($fileName, $filePath, $relativePath)
	{
		$this->delConfigFile('local.config.php');

		if (empty($relativePath)) {
			$root = $this->root;
		} else {
			$root = $this->root->getChild($relativePath);
		}

		vfsStream::newFile($fileName)
			->at($root)
			->setContent(file_get_contents($filePath . DIRECTORY_SEPARATOR . $fileName));

		$configFileSaver = new ConfigFileSaver($this->root->url());
		$configFileLoader = new ConfigFileLoader($this->root->url(), $this->mode);
		$configCache = new ConfigCache();
		$configFileLoader->setupCache($configCache);

		// add the same value
		$configFileSaver->addConfigValue('config', 'admin_email', $configCache->get('config', 'admin_email'));

		$this->assertTrue($configFileSaver->saveToConfigFile());

		$newConfigCache = new ConfigCache();
		$configFileLoader->setupCache($newConfigCache);

		$this->assertEquals($configCache->getAll(), $newConfigCache->getAll());
	}
}
