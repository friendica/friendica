<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\KeyValueStorage\Type;

use Friendica\Core\PConfig\Util\ValueConversion;
use Friendica\Core\KeyValueStorage\Exceptions\KeyValueStoragePersistenceException;
use Friendica\Database\Database;

/**
 * A Key-Value storage provider with DB as persistence layer
 */
class DBKeyValueStorage extends AbstractKeyValueStorage
{
	const NAME               = 'database';
	const DB_KEY_VALUE_TABLE = 'key-value';

	/** @var Database */
	protected $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	/** {@inheritDoc} */
	public function offsetExists($offset): bool
	{
		try {
			return $this->database->exists(self::DB_KEY_VALUE_TABLE, ['k' => $offset]);
		} catch (\Exception $exception) {
			throw new KeyValueStoragePersistenceException(sprintf('Cannot check storage with key %s', $offset), $exception);
		}
	}

	/** {@inheritDoc} */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		try {
			$result = $this->database->selectFirst(self::DB_KEY_VALUE_TABLE, ['v'], ['k' => $offset]);

			if ($this->database->isResult($result)) {
				$value = ValueConversion::toConfigValue($result['v']);

				// just return it in case it is set
				if (isset($value)) {
					return $value;
				}
			}
		} catch (\Exception $exception) {
			throw new KeyValueStoragePersistenceException(sprintf('Cannot get value for key %s', $offset), $exception);
		}

		return null;
	}

	/** {@inheritDoc} */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		try {
			// We store our setting values in a string variable.
			// So we have to do the conversion here so that the compare below works.
			// The exception are array values.
			$compare_value = (!is_array($value) ? (string)$value : $value);
			$stored_value  = $this->get($offset);

			if (isset($stored_value) && ($stored_value === $compare_value)) {
				return;
			}

			$dbValue = ValueConversion::toDbValue($value);

			$return = $this->database->update(self::DB_KEY_VALUE_TABLE, [
				'v'          => $dbValue,
				'updated_at' => time()
			], ['k' => $offset], true);

			if (!$return) {
				throw new \Exception(sprintf('database update failed: %s', $this->database->errorMessage()));
			}
		} catch (\Exception $exception) {
			throw new KeyValueStoragePersistenceException(sprintf('Cannot set value for %s for key %s', $value, $offset), $exception);
		}
	}

	/** {@inheritDoc} */
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		try {
			if (!$this->database->delete(self::DB_KEY_VALUE_TABLE, ['k' => $offset])) {
				throw new \Exception(sprintf('database deletion failed: %s', $this->database->errorMessage()));
			}
		} catch (\Exception $exception) {
			throw new KeyValueStoragePersistenceException(sprintf('Cannot delete value with key %s', $offset), $exception);
		}
	}
}
