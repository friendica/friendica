<?php

namespace Friendica\Test\Util;

use Friendica\Core\Hook;
use Friendica\Model\Storage\IStorage;

use Friendica\Core\L10n\L10n;

/**
 * A backend storage example class
 */
class SampleStorageBackend implements IStorage
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

	public function get(string $reference)
	{
		// we return always the same image data. Which file we load is defined by
		// a config key
		return $this->data[$reference] ?? null;
	}

	public function put(string $data, string $reference = '')
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

	public function getOptions()
	{
		return $this->options;
	}

	public function saveOptions(array $data)
	{
		$this->options = $data;

		// no errors, return empty array
		return $this->options;
	}

	public function __toString()
	{
		return self::NAME;
	}

	public static function getName()
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

