<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Database\Repository;

use Friendica\Database\Database;
use Friendica\Database\DatabaseException;
use Throwable;

/**
 * Repository for cache table
 */
final class CacheRepository
{
	private Database $database;

	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	/**
	 * @throws DatabaseException
	 *
	 * @return array<string>
	 */
	public function getAllKeysValidUntil(string $expires): array
	{
		$throw = $this->database->throwExceptionsOnErrors(true);

		try {
			return $this->getAllKeys($expires, null);
		} catch (Throwable $th) {
			if (! $th instanceof DatabaseException) {
				$th = new DatabaseException('Cannot fetch all keys without prefix', 0, '', $th);
			}

			throw $th;
		} finally {
			$this->database->throwExceptionsOnErrors($throw);
		}
	}

	/**
	 * @throws DatabaseException
	 *
	 * @return array<string>
	 */
	public function getAllKeysValidUntilWithPrefix(string $expires, string $prefix): array
	{
		$throw = $this->database->throwExceptionsOnErrors(true);

		try {
			return $this->getAllKeys($expires, $prefix);
		} catch (Throwable $th) {
			if (! $th instanceof DatabaseException) {
				$th = new DatabaseException(sprintf('Cannot fetch all keys with prefix `%s`', $prefix), 0, '', $th);
			}

			throw $th;
		} finally {
			$this->database->throwExceptionsOnErrors($throw);
		}
	}

	private function getAllKeys(string $expires, ?string $prefix = null): array
	{
		if ($prefix === null) {
			$where = ['`expires` >= ?', $expires];
		} else {
			$where = ['`expires` >= ? AND `k` LIKE CONCAT(?, \'%\')', $expires, $prefix];
		}

		$stmt = $this->database->select('cache', ['k'], $where);

		$keys = [];

		try {
			while ($key = $this->database->fetch($stmt)) {
				array_push($keys, $key['k']);
			}
		} finally {
			$this->database->close($stmt);
		}

		return $keys;
	}
}
