<?php

namespace Friendica\Database\Mysqli;

use Friendica\Core\System;
use Friendica\Database\AbstractDBDriver;
use mysqli;
use ParagonIE\HiddenString\HiddenString;

class Driver extends AbstractDBDriver
{
	const TYPE = 'mysqli';

	/** @var mysqli */
	protected $connection;

	protected function connectInternal(string $host, int $port, string $user, HiddenString $password, string $database, string $charset, string $socket): bool
	{
		if ($socket) {
			$this->connection = @new mysqli(null, $user, (string)$password, $database, null, $socket);
		} elseif ($port > 0) {
			$this->connection = @new mysqli($host, $user, (string)$password, $database, $port);
		} else {
			$this->connection = @new mysqli($host, $user, (string)$password, $database);
		}

		if (!mysqli_connect_errno()) {
			$this->connected = true;

			if ($charset) {
				$this->connection->set_charset($charset);
			}
		} else {
			$this->connected = false;
		}

		return $this->connected;
	}

	public function getConnection(): mysqli
	{
		return $this->connection;
	}

	public function lastInsertId(): int
	{
		return (int)$this->connection->insert_id;
	}

	public function transaction(): bool
	{
		if (!$this->performCommit()) {
			return false;
		}

		if (!$this->connection->begin_transaction()) {
			return false;
		}

		$this->inTransaction = true;
		return true;
	}

	protected function performCommit(): bool
	{
		return $this->connection->commit();
	}

	public function rollback(): bool
	{
		$ret = $this->connection->rollback();
		$this->inTransaction = false;

		return $ret;
	}

	public function getDriver(): string
	{
		return self::TYPE;
	}

	public function escape(string $parameter): string
	{
		if ($this->connected) {
			return @$this->connection->real_escape_string($parameter);
		} else {
			return str_replace("'", "\\'", $parameter);
		}
	}

	public function disconnect()
	{
		$this->connection->close();
		$this->connection = null;
		$this->connected  = false;
	}

	public function connected(): bool
	{
		return $this->connected &&
			   $this->connection->ping();
	}

	public function serverInfo(): string
	{
		if (empty($this->serverInfo)) {
			$this->serverInfo = $this->connection->server_info;
		}

		return $this->serverInfo;
	}

	protected function executeInternal(string $sql, array $parameters = [], bool $withCallstack = false)
	{
		// There are SQL statements that cannot be executed with a prepared statement
		$parts           = explode(' ', $sql);
		$command         = strtolower($parts[0]);
		$can_be_prepared = in_array($command, ['select', 'update', 'insert', 'delete']);

		if ($withCallstack) {
			$sql = "/*" . System::callstack() . " */ " . $sql;
		}

		// The fallback routine is called as well when there are no arguments
		if (!$can_be_prepared || (count($parameters) == 0)) {
			$result = $this->connection->query($this->replaceParameters($sql, $parameters));
			if ($this->connection->errno) {
				return Error::fromConnection($this->connection);
			} else {
				return new Result($result, $this->connection);
			}
		}

		$statement = $this->connection->stmt_init();

		if (!$statement->prepare($sql)) {
			return Error::fromStatement($statement);
		}

		$paramTypes = '';
		$values      = [];
		foreach (array_keys($parameters) as $parameter) {
			if (is_int($parameters[$parameter])) {
				$paramTypes .= 'i';
			} elseif (is_float($parameters[$parameter])) {
				$paramTypes .= 'd';
			} elseif (is_string($parameters[$parameter])) {
				$paramTypes .= 's';
			} elseif (is_object($parameters[$parameter]) && method_exists($parameters[$parameter], '__toString')) {
				$paramTypes         .= 's';
				$parameters[$parameter] = (string)$parameters[$parameter];
			} else {
				$paramTypes .= 'b';
			}
			$values[] = &$parameters[$parameter];
		}

		if (count($values) > 0) {
			array_unshift($values, $paramTypes);
			call_user_func_array([$statement, 'bind_param'], $values);
		}

		if (!$statement->execute()) {
			return Error::fromConnection($this->connection);
		} else {
			$statement->store_result();
			return new Result($statement, $this->connection);
		}
	}
}
