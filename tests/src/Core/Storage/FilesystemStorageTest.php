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

namespace Friendica\Test\src\Core\Storage;

use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Type\Filesystem;
use Friendica\Core\Storage\Type\FilesystemConfig;
use Friendica\Test\Util\VFSTrait;
use org\bovigo\vfs\vfsStream;

class FilesystemStorageTest extends StorageTest
{
	use VFSTrait;

	protected function setUp(): void
	{
		$this->setUpVfsDir();

		vfsStream::create(['storage' => []], $this->root);

		parent::setUp();
	}

	protected function getInstance()
	{
		return new Filesystem($this->root->getChild(FilesystemConfig::DEFAULT_BASE_FOLDER)->url());
	}

	/**
	 * Test the exception in case of missing directory permissions during put new files
	 */
	public function testMissingDirPermissionsDuringPut()
	{
		$this->expectException(StorageException::class);
		$this->expectExceptionMessageMatches("/Filesystem storage failed to create \".*\". Check you write permissions./");
		$this->root->getChild(FilesystemConfig::DEFAULT_BASE_FOLDER)->chmod(0777);

		$instance = $this->getInstance();

		$this->root->getChild(FilesystemConfig::DEFAULT_BASE_FOLDER)->chmod(0000);
		$instance->put('test');
	}

	/**
	 * Test the exception in case the directory isn't writeable
	 */
	public function testMissingDirPermissions()
	{
		$this->expectException(StorageException::class);
		$this->expectExceptionMessageMatches("/Path \".*\" does not exist or is not writeable./");
		$this->root->getChild(FilesystemConfig::DEFAULT_BASE_FOLDER)->chmod(0000);

		$this->getInstance();
	}

	/**
	 * Test the exception in case of missing file permissions
	 *
	 */
	public function testMissingFilePermissions()
	{
		static::markTestIncomplete("Cannot catch file_put_content() error due vfsStream failure");

		$this->expectException(StorageException::class);
		$this->expectExceptionMessageMatches("/Filesystem storage failed to save data to \".*\". Check your write permissions/");

		vfsStream::create(['storage' => ['f0' => ['c0' => ['k0i0' => '']]]], $this->root);

		$this->root->getChild('storage/f0/c0/k0i0')->chmod(000);

		$instance = $this->getInstance();
		$instance->put('test', 'f0c0k0i0');
	}

	/**
	 * Test the backend storage of the Filesystem Storage class
	 */
	public function testDirectoryTree()
	{
		$instance = $this->getInstance();

		$instance->put('test', 'f0c0d0i0');

		$dir  = $this->root->getChild('storage/f0/c0')->url();
		$file = $this->root->getChild('storage/f0/c0/d0i0')->url();

		self::assertDirectoryExists($dir);
		self::assertFileExists($file);

		self::assertDirectoryIsWritable($dir);
		self::assertFileIsWritable($file);

		self::assertEquals('test', file_get_contents($file));
	}
}
