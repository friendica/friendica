<?php

namespace Friendica\Core\Config\Adapter;

use Friendica\Database\Database;

abstract class AbstractDbaConfigAdapter
{
	/**
	 * @var Database
	 */
	protected $database;

	/**
	 * The connection state of the adapter
	 *
	 * @var bool
	 */
	protected $connected = true;

	public function __construct(Database $database)
	{
		$this->database = $database;
		$this->connected = $database->connected();
	}

	/**
	 * Checks if the adapter is currently connected
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->connected;
	}

	/**
	 * Formats a DB value to a config value
	 * - null   = The db-value isn't set
	 * - bool   = The db-value is either '0' or '1'
	 * - array  = The db-value is a serialized array
	 * - string = The db-value is a string
	 *
	 * Keep in mind that there aren't any numeric/integer config values in the database
	 *
	 * @param null|string $value
	 *
	 * @return null|array|string
	 */
	protected function toConfigValue($value)
	{
		if (!isset($value)) {
			return null;
		}

		switch (true) {
			// manage array value
			case preg_match("|^a:[0-9]+:{.*}$|s", $value):
				return unserialize($value);

			default:
				return $value;
		}
	}

	/**
	 * Formats a config value to a DB value (string)
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function toDbValue($value)
	{
		// if not set, save an empty string
		if (!isset($value)) {
			return '';
		}

		switch (true) {
			// manage arrays
			case is_array($value):
				return serialize($value);

			default:
				return (string)$value;
		}
	}
}
