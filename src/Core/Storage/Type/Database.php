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

			return $this->dba->lastInsertId();
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
