<?php

namespace Friendica\Database\PDO;

use Friendica\Database\Capabilities\IDatabaseError;
use Friendica\Database\DBError;
use PDO;
use PDOStatement;

class Error
{
	public static function fromConnection(PDO $connection): IDatabaseError
	{
		$errorInfo = $connection->errorInfo();
		return new DBError(
			(string)$errorInfo[2],
			(int)$errorInfo[1]
		);
	}

	public static function fromStatement(PDOStatement $statement): IDatabaseError
	{
		$errorInfo = $statement->errorInfo();
		return new DBError(
			(string)$errorInfo[2],
			(int)$errorInfo[1]
		);
	}
}
