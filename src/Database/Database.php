<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Database;

use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Core\System;
use Friendica\Database\Definition\DbaDefinition;
use Friendica\Database\Definition\ViewDefinition;
use Friendica\Network\HTTPException\ServiceUnavailableException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use InvalidArgumentException;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * This class is for the low level database stuff that does driver specific things.
 */
class Database
{
	const PDO    = 'pdo';
	const MYSQLI = 'mysqli';

	const INSERT_DEFAULT = 0;
	const INSERT_UPDATE  = 1;
	const INSERT_IGNORE  = 2;

	protected $connected = false;

	/**
	 * @var \Friendica\Core\Config\ValueObject\Cache
	 */
	protected $configCache;
	/**
	 * @var Profiler
	 */
	protected $profiler;
	/**
	 * @var LoggerInterface
	 */
	protected $logger;
	protected $server_info = '';
	/** @var PDO|mysqli */
	protected $connection;
	protected $driver = '';
	protected $pdo_emulate_prepares = false;
	private $error = '';
	private $errorno = 0;
	private $affected_rows = 0;
	protected $in_transaction = false;
	protected $in_retrial = false;
	protected $testmode = false;
	private $relation = [];
	/** @var DbaDefinition */
	protected $dbaDefinition;
	/** @var ViewDefinition */
	protected $viewDefinition;

	public function __construct(Cache $configCache, Profiler $profiler, DbaDefinition $dbaDefinition, ViewDefinition $viewDefinition)
	{
		// We are storing these values for being able to perform a reconnect
		$this->configCache    = $configCache;
		$this->profiler       = $profiler;
		$this->dbaDefinition  = $dbaDefinition;
		$this->viewDefinition = $viewDefinition;

		// Temporary NullLogger until we can fetch the logger class from the config
		$this->logger = new NullLogger();

		$this->connect();
	}

	/**
	 * Tries to connect to database
	 *
	 * @return bool Success
	 */
	public function connect(): bool
	{
		if (!is_null($this->connection) && $this->connected()) {
			return $this->connected;
		}

		// Reset connected state
		$this->connected = false;

		$port       = 0;
		$serveraddr = trim($this->configCache->get('database', 'hostname') ?? '');
		$serverdata = explode(':', $serveraddr);
		$host       = trim($serverdata[0]);
		if (count($serverdata) > 1) {
			$port = trim($serverdata[1]);
		}

		if (trim($this->configCache->get('database', 'port') ?? 0)) {
			$port = trim($this->configCache->get('database', 'port') ?? 0);
		}

		$user     = trim($this->configCache->get('database', 'username'));
		$pass     = trim($this->configCache->get('database', 'password'));
		$database = trim($this->configCache->get('database', 'database'));
		$charset  = trim($this->configCache->get('database', 'charset'));
		$socket   = trim($this->configCache->get('database', 'socket'));

		if (!$host && !$socket || !$user) {
			return false;
		}

		$persistent = (bool)$this->configCache->get('database', 'persistent');

		$this->pdo_emulate_prepares = (bool)$this->configCache->get('database', 'pdo_emulate_prepares');

		if (!$this->configCache->get('database', 'disable_pdo') && class_exists('\PDO') && in_array('mysql', PDO::getAvailableDrivers())) {
			$this->driver = self::PDO;
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
				$this->connection = @new PDO($connect, $user, $pass, [PDO::ATTR_PERSISTENT => $persistent]);
				$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->pdo_emulate_prepares);
				$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
				$this->connected = true;
			} catch (PDOException $e) {
				$this->connected = false;
			}
		}

		if (!$this->connected && class_exists('\mysqli')) {
			$this->driver = self::MYSQLI;

			if ($socket) {
				$this->connection = @new mysqli(null, $user, $pass, $database, null, $socket);
			} elseif ($port > 0) {
				$this->connection = @new mysqli($host, $user, $pass, $database, $port);
			} else {
				$this->connection = @new mysqli($host, $user, $pass, $database);
			}

			if (!mysqli_connect_errno()) {
				$this->connected = true;

				if ($charset) {
					$this->connection->set_charset($charset);
				}
			}
		}

		// No suitable SQL driver was found.
		if (!$this->connected) {
			$this->driver     = '';
			$this->connection = null;
		}

		return $this->connected;
	}

	public function setTestmode(bool $test)
	{
		$this->testmode = $test;
	}

	/**
	 * Sets the logger for DBA
	 *
	 * @note this is necessary because if we want to load the logger configuration
	 *       from the DB, but there's an error, we would print out an exception.
	 *       So the logger gets updated after the logger configuration can be retrieved
	 *       from the database
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * Sets the profiler for DBA
	 *
	 * @param Profiler $profiler
	 */
	public function setProfiler(Profiler $profiler)
	{
		$this->profiler = $profiler;
	}

	/**
	 * Disconnects the current database connection
	 */
	public function disconnect()
	{
		if (!is_null($this->connection)) {
			switch ($this->driver) {
				case self::PDO:
					$this->connection = null;
					break;
				case self::MYSQLI:
					$this->connection->close();
					$this->connection = null;
					break;
			}
		}

		$this->driver    = '';
		$this->connected = false;
	}

	/**
	 * Perform a reconnect of an existing database connection
	 */
	public function reconnect()
	{
		$this->disconnect();
		return $this->connect();
	}

	/**
	 * Return the database object.
	 *
	 * @return PDO|mysqli
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Return the database driver string
	 *
	 * @return string with either "pdo" or "mysqli"
	 */
	public function getDriver(): string
	{
		return $this->driver;
	}

	/**
	 * Returns the MySQL server version string
	 *
	 * This function discriminate between the deprecated mysql API and the current
	 * object-oriented mysqli API. Example of returned string: 5.5.46-0+deb8u1
	 *
	 * @return string Database server information
	 */
	public function serverInfo(): string
	{
		if ($this->server_info == '') {
			switch ($this->driver) {
				case self::PDO:
					$this->server_info = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
					break;
				case self::MYSQLI:
					$this->server_info = $this->connection->server_info;
					break;
			}
		}
		return $this->server_info;
	}

	/**
	 * Returns the selected database name
	 *
	 * @return string Database name
	 * @throws \Exception
	 */
	public function databaseName(): string
	{
		$ret  = $this->p("SELECT DATABASE() AS `db`");
		$data = $this->toArray($ret);
		return $data[0]['db'];
	}

	/**
	 * Analyze a database query and log this if some conditions are met.
	 *
	 * @param string $query The database query that will be analyzed
	 * @return void
	 * @throws \Exception
	 */
	private function logIndex(string $query)
	{

		if (!$this->configCache->get('system', 'db_log_index')) {
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

		$r = $this->p("EXPLAIN " . $query);
		if (!$this->isResult($r)) {
			return;
		}

		$watchlist = explode(',', $this->configCache->get('system', 'db_log_index_watch'));
		$denylist  = explode(',', $this->configCache->get('system', 'db_log_index_denylist'));

		while ($row = $this->fetch($r)) {
			if ((intval($this->configCache->get('system', 'db_loglimit_index')) > 0)) {
				$log = (in_array($row['key'], $watchlist) &&
					($row['rows'] >= intval($this->configCache->get('system', 'db_loglimit_index'))));
			} else {
				$log = false;
			}

			if ((intval($this->configCache->get('system', 'db_loglimit_index_high')) > 0) && ($row['rows'] >= intval($this->configCache->get('system', 'db_loglimit_index_high')))) {
				$log = true;
			}

			if (in_array($row['key'], $denylist) || ($row['key'] == "")) {
				$log = false;
			}

			if ($log) {
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				@file_put_contents(
					$this->configCache->get('system', 'db_log_index'),
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

	/**
	 * Removes every not allowlisted character from the identifier string
	 *
	 * @param string $identifier
	 * @return string sanitized identifier
	 * @throws \Exception
	 */
	private function sanitizeIdentifier(string $identifier): string
	{
		return preg_replace('/[^A-Za-z0-9_\-]+/', '', $identifier);
	}

	public function escape($str)
	{
		if ($this->connected) {
			switch ($this->driver) {
				case self::PDO:
					return substr(@$this->connection->quote($str, PDO::PARAM_STR), 1, -1);

				case self::MYSQLI:
					return @$this->connection->real_escape_string($str);
			}
		} else {
			return str_replace("'", "\\'", $str);
		}
	}

	/**
	 * Returns connected flag
	 *
	 * @return bool Whether connection to database was success
	 */
	public function isConnected(): bool
	{
		return $this->connected;
	}

	/**
	 * Checks connection status
	 *
	 * @return bool Whether connection to database was success
	 */
	public function connected()
	{
		$connected = false;

		if (is_null($this->connection)) {
			return false;
		}

		switch ($this->driver) {
			case self::PDO:
				$r = $this->p("SELECT 1");
				if ($this->isResult($r)) {
					$row       = $this->toArray($r);
					$connected = ($row[0]['1'] == '1');
				}
				break;
			case self::MYSQLI:
				$connected = $this->connection->ping();
				break;
		}

		return $connected;
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
	public function anyValueFallback(string $sql): string
	{
		$server_info = $this->serverInfo();
		if (version_compare($server_info, '5.7.5', '<') ||
			(stripos($server_info, 'MariaDB') !== false)) {
			$sql = str_ireplace('ANY_VALUE(', 'MIN(', $sql);
		}
		return $sql;
	}

	/**
	 * Replaces the ? placeholders with the parameters in the $args array
	 *
	 * @param string $sql  SQL query
	 * @param array  $args The parameters that are to replace the ? placeholders
	 *
	 * @return string The replaced SQL query
	 */
	private function replaceParameters(string $sql, array $args): string
	{
		$offset = 0;
		foreach ($args as $param => $value) {
			if (is_int($args[$param]) || is_float($args[$param]) || is_bool($args[$param])) {
				$replace = intval($args[$param]);
			} elseif (is_null($args[$param])) {
				$replace = 'NULL';
			} else {
				$replace = "'" . $this->escape($args[$param]) . "'";
			}

			$pos = strpos($sql, '?', $offset);
			if ($pos !== false) {
				$sql = substr_replace($sql, $replace, $pos, 1);
			}
			$offset = $pos + strlen($replace);
		}
		return $sql;
	}

	/**
	 * Executes a prepared statement that returns data
	 *
	 * @usage Example: $r = p("SELECT * FROM `post` WHERE `guid` = ?", $guid);
	 *
	 * Please only use it with complicated queries.
	 * For all regular queries please use DBA::select or DBA::exists
	 *
	 * @param string $sql SQL statement
	 *
	 * @return bool|object statement object or result object
	 * @throws \Exception
	 */
	public function p(string $sql)
	{

		$this->profiler->startRecording('database');
		$stamp1 = microtime(true);

		$params = DBA::getParam(func_get_args());

		// Renumber the array keys to be sure that they fit
		$i    = 0;
		$args = [];
		foreach ($params as $param) {
			// Avoid problems with some MySQL servers and boolean values. See issue #3645
			if (is_bool($param)) {
				$param = (int)$param;
			}
			$args[++$i] = $param;
		}

		if (!$this->connected) {
			return false;
		}

		if ((substr_count($sql, '?') != count($args)) && (count($args) > 0)) {
			// Question: Should we continue or stop the query here?
			$this->logger->warning('Query parameters mismatch.', ['query' => $sql, 'args' => $args, 'callstack' => System::callstack()]);
		}

		$sql = DBA::cleanQuery($sql);
		$sql = $this->anyValueFallback($sql);

		$orig_sql = $sql;

		if ($this->configCache->get('system', 'db_callstack') !== null) {
			$sql = "/*" . System::callstack() . " */ " . $sql;
		}

		$is_error            = false;
		$this->error         = '';
		$this->errorno       = 0;
		$this->affected_rows = 0;

		// We have to make some things different if this function is called from "e"
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		if (isset($trace[1])) {
			$called_from = $trace[1];
		} else {
			// We use just something that is defined to avoid warnings
			$called_from = $trace[0];
		}
		// We are having an own error logging in the function "e"
		$called_from_e = ($called_from['function'] == 'e');

		if (!isset($this->connection)) {
			throw new ServiceUnavailableException('The Connection is empty, although connected is set true.');
		}

		switch ($this->driver) {
			case self::PDO:
				// If there are no arguments we use "query"
				if (count($args) == 0) {
					if (!$retval = $this->connection->query($this->replaceParameters($sql, $args))) {
						$errorInfo     = $this->connection->errorInfo();
						$this->error   = (string)$errorInfo[2];
						$this->errorno = (int)$errorInfo[1];
						$retval        = false;
						$is_error      = true;
						break;
					}
					$this->affected_rows = $retval->rowCount();
					break;
				}

				/** @var $stmt mysqli_stmt|PDOStatement */
				if (!$stmt = $this->connection->prepare($sql)) {
					$errorInfo     = $this->connection->errorInfo();
					$this->error   = (string)$errorInfo[2];
					$this->errorno = (int)$errorInfo[1];
					$retval        = false;
					$is_error      = true;
					break;
				}

				foreach (array_keys($args) as $param) {
					$data_type = PDO::PARAM_STR;
					if (is_int($args[$param])) {
						$data_type = PDO::PARAM_INT;
					} elseif ($args[$param] !== null) {
						$args[$param] = (string)$args[$param];
					}

					$stmt->bindParam($param, $args[$param], $data_type);
				}

				if (!$stmt->execute()) {
					$errorInfo     = $stmt->errorInfo();
					$this->error   = (string)$errorInfo[2];
					$this->errorno = (int)$errorInfo[1];
					$retval        = false;
					$is_error      = true;
				} else {
					$retval              = $stmt;
					$this->affected_rows = $retval->rowCount();
				}
				break;
			case self::MYSQLI:
				// There are SQL statements that cannot be executed with a prepared statement
				$parts           = explode(' ', $orig_sql);
				$command         = strtolower($parts[0]);
				$can_be_prepared = in_array($command, ['select', 'update', 'insert', 'delete']);

				// The fallback routine is called as well when there are no arguments
				if (!$can_be_prepared || (count($args) == 0)) {
					$retval = $this->connection->query($this->replaceParameters($sql, $args));
					if ($this->connection->errno) {
						$this->error   = (string)$this->connection->error;
						$this->errorno = (int)$this->connection->errno;
						$retval        = false;
						$is_error      = true;
					} else {
						if (isset($retval->num_rows)) {
							$this->affected_rows = $retval->num_rows;
						} else {
							$this->affected_rows = $this->connection->affected_rows;
						}
					}
					break;
				}

				$stmt = $this->connection->stmt_init();

				if (!$stmt->prepare($sql)) {
					$this->error   = (string)$stmt->error;
					$this->errorno = (int)$stmt->errno;
					$retval        = false;
					$is_error      = true;
					break;
				}

				$param_types = '';
				$values      = [];
				foreach (array_keys($args) as $param) {
					if (is_int($args[$param])) {
						$param_types .= 'i';
					} elseif (is_float($args[$param])) {
						$param_types .= 'd';
					} elseif (is_string($args[$param])) {
						$param_types .= 's';
					} elseif (is_object($args[$param]) && method_exists($args[$param], '__toString')) {
						$param_types  .= 's';
						$args[$param] = (string)$args[$param];
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
					$this->error   = (string)$this->connection->error;
					$this->errorno = (int)$this->connection->errno;
					$retval        = false;
					$is_error      = true;
				} else {
					$stmt->store_result();
					$retval              = $stmt;
					$this->affected_rows = $retval->affected_rows;
				}
				break;
		}

		// See issue https://github.com/friendica/friendica/issues/8572
		// Ensure that we always get an error message on an error.
		if ($is_error && empty($this->errorno)) {
			$this->errorno = -1;
		}

		if ($is_error && empty($this->error)) {
			$this->error = 'Unknown database error';
		}

		// We are having an own error logging in the function "e"
		if (($this->errorno != 0) && !$called_from_e) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$error   = $this->error;
			$errorno = $this->errorno;

			if ($this->testmode) {
				throw new DatabaseException($error, $errorno, $this->replaceParameters($sql, $args));
			}

			$this->logger->error('DB Error', [
				'code'      => $errorno,
				'error'     => $error,
				'callstack' => System::callstack(8),
				'params'    => $this->replaceParameters($sql, $args),
			]);

			// On a lost connection we try to reconnect - but only once.
			if ($errorno == 2006) {
				if ($this->in_retrial || !$this->reconnect()) {
					// It doesn't make sense to continue when the database connection was lost
					if ($this->in_retrial) {
						$this->logger->notice('Giving up retrial because of database error', [
							'code'  => $errorno,
							'error' => $error,
						]);
					} else {
						$this->logger->notice('Couldn\'t reconnect after database error', [
							'code'  => $errorno,
							'error' => $error,
						]);
					}
					exit(1);
				} else {
					// We try it again
					$this->logger->notice('Reconnected after database error', [
						'code'  => $errorno,
						'error' => $error,
					]);
					$this->in_retrial = true;
					$ret              = $this->p($sql, $args);
					$this->in_retrial = false;
					return $ret;
				}
			}

			$this->error   = (string)$error;
			$this->errorno = (int)$errorno;
		}

		$this->profiler->stopRecording();

		if ($this->configCache->get('system', 'db_log')) {
			$stamp2   = microtime(true);
			$duration = (float)($stamp2 - $stamp1);

			if (($duration > $this->configCache->get('system', 'db_loglimit'))) {
				$duration  = round($duration, 3);
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				@file_put_contents(
					$this->configCache->get('system', 'db_log'),
					DateTimeFormat::utcNow() . "\t" . $duration . "\t" .
					basename($backtrace[1]["file"]) . "\t" .
					$backtrace[1]["line"] . "\t" . $backtrace[2]["function"] . "\t" .
					substr($this->replaceParameters($sql, $args), 0, 4000) . "\n",
					FILE_APPEND
				);
			}
		}
		return $retval;
	}

	/**
	 * Executes a prepared statement like UPDATE or INSERT that doesn't return data
	 *
	 * Please use DBA::delete, DBA::insert, DBA::update, ... instead
	 *
	 * @param string $sql SQL statement
	 *
	 * @return boolean Was the query successfull? False is returned only if an error occurred
	 * @throws \Exception
	 */
	public function e(string $sql): bool
	{
		$retval = false;

		$this->profiler->startRecording('database_write');

		$params = DBA::getParam(func_get_args());

		// In a case of a deadlock we are repeating the query 20 times
		$timeout = 20;

		do {
			$stmt = $this->p($sql, $params);

			if (is_bool($stmt)) {
				$retval = $stmt;
			} elseif (is_object($stmt)) {
				$retval = true;
			} else {
				$retval = false;
			}

			$this->close($stmt);

		} while (($this->errorno == 1213) && (--$timeout > 0));

		if ($this->errorno != 0) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$error   = $this->error;
			$errorno = $this->errorno;

			if ($this->testmode) {
				throw new DatabaseException($error, $errorno, $this->replaceParameters($sql, $params));
			}

			$this->logger->error('DB Error', [
				'code'      => $errorno,
				'error'     => $error,
				'callstack' => System::callstack(8),
				'params'    => $this->replaceParameters($sql, $params),
			]);

			// On a lost connection we simply quit.
			// A reconnect like in $this->p could be dangerous with modifications
			if ($errorno == 2006) {
				$this->logger->notice('Giving up because of database error', [
					'code'  => $errorno,
					'error' => $error,
				]);
				exit(1);
			}

			$this->error   = $error;
			$this->errorno = $errorno;
		}

		$this->profiler->stopRecording();

		return $retval;
	}

	/**
	 * Check if data exists
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $condition Array of fields for condition
	 *
	 * @return boolean Are there rows for that condition?
	 * @throws \Exception
	 * @todo Please unwrap the DBStructure::existsTable() call so this method has one behavior only: checking existence on records
	 */
	public function exists(string $table, array $condition): bool
	{
		if (empty($table)) {
			return false;
		}

		$fields = [];

		if (empty($condition)) {
			return DBStructure::existsTable($table);
		}

		reset($condition);
		$first_key = key($condition);
		if (!is_int($first_key)) {
			$fields = [$first_key];
		}

		$stmt = $this->select($table, $fields, $condition, ['limit' => 1]);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = ($this->numRows($stmt) > 0);
		}

		$this->close($stmt);

		return $retval;
	}

	/**
	 * Fetches the first row
	 *
	 * Please use DBA::selectFirst or DBA::exists whenever this is possible.
	 *
	 * Fetches the first row
	 *
	 * @param string $sql SQL statement
	 *
	 * @return array|bool first row of query or false on failure
	 * @throws \Exception
	 */
	public function fetchFirst(string $sql)
	{
		$params = DBA::getParam(func_get_args());

		$stmt = $this->p($sql, $params);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = $this->fetch($stmt);
		}

		$this->close($stmt);

		return $retval;
	}

	/**
	 * Returns the number of affected rows of the last statement
	 *
	 * @return int Number of rows
	 */
	public function affectedRows(): int
	{
		return $this->affected_rows;
	}

	/**
	 * Returns the number of columns of a statement
	 *
	 * @param object Statement object
	 *
	 * @return int Number of columns
	 */
	public function columnCount($stmt): int
	{
		if (!is_object($stmt)) {
			return 0;
		}
		switch ($this->driver) {
			case self::PDO:
				return $stmt->columnCount();
			case self::MYSQLI:
				return $stmt->field_count;
		}
		return 0;
	}

	/**
	 * Returns the number of rows of a statement
	 *
	 * @param PDOStatement|mysqli_result|mysqli_stmt Statement object
	 *
	 * @return int Number of rows
	 */
	public function numRows($stmt): int
	{
		if (!is_object($stmt)) {
			return 0;
		}
		switch ($this->driver) {
			case self::PDO:
				return $stmt->rowCount();
			case self::MYSQLI:
				return $stmt->num_rows;
		}
		return 0;
	}

	/**
	 * Fetch a single row
	 *
	 * @param bool|PDOStatement|mysqli_stmt $stmt statement object
	 *
	 * @return array|bool Current row or false on failure
	 */
	public function fetch($stmt)
	{
		$this->profiler->startRecording('database');

		$columns = [];

		if (!is_object($stmt)) {
			return false;
		}

		switch ($this->driver) {
			case self::PDO:
				$columns = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!empty($stmt->table) && is_array($columns)) {
					$columns = $this->castFields($stmt->table, $columns);
				}
				break;
			case self::MYSQLI:
				if (get_class($stmt) == 'mysqli_result') {
					$columns = $stmt->fetch_assoc() ?? false;
					break;
				}

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

				foreach ($cols_num as $param => $col) {
					$columns[$fields[$param]->name] = $col;
				}
		}

		$this->profiler->stopRecording();

		return $columns;
	}

	/**
	 * Insert a row into a table. Field value objects will be cast as string.
	 *
	 * @param string $table          Table name in format [schema.]table
	 * @param array  $param          parameter array
	 * @param int    $duplicate_mode What to do on a duplicated entry
	 *
	 * @return boolean was the insert successful?
	 * @throws \Exception
	 */
	public function insert(string $table, array $param, int $duplicate_mode = self::INSERT_DEFAULT): bool
	{
		if (empty($table) || empty($param)) {
			$this->logger->info('Table and fields have to be set');
			return false;
		}

		$param = $this->castFields($table, $param);

		$table_string = DBA::buildTableString([$table]);

		$fields_string = implode(', ', array_map([DBA::class, 'quoteIdentifier'], array_keys($param)));

		$values_string = substr(str_repeat("?, ", count($param)), 0, -2);

		$sql = "INSERT ";

		if ($duplicate_mode == self::INSERT_IGNORE) {
			$sql .= "IGNORE ";
		}

		$sql .= "INTO " . $table_string . " (" . $fields_string . ") VALUES (" . $values_string . ")";

		if ($duplicate_mode == self::INSERT_UPDATE) {
			$fields_string = implode(' = ?, ', array_map([DBA::class, 'quoteIdentifier'], array_keys($param)));

			$sql .= " ON DUPLICATE KEY UPDATE " . $fields_string . " = ?";

			$values = array_values($param);
			$param  = array_merge_recursive($values, $values);
		}

		$result = $this->e($sql, $param);
		if (!$result || ($duplicate_mode != self::INSERT_IGNORE)) {
			return $result;
		}

		return $this->affectedRows() != 0;
	}

	/**
	 * Inserts a row with the provided data in the provided table.
	 * If the data corresponds to an existing row through a UNIQUE or PRIMARY index constraints, it updates the row instead.
	 *
	 * @param string $table Table name in format [schema.]table
	 * @param array  $param parameter array
	 * @return boolean was the insert successful?
	 * @throws \Exception
	 */
	public function replace(string $table, array $param): bool
	{
		if (empty($table) || empty($param)) {
			$this->logger->info('Table and fields have to be set');
			return false;
		}

		$param = $this->castFields($table, $param);

		$table_string = DBA::buildTableString([$table]);

		$fields_string = implode(', ', array_map([DBA::class, 'quoteIdentifier'], array_keys($param)));

		$values_string = substr(str_repeat("?, ", count($param)), 0, -2);

		$sql = "REPLACE " . $table_string . " (" . $fields_string . ") VALUES (" . $values_string . ")";

		return $this->e($sql, $param);
	}

	/**
	 * Fetch the id of the last insert command
	 *
	 * @return integer Last inserted id
	 */
	public function lastInsertId(): int
	{
		switch ($this->driver) {
			case self::PDO:
				$id = $this->connection->lastInsertId();
				break;
			case self::MYSQLI:
				$id = $this->connection->insert_id;
				break;
		}
		return (int)$id;
	}

	/**
	 * Locks a table for exclusive write access
	 *
	 * This function can be extended in the future to accept a table array as well.
	 *
	 * @param string $table Table name in format [schema.]table
	 * @return boolean was the lock successful?
	 * @throws \Exception
	 */
	public function lock(string $table): bool
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		if ($this->driver == self::PDO) {
			$this->e("SET autocommit=0");
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		} else {
			$this->connection->autocommit(false);
		}

		$success = $this->e("LOCK TABLES " . DBA::buildTableString([$table]) . " WRITE");

		if ($this->driver == self::PDO) {
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->pdo_emulate_prepares);
		}

		if (!$success) {
			if ($this->driver == self::PDO) {
				$this->e("SET autocommit=1");
			} else {
				$this->connection->autocommit(true);
			}
		} else {
			$this->in_transaction = true;
		}
		return $success;
	}

	/**
	 * Unlocks all locked tables
	 *
	 * @return boolean was the unlock successful?
	 * @throws \Exception
	 */
	public function unlock(): bool
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		$this->performCommit();

		if ($this->driver == self::PDO) {
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		}

		$success = $this->e("UNLOCK TABLES");

		if ($this->driver == self::PDO) {
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->pdo_emulate_prepares);
			$this->e("SET autocommit=1");
		} else {
			$this->connection->autocommit(true);
		}

		$this->in_transaction = false;
		return $success;
	}

	/**
	 * Starts a transaction
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public function transaction(): bool
	{
		if (!$this->performCommit()) {
			return false;
		}

		switch ($this->driver) {
			case self::PDO:
				if (!$this->connection->inTransaction() && !$this->connection->beginTransaction()) {
					return false;
				}
				break;

			case self::MYSQLI:
				if (!$this->connection->begin_transaction()) {
					return false;
				}
				break;
		}

		$this->in_transaction = true;
		return true;
	}

	/**
	 * Performs the commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	protected function performCommit(): bool
	{
		switch ($this->driver) {
			case self::PDO:
				if (!$this->connection->inTransaction()) {
					return true;
				}

				return $this->connection->commit();

			case self::MYSQLI:
				return $this->connection->commit();
		}

		return true;
	}

	/**
	 * Does a commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public function commit(): bool
	{
		if (!$this->performCommit()) {
			return false;
		}
		$this->in_transaction = false;
		return true;
	}

	/**
	 * Does a rollback
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public function rollback(): bool
	{
		$ret = false;

		switch ($this->driver) {
			case self::PDO:
				if (!$this->connection->inTransaction()) {
					$ret = true;
					break;
				}
				$ret = $this->connection->rollBack();
				break;

			case self::MYSQLI:
				$ret = $this->connection->rollback();
				break;
		}

		$this->in_transaction = false;
		return $ret;
	}

	/**
	 * Delete a row from a table
	 *
	 * @param string $table      Table name
	 * @param array  $conditions Field condition(s)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public function delete(string $table, array $conditions): bool
	{
		if (empty($table) || empty($conditions)) {
			$this->logger->info('Table and conditions have to be set');
			return false;
		}

		$table_string = DBA::buildTableString([$table]);

		$condition_string = DBA::buildCondition($conditions);

		$sql = "DELETE FROM " . $table_string . " " . $condition_string;
		$this->logger->debug($this->replaceParameters($sql, $conditions), ['callstack' => System::callstack(6)]);
		return $this->e($sql, $conditions);
	}

	/**
	 * Updates rows in the database. Field value objects will be cast as string.
	 *
	 * When $old_fields is set to an array,
	 * the system will only do an update if the fields in that array changed.
	 *
	 * Attention:
	 * Only the values in $old_fields are compared.
	 * This is an intentional behaviour.
	 *
	 * Example:
	 * We include the timestamp field in $fields but not in $old_fields.
	 * Then the row will only get the new timestamp when the other fields had changed.
	 *
	 * When $old_fields is set to a boolean value the system will do this compare itself.
	 * When $old_fields is set to "true" the system will do an insert if the row doesn't exists.
	 *
	 * Attention:
	 * Only set $old_fields to a boolean value when you are sure that you will update a single row.
	 * When you set $old_fields to "true" then $fields must contain all relevant fields!
	 *
	 * @param string        $table      Table name in format [schema.]table
	 * @param array         $fields     contains the fields that are updated
	 * @param array         $condition  condition array with the key values
	 * @param array|boolean $old_fields array with the old field values that are about to be replaced (true = update on duplicate, false = don't update identical fields)
	 * @param array         $params     Parameters: "ignore" If set to "true" then the update is done with the ignore parameter
	 *
	 * @return boolean was the update successfull?
	 * @throws \Exception
	 * @todo Implement "bool $update_on_duplicate" to avoid mixed type for $old_fields
	 */
	public function update(string $table, array $fields, array $condition, $old_fields = [], array $params = [])
	{
		if (empty($table) || empty($fields) || empty($condition)) {
			$this->logger->info('Table, fields and condition have to be set');
			return false;
		}

		if (is_bool($old_fields)) {
			$do_insert = $old_fields;

			$old_fields = $this->selectFirst($table, [], $condition);

			if (is_bool($old_fields)) {
				if ($do_insert) {
					$values = array_merge($condition, $fields);
					return $this->replace($table, $values);
				}
				$old_fields = [];
			}
		}

		foreach ($old_fields as $fieldname => $content) {
			if (isset($fields[$fieldname]) && !is_null($content) && ($fields[$fieldname] == $content)) {
				unset($fields[$fieldname]);
			}
		}

		if (count($fields) == 0) {
			return true;
		}

		$fields = $this->castFields($table, $fields);

		$table_string = DBA::buildTableString([$table]);

		$condition_string = DBA::buildCondition($condition);

		if (!empty($params['ignore'])) {
			$ignore = 'IGNORE ';
		} else {
			$ignore = '';
		}

		$sql = "UPDATE " . $ignore . $table_string . " SET "
			. implode(" = ?, ", array_map([DBA::class, 'quoteIdentifier'], array_keys($fields))) . " = ?"
			. $condition_string;

		// Combines the updated fields parameter values with the condition parameter values
		$params = array_merge(array_values($fields), $condition);

		return $this->e($sql, $params);
	}

	/**
	 * Retrieve a single record from a table and returns it in an associative array
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return bool|array
	 * @throws \Exception
	 * @see   $this->select
	 */
	public function selectFirst(string $table, array $fields = [], array $condition = [], array $params = [])
	{
		$params['limit'] = 1;
		$result          = $this->select($table, $fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = $this->fetch($result);
			$this->close($result);
			return $row;
		}
	}

	/**
	 * Select rows from a table and fills an array with the data
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 * @return array Data array
	 * @throws \Exception
	 * @see   self::select
	 */
	public function selectToArray(string $table, array $fields = [], array $condition = [], array $params = [])
	{
		return $this->toArray($this->select($table, $fields, $condition, $params));
	}

	/**
	 * Escape fields, adding special treatment for "group by" handling
	 *
	 * @param array $fields
	 * @param array $options
	 * @return array Escaped fields
	 */
	private function escapeFields(array $fields, array $options): array
	{
		// In the case of a "GROUP BY" we have to add all the ORDER fields to the fieldlist.
		// This needs to done to apply the "ANY_VALUE(...)" treatment from below to them.
		// Otherwise MySQL would report errors.
		if (!empty($options['group_by']) && !empty($options['order'])) {
			foreach ($options['order'] as $key => $field) {
				if (!is_int($key)) {
					if (!in_array($key, $fields)) {
						$fields[] = $key;
					}
				} else {
					if (!in_array($field, $fields)) {
						$fields[] = $field;
					}
				}
			}
		}

		array_walk($fields, function (&$value, $key) use ($options) {
			$field = $value;
			$value = DBA::quoteIdentifier($field);

			if (!empty($options['group_by']) && !in_array($field, $options['group_by'])) {
				$value = 'ANY_VALUE(' . $value . ') AS ' . $value;
			}
		});

		return $fields;
	}

	/**
	 * Select rows from a table
	 *
	 *
	 * Example:
	 * $table = 'post';
	 * or:
	 * $table = ['schema' => 'table'];
	 * @see DBA::buildTableString()
	 *
	 * $fields = ['id', 'uri', 'uid', 'network'];
	 *
	 * $condition = ['uid' => 1, 'network' => 'dspr', 'blocked' => true];
	 * or:
	 * $condition = ['`uid` = ? AND `network` IN (?, ?)', 1, 'dfrn', 'dspr'];
	 * @see DBA::buildCondition()
	 *
	 * $params = ['order' => ['id', 'received' => true, 'created' => 'ASC'), 'limit' => 10];
	 * @see DBA::buildParameter()
	 *
	 * $data = DBA::select($table, $fields, $condition, $params);
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 * @return boolean|object
	 * @throws \Exception
	 */
	public function select(string $table, array $fields = [], array $condition = [], array $params = [])
	{
		if (empty($table)) {
			return false;
		}

		if (count($fields) > 0) {
			$fields        = $this->escapeFields($fields, $params);
			$select_string = implode(', ', $fields);
		} else {
			$select_string = '*';
		}

		$table_string = DBA::buildTableString([$table]);

		$condition_string = DBA::buildCondition($condition);

		$param_string = DBA::buildParameter($params);

		$sql = "SELECT " . $select_string . " FROM " . $table_string . $condition_string . $param_string;

		$result = $this->p($sql, $condition);

		if (($this->driver == self::PDO) && !empty($result) && is_string($table)) {
			$result->table = $table;
		}

		return $result;
	}

	/**
	 * Counts the rows from a table satisfying the provided condition
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return int Count of rows
	 *
	 * Example:
	 * $table = "post";
	 *
	 * $condition = ["uid" => 1, "network" => 'dspr'];
	 * or:
	 * $condition = ["`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr'];
	 *
	 * $count = DBA::count($table, $condition);
	 * @throws \Exception
	 */
	public function count(string $table, array $condition = [], array $params = []): int
	{
		if (empty($table)) {
			throw new InvalidArgumentException('Parameter "table" cannot be empty.');
		}

		$table_string = DBA::buildTableString([$table]);

		$condition_string = DBA::buildCondition($condition);

		if (empty($params['expression'])) {
			$expression = '*';
		} elseif (!empty($params['distinct'])) {
			$expression = "DISTINCT " . DBA::quoteIdentifier($params['expression']);
		} else {
			$expression = DBA::quoteIdentifier($params['expression']);
		}

		$sql = "SELECT COUNT(" . $expression . ") AS `count` FROM " . $table_string . $condition_string;

		$row = $this->fetchFirst($sql, $condition);

		if (!isset($row['count'])) {
			$this->logger->notice('Invalid count.', ['table' => $table, 'row' => $row, 'expression' => $expression, 'condition' => $condition_string, 'callstack' => System::callstack()]);
			return 0;
		} else {
			return (int)$row['count'];
		}
	}

	/**
	 * Fills an array with data from a query
	 *
	 * @param object $stmt     statement object
	 * @param bool   $do_close Close database connection after last row
	 * @param int    $count    maximum number of rows to be fetched
	 *
	 * @return array Data array
	 */
	public function toArray($stmt, bool $do_close = true, int $count = 0): array
	{
		if (is_bool($stmt)) {
			return [];
		}

		$data = [];
		while ($row = $this->fetch($stmt)) {
			$data[] = $row;
			if (($count != 0) && (count($data) == $count)) {
				return $data;
			}
		}

		if ($do_close) {
			$this->close($stmt);
		}

		return $data;
	}

	/**
	 * Cast field types according to the table definition
	 *
	 * @param string $table
	 * @param array  $fields
	 * @return array casted fields
	 */
	public function castFields(string $table, array $fields): array
	{
		// When there is no data, we don't need to do something
		if (empty($fields)) {
			return $fields;
		}

		// We only need to cast fields with PDO
		if ($this->driver != self::PDO) {
			return $fields;
		}

		// We only need to cast when emulating the prepares
		if (!$this->connection->getAttribute(PDO::ATTR_EMULATE_PREPARES)) {
			return $fields;
		}

		$types = [];

		$tables = $this->dbaDefinition->getAll();
		if (empty($tables[$table])) {
			// When a matching table wasn't found we check if it is a view
			$views = $this->viewDefinition->getAll();
			if (empty($views[$table])) {
				return $fields;
			}

			foreach (array_keys($fields) as $field) {
				if (!empty($views[$table]['fields'][$field])) {
					$viewdef = $views[$table]['fields'][$field];
					if (!empty($tables[$viewdef[0]]['fields'][$viewdef[1]]['type'])) {
						$types[$field] = $tables[$viewdef[0]]['fields'][$viewdef[1]]['type'];
					}
				}
			}
		} else {
			foreach ($tables[$table]['fields'] as $field => $definition) {
				$types[$field] = $definition['type'];
			}
		}

		foreach ($fields as $field => $content) {
			if (is_null($content) || empty($types[$field])) {
				continue;
			}

			if ((substr($types[$field], 0, 7) == 'tinyint') || (substr($types[$field], 0, 8) == 'smallint') ||
				(substr($types[$field], 0, 9) == 'mediumint') || (substr($types[$field], 0, 3) == 'int') ||
				(substr($types[$field], 0, 6) == 'bigint') || (substr($types[$field], 0, 7) == 'boolean')) {
				$fields[$field] = (int)$content;
			}
			if ((substr($types[$field], 0, 5) == 'float') || (substr($types[$field], 0, 6) == 'double')) {
				$fields[$field] = (float)$content;
			}
		}

		return $fields;
	}

	/**
	 * Returns the error number of the last query
	 *
	 * @return string Error number (0 if no error)
	 */
	public function errorNo(): int
	{
		return $this->errorno;
	}

	/**
	 * Returns the error message of the last query
	 *
	 * @return string Error message ('' if no error)
	 */
	public function errorMessage(): string
	{
		return $this->error;
	}

	/**
	 * Closes the current statement
	 *
	 * @param object $stmt statement object
	 *
	 * @return boolean was the close successful?
	 */
	public function close($stmt): bool
	{

		$this->profiler->startRecording('database');

		if (!is_object($stmt)) {
			return false;
		}

		switch ($this->driver) {
			case self::PDO:
				$ret = $stmt->closeCursor();
				break;
			case self::MYSQLI:
				// MySQLi offers both a mysqli_stmt and a mysqli_result class.
				// We should be careful not to assume the object type of $stmt
				// because DBA::p() has been able to return both types.
				if ($stmt instanceof mysqli_stmt) {
					$stmt->free_result();
					$ret = $stmt->close();
				} elseif ($stmt instanceof mysqli_result) {
					$stmt->free();
					$ret = true;
				} else {
					$ret = false;
				}
				break;
		}

		$this->profiler->stopRecording();

		return $ret;
	}

	/**
	 * Return a list of database processes
	 *
	 * @return array
	 *      'list' => List of processes, separated in their different states
	 *      'amount' => Number of concurrent database processes
	 * @throws \Exception
	 */
	public function processlist(): array
	{
		$ret  = $this->p('SHOW PROCESSLIST');
		$data = $this->toArray($ret);

		$processes = 0;
		$states    = [];
		foreach ($data as $process) {
			$state = trim($process['State']);

			// Filter out all non blocking processes
			if (!in_array($state, ['', 'init', 'statistics', 'updating'])) {
				++$states[$state];
				++$processes;
			}
		}

		$statelist = '';
		foreach ($states as $state => $usage) {
			if ($statelist != '') {
				$statelist .= ', ';
			}
			$statelist .= $state . ': ' . $usage;
		}
		return (['list' => $statelist, 'amount' => $processes]);
	}

	/**
	 * Fetch a database variable
	 *
	 * @param string $name
	 * @return string|null content or null if inexistent
	 * @throws \Exception
	 */
	public function getVariable(string $name)
	{
		$result = $this->fetchFirst("SHOW GLOBAL VARIABLES WHERE `Variable_name` = ?", $name);
		return $result['Value'] ?? null;
	}

	/**
	 * Checks if $array is a filled array with at least one entry.
	 *
	 * @param mixed $array A filled array with at least one entry
	 * @return boolean Whether $array is a filled array or an object with rows
	 */
	public function isResult($array): bool
	{
		// It could be a return value from an update statement
		if (is_bool($array)) {
			return $array;
		}

		if (is_object($array)) {
			return $this->numRows($array) > 0;
		}

		return (is_array($array) && (count($array) > 0));
	}

	/**
	 * Callback function for "esc_array"
	 *
	 * @param mixed   $value         Array value
	 * @param string  $key           Array key
	 * @param boolean $add_quotation add quotation marks for string values
	 * @return void
	 */
	private function escapeArrayCallback(&$value, string $key, bool $add_quotation)
	{
		if (!$add_quotation) {
			if (is_bool($value)) {
				$value = ($value ? '1' : '0');
			} else {
				$value = $this->escape($value);
			}
			return;
		}

		if (is_bool($value)) {
			$value = ($value ? 'true' : 'false');
		} elseif (is_float($value) || is_integer($value)) {
			$value = (string)$value;
		} else {
			$value = "'" . $this->escape($value) . "'";
		}
	}

	/**
	 * Escapes a whole array
	 *
	 * @param mixed   $arr           Array with values to be escaped
	 * @param boolean $add_quotation add quotation marks for string values
	 * @return void
	 */
	public function escapeArray(&$arr, bool $add_quotation = false)
	{
		array_walk($arr, [$this, 'escapeArrayCallback'], $add_quotation);
	}

	/**
	 * Replaces a string in the provided fields of the provided table
	 *
	 * @param string $table   Table name
	 * @param array  $fields  List of field names in the provided table
	 * @param string $search  String to search for
	 * @param string $replace String to replace with
	 * @return void
	 * @throws \Exception
	 */
	public function replaceInTableFields(string $table, array $fields, string $search, string $replace)
	{
		$search  = $this->escape($search);
		$replace = $this->escape($replace);

		$upd = [];
		foreach ($fields as $field) {
			$field = DBA::quoteIdentifier($field);
			$upd[] = "$field = REPLACE($field, '$search', '$replace')";
		}

		$upds = implode(', ', $upd);

		$r = $this->e(sprintf("UPDATE %s SET %s;", DBA::quoteIdentifier($table), $upds));

		if (!$this->isResult($r)) {
			throw new \RuntimeException("Failed updating `$table`: " . $this->errorMessage());
		}
	}
}
