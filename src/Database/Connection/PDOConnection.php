<?php

namespace Friendica\Database\Connection;

use PDO;
use PDOStatement;

class PDOConnection extends AbstractConnection implements IConnection
{
	/**
	 * The connection to the database
	 *
	 * @var PDO?
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

		$connect = "mysql:host=" . $this->dbHost . ";dbname=" . $this->dbName;

		if ($this->dbPort > 0) {
			$connect .= ";port=" . $this->dbPort;
		}

		if ($this->dbCharset) {
			$connect .= ";charset=" . $this->dbCharset;
		}

		try {
			$this->connection = @new PDO($connect, $this->dbUser, $this->dbPass);
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->isConnected = true;
			return true;
		} catch (\PDOException $e) {
			/// @TODO At least log exception, don't ignore it!
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

		return $this->reconnect();
	}

	/**
	 * {@inheritDoc}
	 */
	function disconnect()
	{
		if (!isset($this->connection)) {
			return;
		}

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
	function getServerInfo()
	{
		$this->serverInfo = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	/**
	 * {@inheritDoc}
	 */
	public function escape($sql)
	{
		return substr(@$this->connection->quote($sql, PDO::PARAM_STR), 1, -1);
	}

	/**
	 * {@inheritDoc}
	 * @param PDOStatement $stmt
	 */
	public function closeStatement($stmt)
	{
		return $stmt->closeCursor();
	}

	/**
	 * {@inheritDoc}
	 */
	public function performCommit()
	{
		if (!$this->connection->inTransaction()) {
			return true;
		}

		return $this->connection->commit();
	}

	/**
	 * {@inheritDoc}
	 */
	public function startTransaction()
	{
		!$this->connection->inTransaction() && !$this->connection->beginTransaction();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param PDOStatement $stmt
	 */
	public function fetch($stmt)
	{
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * {@inheritDoc}
	 */
	public function executePrepared($sql, array $args = [])
	{
		// If there are no arguments we use "query"
		if (count($args) == 0) {
			if (!$retval = $this->connection->query($sql)) {
				$errorInfo = $this->connection->errorInfo();
				throw new ConnectionException($errorInfo[2], $errorInfo[1]);
			}
			return $retval;
		}

		if (!$stmt = $this->connection->prepare($sql)) {
			$errorInfo = $this->connection->errorInfo();
			throw new ConnectionException($errorInfo[2], $errorInfo[1]);
		}

		foreach ($args AS $param => $value) {
			if (is_int($args[$param])) {
				$data_type = PDO::PARAM_INT;
			} else {
				$data_type = PDO::PARAM_STR;
			}
			$stmt->bindParam($param, $args[$param], $data_type);
		}

		if (!$stmt->execute()) {
			$errorInfo = $stmt->errorInfo();
			throw new ConnectionException($errorInfo[2], $errorInfo[1]);
		} else {
			return $stmt;
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param PDOStatement
	 */
	public function getNumRows($stmt)
	{
		if ($stmt instanceof PDOStatement) {
			return $stmt->rowCount();
		} else {
			throw new ConnectionException('Wrong statement for this connection');
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function rollbackTransaction()
	{
		if (!$this->connection->inTransaction()) {
			return true;
		}

		return $this->connection->rollBack();
	}
}
