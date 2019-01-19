<?php

namespace Friendica\Test\Util\Mocks;


use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

trait VFSTrait
{
	/**
	 * @var vfsStreamDirectory The Stream Directory
	 */
	protected $root;

	/**
	 * Sets up the Virtual File System for Friendica with common files (config, dbstructure)
	 */
	protected function setUpVfsDir()
	{
		// the used directories inside the App class
		$structure = [
			'config' => [],
			'bin' => [],
			'test' => []
		];

		// create a virtual directory and copy all needed files and folders to it
		$this->root = vfsStream::setup('friendica', null, $structure);

		$this->setConfigFile('defaults.config.php');
		$this->setConfigFile('settings.config.php');
		$this->setConfigFile('local-sample.config.php');
		$this->setConfigFile('dbstructure.config.php');

		/// rename local-sample.config.php to local.config.php
		if ($this->root->hasChild('config/local-sample.config.php')) {
			$this->root
				->getChild('config/local-sample.config.php')
				->rename('local.config.php');
		}
	}

	/**
	 * Copying a config file from the file system to the Virtual File System
	 *
	 * @param string $filename The filename of the config file
	 */
	protected function setConfigFile($filename)
	{
		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
			'config' . DIRECTORY_SEPARATOR .
			$filename;

		if (file_exists($file)) {
			vfsStream::newFile($filename)
				->at($this->root->getChild('config'))
				->setContent(file_get_contents($file));
		}
	}

	/**
	 * Delets a config file from the Virtual File System
	 *
	 * @param string $filename The filename of the config file
	 */
	protected function delConfigFile($filename)
	{
		if ($this->root->hasChild('config/' . $filename)) {
			$this->root->removeChild('config/' . $filename);
		}
	}
}
