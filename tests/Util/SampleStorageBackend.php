<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Util;

use Friendica\Core\Hook;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Core\L10n;

/**
 * A backend storage example class
 */
class SampleStorageBackend implements ICanWriteToStorage
{
	public const NAME = 'Sample Storage';

	/** @var array */
	private $options = [
		'filename' => [
			'input',    // will use a simple text input
			'The file to return',    // the label
			'sample',    // the current value
			'Enter the path to a file', // the help text
			// no extra data for 'input' type..
		],
	];
	/** @var array Just save the data in memory */
	private $data = [];

	/**
	 * SampleStorageBackend constructor.
	 *
	 * @param L10n $l10n The configuration of Friendica
	 *
	 * You can add here every dynamic class as dependency you like and add them to a private field
	 * Friendica automatically creates these classes and passes them as argument to the constructor
	 */
	public function __construct(private readonly L10n $l10n) // @phpstan-ignore property.onlyWritten
	{}

	public function get(string $reference): string
	{
		// we return always the same image data. Which file we load is defined by
		// a config key
		return $this->data[$reference] ?? '';
	}

	public function put(string $data, string $reference = ''): string
	{
		if ($reference === '') {
			$reference = 'sample';
		}

		$this->data[$reference] = $data;

		return $reference;
	}

	public function delete(string $reference)
	{
		if (isset($this->data[$reference])) {
			unset($this->data[$reference]);
		}

		return true;
	}

	public function getOptions(): array
	{
		return $this->options;
	}

	public function saveOptions(array $data): array
	{
		$this->options = $data;

		// no errors, return empty array
		return $this->options;
	}

	public function __toString(): string
	{
		return self::NAME;
	}

	public static function getName(): string
	{
		return self::NAME;
	}

	/**
	 * This one is a hack to register this class to the hook
	 */
	public static function registerHook()
	{
		Hook::register('storage_instance', __DIR__ . '/SampleStorageBackendInstance.php', 'create_instance');
	}
}
