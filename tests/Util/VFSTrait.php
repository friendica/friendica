<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\Util;


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
	protected function setUpVfsDir() {
		// the used directories inside the App class
		$structure = [
			'config' => [],
			'bin' => [],
			'static' => [],
			'test' => [],
			'logs' => [],
			'config2' => [],
		];

		// create a virtual directory and copy all needed files and folders to it
		$this->root = vfsStream::setup('friendica', 0777, $structure);

		$this->setConfigFile('static' . DIRECTORY_SEPARATOR . 'dbstructure.config.php', true);
		$this->setConfigFile('static' . DIRECTORY_SEPARATOR . 'dbview.config.php', true);
		$this->setConfigFile('static' . DIRECTORY_SEPARATOR . 'defaults.config.php', true);
		$this->setConfigFile('static' . DIRECTORY_SEPARATOR . 'settings.config.php', true);
		$this->setConfigFile(
			'mods' . DIRECTORY_SEPARATOR . 'local.config.ci.php',
			false, 'local.config.php'
		);
	}

	/**
	 * Copying a config file from the file system to the Virtual File System
	 *
	 * @param string $sourceFilePath The filename of the config file
	 * @param bool   $static         True, if the folder `static` instead of `config` should be used
	 */
	protected function setConfigFile(string $sourceFilePath, bool $static = false, string $targetFileName = null)
	{
		$file = dirname(__DIR__) . DIRECTORY_SEPARATOR .
			'..' . DIRECTORY_SEPARATOR .
				$sourceFilePath;

		if (file_exists($file)) {
			if (empty($targetFileName)) {
				$tmpArray = preg_split('/\\' . DIRECTORY_SEPARATOR . '/', $sourceFilePath);
				$targetFileName = array_pop($tmpArray);
			}
			vfsStream::newFile($targetFileName)
				->at($this->root->getChild(($static ? 'static' : 'config')))
				->setContent(file_get_contents($file));
		} else {
			throw new \Exception(sprintf('Unexpected missing config \'%s\'', $file));
		}
	}

	/**
	 * Delets a config file from the Virtual File System
	 *
	 * @param string $filename The filename of the config file
	 * @param bool   $static   True, if the folder `static` instead of `config` should be used
	 */
	protected function delConfigFile(string $filename, bool $static = false)
	{
		if ($this->root->hasChild(($static ? 'static' : 'config') . '/' . $filename)) {
			$this->root->getChild(($static ? 'static' : 'config'))->removeChild($filename);
		}
	}
}
