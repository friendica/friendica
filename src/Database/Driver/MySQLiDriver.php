<?php

namespace Friendica\Database\Driver;

use mysqli;
use mysqli_result;
use mysqli_stmt;

class MySQLiDriver extends AbstractDriver implements IDriver
{
	/**
	 * The connection to the database
	 * @var mysqli?
	 */
	private $connection = null;

	/**
	 * {@inheritDoc}
	 */
	public function connect()
	{
		if ($this->isConnected()) {
			return true;
		}

		if ($this->dbPort > 0) {
			$this->connection = @new mysqli($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName, $this->dbPort);
		} else {
			$this->connection = @new mysqli($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);
		}

		if (!mysqli_connect_errno()) {
			$this->isConnected = true;

			if ($this->dbCharset) {
				$this->connection->set_charset($this->dbCharset);
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	function isConnected($force = false)
	{
		if (!$force) {
			return $this->isConnected;
		}

		if (!isset($this->connection)) {
			return false;
		}

		return $this->connection->ping();
	}

	/**
	 * {@inheritDoc}
	 */
	function disconnect()
	{
		if (!isset($this->connection)) {
			return;
		}

		$this->connection->close();
		$this->connection = null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function reconnect()
	{
		$this->disconnect();
		return $this->connect();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getServerInfo()
	{
		return $this->connection->server_info;
	}

	/**
	 * {@inheritDoc}
	 */
	public function escape($sql)
	{
		return @$this->connection->real_escape_string($sql);
	}

	/**
	 * {@inheritDoc}
	 * @param mysqli_stmt|mysqli_result
	 */
	public function closeStatement($stmt)
	{
		// MySQLi offers both a mysqli_stmt and a mysqli_result class.
		// We should be careful not to assume the object type of $stmt
		// because DBA::p() has been able to return both types.
		if ($stmt instanceof mysqli_stmt) {
			$stmt->free_result();
			return $stmt->close();
		} elseif ($stmt instanceof mysqli_result) {
			$stmt->free();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function performCommit()
	{
		return $this->connection->commit();
	}

	/**
	 * {@inheritDoc}
	 */
	public function startTransaction()
	{
		return $this->connection->begin_transaction();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mysqli_stmt|mysqli_result
	 *
	 * @throws DriverException In case of a wrong statement
	 */
	public function fetch($stmt)
	{
		if ($stmt instanceof mysqli_result) {
			return $stmt->fetch_assoc();
		}

		if ($stmt instanceof mysqli_stmt) {
			// This code works, but is slow

			// Bind the result to a result array
			$cols = [];

			$cols_num = [];
			for ($x = 0; $x < $stmt->field_count; $x++) {
				$cols[] = &$cols_num[$x];
			}

			call_user_func_array([$stmt, 'bind_result'], $cols);

			if (!$stmt->fetch()) {
				return false;
			}

			// The slow part:
			// We need to get the field names for the array keys
			// It seems that there is no better way to do this.
			$result = $stmt->result_metadata();
			$fields = $result->fetch_fields();

			foreach ($cols_num AS $param => $col) {
				$columns[$fields[$param]->name] = $col;
			}
		}

		throw new DriverException('Wrong statement for this connection');
	}

	/**
	 * {@inheritDoc}
	 */
	public function executePrepared($sql, array $args = [])
	{
		// There are SQL statements that cannot be executed with a prepared statement
		$parts = explode(' ', $sql);
		$command = strtolower($parts[0]);
		$can_be_prepared = in_array($command, ['select', 'update', 'insert', 'delete']);

		// The fallback routine is called as well when there are no arguments
		if (!$can_be_prepared || (count($args) == 0)) {

			$retval = $this->connection->query($this->replaceParameters($sql, $args));

			if ($this->connection->errno) {
				throw new DriverException($this->connection->error, $this->connection->errno);
			}

			return $retval;
		}

		$stmt = $this->connection->stmt_init();

		if (!$stmt->prepare($sql)) {
			throw new DriverException($this->connection->error, $this->connection->errno);
		}

		$param_types = '';
		$values = [];
		foreach ($args AS $param => $value) {
			if (is_int($args[$param])) {
				$param_types .= 'i';
			} elseif (is_float($args[$param])) {
				$param_types .= 'd';
			} elseif (is_string($args[$param])) {
				$param_types .= 's';
			} else {
				$param_types .= 'b';
			}
			$values[] = &$args[$param];
		}

		if (count($values) > 0) {
			array_unshift($values, $param_types);
			call_user_func_array([$stmt, 'bind_param'], $values);
		}

		if (!$stmt->execute()) {
			throw new DriverException($this->connection->error, $this->connection->errno);
		} else {
			$stmt->store_result();
			return $stmt;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mysqli_stmt|mysqli_result
	 */
	public function getNumRows($stmt)
	{
		if ($stmt instanceof mysqli_stmt) {
			if (!isset($stmt->num_rows)) {
				return $this->connection->affected_rows;
			} else {
				return $stmt->num_rows;
			}
		} elseif ($stmt instanceof mysqli_result) {
			if (!isset($stmt->num_rows)) {
				return $this->connection->affected_rows;
			} else {
				return $stmt->num_rows;
			}
		} else {
			throw new DriverException('Wrong statement for this connection');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function rollbackTransaction()
	{
		return $this->connection->rollback();
	}
}
