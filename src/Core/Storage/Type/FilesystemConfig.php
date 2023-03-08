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

namespace Friendica\Core\Storage\Type;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Storage\Capability\ICanConfigureStorage;

/**
 * Filesystem based storage backend configuration
 */
class FilesystemConfig implements ICanConfigureStorage
{
	// Default base folder
	const DEFAULT_BASE_FOLDER = 'storage';

	/** @var IManageConfigValues */
	private $config;

	/** @var string */
	private $storagePath;

	/** @var L10n */
	private $l10n;

	/**
	 * Returns the current storage path
	 *
	 * @return string
	 */
	public function getStoragePath(): string
	{
		return $this->storagePath;
	}

	/**
	 * Filesystem constructor.
	 *
	 * @param IManageConfigValues $config
	 * @param L10n                $l10n
	 */
	public function __construct(IManageConfigValues $config, L10n $l10n)
	{
		$this->config = $config;
		$this->l10n   = $l10n;

		$path              = $this->config->get('storage', 'filesystem_path', self::DEFAULT_BASE_FOLDER);
		$this->storagePath = rtrim($path, '/');
	}

	/**
	 * @inheritDoc
	 */
	public function getOptions(): array
	{
		return [
			'storagepath' => [
				'input',
				$this->l10n->t('Storage base path'),
				$this->storagePath,
				$this->l10n->t('Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree')
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function saveOptions(array $data): array
	{
		$storagePath = $data['storagepath'] ?? '';
		if ($storagePath === '' || !is_dir($storagePath)) {
			return [
				'storagepath' => $this->l10n->t('Enter a valid existing folder')
			];
		};
		$this->config->set('storage', 'filesystem_path', $storagePath);
		$this->storagePath = $storagePath;
		return [];
	}
}
