<?php

namespace Friendica\Database\Mysqli;

use Friendica\Database\Capabilities\IDatabaseError;
use Friendica\Database\DBError;
use mysqli;
use mysqli_stmt;

class Error
{
	public static function fromConnection(mysqli $connection): IDatabaseError
	{
		return new DBError(
			$connection->error,
			$connection->errno
		);
	}

	public static function fromStatement(mysqli_stmt $statement): IDatabaseError
	{
		return new DBError(
			$statement->error,
			$statement->errno
		);
	}
}
