<?php

namespace Friendica\Database;

use Friendica\Core\Config\Model\ReadOnlyFileConfig;
use Friendica\Database\Capabilities\IDatabaseDriver;
use Friendica\Database\Capabilities\IDatabaseError;
use Friendica\Database\Capabilities\IDatabaseResult;
use Friendica\Util\DateTimeFormat;
use ParagonIE\HiddenString\HiddenString;

abstract class AbstractDBDriver implements IDatabaseDriver
{
	/** @var bool */
	protected $connected = false;
	/** @var string */
	protected $serverInfo = '';
	/** @var ReadOnlyFileConfig */
	protected $config;
	protected $inTransaction = false;

	/**
	 * @param string $sql
	 * @param array  $parameters
	 * @param bool   $withCallstack
	 *
	 * @return IDatabaseResult|IDatabaseError
	 */
	abstract protected function executeInternal(string $sql, array $parameters = [], bool $withCallstack = false);

	/**
	 * @param string       $host
	 * @param int          $port
	 * @param string       $user
	 * @param HiddenString $password
	 * @param string       $database
	 * @param string       $charset
	 * @param string       $socket
	 *
	 * @return bool
	 */
	abstract protected function connectInternal(string $host, int $port, string $user, HiddenString $password, string $database, string $charset, string $socket): bool;

	public function __construct(ReadOnlyFileConfig $config)
	{
		$this->config = $config;
	}

	public function isConnected(): bool
	{
		return $this->connected;
	}

	public function inTransaction(): bool
	{
		return $this->inTransaction;
	}

	public function reconnect(): bool
	{
		$this->connect();
		return $this->connect();
	}

	/** {@inheritDoc} */
	public function connect(): bool
	{
		if (!is_null($this->connection) && $this->connected()) {
			return $this->connected;
		}

		// Reset connected state
		$this->connected = false;

		$port       = 0;
		$serveraddr = trim($this->config->get('database', 'hostname') ?? '');
		$serverdata = explode(':', $serveraddr);
		$host       = trim($serverdata[0]);
		if (count($serverdata) > 1) {
			$port = trim($serverdata[1]);
		}

		if (trim($this->config->get('database', 'port') ?? 0)) {
			$port = trim($this->config->get('database', 'port') ?? 0);
		}

		$user     = trim($this->config->get('database', 'username'));
		$pass     = new HiddenString(trim($this->config->get('database', 'password')));
		$database = trim($this->config->get('database', 'database'));
		$charset  = trim($this->config->get('database', 'charset'));
		$socket   = trim($this->config->get('database', 'socket'));

		if (!$host && !$socket || !$user) {
			return false;
		}

		$this->connected = $this->connectInternal($host, $port, $user, $pass, $database, $charset, $socket);

		return $this->connected;
	}

	public function databaseName(): string
	{
		$result = $this->read("SELECT DATABASE() AS `db`");
		$data   = $result->toArray();
		return $data[0]['db'] ?? '';
	}

	/** {@inheritDoc} */
	public function getVariable(string $name): ?string
	{
		$result = $this->fetchFirst("SHOW GLOBAL VARIABLES WHERE `Variable_name` = ?", $name);
		return $result['Value'] ?? null;
	}

	/**
	 * Replaces ANY_VALUE() function by MIN() function,
	 * if the database server does not support ANY_VALUE().
	 *
	 * Considerations for Standard SQL, or MySQL with ONLY_FULL_GROUP_BY (default since 5.7.5).
	 * ANY_VALUE() is available from MySQL 5.7.5 https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html
	 * A standard fall-back is to use MIN().
	 *
	 * @param string $sql An SQL string without the values
	 *
	 * @return string The input SQL string modified if necessary.
	 */
	protected function anyValueFallback(string $sql): string
	{
		$server_info = $this->serverInfo();
		if (version_compare($server_info, '5.7.5', '<') ||
			(stripos($server_info, 'MariaDB') !== false)) {
			$sql = str_ireplace('ANY_VALUE(', 'MIN(', $sql);
		}
		return $sql;
	}

	public function replaceParameters(string $sql, array $parameters): string
	{
		$offset = 0;
		foreach ($parameters as $param => $value) {
			if (is_int($value) || is_float($value) || is_bool($value)) {
				$replace = intval($value);
			} else if (is_null($value)) {
				$replace = 'NULL';
			} else {
				$replace = "'" . $this->escape($value) . "'";
			}

			$pos = strpos($sql, '?', $offset);
			if ($pos !== false) {
				$sql = substr_replace($sql, $replace, $pos, 1);
			}
			$offset = $pos + strlen($replace);
		}
		return $sql;
	}

	/** {@inheritDoc} */
	public function fetchFirst(string $sql, ...$parameters)
	{
		$stmt = $this->read($sql, $parameters);

		if (is_bool($stmt)) {
			$returnValue = $stmt;
		} else if ($stmt instanceof IDatabaseResult) {
			$returnValue = $stmt->fetch();
			$stmt->close();
		} else {
			$returnValue = false;
		}

		return $returnValue;
	}

	public function setTransaction(bool $inTransaction = false)
	{
		$this->inTransaction = $inTransaction;
	}

	public function commit(): bool
	{
		if (!$this->performCommit()) {
			return false;
		}
		$this->inTransaction = false;
		return true;
	}

	/** {@inheritDoc} */
	public function read(string $sql, ...$parameters)
	{
		return $this->readInternal($sql, $parameters);
	}


	/** {@inheritDoc} */
	public function write(string $sql, ...$parameters): bool
	{
		// In a case of a deadlock we are repeating the query 20 times
		$timeout = 20;

		do {
			$result = $this->readInternal($sql, $parameters, false);

			if ($result instanceof IDatabaseResult) {
				$result->close();
				return true;
			}

		} while (($result instanceof IDatabaseError) && ($result->getErrorNumber() === 1213) && (--$timeout > 0));

		// On a lost connection we simply throw an exception.
		// A reconnect like in $this->read could be dangerous with modifications
		if ($result->getErrorNumber() == 2006) {
			throw new DatabaseException($result->getError(), $result->getErrorNumber(), $this->replaceParameters($sql, $parameters));
		}

		return false;
	}

	/**
	 * @param string $sql
	 * @param array  $parameters
	 * @param bool   $retry
	 *
	 * @return IDatabaseError|IDatabaseResult
	 *
	 * @throws DatabaseException
	 */
	protected function readInternal(string $sql, array $parameters = [], bool $retry = true)
	{
		if (!$this->connected) {
			throw new DatabaseException('Database not connected.', 500, $this->replaceParameters($sql, $parameters));
		}

		if (!isset($this->connection)) {
			throw new DatabaseException('The Connection is empty, although connected is set true.', 500, $this->replaceParameters($sql, $parameters));
		}

		// Renumber the array keys to be sure that they fit
		$i         = 0;
		$arguments = [];
		foreach ($parameters as $parameter) {
			// Avoid problems with some MySQL servers and boolean values. See issue #3645
			if (is_bool($parameter)) {
				$parameter = (int)$parameter;
			}
			$arguments[++$i] = $parameter;
		}

		$sql = DatabaseUtils::cleanQuery($sql);
		$sql = $this->anyValueFallback($sql);

		$result = $this->executeInternal($sql, $arguments, !empty($this->config->get('system', 'db_callstack')));

		if ($result instanceof IDatabaseResult) {
			return $result;
		}

		if ($retry && $result->getErrorNumber() === 2006) {
			if (!$this->reconnect()) {
				throw new DatabaseException($result->getError(), $result->getErrorNumber(), $this->replaceParameters($sql, $arguments));
			}

			// try again
			$result = $this->executeInternal($sql, $arguments, !empty($this->config->get('system', 'db_callstack')));
		}

		return $result;
	}

	/**
	 * Analyze a database query and log this if some conditions are met.
	 *
	 * @param string $query The database query that will be analyzed
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function logIndex(string $query)
	{
		if (!$this->config->get('system', 'db_log_index')) {
			return;
		}

		// Don't explain an explain statement
		if (strtolower(substr($query, 0, 7)) == "explain") {
			return;
		}

		// Only do the explain on "select", "update" and "delete"
		if (!in_array(strtolower(substr($query, 0, 6)), ["select", "update", "delete"])) {
			return;
		}

		$result = $this->read("EXPLAIN " . $query);
		if ($result instanceof IDatabaseError) {
			return;
		}

		$watchlist = explode(',', $this->config->get('system', 'db_log_index_watch'));
		$denylist  = explode(',', $this->config->get('system', 'db_log_index_denylist'));

		while ($row = $result->fetch($result)) {
			if ((intval($this->config->get('system', 'db_loglimit_index')) > 0)) {
				$log = (in_array($row['key'], $watchlist) &&
						($row['rows'] >= intval($this->config->get('system', 'db_loglimit_index'))));
			} else {
				$log = false;
			}

			if ((intval($this->config->get('system', 'db_loglimit_index_high')) > 0) && ($row['rows'] >= intval($this->config->get('system', 'db_loglimit_index_high')))) {
				$log = true;
			}

			if (in_array($row['key'], $denylist) || ($row['key'] == "")) {
				$log = false;
			}

			if ($log) {
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				@file_put_contents(
					$this->config->get('system', 'db_log_index'),
					DateTimeFormat::utcNow() . "\t" .
					$row['key'] . "\t" . $row['rows'] . "\t" . $row['Extra'] . "\t" .
					basename($backtrace[1]["file"]) . "\t" .
					$backtrace[1]["line"] . "\t" . $backtrace[2]["function"] . "\t" .
					substr($query, 0, 4000) . "\n",
					FILE_APPEND
				);
			}
		}
	}
}
