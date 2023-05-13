<?php

namespace Friendica\Database\PDO;

use Friendica\Database\Capabilities\IDatabaseLock;
use Friendica\Database\DatabaseUtils;
use PDO;

class Lock implements IDatabaseLock
{
	/** @var Driver */
	protected $connection;

	public function __construct(Driver $connection)
	{
		if (!extension_loaded('ext-pdo')) {
			throw new \RuntimeException('PDO extension is not installed');
		}

		$this->connection = $connection;
	}

	public function lock(string $table): bool
	{
		$this->connection->write("SET autocommit=0");
		$pdoEmulatePrepare = $this->connection->getConnection()->getAttribute(PDO::ATTR_EMULATE_PREPARES);
		$this->connection->getConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		$success = $this->connection->write("LOCK TABLES " . DatabaseUtils::buildTableString([$table]) . " WRITE");

		$this->connection->getConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, $pdoEmulatePrepare);

		if (!$success) {
			$this->connection->write("SET autocommit=1");
		} else {
			$this->connection->setTransaction(true);
		}

		return $success;
	}

	public function unlock(): bool
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		$this->connection->commit();

		$pdoEmulatePrepare = $this->connection->getConnection()->getAttribute(PDO::ATTR_EMULATE_PREPARES);
		$this->connection->getConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

		$success = $this->connection->write("UNLOCK LOCK TABLES");

		$this->connection->getConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, $pdoEmulatePrepare);
		$this->connection->write("SET autocommit=1");

		$this->connection->setTransaction(false);

		return $success;
	}
}
