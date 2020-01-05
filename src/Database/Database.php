<?php

namespace Friendica\Database;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\System;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;

/**
 * @class MySQL database class
 *
 * This class is for the low level database stuff that does driver specific things.
 */
class Database
{
	protected $connected = false;

	/**
	 * @var ConfigCache
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
	protected $server_info    = '';
	/** @var PDO|mysqli */
	protected $connection;
	protected $driver;
	private $error          = false;
	private $errorno        = 0;
	private $affected_rows  = 0;
	protected $in_transaction = false;
	protected $in_retrial     = false;
	private $relation       = [];

	public function __construct(ConfigCache $configCache, Profiler $profiler, LoggerInterface $logger, array $server = [])
	{
		// We are storing these values for being able to perform a reconnect
		$this->configCache   = $configCache;
		$this->profiler      = $profiler;
		$this->logger        = $logger;

		$this->readServerVariables($server);
		$this->connect();

		if ($this->isConnected()) {
			// Loads DB_UPDATE_VERSION constant
			DBStructure::definition($configCache->get('system', 'basepath'), false);
		}
	}

	private function readServerVariables(array $server)
	{
		// Use environment variables for mysql if they are set beforehand
		if (!empty($server['MYSQL_HOST'])
		    && (!empty($server['MYSQL_USERNAME'] || !empty($server['MYSQL_USER'])))
		    && $server['MYSQL_PASSWORD'] !== false
		    && !empty($server['MYSQL_DATABASE']))
		{
			$db_host = $server['MYSQL_HOST'];
			if (!empty($server['MYSQL_PORT'])) {
				$db_host .= ':' . $server['MYSQL_PORT'];
			}
			$this->configCache->set('database', 'hostname', $db_host);
			unset($db_host);
			if (!empty($server['MYSQL_USERNAME'])) {
				$this->configCache->set('database', 'username', $server['MYSQL_USERNAME']);
			} else {
				$this->configCache->set('database', 'username', $server['MYSQL_USER']);
			}
			$this->configCache->set('database', 'password', (string) $server['MYSQL_PASSWORD']);
			$this->configCache->set('database', 'database', $server['MYSQL_DATABASE']);
		}
	}

	public function connect()
	{
		if (!is_null($this->connection) && $this->connected()) {
			return $this->connected;
		}

		// Reset connected state
		$this->connected = false;

		$port       = 0;
		$serveraddr = trim($this->configCache->get('database', 'hostname'));
		$serverdata = explode(':', $serveraddr);
		$server     = $serverdata[0];
		if (count($serverdata) > 1) {
			$port = trim($serverdata[1]);
		}
		$server  = trim($server);
		$user    = trim($this->configCache->get('database', 'username'));
		$pass    = trim($this->configCache->get('database', 'password'));
		$db      = trim($this->configCache->get('database', 'database'));
		$charset = trim($this->configCache->get('database', 'charset'));

		if (!(strlen($server) && strlen($user))) {
			return false;
		}

		if (class_exists('\PDO') && in_array('mysql', PDO::getAvailableDrivers())) {
			$this->driver = 'pdo';
			$connect      = "mysql:host=" . $server . ";dbname=" . $db;

			if ($port > 0) {
				$connect .= ";port=" . $port;
			}

			if ($charset) {
				$connect .= ";charset=" . $charset;
			}

			try {
				$this->connection = @new PDO($connect, $user, $pass);
				$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				$this->connected = true;
			} catch (PDOException $e) {
				$this->connected = false;
			}
		}

		if (!$this->connected && class_exists('\mysqli')) {
			$this->driver = 'mysqli';

			if ($port > 0) {
				$this->connection = @new mysqli($server, $user, $pass, $db, $port);
			} else {
				$this->connection = @new mysqli($server, $user, $pass, $db);
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
			$this->driver     = null;
			$this->connection = null;
		}

		return $this->connected;
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
				case 'pdo':
					$this->connection = null;
					break;
				case 'mysqli':
					$this->connection->close();
					$this->connection = null;
					break;
			}
		}

		$this->driver    = null;
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
	 * @brief Returns the MySQL server version string
	 *
	 * This function discriminate between the deprecated mysql API and the current
	 * object-oriented mysqli API. Example of returned string: 5.5.46-0+deb8u1
	 *
	 * @return string
	 */
	public function serverInfo()
	{
		if ($this->server_info == '') {
			switch ($this->driver) {
				case 'pdo':
					$this->server_info = $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
					break;
				case 'mysqli':
					$this->server_info = $this->connection->server_info;
					break;
			}
		}
		return $this->server_info;
	}

	/**
	 * @brief Returns the selected database name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function databaseName()
	{
		$ret  = $this->p("SELECT DATABASE() AS `db`");
		$data = $this->toArray($ret);
		return $data[0]['db'];
	}

	/**
	 * @brief Analyze a database query and log this if some conditions are met.
	 *
	 * @param string $query The database query that will be analyzed
	 *
	 * @throws \Exception
	 */
	private function logIndex($query)
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
		$blacklist = explode(',', $this->configCache->get('system', 'db_log_index_blacklist'));

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

			if (in_array($row['key'], $blacklist) || ($row['key'] == "")) {
				$log = false;
			}

			if ($log) {
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				@file_put_contents($this->configCache->get('system', 'db_log_index'), DateTimeFormat::utcNow() . "\t" .
				                                                                      $row['key'] . "\t" . $row['rows'] . "\t" . $row['Extra'] . "\t" .
				                                                                      basename($backtrace[1]["file"]) . "\t" .
				                                                                      $backtrace[1]["line"] . "\t" . $backtrace[2]["function"] . "\t" .
				                                                                      substr($query, 0, 2000) . "\n", FILE_APPEND);
			}
		}
	}

	/**
	 * Removes every not whitelisted character from the identifier string
	 *
	 * @param string $identifier
	 *
	 * @return string sanitized identifier
	 * @throws \Exception
	 */
	private function sanitizeIdentifier($identifier)
	{
		return preg_replace('/[^A-Za-z0-9_\-]+/', '', $identifier);
	}

	public function escape($str)
	{
		if ($this->connected) {
			switch ($this->driver) {
				case 'pdo':
					return substr(@$this->connection->quote($str, PDO::PARAM_STR), 1, -1);

				case 'mysqli':
					return @$this->connection->real_escape_string($str);
			}
		} else {
			return str_replace("'", "\\'", $str);
		}
	}

	public function isConnected()
	{
		return $this->connected;
	}

	public function connected()
	{
		$connected = false;

		if (is_null($this->connection)) {
			return false;
		}

		switch ($this->driver) {
			case 'pdo':
				$r = $this->p("SELECT 1");
				if ($this->isResult($r)) {
					$row       = $this->toArray($r);
					$connected = ($row[0]['1'] == '1');
				}
				break;
			case 'mysqli':
				$connected = $this->connection->ping();
				break;
		}

		return $connected;
	}

	/**
	 * @brief Replaces ANY_VALUE() function by MIN() function,
	 *  if the database server does not support ANY_VALUE().
	 *
	 * Considerations for Standard SQL, or MySQL with ONLY_FULL_GROUP_BY (default since 5.7.5).
	 * ANY_VALUE() is available from MySQL 5.7.5 https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html
	 * A standard fall-back is to use MIN().
	 *
	 * @param string $sql An SQL string without the values
	 *
	 * @return string The input SQL string modified if necessary.
	 */
	public function anyValueFallback($sql)
	{
		$server_info = $this->serverInfo();
		if (version_compare($server_info, '5.7.5', '<') ||
		    (stripos($server_info, 'MariaDB') !== false)) {
			$sql = str_ireplace('ANY_VALUE(', 'MIN(', $sql);
		}
		return $sql;
	}

	/**
	 * @brief Replaces the ? placeholders with the parameters in the $args array
	 *
	 * @param string $sql  SQL query
	 * @param array  $args The parameters that are to replace the ? placeholders
	 *
	 * @return string The replaced SQL query
	 */
	private function replaceParameters($sql, $args)
	{
		$offset = 0;
		foreach ($args AS $param => $value) {
			if (is_int($args[$param]) || is_float($args[$param])) {
				$replace = intval($args[$param]);
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
	 * @brief Executes a prepared statement that returns data
	 * @usage Example: $r = p("SELECT * FROM `item` WHERE `guid` = ?", $guid);
	 *
	 * Please only use it with complicated queries.
	 * For all regular queries please use DBA::select or DBA::exists
	 *
	 * @param string $sql SQL statement
	 *
	 * @return bool|object statement object or result object
	 * @throws \Exception
	 */
	public function p($sql)
	{

		$stamp1 = microtime(true);

		$params = DBA::getParam(func_get_args());

		// Renumber the array keys to be sure that they fit
		$i    = 0;
		$args = [];
		foreach ($params AS $param) {
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
			throw new InternalServerErrorException('The Connection is empty, although connected is set true.');
		}

		switch ($this->driver) {
			case 'pdo':
				// If there are no arguments we use "query"
				if (count($args) == 0) {
					if (!$retval = $this->connection->query($sql)) {
						$errorInfo     = $this->connection->errorInfo();
						$this->error   = $errorInfo[2];
						$this->errorno = $errorInfo[1];
						$retval        = false;
						break;
					}
					$this->affected_rows = $retval->rowCount();
					break;
				}

				/** @var $stmt mysqli_stmt|PDOStatement */
				if (!$stmt = $this->connection->prepare($sql)) {
					$errorInfo     = $this->connection->errorInfo();
					$this->error   = $errorInfo[2];
					$this->errorno = $errorInfo[1];
					$retval        = false;
					break;
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
					$errorInfo     = $stmt->errorInfo();
					$this->error   = $errorInfo[2];
					$this->errorno = $errorInfo[1];
					$retval        = false;
				} else {
					$retval              = $stmt;
					$this->affected_rows = $retval->rowCount();
				}
				break;
			case 'mysqli':
				// There are SQL statements that cannot be executed with a prepared statement
				$parts           = explode(' ', $orig_sql);
				$command         = strtolower($parts[0]);
				$can_be_prepared = in_array($command, ['select', 'update', 'insert', 'delete']);

				// The fallback routine is called as well when there are no arguments
				if (!$can_be_prepared || (count($args) == 0)) {
					$retval = $this->connection->query($this->replaceParameters($sql, $args));
					if ($this->connection->errno) {
						$this->error   = $this->connection->error;
						$this->errorno = $this->connection->errno;
						$retval        = false;
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
					$this->error   = $stmt->error;
					$this->errorno = $stmt->errno;
					$retval        = false;
					break;
				}

				$param_types = '';
				$values      = [];
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
					$this->error   = $this->connection->error;
					$this->errorno = $this->connection->errno;
					$retval        = false;
				} else {
					$stmt->store_result();
					$retval              = $stmt;
					$this->affected_rows = $retval->affected_rows;
				}
				break;
		}

		// We are having an own error logging in the function "e"
		if (($this->errorno != 0) && !$called_from_e) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$error   = $this->error;
			$errorno = $this->errorno;

			$this->logger->error('DB Error', [
				'code'      => $this->errorno,
				'error'     => $this->error,
				'callstack' => System::callstack(8),
				'params'    => $this->replaceParameters($sql, $args),
			]);

			// On a lost connection we try to reconnect - but only once.
			if ($errorno == 2006) {
				if ($this->in_retrial || !$this->reconnect()) {
					// It doesn't make sense to continue when the database connection was lost
					if ($this->in_retrial) {
						$this->logger->notice('Giving up retrial because of database error', [
							'code'  => $this->errorno,
							'error' => $this->error,
						]);
					} else {
						$this->logger->notice('Couldn\'t reconnect after database error', [
							'code'  => $this->errorno,
							'error' => $this->error,
						]);
					}
					exit(1);
				} else {
					// We try it again
					$this->logger->notice('Reconnected after database error', [
						'code'  => $this->errorno,
						'error' => $this->error,
					]);
					$this->in_retrial = true;
					$ret              = $this->p($sql, $args);
					$this->in_retrial = false;
					return $ret;
				}
			}

			$this->error   = $error;
			$this->errorno = $errorno;
		}

		$this->profiler->saveTimestamp($stamp1, 'database', System::callstack());

		if ($this->configCache->get('system', 'db_log')) {
			$stamp2   = microtime(true);
			$duration = (float)($stamp2 - $stamp1);

			if (($duration > $this->configCache->get('system', 'db_loglimit'))) {
				$duration  = round($duration, 3);
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				@file_put_contents($this->configCache->get('system', 'db_log'), DateTimeFormat::utcNow() . "\t" . $duration . "\t" .
				                                                                basename($backtrace[1]["file"]) . "\t" .
				                                                                $backtrace[1]["line"] . "\t" . $backtrace[2]["function"] . "\t" .
				                                                                substr($this->replaceParameters($sql, $args), 0, 2000) . "\n", FILE_APPEND);
			}
		}
		return $retval;
	}

	/**
	 * @brief Executes a prepared statement like UPDATE or INSERT that doesn't return data
	 *
	 * Please use DBA::delete, DBA::insert, DBA::update, ... instead
	 *
	 * @param string $sql SQL statement
	 *
	 * @return boolean Was the query successfull? False is returned only if an error occurred
	 * @throws \Exception
	 */
	public function e($sql)
	{

		$stamp = microtime(true);

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

			$this->logger->error('DB Error', [
				'code'      => $this->errorno,
				'error'     => $this->error,
				'callstack' => System::callstack(8),
				'params'    => $this->replaceParameters($sql, $params),
			]);

			// On a lost connection we simply quit.
			// A reconnect like in $this->p could be dangerous with modifications
			if ($errorno == 2006) {
				$this->logger->notice('Giving up because of database error', [
					'code'  => $this->errorno,
					'error' => $this->error,
				]);
				exit(1);
			}

			$this->error   = $error;
			$this->errorno = $errorno;
		}

		$this->profiler->saveTimestamp($stamp, "database_write", System::callstack());

		return $retval;
	}

	/**
	 * @brief Check if data exists
	 *
	 * @param string|array $table     Table name or array [schema => table]
	 * @param array        $condition array of fields for condition
	 *
	 * @return boolean Are there rows for that condition?
	 * @throws \Exception
	 */
	public function exists($table, $condition)
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
	 * @brief Fetches the first row
	 *
	 * @param string $sql SQL statement
	 *
	 * @return array first row of query
	 * @throws \Exception
	 */
	public function fetchFirst($sql)
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
	 * @brief Returns the number of affected rows of the last statement
	 *
	 * @return int Number of rows
	 */
	public function affectedRows()
	{
		return $this->affected_rows;
	}

	/**
	 * @brief Returns the number of columns of a statement
	 *
	 * @param object Statement object
	 *
	 * @return int Number of columns
	 */
	public function columnCount($stmt)
	{
		if (!is_object($stmt)) {
			return 0;
		}
		switch ($this->driver) {
			case 'pdo':
				return $stmt->columnCount();
			case 'mysqli':
				return $stmt->field_count;
		}
		return 0;
	}

	/**
	 * @brief Returns the number of rows of a statement
	 *
	 * @param PDOStatement|mysqli_result|mysqli_stmt Statement object
	 *
	 * @return int Number of rows
	 */
	public function numRows($stmt)
	{
		if (!is_object($stmt)) {
			return 0;
		}
		switch ($this->driver) {
			case 'pdo':
				return $stmt->rowCount();
			case 'mysqli':
				return $stmt->num_rows;
		}
		return 0;
	}

	/**
	 * @brief Fetch a single row
	 *
	 * @param mixed $stmt statement object
	 *
	 * @return array current row
	 */
	public function fetch($stmt)
	{

		$stamp1 = microtime(true);

		$columns = [];

		if (!is_object($stmt)) {
			return false;
		}

		switch ($this->driver) {
			case 'pdo':
				$columns = $stmt->fetch(PDO::FETCH_ASSOC);
				break;
			case 'mysqli':
				if (get_class($stmt) == 'mysqli_result') {
					$columns = $stmt->fetch_assoc();
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

				foreach ($cols_num AS $param => $col) {
					$columns[$fields[$param]->name] = $col;
				}
		}

		$this->profiler->saveTimestamp($stamp1, 'database', System::callstack());

		return $columns;
	}

	/**
	 * @brief Insert a row into a table
	 *
	 * @param string|array $table               Table name or array [schema => table]
	 * @param array        $param               parameter array
	 * @param bool         $on_duplicate_update Do an update on a duplicate entry
	 *
	 * @return boolean was the insert successful?
	 * @throws \Exception
	 */
	public function insert($table, $param, $on_duplicate_update = false)
	{
		if (empty($table) || empty($param)) {
			$this->logger->info('Table and fields have to be set');
			return false;
		}

		$table_string = DBA::buildTableString($table);

		$fields_string = implode(', ', array_map([DBA::class, 'quoteIdentifier'], array_keys($param)));

		$values_string = substr(str_repeat("?, ", count($param)), 0, -2);

		$sql = "INSERT INTO " . $table_string . " (" . $fields_string . ") VALUES (" . $values_string . ")";

		if ($on_duplicate_update) {
			$fields_string = implode(' = ?, ', array_map([DBA::class, 'quoteIdentifier'], array_keys($param)));

			$sql .= " ON DUPLICATE KEY UPDATE " . $fields_string . " = ?";

			$values = array_values($param);
			$param  = array_merge_recursive($values, $values);
		}

		return $this->e($sql, $param);
	}

	/**
	 * @brief Fetch the id of the last insert command
	 *
	 * @return integer Last inserted id
	 */
	public function lastInsertId()
	{
		switch ($this->driver) {
			case 'pdo':
				$id = $this->connection->lastInsertId();
				break;
			case 'mysqli':
				$id = $this->connection->insert_id;
				break;
		}
		return $id;
	}

	/**
	 * @brief Locks a table for exclusive write access
	 *
	 * This function can be extended in the future to accept a table array as well.
	 *
	 * @param string|array $table Table name or array [schema => table]
	 *
	 * @return boolean was the lock successful?
	 * @throws \Exception
	 */
	public function lock($table)
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		if ($this->driver == 'pdo') {
			$this->e("SET autocommit=0");
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		} else {
			$this->connection->autocommit(false);
		}

		$success = $this->e("LOCK TABLES " . DBA::buildTableString($table) . " WRITE");

		if ($this->driver == 'pdo') {
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}

		if (!$success) {
			if ($this->driver == 'pdo') {
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
	 * @brief Unlocks all locked tables
	 *
	 * @return boolean was the unlock successful?
	 * @throws \Exception
	 */
	public function unlock()
	{
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		$this->performCommit();

		if ($this->driver == 'pdo') {
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		}

		$success = $this->e("UNLOCK TABLES");

		if ($this->driver == 'pdo') {
			$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			$this->e("SET autocommit=1");
		} else {
			$this->connection->autocommit(true);
		}

		$this->in_transaction = false;
		return $success;
	}

	/**
	 * @brief Starts a transaction
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public function transaction()
	{
		if (!$this->performCommit()) {
			return false;
		}

		switch ($this->driver) {
			case 'pdo':
				if (!$this->connection->inTransaction() && !$this->connection->beginTransaction()) {
					return false;
				}
				break;

			case 'mysqli':
				if (!$this->connection->begin_transaction()) {
					return false;
				}
				break;
		}

		$this->in_transaction = true;
		return true;
	}

	protected function performCommit()
	{
		switch ($this->driver) {
			case 'pdo':
				if (!$this->connection->inTransaction()) {
					return true;
				}

				return $this->connection->commit();

			case 'mysqli':
				return $this->connection->commit();
		}

		return true;
	}

	/**
	 * @brief Does a commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public function commit()
	{
		if (!$this->performCommit()) {
			return false;
		}
		$this->in_transaction = false;
		return true;
	}

	/**
	 * @brief Does a rollback
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public function rollback()
	{
		$ret = false;

		switch ($this->driver) {
			case 'pdo':
				if (!$this->connection->inTransaction()) {
					$ret = true;
					break;
				}
				$ret = $this->connection->rollBack();
				break;

			case 'mysqli':
				$ret = $this->connection->rollback();
				break;
		}
		$this->in_transaction = false;
		return $ret;
	}

	/**
	 * @brief Build the array with the table relations
	 *
	 * The array is build from the database definitions in DBStructure.php
	 *
	 * This process must only be started once, since the value is cached.
	 */
	private function buildRelationData()
	{
		$definition = DBStructure::definition($this->configCache->get('system', 'basepath'));

		foreach ($definition AS $table => $structure) {
			foreach ($structure['fields'] AS $field => $field_struct) {
				if (isset($field_struct['relation'])) {
					foreach ($field_struct['relation'] AS $rel_table => $rel_field) {
						$this->relation[$rel_table][$rel_field][$table][] = $field;
					}
				}
			}
		}
	}

	/**
	 * @brief Delete a row from a table
	 *
	 * Note: this methods does NOT accept schema => table arrays because of the complex relation stuff.
	 *
	 * @param string $table      Table name
	 * @param array  $conditions Field condition(s)
	 * @param array  $options
	 *                           - cascade: If true we delete records in other tables that depend on the one we're deleting through
	 *                           relations (default: true)
	 * @param array  $callstack  Internal use: prevent endless loops
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public function delete($table, array $conditions, array $options = [], array &$callstack = [])
	{
		if (empty($table) || empty($conditions)) {
			$this->logger->info('Table and conditions have to be set');
			return false;
		}

		$commands = [];

		// Create a key for the loop prevention
		$key = $table . ':' . json_encode($conditions);

		// We quit when this key already exists in the callstack.
		if (isset($callstack[$key])) {
			return true;
		}

		$callstack[$key] = true;

		$commands[$key] = ['table' => $table, 'conditions' => $conditions];

		// Don't use "defaults" here, since it would set "false" to "true"
		if (isset($options['cascade'])) {
			$cascade = $options['cascade'];
		} else {
			$cascade = true;
		}

		// To speed up the whole process we cache the table relations
		if ($cascade && count($this->relation) == 0) {
			$this->buildRelationData();
		}

		// Is there a relation entry for the table?
		if ($cascade && isset($this->relation[$table])) {
			// We only allow a simple "one field" relation.
			$field   = array_keys($this->relation[$table])[0];
			$rel_def = array_values($this->relation[$table])[0];

			// Create a key for preventing double queries
			$qkey = $field . '-' . $table . ':' . json_encode($conditions);

			// When the search field is the relation field, we don't need to fetch the rows
			// This is useful when the leading record is already deleted in the frontend but the rest is done in the backend
			if ((count($conditions) == 1) && ($field == array_keys($conditions)[0])) {
				foreach ($rel_def AS $rel_table => $rel_fields) {
					foreach ($rel_fields AS $rel_field) {
						$this->delete($rel_table, [$rel_field => array_values($conditions)[0]], $options, $callstack);
					}
				}
				// We quit when this key already exists in the callstack.
			} elseif (!isset($callstack[$qkey])) {
				$callstack[$qkey] = true;

				// Fetch all rows that are to be deleted
				$data = $this->select($table, [$field], $conditions);

				while ($row = $this->fetch($data)) {
					$this->delete($table, [$field => $row[$field]], $options, $callstack);
				}

				$this->close($data);

				// Since we had split the delete command we don't need the original command anymore
				unset($commands[$key]);
			}
		}

		// Now we finalize the process
		$do_transaction = !$this->in_transaction;

		if ($do_transaction) {
			$this->transaction();
		}

		$compacted = [];
		$counter   = [];

		foreach ($commands AS $command) {
			$conditions = $command['conditions'];
			reset($conditions);
			$first_key = key($conditions);

			$condition_string = DBA::buildCondition($conditions);

			if ((count($command['conditions']) > 1) || is_int($first_key)) {
				$sql = "DELETE FROM " . DBA::quoteIdentifier($command['table']) . " " . $condition_string;
				$this->logger->debug($this->replaceParameters($sql, $conditions));

				if (!$this->e($sql, $conditions)) {
					if ($do_transaction) {
						$this->rollback();
					}
					return false;
				}
			} else {
				$key_table     = $command['table'];
				$key_condition = array_keys($command['conditions'])[0];
				$value         = array_values($command['conditions'])[0];

				// Split the SQL queries in chunks of 100 values
				// We do the $i stuff here to make the code better readable
				$i = isset($counter[$key_table][$key_condition]) ? $counter[$key_table][$key_condition] : 0;
				if (isset($compacted[$key_table][$key_condition][$i]) && count($compacted[$key_table][$key_condition][$i]) > 100) {
					++$i;
				}

				$compacted[$key_table][$key_condition][$i][$value] = $value;
				$counter[$key_table][$key_condition]               = $i;
			}
		}
		foreach ($compacted AS $table => $values) {
			foreach ($values AS $field => $field_value_list) {
				foreach ($field_value_list AS $field_values) {
					$sql = "DELETE FROM " . DBA::quoteIdentifier($table) . " WHERE " . DBA::quoteIdentifier($field) . " IN (" .
					       substr(str_repeat("?, ", count($field_values)), 0, -2) . ");";

					$this->logger->debug($this->replaceParameters($sql, $field_values));

					if (!$this->e($sql, $field_values)) {
						if ($do_transaction) {
							$this->rollback();
						}
						return false;
					}
				}
			}
		}
		if ($do_transaction) {
			$this->commit();
		}
		return true;
	}

	/**
	 * @brief Updates rows
	 *
	 * Updates rows in the database. When $old_fields is set to an array,
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
	 * @param string|array  $table      Table name or array [schema => table]
	 * @param array         $fields     contains the fields that are updated
	 * @param array         $condition  condition array with the key values
	 * @param array|boolean $old_fields array with the old field values that are about to be replaced (true = update on duplicate)
	 *
	 * @return boolean was the update successfull?
	 * @throws \Exception
	 */
	public function update($table, $fields, $condition, $old_fields = [])
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
					return $this->insert($table, $values, $do_insert);
				}
				$old_fields = [];
			}
		}

		$do_update = (count($old_fields) == 0);

		foreach ($old_fields AS $fieldname => $content) {
			if (isset($fields[$fieldname])) {
				if (($fields[$fieldname] == $content) && !is_null($content)) {
					unset($fields[$fieldname]);
				} else {
					$do_update = true;
				}
			}
		}

		if (!$do_update || (count($fields) == 0)) {
			return true;
		}

		$table_string = DBA::buildTableString($table);

		$condition_string = DBA::buildCondition($condition);

		$sql = "UPDATE " . $table_string . " SET "
			. implode(" = ?, ", array_map([DBA::class, 'quoteIdentifier'], array_keys($fields))) . " = ?"
			. $condition_string;

		// Combines the updated fields parameter values with the condition parameter values
		$params  = array_merge(array_values($fields), $condition);

		return $this->e($sql, $params);
	}

	/**
	 * Retrieve a single record from a table and returns it in an associative array
	 *
	 * @brief Retrieve a single record from a table
	 *
	 * @param string|array $table
	 * @param array        $fields
	 * @param array        $condition
	 * @param array        $params
	 *
	 * @return bool|array
	 * @throws \Exception
	 * @see   $this->select
	 */
	public function selectFirst($table, array $fields = [], array $condition = [], $params = [])
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
	 * @brief Select rows from a table and fills an array with the data
	 *
	 * @param string|array $table     Table name or array [schema => table]
	 * @param array        $fields    Array of selected fields, empty for all
	 * @param array        $condition Array of fields for condition
	 * @param array        $params    Array of several parameters
	 *
	 * @return array Data array
	 * @throws \Exception
	 * @see   self::select
	 */
	public function selectToArray($table, array $fields = [], array $condition = [], array $params = [])
	{
		return $this->toArray($this->select($table, $fields, $condition, $params));
	}

	/**
	 * @brief Select rows from a table
	 *
	 * @param string|array $table     Table name or array [schema => table]
	 * @param array        $fields    Array of selected fields, empty for all
	 * @param array        $condition Array of fields for condition
	 * @param array        $params    Array of several parameters
	 *
	 * @return boolean|object
	 *
	 * Example:
	 * $table = "item";
	 * $fields = array("id", "uri", "uid", "network");
	 *
	 * $condition = array("uid" => 1, "network" => 'dspr');
	 * or:
	 * $condition = array("`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr');
	 *
	 * $params = array("order" => array("id", "received" => true), "limit" => 10);
	 *
	 * $data = DBA::select($table, $fields, $condition, $params);
	 * @throws \Exception
	 */
	public function select($table, array $fields = [], array $condition = [], array $params = [])
	{
		if (empty($table)) {
			return false;
		}

		if (count($fields) > 0) {
			$select_string = implode(', ', array_map([DBA::class, 'quoteIdentifier'], $fields));
		} else {
			$select_string = '*';
		}

		$table_string = DBA::buildTableString($table);

		$condition_string = DBA::buildCondition($condition);

		$param_string = DBA::buildParameter($params);

		$sql = "SELECT " . $select_string . " FROM " . $table_string . $condition_string . $param_string;

		$result = $this->p($sql, $condition);

		return $result;
	}

	/**
	 * @brief Counts the rows from a table satisfying the provided condition
	 *
	 * @param string|array $table     Table name or array [schema => table]
	 * @param array        $condition Array of fields for condition
	 * @param array        $params    Array of several parameters
	 *
	 * @return int
	 *
	 * Example:
	 * $table = "item";
	 *
	 * $condition = ["uid" => 1, "network" => 'dspr'];
	 * or:
	 * $condition = ["`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr'];
	 *
	 * $count = DBA::count($table, $condition);
	 * @throws \Exception
	 */
	public function count($table, array $condition = [], array $params = [])
	{
		if (empty($table)) {
			return false;
		}

		$table_string = DBA::buildTableString($table);

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

		return $row['count'];
	}

	/**
	 * @brief Fills an array with data from a query
	 *
	 * @param object $stmt statement object
	 * @param bool   $do_close
	 *
	 * @return array Data array
	 */
	public function toArray($stmt, $do_close = true)
	{
		if (is_bool($stmt)) {
			return [];
		}

		$data = [];
		while ($row = $this->fetch($stmt)) {
			$data[] = $row;
		}

		if ($do_close) {
			$this->close($stmt);
		}

		return $data;
	}

	/**
	 * @brief Returns the error number of the last query
	 *
	 * @return string Error number (0 if no error)
	 */
	public function errorNo()
	{
		return $this->errorno;
	}

	/**
	 * @brief Returns the error message of the last query
	 *
	 * @return string Error message ('' if no error)
	 */
	public function errorMessage()
	{
		return $this->error;
	}

	/**
	 * @brief Closes the current statement
	 *
	 * @param object $stmt statement object
	 *
	 * @return boolean was the close successful?
	 */
	public function close($stmt)
	{

		$stamp1 = microtime(true);

		if (!is_object($stmt)) {
			return false;
		}

		switch ($this->driver) {
			case 'pdo':
				$ret = $stmt->closeCursor();
				break;
			case 'mysqli':
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

		$this->profiler->saveTimestamp($stamp1, 'database', System::callstack());

		return $ret;
	}

	/**
	 * @brief Return a list of database processes
	 *
	 * @return array
	 *      'list' => List of processes, separated in their different states
	 *      'amount' => Number of concurrent database processes
	 * @throws \Exception
	 */
	public function processlist()
	{
		$ret  = $this->p("SHOW PROCESSLIST");
		$data = $this->toArray($ret);

		$processes = 0;
		$states    = [];
		foreach ($data as $process) {
			$state = trim($process["State"]);

			// Filter out all non blocking processes
			if (!in_array($state, ["", "init", "statistics", "updating"])) {
				++$states[$state];
				++$processes;
			}
		}

		$statelist = "";
		foreach ($states as $state => $usage) {
			if ($statelist != "") {
				$statelist .= ", ";
			}
			$statelist .= $state . ": " . $usage;
		}
		return (["list" => $statelist, "amount" => $processes]);
	}

	/**
	 * Checks if $array is a filled array with at least one entry.
	 *
	 * @param mixed $array A filled array with at least one entry
	 *
	 * @return boolean Whether $array is a filled array or an object with rows
	 */
	public function isResult($array)
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
	 * @brief Callback function for "esc_array"
	 *
	 * @param mixed   $value         Array value
	 * @param string  $key           Array key
	 * @param boolean $add_quotation add quotation marks for string values
	 *
	 * @return void
	 */
	private function escapeArrayCallback(&$value, $key, $add_quotation)
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
	 * @brief Escapes a whole array
	 *
	 * @param mixed   $arr           Array with values to be escaped
	 * @param boolean $add_quotation add quotation marks for string values
	 *
	 * @return void
	 */
	public function escapeArray(&$arr, $add_quotation = false)
	{
		array_walk($arr, [$this, 'escapeArrayCallback'], $add_quotation);
	}
}
