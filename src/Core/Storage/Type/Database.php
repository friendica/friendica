<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Storage\Type;

use Exception;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Database\Database as DBA;

/**
 * Database based storage system
 *
 * This class manage data stored in database table.
 */
class Database implements ICanWriteToStorage
{
	const NAME = 'Database';

	/** @var DBA */
	private $dba;

	/**
	 * @param DBA             $dba
	 */
	public function __construct(DBA $dba)
	{
		$this->dba = $dba;
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $reference): string
	{
		try {
			$result = $this->dba->selectFirst('storage', ['data'], ['id' => $reference]);
			if (!$this->dba->isResult($result)) {
				throw new ReferenceStorageException(sprintf('Database storage cannot find data for reference %s', $reference));
			}

			return $result['data'];
		} catch (Exception $exception) {
			if ($exception instanceof ReferenceStorageException) {
				throw $exception;
			} else {
				throw new StorageException(sprintf('Database storage failed to get %s', $reference), $exception->getCode(), $exception);
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function put(string $data, string $reference = ''): string
	{
		if ($reference !== '') {
			try {
				$result = $this->dba->update('storage', ['data' => $data], ['id' => $reference]);
			} catch (Exception $exception) {
				throw new StorageException(sprintf('Database storage failed to update %s', $reference), $exception->getCode(), $exception);
			}
			if ($result === false) {
				throw new StorageException(sprintf('Database storage failed to update %s', $reference), 500, new Exception($this->dba->errorMessage(), $this->dba->errorNo()));
			}

			return $reference;
		} else {
			try {
				$result = $this->dba->insert('storage', ['data' => $data]);
			} catch (Exception $exception) {
				throw new StorageException(sprintf('Database storage failed to insert %s', $reference), $exception->getCode(), $exception);
			}
			if ($result === false) {
				throw new StorageException(sprintf('Database storage failed to update %s', $reference), 500, new Exception($this->dba->errorMessage(), $this->dba->errorNo()));
			}

			return (string) $this->dba->lastInsertId();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function delete(string $reference)
	{
		try {
			if (!$this->dba->delete('storage', ['id' => $reference]) || $this->dba->affectedRows() === 0) {
				throw new ReferenceStorageException(sprintf('Database storage failed to delete %s', $reference));
			}
		} catch (Exception $exception) {
			if ($exception instanceof ReferenceStorageException) {
				throw $exception;
			} else {
				throw new StorageException(sprintf('Database storage failed to delete %s', $reference), $exception->getCode(), $exception);
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public static function getName(): string
	{
		return self::NAME;
	}

	public function __toString(): string
	{
		return self::getName();
	}
}
