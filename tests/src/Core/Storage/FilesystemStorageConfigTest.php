<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\src\Core\Storage;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Core\Storage\Type\FilesystemConfig;
use Friendica\Test\StorageConfigTestCase;
use Friendica\Test\Util\VFSTrait;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

class FilesystemStorageConfigTest extends StorageConfigTestCase
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
		/** @var L10n&MockInterface $l10n */
		$l10n   = \Mockery::mock(L10n::class)->makePartial();
		$config = \Mockery::mock(IManageConfigValues::class);
		$config->shouldReceive('get')
					 ->with('storage', 'filesystem_path', FilesystemConfig::DEFAULT_BASE_FOLDER)
					 ->andReturn($this->root->getChild('storage')->url());

		return new FilesystemConfig($config, $l10n);
	}

	protected function assertOption(ICanConfigureStorage $storage)
	{
		self::assertEquals([
			'storagepath' => [
				'input', 'Storage base path',
				$this->root->getChild('storage')->url(),
				'Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree',
			],
		], $storage->getOptions());
	}
}
