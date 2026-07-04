<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Config\Cache;

use Friendica\Core\Config\Factory\Config;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Test\MockedTestCase;
use Friendica\Test\Util\VFSTrait;
use Friendica\Core\Config\Util\ConfigFileManager;
use org\bovigo\vfs\vfsStream;

class ConfigFileManagerTest extends MockedTestCase
{
	use VFSTrait;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();
	}

	/**
	 * Test the loadConfigFiles() method with default values
	 */
	public function testLoadConfigFiles(): void
	{
		$this->delConfigFile('local.config.php');

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);

		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals($this->root->url(), $configCache->get('system', 'basepath'));
	}

	/**
	 * Test the loadConfigFiles() method with a wrong local.config.php
	 *
	 */
	public function testLoadConfigWrong(): void
	{
		$this->expectExceptionMessageMatches("/Error loading config file \w+/");
		$this->expectException(\Exception::class);
		$this->delConfigFile('local.config.php');

		vfsStream::newFile('local.config.php')
				 ->at($this->root->getChild('config'))
				 ->setContent('<?php return true;');

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);
	}

	/**
	 * Test the loadConfigFiles() method with a local.config.php file
	 */
	public function testLoadConfigFilesLocal(): void
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. 'Fixtures' . DIRECTORY_SEPARATOR
				. 'config' . DIRECTORY_SEPARATOR
				. 'A.config.php';

		vfsStream::newFile('local.config.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('testhost', $configCache->get('database', 'hostname'));
		self::assertEquals('testuser', $configCache->get('database', 'username'));
		self::assertEquals('testpw', $configCache->get('database', 'password'));
		self::assertEquals('testdb', $configCache->get('database', 'database'));

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		self::assertEquals('Friendica Social Network', $configCache->get('config', 'sitename'));
	}

	/**
	 * Test the loadConfigFile() method with a local.ini.php file
	 */
	public function testLoadConfigFilesINI(): void
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. 'Fixtures' . DIRECTORY_SEPARATOR
				. 'config' . DIRECTORY_SEPARATOR
				. 'A.ini.php';

		vfsStream::newFile('local.ini.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('testhost', $configCache->get('database', 'hostname'));
		self::assertEquals('testuser', $configCache->get('database', 'username'));
		self::assertEquals('testpw', $configCache->get('database', 'password'));
		self::assertEquals('testdb', $configCache->get('database', 'database'));

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
	}

	/**
	 * Test the loadConfigFile() method with a .htconfig.php file
	 */
	public function testLoadConfigFilesHtconfig(): void
	{
		$this->delConfigFile('local.config.php');

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. 'Fixtures' . DIRECTORY_SEPARATOR
				. 'config' . DIRECTORY_SEPARATOR
				. '.htconfig.php';

		vfsStream::newFile('.htconfig.php')
				 ->at($this->root)
				 ->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('testhost', $configCache->get('database', 'hostname'));
		self::assertEquals('testuser', $configCache->get('database', 'username'));
		self::assertEquals('testpw', $configCache->get('database', 'password'));
		self::assertEquals('testdb', $configCache->get('database', 'database'));
		self::assertEquals('anotherCharset', $configCache->get('database', 'charset'));

		self::assertEquals('/var/run/friendica.pid', $configCache->get('system', 'pidfile'));
		self::assertEquals('Europe/Berlin', $configCache->get('system', 'default_timezone'));
		self::assertEquals('fr', $configCache->get('system', 'language'));

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		self::assertEquals('Friendly admin', $configCache->get('config', 'admin_nickname'));

		self::assertEquals('/another/php', $configCache->get('config', 'php_path'));
		self::assertEquals('999', $configCache->get('config', 'max_import_size'));
		self::assertEquals('666', $configCache->get('system', 'maximagesize'));

		self::assertEquals('frio,vier', $configCache->get('system', 'allowed_themes'));
		self::assertEquals('1', $configCache->get('system', 'no_regfullname'));
	}

	public function testLoadAddonConfig(): void
	{
		$structure = [
			'addon' => [
				'test' => [
					'config' => [],
				],
			],
		];

		vfsStream::create($structure, $this->root);

		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. '..' . DIRECTORY_SEPARATOR
				. 'Fixtures' . DIRECTORY_SEPARATOR
				. 'config' . DIRECTORY_SEPARATOR
				. 'A.config.php';

		/** @var \org\bovigo\vfs\vfsStreamDirectory $addonDir */
		$addonDir = $this->root->getChild('addon');
		/** @var \org\bovigo\vfs\vfsStreamDirectory $testDir */
		$testDir = $addonDir->getChild('test');
		vfsStream::newFile('test.config.php')
				 ->at($testDir->getChild('config'))
				 ->setContent(file_get_contents($file));

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);

		$conf = $configFileLoader->loadAddonConfig('test');

		self::assertEquals('testhost', $conf['database']['hostname']);
		self::assertEquals('testuser', $conf['database']['username']);
		self::assertEquals('testpw', $conf['database']['password']);
		self::assertEquals('testdb', $conf['database']['database']);

		self::assertEquals('admin@test.it', $conf['config']['admin_email']);
	}

	/**
	 * test loading multiple config files - the last config should work
	 */
	public function testLoadMultipleConfigs(): void
	{
		$this->delConfigFile('local.config.php');

		$fileDir = dirname(__DIR__) . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . 'Fixtures' . DIRECTORY_SEPARATOR
				   . 'config' . DIRECTORY_SEPARATOR;

		vfsStream::newFile('A.config.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($fileDir . 'A.config.php'));
		vfsStream::newFile('B.config.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($fileDir . 'B.config.php'));

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('admin@overwritten.local', $configCache->get('config', 'admin_email'));
		self::assertEquals('newValue', $configCache->get('system', 'newKey'));
	}

	/**
	 * test loading multiple config files - the last config should work (INI-version)
	 */
	public function testLoadMultipleInis(): void
	{
		$this->delConfigFile('local.config.php');

		$fileDir = dirname(__DIR__) . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . 'Fixtures' . DIRECTORY_SEPARATOR
				   . 'config' . DIRECTORY_SEPARATOR;

		vfsStream::newFile('A.ini.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($fileDir . 'A.ini.php'));
		vfsStream::newFile('B.ini.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($fileDir . 'B.ini.php'));

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);
		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('admin@overwritten.local', $configCache->get('config', 'admin_email'));
		self::assertEquals('newValue', $configCache->get('system', 'newKey'));
	}

	/**
	 * Test that sample-files (e.g. local-sample.config.php) is never loaded
	 */
	public function testNotLoadingSamples(): void
	{
		$this->delConfigFile('local.config.php');

		$fileDir = dirname(__DIR__) . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . 'Fixtures' . DIRECTORY_SEPARATOR
				   . 'config' . DIRECTORY_SEPARATOR;

		vfsStream::newFile('A.ini.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($fileDir . 'A.ini.php'));
		vfsStream::newFile('B-sample.ini.php')
				 ->at($this->root->getChild('config'))
				 ->setContent(file_get_contents($fileDir . 'B.ini.php'));

		$configFileLoader = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . DIRECTORY_SEPARATOR . 'addon',
			$this->root->url() . DIRECTORY_SEPARATOR . Config::CONFIG_DIR,
			$this->root->url() . DIRECTORY_SEPARATOR . Config::STATIC_DIR,
		);

		$configCache = new Cache();

		$configFileLoader->setupCache($configCache);

		self::assertEquals('admin@test.it', $configCache->get('config', 'admin_email'));
		self::assertEmpty($configCache->get('system', 'NewKey'));
	}

	/**
	 * Test that using a wrong configuration directory leads to the "normal" config path
	 */
	public function testWrongEnvDir(): void
	{
		$this->delConfigFile('local.config.php');

		$configFileManager = (new Config())->createConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			['FRIENDICA_CONFIG_DIR' => '/a/wrong/dir/'],
		);
		$configCache = new Cache();

		$configFileManager->setupCache($configCache);

		self::assertEquals($this->root->url(), $configCache->get('system', 'basepath'));
	}

	/**
	 * Test that a different location of the configuration directory produces the expected output
	 */
	public function testRightEnvDir(): void
	{
		$this->delConfigFile('local.config.php');

		$fileDir = dirname(__DIR__) . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . '..' . DIRECTORY_SEPARATOR
				   . 'Fixtures' . DIRECTORY_SEPARATOR
				   . 'config' . DIRECTORY_SEPARATOR;

		vfsStream::newFile('B.config.php')
				 ->at($this->root->getChild('config2'))
				 ->setContent(file_get_contents($fileDir . 'B.config.php'));

		$configFileManager = (new Config())->createConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			['FRIENDICA_CONFIG_DIR' => $this->root->getChild('config2')->url()],
		);
		$configCache = new Cache();

		$configFileManager->setupCache($configCache);

		self::assertEquals('newValue', $configCache->get('system', 'newKey'));
	}

	/**
	 * Test for empty node.config.php
	 */
	public function testEmptyFile(): void
	{
		$this->delConfigFile('node.config.php');

		vfsStream::newFile('node.config.php')
				 ->at($this->root->getChild('config'))
				 ->setContent('');

		$configFileManager = new ConfigFileManager(
			$this->root->url(),
			$this->root->url() . '/addon',
			$this->root->url() . '/config',
			$this->root->url() . '/static',
			[],
		);

		$configFileManager->setupCache(new Cache());

		self::assertStringEqualsFile($this->root->url() . '/config/node.config.php', '');
	}
}
