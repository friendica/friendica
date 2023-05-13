<?php

namespace Friendica\Database\PDO;

use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Core\System;
use Friendica\Database\AbstractDBDriver;
use Friendica\Database\Capabilities\IDatabaseDriver;
use Friendica\Database\Capabilities\IDatabaseResult;
use Friendica\Database\DatabaseException;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use ParagonIE\HiddenString\HiddenString;
use PDO;
use PDOException;

class Driver extends AbstractDBDriver implements IDatabaseDriver
{
	public const TYPE = 'PDO';

	/** @var PDO */
	protected $connection;

	/** @var bool */
	protected $pdo_emulate_prepares = false;
	/** @var DbaDefinition  */
	protected $dbaDefinition;
	/** @var ViewDefinition  */
	protected $viewDefinition;

	public function __construct(ReadOnlyFileConfig $config, DbaDefinition $dbaDefinition, ViewDefinition $viewDefinition)
	{
		parent::__construct($config);

		$this->dbaDefinition  = $dbaDefinition;
		$this->viewDefinition = $viewDefinition;
	}

	public function getDriver(): string
	{
		return self::TYPE;
	}

	public function getConnection(): PDO
	{
		return $this->connection;
	}

	public function lastInsertId(): int
	{
		return (int)$this->connection->lastInsertId();
	}

	public function escape(string $parameter): string
	{
		if ($this->connected) {
			return substr(@$this->connection->quote($parameter), 1, -1);
		} else {
			return str_replace("'", "\\'", $parameter);
		}
	}

	protected function connectInternal(string $host, int $port, string $user, HiddenString $password, string $database, string $charset, string $socket): bool
	{
		$persistent = (bool)$this->config->get('database', 'persistent');
		$this->pdo_emulate_prepares = (bool)$this->config->get('database', 'pdo_emulate_prepares');

		if ($socket) {
			$connect = 'mysql:unix_socket=' . $socket;
		} else {
			$connect = 'mysql:host=' . $host;
			if ($port > 0) {
				$connect .= ';port=' . $port;
			}
		}

		if ($charset) {
			$connect .= ';charset=' . $charset;
		}

		$connect .= ';dbname=' . $database;

		try {
			$this->connection = @new PDO($connect, $user, (string)$password, [PDO::ATTR_PERSISTENT => $persistent]);
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->pdo_emulate_prepares);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			$this->connected = true;
		} catch (PDOException $e) {
			$this->connected = false;
		}

		return $this->connected;
	}

	/**
	 * Checks, if the table definition cache is high enough
	 *
	 * @return bool|null true/false if the definition cache is high enough, null if it couldn't get determined
	 *
	 * @throws DatabaseException
	 */
	public function checkSuggestedDefinitionCache(): ?bool
	{
		$table_definition_cache = $this->getVariable('table_definition_cache');
		$table_open_cache = $this->getVariable('table_open_cache');
		if (!empty($table_definition_cache) && !empty($table_open_cache)) {
			$suggested_definition_cache = min(400 + round($table_open_cache / 2, 1), 2000);

			return $suggested_definition_cache > $table_definition_cache;
		}

		return null;
	}

	public function transaction(): bool
	{
		if (!$this->performCommit()) {
			return false;
		}

		if (!$this->connection->inTransaction() && !$this->connection->beginTransaction()) {
			return false;
		}

		$this->inTransaction = true;
		return true;
	}

	protected function performCommit(): bool
	{
		if (!$this->connection->inTransaction()) {
			return true;
		}

		return $this->connection->commit();
	}

	public function rollback(): bool
	{
		if (!$this->connection->inTransaction()) {
			$ret = true;
		} else {
			$ret = $this->connection->rollBack();
		}

		$this->inTransaction = false;
		return $ret;
	}

	public function disconnect()
	{
		$this->connection = null;
		$this->connected  = false;
	}

	public function connected(): bool
	{
		if (!$this->connected) {
			return false;
		}

		$result = $this->read("Select 1");
		if ($result instanceof IDatabaseResult &&
			$row = $result->toArray() ?? []) {
			return $row[0]['1'] ?? false == '1';
		} else {
			return false;
		}
	}

	public function serverInfo(): string
	{
		if (empty($this->serverInfo)) {
			$this->serverInfo = $this->connection->getAttribute(PDO::ATTR_SERVER_INFO);
		}

		return $this->serverInfo;
	}

	protected function executeInternal(string $sql, array $parameters = [], bool $withCallstack = false)
	{
		if ($withCallstack) {
			$sql = "/*" . System::callstack() . " */ " . $sql;
		}

		if (count($parameters) === 0) {
			if ($statement = $this->connection->query($this->replaceParameters($sql, $parameters))) {
				return new Result($statement);
			} else {
				return Error::fromConnection($this->connection);
			}
		}

		if (!$statement = $this->connection->prepare($sql)) {
			return Error::fromConnection($this->connection);
		} else {
			foreach (array_keys($parameters) as $param) {
				$data_type = PDO::PARAM_STR;
				if (is_int($parameters[$param])) {
					$data_type = PDO::PARAM_INT;
				} else if ($parameters[$param] !== null) {
					$parameters[$param] = (string)$parameters[$param];
				}

				$statement->bindParam($param, $parameters[$param], $data_type);
			}

			if (!$statement->execute()) {
				return Error::fromStatement($statement);
			} else {
				return new Result($statement);
			}
		}
	}
}
