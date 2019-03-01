<?php

namespace Friendica\Database;

use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use mysqli;
use mysqli_result;
use mysqli_stmt;
use PDO;
use PDOException;
use PDOStatement;

/**
 * @class MySQL database class
 *
 * This class is for the low level database stuff that does driver specific things.
 */
class DBA
{
	/**
	 * Lowest possible date value
	 */
	const NULL_DATE     = '0001-01-01';
	/**
	 * Lowest possible datetime value
	 */
	const NULL_DATETIME = '0001-01-01 00:00:00';

	public static $connected = false;

	/**
	 * @var IConfigCache
	 */
	private static $configCache;
	/**
	 * @var Profiler
	 */
	private static $profiler;
	/**
	 * @var string
	 */
	private static $basePath;
	private static $server_info = '';
	private static $connection;
	private static $driver;
	private static $error = false;
	private static $errorno = 0;
	private static $affected_rows = 0;
	private static $in_transaction = false;
	private static $in_retrial = false;
	private static $relation = [];

	/**
	 * @var IDatabase
	 */
	private static $db;

	/**
	 * Initialize the DBA with a given database
	 *
	 * @param IDatabase $db
	 */
	public function init(IDatabase $db)
	{
		self::$db = $db;
	}

	/**
	 * Disconnects the current database connection
	 */
	public static function disconnect()
	{
		self::$db->disconnect();
	}

	/**
	 * Perform a reconnect of an existing database connection
	 */
	public static function reconnect()
	{
		self::$db->reconnect();
	}

	/**
	 * Return the database object.
	 * @return PDO|mysqli
	 */
	public static function getConnection()
	{
		return self::$db->getConnection();
	}

	/**
	 * @brief Returns the MySQL server version string
	 *
	 * This function discriminate between the deprecated mysql API and the current
	 * object-oriented mysqli API. Example of returned string: 5.5.46-0+deb8u1
	 *
	 * @return string
	 */
	public static function serverInfo()
	{
		return self::$db->serverInfo();
	}

	/**
	 * @brief Returns the selected database name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function databaseName() {
		$ret = self::p("SELECT DATABASE() AS `db`");
		$data = self::toArray($ret);
		return $data[0]['db'];
	}

	/**
	 * @brief Analyze a database query and log this if some conditions are met.
	 *
	 * @param string $query The database query that will be analyzed
	 * @throws \Exception
	 */
	private static function logIndex($query) {

		if (!self::$configCache->get('system', 'db_log_index')) {
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

		$r = self::p("EXPLAIN ".$query);
		if (!self::isResult($r)) {
			return;
		}

		$watchlist = explode(',', self::$configCache->get('system', 'db_log_index_watch'));
		$blacklist = explode(',', self::$configCache->get('system', 'db_log_index_blacklist'));

		while ($row = self::fetch($r)) {
			if ((intval(self::$configCache->get('system', 'db_loglimit_index')) > 0)) {
				$log = (in_array($row['key'], $watchlist) &&
					($row['rows'] >= intval(self::$configCache->get('system', 'db_loglimit_index'))));
			} else {
				$log = false;
			}

			if ((intval(self::$configCache->get('system', 'db_loglimit_index_high')) > 0) && ($row['rows'] >= intval(self::$configCache->get('system', 'db_loglimit_index_high')))) {
				$log = true;
			}

			if (in_array($row['key'], $blacklist) || ($row['key'] == "")) {
				$log = false;
			}

			if ($log) {
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				@file_put_contents(self::$configCache->get('system', 'db_log_index'), DateTimeFormat::utcNow()."\t".
						$row['key']."\t".$row['rows']."\t".$row['Extra']."\t".
						basename($backtrace[1]["file"])."\t".
						$backtrace[1]["line"]."\t".$backtrace[2]["function"]."\t".
						substr($query, 0, 2000)."\n", FILE_APPEND);
			}
		}
	}

	public static function escape($str) {
		if (self::$connected) {
			switch (self::$driver) {
				case 'pdo':
					return substr(@self::$connection->quote($str, PDO::PARAM_STR), 1, -1);

				case 'mysqli':
					return @self::$connection->real_escape_string($str);
			}
		} else {
			return str_replace("'", "\\'", $str);
		}
	}

	public static function connected() {
		$connected = false;

		if (is_null(self::$connection)) {
			return false;
		}

		switch (self::$driver) {
			case 'pdo':
				$r = self::p("SELECT 1");
				if (self::isResult($r)) {
					$row = self::toArray($r);
					$connected = ($row[0]['1'] == '1');
				}
				break;
			case 'mysqli':
				$connected = self::$connection->ping();
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
	 * @return string The input SQL string modified if necessary.
	 */
	public static function anyValueFallback($sql) {
		$server_info = self::serverInfo();
		if (version_compare($server_info, '5.7.5', '<') ||
			(stripos($server_info, 'MariaDB') !== false)) {
			$sql = str_ireplace('ANY_VALUE(', 'MIN(', $sql);
		}
		return $sql;
	}

	/**
	 * @brief beautifies the query - useful for "SHOW PROCESSLIST"
	 *
	 * This is safe when we bind the parameters later.
	 * The parameter values aren't part of the SQL.
	 *
	 * @param string $sql An SQL string without the values
	 * @return string The input SQL string modified if necessary.
	 */
	public static function cleanQuery($sql) {
		$search = ["\t", "\n", "\r", "  "];
		$replace = [' ', ' ', ' ', ' '];
		do {
			$oldsql = $sql;
			$sql = str_replace($search, $replace, $sql);
		} while ($oldsql != $sql);

		return $sql;
	}


	/**
	 * @brief Replaces the ? placeholders with the parameters in the $args array
	 *
	 * @param string $sql SQL query
	 * @param array $args The parameters that are to replace the ? placeholders
	 * @return string The replaced SQL query
	 */
	private static function replaceParameters($sql, $args) {
		$offset = 0;
		foreach ($args AS $param => $value) {
			if (is_int($args[$param]) || is_float($args[$param])) {
				$replace = intval($args[$param]);
			} else {
				$replace = "'".self::escape($args[$param])."'";
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
	 * @brief Convert parameter array to an universal form
	 * @param array $args Parameter array
	 * @return array universalized parameter array
	 */
	private static function getParam($args) {
		unset($args[0]);

		// When the second function parameter is an array then use this as the parameter array
		if ((count($args) > 0) && (is_array($args[1]))) {
			return $args[1];
		} else {
			return $args;
		}
	}

	/**
	 * @brief Executes a prepared statement that returns data
	 * @usage Example: $r = p("SELECT * FROM `item` WHERE `guid` = ?", $guid);
	 *
	 * Please only use it with complicated queries.
	 * For all regular queries please use DBA::select or DBA::exists
	 *
	 * @param string $sql SQL statement
	 * @return bool|object statement object or result object
	 * @throws \Exception
	 */
	public static function p($sql) {

		$stamp1 = microtime(true);

		$params = self::getParam(func_get_args());

		// Renumber the array keys to be sure that they fit
		$i = 0;
		$args = [];
		foreach ($params AS $param) {
			// Avoid problems with some MySQL servers and boolean values. See issue #3645
			if (is_bool($param)) {
				$param = (int)$param;
			}
			$args[++$i] = $param;
		}

		if (!self::$connected) {
			return false;
		}

		if ((substr_count($sql, '?') != count($args)) && (count($args) > 0)) {
			// Question: Should we continue or stop the query here?
			Logger::warning('Query parameters mismatch.', ['query' => $sql, 'args' => $args, 'callstack' => System::callstack()]);
		}

		$sql = self::cleanQuery($sql);
		$sql = self::anyValueFallback($sql);

		$orig_sql = $sql;

		if (self::$configCache->get('system', 'db_callstack') !== null) {
			$sql = "/*".System::callstack()." */ ".$sql;
		}

		self::$error = '';
		self::$errorno = 0;
		self::$affected_rows = 0;

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

		switch (self::$driver) {
			case 'pdo':
				// If there are no arguments we use "query"
				if (count($args) == 0) {
					if (!$retval = self::$connection->query($sql)) {
						$errorInfo = self::$connection->errorInfo();
						self::$error = $errorInfo[2];
						self::$errorno = $errorInfo[1];
						$retval = false;
						break;
					}
					self::$affected_rows = $retval->rowCount();
					break;
				}

				if (!$stmt = self::$connection->prepare($sql)) {
					$errorInfo = self::$connection->errorInfo();
					self::$error = $errorInfo[2];
					self::$errorno = $errorInfo[1];
					$retval = false;
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
					$errorInfo = $stmt->errorInfo();
					self::$error = $errorInfo[2];
					self::$errorno = $errorInfo[1];
					$retval = false;
				} else {
					$retval = $stmt;
					self::$affected_rows = $retval->rowCount();
				}
				break;
			case 'mysqli':
				// There are SQL statements that cannot be executed with a prepared statement
				$parts = explode(' ', $orig_sql);
				$command = strtolower($parts[0]);
				$can_be_prepared = in_array($command, ['select', 'update', 'insert', 'delete']);

				// The fallback routine is called as well when there are no arguments
				if (!$can_be_prepared || (count($args) == 0)) {
					$retval = self::$connection->query(self::replaceParameters($sql, $args));
					if (self::$connection->errno) {
						self::$error = self::$connection->error;
						self::$errorno = self::$connection->errno;
						$retval = false;
					} else {
						if (isset($retval->num_rows)) {
							self::$affected_rows = $retval->num_rows;
						} else {
							self::$affected_rows = self::$connection->affected_rows;
						}
					}
					break;
				}

				$stmt = self::$connection->stmt_init();

				if (!$stmt->prepare($sql)) {
					self::$error = $stmt->error;
					self::$errorno = $stmt->errno;
					$retval = false;
					break;
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
					self::$error = self::$connection->error;
					self::$errorno = self::$connection->errno;
					$retval = false;
				} else {
					$stmt->store_result();
					$retval = $stmt;
					self::$affected_rows = $retval->affected_rows;
				}
				break;
		}

		// We are having an own error logging in the function "e"
		if ((self::$errorno != 0) && !$called_from_e) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$error = self::$error;
			$errorno = self::$errorno;

			Logger::log('DB Error '.self::$errorno.': '.self::$error."\n".
				System::callstack(8)."\n".self::replaceParameters($sql, $args));

			// On a lost connection we try to reconnect - but only once.
			if ($errorno == 2006) {
				if (self::$in_retrial || !self::reconnect()) {
					// It doesn't make sense to continue when the database connection was lost
					if (self::$in_retrial) {
						Logger::log('Giving up retrial because of database error '.$errorno.': '.$error);
					} else {
						Logger::log("Couldn't reconnect after database error ".$errorno.': '.$error);
					}
					exit(1);
				} else {
					// We try it again
					Logger::log('Reconnected after database error '.$errorno.': '.$error);
					self::$in_retrial = true;
					$ret = self::p($sql, $args);
					self::$in_retrial = false;
					return $ret;
				}
			}

			self::$error = $error;
			self::$errorno = $errorno;
		}

		self::$profiler->saveTimestamp($stamp1, 'database', System::callstack());

		if (self::$configCache->get('system', 'db_log')) {
			$stamp2 = microtime(true);
			$duration = (float)($stamp2 - $stamp1);

			if (($duration > self::$configCache->get('system', 'db_loglimit'))) {
				$duration = round($duration, 3);
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				@file_put_contents(self::$configCache->get('system', 'db_log'), DateTimeFormat::utcNow()."\t".$duration."\t".
						basename($backtrace[1]["file"])."\t".
						$backtrace[1]["line"]."\t".$backtrace[2]["function"]."\t".
						substr(self::replaceParameters($sql, $args), 0, 2000)."\n", FILE_APPEND);
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
	 * @return boolean Was the query successfull? False is returned only if an error occurred
	 * @throws \Exception
	 */
	public static function e($sql) {

		$stamp = microtime(true);

		$params = self::getParam(func_get_args());

		// In a case of a deadlock we are repeating the query 20 times
		$timeout = 20;

		do {
			$stmt = self::p($sql, $params);

			if (is_bool($stmt)) {
				$retval = $stmt;
			} elseif (is_object($stmt)) {
				$retval = true;
			} else {
				$retval = false;
			}

			self::close($stmt);

		} while ((self::$errorno == 1213) && (--$timeout > 0));

		if (self::$errorno != 0) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$error = self::$error;
			$errorno = self::$errorno;

			Logger::log('DB Error '.self::$errorno.': '.self::$error."\n".
				System::callstack(8)."\n".self::replaceParameters($sql, $params));

			// On a lost connection we simply quit.
			// A reconnect like in self::p could be dangerous with modifications
			if ($errorno == 2006) {
				Logger::log('Giving up because of database error '.$errorno.': '.$error);
				exit(1);
			}

			self::$error = $error;
			self::$errorno = $errorno;
		}

		self::$profiler->saveTimestamp($stamp, "database_write", System::callstack());

		return $retval;
	}

	/**
	 * @brief Check if data exists
	 *
	 * @param string $table     Table name
	 * @param array  $condition array of fields for condition
	 *
	 * @return boolean Are there rows for that condition?
	 * @throws \Exception
	 */
	public static function exists($table, $condition) {
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

		$stmt = self::select($table, $fields, $condition, ['limit' => 1]);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = (self::numRows($stmt) > 0);
		}

		self::close($stmt);

		return $retval;
	}

	/**
	 * Fetches the first row
	 *
	 * Please use DBA::selectFirst or DBA::exists whenever this is possible.
	 *
	 * @brief Fetches the first row
	 * @param string $sql SQL statement
	 * @return array first row of query
	 * @throws \Exception
	 */
	public static function fetchFirst($sql) {
		$params = self::getParam(func_get_args());

		$stmt = self::p($sql, $params);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = self::fetch($stmt);
		}

		self::close($stmt);

		return $retval;
	}

	/**
	 * @brief Returns the number of affected rows of the last statement
	 *
	 * @return int Number of rows
	 */
	public static function affectedRows() {
		return self::$affected_rows;
	}

	/**
	 * @brief Returns the number of columns of a statement
	 *
	 * @param object Statement object
	 * @return int Number of columns
	 */
	public static function columnCount($stmt) {
		if (!is_object($stmt)) {
			return 0;
		}
		switch (self::$driver) {
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
	 * @return int Number of rows
	 */
	public static function numRows($stmt) {
		if (!is_object($stmt)) {
			return 0;
		}
		switch (self::$driver) {
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
	 * @return array current row
	 */
	public static function fetch($stmt) {

		$stamp1 = microtime(true);

		$columns = [];

		if (!is_object($stmt)) {
			return false;
		}

		switch (self::$driver) {
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

		self::$profiler->saveTimestamp($stamp1, 'database', System::callstack());

		return $columns;
	}

	/**
	 * @brief Insert a row into a table
	 *
	 * @param string $table               Table name
	 * @param array  $param               parameter array
	 * @param bool   $on_duplicate_update Do an update on a duplicate entry
	 *
	 * @return boolean was the insert successful?
	 * @throws \Exception
	 */
	public static function insert($table, $param, $on_duplicate_update = false) {

		if (empty($table) || empty($param)) {
			Logger::log('Table and fields have to be set');
			return false;
		}

		$sql = "INSERT INTO `".self::escape($table)."` (`".implode("`, `", array_keys($param))."`) VALUES (".
			substr(str_repeat("?, ", count($param)), 0, -2).")";

		if ($on_duplicate_update) {
			$sql .= " ON DUPLICATE KEY UPDATE `".implode("` = ?, `", array_keys($param))."` = ?";

			$values = array_values($param);
			$param = array_merge_recursive($values, $values);
		}

		return self::e($sql, $param);
	}

	/**
	 * @brief Fetch the id of the last insert command
	 *
	 * @return integer Last inserted id
	 */
	public static function lastInsertId() {
		switch (self::$driver) {
			case 'pdo':
				$id = self::$connection->lastInsertId();
				break;
			case 'mysqli':
				$id = self::$connection->insert_id;
				break;
		}
		return $id;
	}

	/**
	 * @brief Locks a table for exclusive write access
	 *
	 * This function can be extended in the future to accept a table array as well.
	 *
	 * @param string $table Table name
	 *
	 * @return boolean was the lock successful?
	 * @throws \Exception
	 */
	public static function lock($table) {
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		if (self::$driver == 'pdo') {
			self::e("SET autocommit=0");
			self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		} else {
			self::$connection->autocommit(false);
		}

		$success = self::e("LOCK TABLES `".self::escape($table)."` WRITE");

		if (self::$driver == 'pdo') {
			self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}

		if (!$success) {
			if (self::$driver == 'pdo') {
				self::e("SET autocommit=1");
			} else {
				self::$connection->autocommit(true);
			}
		} else {
			self::$in_transaction = true;
		}
		return $success;
	}

	/**
	 * @brief Unlocks all locked tables
	 *
	 * @return boolean was the unlock successful?
	 * @throws \Exception
	 */
	public static function unlock() {
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		self::performCommit();

		if (self::$driver == 'pdo') {
			self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		}

		$success = self::e("UNLOCK TABLES");

		if (self::$driver == 'pdo') {
			self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			self::e("SET autocommit=1");
		} else {
			self::$connection->autocommit(true);
		}

		self::$in_transaction = false;
		return $success;
	}

	/**
	 * @brief Starts a transaction
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function transaction() {
		if (!self::performCommit()) {
			return false;
		}

		switch (self::$driver) {
			case 'pdo':
				if (!self::$connection->inTransaction() && !self::$connection->beginTransaction()) {
					return false;
				}
				break;

			case 'mysqli':
				if (!self::$connection->begin_transaction()) {
					return false;
				}
				break;
		}

		self::$in_transaction = true;
		return true;
	}

	private static function performCommit()
	{
		switch (self::$driver) {
			case 'pdo':
				if (!self::$connection->inTransaction()) {
					return true;
				}

				return self::$connection->commit();

			case 'mysqli':
				return self::$connection->commit();
		}

		return true;
	}

	/**
	 * @brief Does a commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function commit() {
		if (!self::performCommit()) {
			return false;
		}
		self::$in_transaction = false;
		return true;
	}

	/**
	 * @brief Does a rollback
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function rollback() {
		$ret = false;

		switch (self::$driver) {
			case 'pdo':
				if (!self::$connection->inTransaction()) {
					$ret = true;
					break;
				}
				$ret = self::$connection->rollBack();
				break;

			case 'mysqli':
				$ret = self::$connection->rollback();
				break;
		}
		self::$in_transaction = false;
		return $ret;
	}

	/**
	 * @brief Build the array with the table relations
	 *
	 * The array is build from the database definitions in DBStructure.php
	 *
	 * This process must only be started once, since the value is cached.
	 */
	private static function buildRelationData() {
		$definition = DBStructure::definition(self::$basePath);

		foreach ($definition AS $table => $structure) {
			foreach ($structure['fields'] AS $field => $field_struct) {
				if (isset($field_struct['relation'])) {
					foreach ($field_struct['relation'] AS $rel_table => $rel_field) {
						self::$relation[$rel_table][$rel_field][$table][] = $field;
					}
				}
			}
		}
	}

	/**
	 * @brief Delete a row from a table
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
	public static function delete($table, array $conditions, array $options = [], array &$callstack = [])
	{
		if (empty($table) || empty($conditions)) {
			Logger::log('Table and conditions have to be set');
			return false;
		}

		$commands = [];

		// Create a key for the loop prevention
		$key = $table . ':' . json_encode($conditions);

		// We quit when this key already exists in the callstack.
		if (isset($callstack[$key])) {
			return $commands;
		}

		$callstack[$key] = true;

		$table = self::escape($table);

		$commands[$key] = ['table' => $table, 'conditions' => $conditions];

		// Don't use "defaults" here, since it would set "false" to "true"
		if (isset($options['cascade'])) {
			$cascade = $options['cascade'];
		} else {
			$cascade = true;
		}

		// To speed up the whole process we cache the table relations
		if ($cascade && count(self::$relation) == 0) {
			self::buildRelationData();
		}

		// Is there a relation entry for the table?
		if ($cascade && isset(self::$relation[$table])) {
			// We only allow a simple "one field" relation.
			$field = array_keys(self::$relation[$table])[0];
			$rel_def = array_values(self::$relation[$table])[0];

			// Create a key for preventing double queries
			$qkey = $field . '-' . $table . ':' . json_encode($conditions);

			// When the search field is the relation field, we don't need to fetch the rows
			// This is useful when the leading record is already deleted in the frontend but the rest is done in the backend
			if ((count($conditions) == 1) && ($field == array_keys($conditions)[0])) {
				foreach ($rel_def AS $rel_table => $rel_fields) {
					foreach ($rel_fields AS $rel_field) {
						self::delete($rel_table, [$rel_field => array_values($conditions)[0]], $options, $callstack);
					}
				}
				// We quit when this key already exists in the callstack.
			} elseif (!isset($callstack[$qkey])) {
				$callstack[$qkey] = true;

				// Fetch all rows that are to be deleted
				$data = self::select($table, [$field], $conditions);

				while ($row = self::fetch($data)) {
					self::delete($table, [$field => $row[$field]], $options, $callstack);
				}

				self::close($data);

				// Since we had split the delete command we don't need the original command anymore
				unset($commands[$key]);
			}
		}

		// Now we finalize the process
		$do_transaction = !self::$in_transaction;

		if ($do_transaction) {
			self::transaction();
		}

		$compacted = [];
		$counter = [];

		foreach ($commands AS $command) {
			$conditions = $command['conditions'];
			reset($conditions);
			$first_key = key($conditions);

			$condition_string = self::buildCondition($conditions);

			if ((count($command['conditions']) > 1) || is_int($first_key)) {
				$sql = "DELETE FROM `" . $command['table'] . "`" . $condition_string;
				Logger::log(self::replaceParameters($sql, $conditions), Logger::DATA);

				if (!self::e($sql, $conditions)) {
					if ($do_transaction) {
						self::rollback();
					}
					return false;
				}
			} else {
				$key_table = $command['table'];
				$key_condition = array_keys($command['conditions'])[0];
				$value = array_values($command['conditions'])[0];

				// Split the SQL queries in chunks of 100 values
				// We do the $i stuff here to make the code better readable
				$i = isset($counter[$key_table][$key_condition]) ? $counter[$key_table][$key_condition] : 0;
				if (isset($compacted[$key_table][$key_condition][$i]) && count($compacted[$key_table][$key_condition][$i]) > 100) {
					++$i;
				}

				$compacted[$key_table][$key_condition][$i][$value] = $value;
				$counter[$key_table][$key_condition] = $i;
			}
		}
		foreach ($compacted AS $table => $values) {
			foreach ($values AS $field => $field_value_list) {
				foreach ($field_value_list AS $field_values) {
					$sql = "DELETE FROM `" . $table . "` WHERE `" . $field . "` IN (" .
						substr(str_repeat("?, ", count($field_values)), 0, -2) . ");";

					Logger::log(self::replaceParameters($sql, $field_values), Logger::DATA);

					if (!self::e($sql, $field_values)) {
						if ($do_transaction) {
							self::rollback();
						}
						return false;
					}
				}
			}
		}
		if ($do_transaction) {
			self::commit();
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
	 * @param string        $table      Table name
	 * @param array         $fields     contains the fields that are updated
	 * @param array         $condition  condition array with the key values
	 * @param array|boolean $old_fields array with the old field values that are about to be replaced (true = update on duplicate)
	 *
	 * @return boolean was the update successfull?
	 * @throws \Exception
	 */
	public static function update($table, $fields, $condition, $old_fields = []) {

		if (empty($table) || empty($fields) || empty($condition)) {
			Logger::log('Table, fields and condition have to be set');
			return false;
		}

		$table = self::escape($table);

		$condition_string = self::buildCondition($condition);

		if (is_bool($old_fields)) {
			$do_insert = $old_fields;

			$old_fields = self::selectFirst($table, [], $condition);

			if (is_bool($old_fields)) {
				if ($do_insert) {
					$values = array_merge($condition, $fields);
					return self::insert($table, $values, $do_insert);
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

		$sql = "UPDATE `".$table."` SET `".
			implode("` = ?, `", array_keys($fields))."` = ?".$condition_string;

		$params1 = array_values($fields);
		$params2 = array_values($condition);
		$params = array_merge_recursive($params1, $params2);

		return self::e($sql, $params);
	}

	/**
	 * Retrieve a single record from a table and returns it in an associative array
	 *
	 * @brief Retrieve a single record from a table
	 * @param string $table
	 * @param array  $fields
	 * @param array  $condition
	 * @param array  $params
	 * @return bool|array
	 * @throws \Exception
	 * @see   self::select
	 */
	public static function selectFirst($table, array $fields = [], array $condition = [], $params = [])
	{
		$params['limit'] = 1;
		$result = self::select($table, $fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = self::fetch($result);
			self::close($result);
			return $row;
		}
	}

	/**
	 * @brief Select rows from a table
	 *
	 * @param string $table     Table name
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
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
	public static function select($table, array $fields = [], array $condition = [], array $params = [])
	{
		if ($table == '') {
			return false;
		}

		$table = self::escape($table);

		if (count($fields) > 0) {
			$select_fields = "`" . implode("`, `", array_values($fields)) . "`";
		} else {
			$select_fields = "*";
		}

		$condition_string = self::buildCondition($condition);

		$param_string = self::buildParameter($params);

		$sql = "SELECT " . $select_fields . " FROM `" . $table . "`" . $condition_string . $param_string;

		$result = self::p($sql, $condition);

		return $result;
	}

	/**
	 * @brief Counts the rows from a table satisfying the provided condition
	 *
	 * @param string $table     Table name
	 * @param array  $condition array of fields for condition
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
	public static function count($table, array $condition = [])
	{
		if ($table == '') {
			return false;
		}

		$condition_string = self::buildCondition($condition);

		$sql = "SELECT COUNT(*) AS `count` FROM `".$table."`".$condition_string;

		$row = self::fetchFirst($sql, $condition);

		return $row['count'];
	}

	/**
	 * @brief Returns the SQL condition string built from the provided condition array
	 *
	 * This function operates with two modes.
	 * - Supplied with a filed/value associative array, it builds simple strict
	 *   equality conditions linked by AND.
	 * - Supplied with a flat list, the first element is the condition string and
	 *   the following arguments are the values to be interpolated
	 *
	 * $condition = ["uid" => 1, "network" => 'dspr'];
	 * or:
	 * $condition = ["`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr'];
	 *
	 * In either case, the provided array is left with the parameters only
	 *
	 * @param array $condition
	 * @return string
	 */
	public static function buildCondition(array &$condition = [])
	{
		$condition_string = '';
		if (count($condition) > 0) {
			reset($condition);
			$first_key = key($condition);
			if (is_int($first_key)) {
				$condition_string = " WHERE (" . array_shift($condition) . ")";
			} else {
				$new_values = [];
				$condition_string = "";
				foreach ($condition as $field => $value) {
					if ($condition_string != "") {
						$condition_string .= " AND ";
					}
					if (is_array($value)) {
						/* Workaround for MySQL Bug #64791.
						 * Never mix data types inside any IN() condition.
						 * In case of mixed types, cast all as string.
						 * Logic needs to be consistent with DBA::p() data types.
						 */
						$is_int = false;
						$is_alpha = false;
						foreach ($value as $single_value) {
							if (is_int($single_value)) {
								$is_int = true;
							} else {
								$is_alpha = true;
							}
						}

						if ($is_int && $is_alpha) {
							foreach ($value as &$ref) {
								if (is_int($ref)) {
									$ref = (string)$ref;
								}
							}
							unset($ref); //Prevent accidental re-use.
						}

						$new_values = array_merge($new_values, array_values($value));
						$placeholders = substr(str_repeat("?, ", count($value)), 0, -2);
						$condition_string .= "`" . $field . "` IN (" . $placeholders . ")";
					} else {
						$new_values[$field] = $value;
						$condition_string .= "`" . $field . "` = ?";
					}
				}
				$condition_string = " WHERE (" . $condition_string . ")";
				$condition = $new_values;
			}
		}

		return $condition_string;
	}

	/**
	 * @brief Returns the SQL parameter string built from the provided parameter array
	 *
	 * @param array $params
	 * @return string
	 */
	public static function buildParameter(array $params = [])
	{
		$order_string = '';
		if (isset($params['order'])) {
			$order_string = " ORDER BY ";
			foreach ($params['order'] AS $fields => $order) {
				if (!is_int($fields)) {
					$order_string .= "`" . $fields . "` " . ($order ? "DESC" : "ASC") . ", ";
				} else {
					$order_string .= "`" . $order . "`, ";
				}
			}
			$order_string = substr($order_string, 0, -2);
		}

		$limit_string = '';
		if (isset($params['limit']) && is_numeric($params['limit'])) {
			$limit_string = " LIMIT " . intval($params['limit']);
		}

		if (isset($params['limit']) && is_array($params['limit'])) {
			$limit_string = " LIMIT " . intval($params['limit'][0]) . ", " . intval($params['limit'][1]);
		}

		return $order_string.$limit_string;
	}

	/**
	 * @brief Fills an array with data from a query
	 *
	 * @param object $stmt statement object
	 * @param bool   $do_close
	 * @return array Data array
	 */
	public static function toArray($stmt, $do_close = true) {
		if (is_bool($stmt)) {
			return $stmt;
		}

		$data = [];
		while ($row = self::fetch($stmt)) {
			$data[] = $row;
		}
		if ($do_close) {
			self::close($stmt);
		}
		return $data;
	}

	/**
	 * @brief Returns the error number of the last query
	 *
	 * @return string Error number (0 if no error)
	 */
	public static function errorNo() {
		return self::$errorno;
	}

	/**
	 * @brief Returns the error message of the last query
	 *
	 * @return string Error message ('' if no error)
	 */
	public static function errorMessage() {
		return self::$error;
	}

	/**
	 * @brief Closes the current statement
	 *
	 * @param object $stmt statement object
	 * @return boolean was the close successful?
	 */
	public static function close($stmt) {

		$stamp1 = microtime(true);

		if (!is_object($stmt)) {
			return false;
		}

		switch (self::$driver) {
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

		self::$profiler->saveTimestamp($stamp1, 'database', System::callstack());

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
	public static function processlist()
	{
		$ret = self::p("SHOW PROCESSLIST");
		$data = self::toArray($ret);

		$processes = 0;
		$states = [];
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
			$statelist .= $state.": ".$usage;
		}
		return(["list" => $statelist, "amount" => $processes]);
	}

	/**
	 * Checks if $array is a filled array with at least one entry.
	 *
	 * @param mixed $array A filled array with at least one entry
	 *
	 * @return boolean Whether $array is a filled array or an object with rows
	 */
	public static function isResult($array)
	{
		// It could be a return value from an update statement
		if (is_bool($array)) {
			return $array;
		}

		if (is_object($array)) {
			return self::numRows($array) > 0;
		}

		return (is_array($array) && (count($array) > 0));
	}

	/**
	 * @brief Callback function for "esc_array"
	 *
	 * @param mixed   $value         Array value
	 * @param string  $key           Array key
	 * @param boolean $add_quotation add quotation marks for string values
	 * @return void
	 */
	private static function escapeArrayCallback(&$value, $key, $add_quotation)
	{
		if (!$add_quotation) {
			if (is_bool($value)) {
				$value = ($value ? '1' : '0');
			} else {
				$value = self::escape($value);
			}
			return;
		}

		if (is_bool($value)) {
			$value = ($value ? 'true' : 'false');
		} elseif (is_float($value) || is_integer($value)) {
			$value = (string) $value;
		} else {
			$value = "'" . self::escape($value) . "'";
		}
	}

	/**
	 * @brief Escapes a whole array
	 *
	 * @param mixed   $arr           Array with values to be escaped
	 * @param boolean $add_quotation add quotation marks for string values
	 * @return void
	 */
	public static function escapeArray(&$arr, $add_quotation = false)
	{
		array_walk($arr, 'self::escapeArrayCallback', $add_quotation);
	}
}
