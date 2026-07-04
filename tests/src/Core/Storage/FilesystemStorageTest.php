<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Storage;

use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Type\Filesystem;
use Friendica\Core\Storage\Type\FilesystemConfig;
use Friendica\Test\StorageTestCase;
use Friendica\Test\Util\VFSTrait;
use org\bovigo\vfs\vfsStream;

class FilesystemStorageTest extends StorageTestCase
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
	public function testMissingDirPermissionsDuringPut(): void
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
	public function testMissingDirPermissions(): void
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
	public function testMissingFilePermissions(): void
	{
		static::markTestIncomplete("Cannot catch file_put_content() error due vfsStream failure");

		$this->expectException(StorageException::class); // @phpstan-ignore deadCode.unreachable (skipped test)
		$this->expectExceptionMessageMatches("/Filesystem storage failed to save data to \".*\". Check your write permissions/");

		vfsStream::create(['storage' => ['f0' => ['c0' => ['k0i0' => '']]]], $this->root);

		$this->root->getChild('storage/f0/c0/k0i0')->chmod(000);

		$instance = $this->getInstance();
		$instance->put('test', 'f0c0k0i0');
	}

	/**
	 * Test the backend storage of the Filesystem Storage class
	 */
	public function testDirectoryTree(): void
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
