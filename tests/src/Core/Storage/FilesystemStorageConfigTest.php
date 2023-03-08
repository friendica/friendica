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

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Core\Storage\Type\FilesystemConfig;
use Friendica\Test\Util\VFSTrait;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;

class FilesystemStorageConfigTest extends StorageConfigTest
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
		/** @var MockInterface|L10n $l10n */
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
				'Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'
			]
		], $storage->getOptions());
	}
}
