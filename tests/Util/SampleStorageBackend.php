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

use Friendica\Core\Hook;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;

use Friendica\Core\L10n;

/**
 * A backend storage example class
 */
class SampleStorageBackend implements ICanWriteToStorage
{
	const NAME = 'Sample Storage';

	/** @var L10n */
	private $l10n;

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
	public function __construct(L10n $l10n)
	{
		$this->l10n = $l10n;
	}

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
