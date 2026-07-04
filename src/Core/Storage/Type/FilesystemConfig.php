<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public const DEFAULT_BASE_FOLDER = 'storage';

	/** @var string */
	private $storagePath;

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
	public function __construct(private readonly IManageConfigValues $config, private readonly L10n $l10n)
	{
		$path              = $this->config->get('storage', 'filesystem_path', self::DEFAULT_BASE_FOLDER);
		$this->storagePath = rtrim((string) $path, '/');
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
				$this->l10n->t('Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'),
			],
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
				'storagepath' => $this->l10n->t('Enter a valid existing folder'),
			];
		};
		$this->config->set('storage', 'filesystem_path', $storagePath);
		$this->storagePath = $storagePath;
		return [];
	}
}
