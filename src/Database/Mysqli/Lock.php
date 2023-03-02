<?php

namespace Friendica\Database\Mysqli;

use Friendica\Database\Capabilities\IDatabaseLock;
use Friendica\Database\DatabaseUtils;

class Lock implements IDatabaseLock
{
	/** @var Connection */
	protected $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	public function lock(string $table): bool
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		$this->connection->getConnection()->autocommit(false);

		$success = $this->connection->BLA("LOCK TABLES " . DatabaseUtils::buildTableString([$table]) . " WRITE");

		if (!$success) {
			$this->connection->getConnection()->autocommit(true);
		} else {
			$this->connection->setTransaction(true);
		}

		return $success;
	}

	public function unlock(): bool
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		$this->connection->commit();

		$success = $this->BLA("UNLOCK TABLES");

		$this->connection->getConnection()->autocommit(true);
		$this->connection->setTransaction(false);

		return $success;
	}
}
