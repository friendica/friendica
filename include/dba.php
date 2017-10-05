<?php
use \Friendica\Core\System;

require_once("dbm.php");
require_once('include/datetime.php');

/**
 * @class MySQL database class
 *
 * This class is for the low level database stuff that does driver specific things.
 */

class dba {

	private $debug = 0;
	private $db;
	private $result;
	private $driver;
	public  $connected = false;
	public  $error = false;
	public  $errorno = 0;
	public  $affected_rows = 0;
	private $_server_info = '';
	private static $in_transaction = false;
	private static $dbo;
	private static $relation = array();

	function __construct($serveraddr, $user, $pass, $db, $install = false) {
		$a = get_app();

		$stamp1 = microtime(true);

		$serveraddr = trim($serveraddr);

		$serverdata = explode(':', $serveraddr);
		$server = $serverdata[0];

		if (count($serverdata) > 1) {
			$port = trim($serverdata[1]);
		}

		$server = trim($server);
		$user = trim($user);
		$pass = trim($pass);
		$db = trim($db);

		if (!(strlen($server) && strlen($user))) {
			$this->connected = false;
			$this->db = null;
			return;
		}

		if ($install) {
			if (strlen($server) && ($server !== 'localhost') && ($server !== '127.0.0.1')) {
				if (! dns_get_record($server, DNS_A + DNS_CNAME + DNS_PTR)) {
					$this->error = sprintf(t('Cannot locate DNS info for database server \'%s\''), $server);
					$this->connected = false;
					$this->db = null;
					return;
				}
			}
		}

		if (class_exists('\PDO') && in_array('mysql', PDO::getAvailableDrivers())) {
			$this->driver = 'pdo';
			$connect = "mysql:host=".$server.";dbname=".$db;

			if (isset($port)) {
				$connect .= ";port=".$port;
			}

			if (isset($a->config["system"]["db_charset"])) {
				$connect .= ";charset=".$a->config["system"]["db_charset"];
			}
			try {
				$this->db = @new PDO($connect, $user, $pass);
				$this->connected = true;
			} catch (PDOException $e) {
				$this->connected = false;
			}
		}

		if (!$this->connected && class_exists('mysqli')) {
			$this->driver = 'mysqli';
			$this->db = @new mysqli($server, $user, $pass, $db, $port);
			if (!mysqli_connect_errno()) {
				$this->connected = true;

				if (isset($a->config["system"]["db_charset"])) {
					$this->db->set_charset($a->config["system"]["db_charset"]);
				}
			}
		}

		if (!$this->connected && function_exists('mysql_connect')) {
			$this->driver = 'mysql';
			$this->db = mysql_connect($serveraddr, $user, $pass);
			if ($this->db && mysql_select_db($db, $this->db)) {
				$this->connected = true;

				if (isset($a->config["system"]["db_charset"])) {
					mysql_set_charset($a->config["system"]["db_charset"], $this->db);
				}
			}
		}

		// No suitable SQL driver was found.
		if (!$this->connected) {
			$this->db = null;
			if (!$install) {
				system_unavailable();
			}
		}
		$a->save_timestamp($stamp1, "network");

		self::$dbo = $this;
	}

	/**
	 * @brief Returns the MySQL server version string
	 *
	 * This function discriminate between the deprecated mysql API and the current
	 * object-oriented mysqli API. Example of returned string: 5.5.46-0+deb8u1
	 *
	 * @return string
	 */
	public function server_info() {
		if ($this->_server_info == '') {
			switch ($this->driver) {
				case 'pdo':
					$this->_server_info = $this->db->getAttribute(PDO::ATTR_SERVER_VERSION);
					break;
				case 'mysqli':
					$this->_server_info = $this->db->server_info;
					break;
				case 'mysql':
					$this->_server_info = mysql_get_server_info($this->db);
					break;
			}
		}
		return $this->_server_info;
	}

	/**
	 * @brief Returns the selected database name
	 *
	 * @return string
	 */
	public function database_name() {
		$r = $this->q("SELECT DATABASE() AS `db`");

		return $r[0]['db'];
	}

	/**
	 * @brief Analyze a database query and log this if some conditions are met.
	 *
	 * @param string $query The database query that will be analyzed
	 */
	public function log_index($query) {
		$a = get_app();

		if (empty($a->config["system"]["db_log_index"])) {
			return;
		}

		// Don't explain an explain statement
		if (strtolower(substr($query, 0, 7)) == "explain") {
			return;
		}

		// Only do the explain on "select", "update" and "delete"
		if (!in_array(strtolower(substr($query, 0, 6)), array("select", "update", "delete"))) {
			return;
		}

		$r = $this->q("EXPLAIN ".$query);
		if (!dbm::is_result($r)) {
			return;
		}

		$watchlist = explode(',', $a->config["system"]["db_log_index_watch"]);
		$blacklist = explode(',', $a->config["system"]["db_log_index_blacklist"]);

		foreach ($r AS $row) {
			if ((intval($a->config["system"]["db_loglimit_index"]) > 0)) {
				$log = (in_array($row['key'], $watchlist) &&
					($row['rows'] >= intval($a->config["system"]["db_loglimit_index"])));
			} else {
				$log = false;
			}

			if ((intval($a->config["system"]["db_loglimit_index_high"]) > 0) && ($row['rows'] >= intval($a->config["system"]["db_loglimit_index_high"]))) {
				$log = true;
			}

			if (in_array($row['key'], $blacklist) || ($row['key'] == "")) {
				$log = false;
			}

			if ($log) {
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				@file_put_contents($a->config["system"]["db_log_index"], datetime_convert()."\t".
						$row['key']."\t".$row['rows']."\t".$row['Extra']."\t".
						basename($backtrace[1]["file"])."\t".
						$backtrace[1]["line"]."\t".$backtrace[2]["function"]."\t".
						substr($query, 0, 2000)."\n", FILE_APPEND);
			}
		}
	}

	/**
	 * @brief execute SQL query - deprecated
	 *
	 * Please use the dba:: functions instead:
	 * dba::select, dba::exists, dba::insert
	 * dba::delete, dba::update, dba::p, dba::e
	 *
	 * @param string $sql SQL query
	 * @return array Query array
	 */
	public function q($sql) {
		$ret = self::p($sql);

		if (is_bool($ret)) {
			return $ret;
		}

		$columns = self::columnCount($ret);

		$data = self::inArray($ret);

		if ((count($data) == 0) && ($columns == 0)) {
			return true;
		}

		return $data;
	}

	public function escape($str) {
		if ($this->db && $this->connected) {
			switch ($this->driver) {
				case 'pdo':
					return substr(@$this->db->quote($str, PDO::PARAM_STR), 1, -1);
				case 'mysqli':
					return @$this->db->real_escape_string($str);
				case 'mysql':
					return @mysql_real_escape_string($str,$this->db);
			}
		}
	}

	function connected() {
		switch ($this->driver) {
			case 'pdo':
				// Not sure if this really is working like expected
				$connected = ($this->db->getAttribute(PDO::ATTR_CONNECTION_STATUS) != "");
				break;
			case 'mysqli':
				$connected = $this->db->ping();
				break;
			case 'mysql':
				$connected = mysql_ping($this->db);
				break;
		}
		return $connected;
	}

	function __destruct() {
		if ($this->db) {
			switch ($this->driver) {
				case 'pdo':
					$this->db = null;
					break;
				case 'mysqli':
					$this->db->close();
					break;
				case 'mysql':
					mysql_close($this->db);
					break;
			}
		}
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
	public function any_value_fallback($sql) {
		$server_info = $this->server_info();
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
	public function clean_query($sql) {
		$search = array("\t", "\n", "\r", "  ");
		$replace = array(' ', ' ', ' ', ' ');
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
	private static function replace_parameters($sql, $args) {
		$offset = 0;
		foreach ($args AS $param => $value) {
			if (is_int($args[$param]) || is_float($args[$param])) {
				$replace = intval($args[$param]);
			} else {
				$replace = "'".self::$dbo->escape($args[$param])."'";
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
	 * For all regular queries please use dba::select or dba::exists
	 *
	 * @param string $sql SQL statement
	 * @return object statement object
	 */
	public static function p($sql) {
		$a = get_app();

		$stamp1 = microtime(true);

		$params = self::getParam(func_get_args());

		// Renumber the array keys to be sure that they fit
		$i = 0;
		$args = array();
		foreach ($params AS $param) {
			// Avoid problems with some MySQL servers and boolean values. See issue #3645
			if (is_bool($param)) {
				$param = (int)$param;
			}
			$args[++$i] = $param;
		}

		if (!self::$dbo || !self::$dbo->connected) {
			return false;
		}

		if ((substr_count($sql, '?') != count($args)) && (count($args) > 0)) {
			// Question: Should we continue or stop the query here?
			logger('Parameter mismatch. Query "'.$sql.'" - Parameters '.print_r($args, true), LOGGER_DEBUG);
		}

		$sql = self::$dbo->clean_query($sql);
		$sql = self::$dbo->any_value_fallback($sql);

		$orig_sql = $sql;

		if (x($a->config,'system') && x($a->config['system'], 'db_callstack')) {
			$sql = "/*".System::callstack()." */ ".$sql;
		}

		self::$dbo->error = '';
		self::$dbo->errorno = 0;
		self::$dbo->affected_rows = 0;

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

		switch (self::$dbo->driver) {
			case 'pdo':
				// If there are no arguments we use "query"
				if (count($args) == 0) {
					if (!$retval = self::$dbo->db->query($sql)) {
						$errorInfo = self::$dbo->db->errorInfo();
						self::$dbo->error = $errorInfo[2];
						self::$dbo->errorno = $errorInfo[1];
						$retval = false;
						break;
					}
					self::$dbo->affected_rows = $retval->rowCount();
					break;
				}

				if (!$stmt = self::$dbo->db->prepare($sql)) {
					$errorInfo = self::$dbo->db->errorInfo();
					self::$dbo->error = $errorInfo[2];
					self::$dbo->errorno = $errorInfo[1];
					$retval = false;
					break;
				}

				foreach ($args AS $param => $value) {
					$stmt->bindParam($param, $args[$param]);
				}

				if (!$stmt->execute()) {
					$errorInfo = $stmt->errorInfo();
					self::$dbo->error = $errorInfo[2];
					self::$dbo->errorno = $errorInfo[1];
					$retval = false;
				} else {
					$retval = $stmt;
					self::$dbo->affected_rows = $retval->rowCount();
				}
				break;
			case 'mysqli':
				// There are SQL statements that cannot be executed with a prepared statement
				$parts = explode(' ', $orig_sql);
				$command = strtolower($parts[0]);
				$can_be_prepared = in_array($command, array('select', 'update', 'insert', 'delete'));

				// The fallback routine is called as well when there are no arguments
				if (!$can_be_prepared || (count($args) == 0)) {
					$retval = self::$dbo->db->query(self::replace_parameters($sql, $args));
					if (self::$dbo->db->errno) {
						self::$dbo->error = self::$dbo->db->error;
						self::$dbo->errorno = self::$dbo->db->errno;
						$retval = false;
					} else {
						if (isset($retval->num_rows)) {
							self::$dbo->affected_rows = $retval->num_rows;
						} else {
							self::$dbo->affected_rows = self::$dbo->db->affected_rows;
						}
					}
					break;
				}

				$stmt = self::$dbo->db->stmt_init();

				if (!$stmt->prepare($sql)) {
					self::$dbo->error = $stmt->error;
					self::$dbo->errorno = $stmt->errno;
					$retval = false;
					break;
				}

				$params = '';
				$values = array();
				foreach ($args AS $param => $value) {
					if (is_int($args[$param])) {
						$params .= 'i';
					} elseif (is_float($args[$param])) {
						$params .= 'd';
					} elseif (is_string($args[$param])) {
						$params .= 's';
					} else {
						$params .= 'b';
					}
					$values[] = &$args[$param];
				}

				if (count($values) > 0) {
					array_unshift($values, $params);
					call_user_func_array(array($stmt, 'bind_param'), $values);
				}

				if (!$stmt->execute()) {
					self::$dbo->error = self::$dbo->db->error;
					self::$dbo->errorno = self::$dbo->db->errno;
					$retval = false;
				} else {
					$stmt->store_result();
					$retval = $stmt;
					self::$dbo->affected_rows = $retval->affected_rows;
				}
				break;
			case 'mysql':
				// For the old "mysql" functions we cannot use prepared statements
				$retval = mysql_query(self::replace_parameters($sql, $args), self::$dbo->db);
				if (mysql_errno(self::$dbo->db)) {
					self::$dbo->error = mysql_error(self::$dbo->db);
					self::$dbo->errorno = mysql_errno(self::$dbo->db);
				} else {
					self::$dbo->affected_rows = mysql_affected_rows($retval);

					// Due to missing mysql_* support this here wasn't tested at all
					// See here: http://php.net/manual/en/function.mysql-num-rows.php
					if (self::$dbo->affected_rows <= 0) {
						self::$dbo->affected_rows = mysql_num_rows($retval);
					}
				}
				break;
		}

		// We are having an own error logging in the function "e"
		if ((self::$dbo->errorno != 0) && !$called_from_e) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$error = self::$dbo->error;
			$errorno = self::$dbo->errorno;

			logger('DB Error '.self::$dbo->errorno.': '.self::$dbo->error."\n".
				System::callstack(8)."\n".self::replace_parameters($sql, $params));

			self::$dbo->error = $error;
			self::$dbo->errorno = $errorno;
		}

		$a->save_timestamp($stamp1, 'database');

		if (x($a->config,'system') && x($a->config['system'], 'db_log')) {

			$stamp2 = microtime(true);
			$duration = (float)($stamp2 - $stamp1);

			if (($duration > $a->config["system"]["db_loglimit"])) {
				$duration = round($duration, 3);
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				@file_put_contents($a->config["system"]["db_log"], datetime_convert()."\t".$duration."\t".
						basename($backtrace[1]["file"])."\t".
						$backtrace[1]["line"]."\t".$backtrace[2]["function"]."\t".
						substr(self::replace_parameters($sql, $args), 0, 2000)."\n", FILE_APPEND);
			}
		}
		return $retval;
	}

	/**
	 * @brief Executes a prepared statement like UPDATE or INSERT that doesn't return data
	 *
	 * Please use dba::delete, dba::insert, dba::update, ... instead
	 *
	 * @param string $sql SQL statement
	 * @return boolean Was the query successfull? False is returned only if an error occurred
	 */
	public static function e($sql) {
		$a = get_app();

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

		} while ((self::$dbo->errorno == 1213) && (--$timeout > 0));

		if (self::$dbo->errorno != 0) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$error = self::$dbo->error;
			$errorno = self::$dbo->errorno;

			logger('DB Error '.self::$dbo->errorno.': '.self::$dbo->error."\n".
				System::callstack(8)."\n".self::replace_parameters($sql, $params));

			self::$dbo->error = $error;
			self::$dbo->errorno = $errorno;
		}

		$a->save_timestamp($stamp, "database_write");

		return $retval;
	}

	/**
	 * @brief Check if data exists
	 *
	 * @param string $table Table name
	 * @param array $condition array of fields for condition
	 *
	 * @return boolean Are there rows for that condition?
	 */
	public static function exists($table, $condition) {
		if (empty($table)) {
			return false;
		}

		$fields = array();

		$array_element = each($condition);
		$array_key = $array_element['key'];
		if (!is_int($array_key)) {
			$fields = array($array_key);
		}

		$stmt = self::select($table, $fields, $condition, array('limit' => 1, 'only_query' => true));

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = (self::num_rows($stmt) > 0);
		}

		self::close($stmt);

		return $retval;
	}

	/**
	 * @brief Fetches the first row
	 *
	 * Please use dba::select or dba::exists whenever this is possible.
	 *
	 * @param string $sql SQL statement
	 * @return array first row of query
	 */
	public static function fetch_first($sql) {
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
	public static function affected_rows() {
		return self::$dbo->affected_rows;
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
		switch (self::$dbo->driver) {
			case 'pdo':
				return $stmt->columnCount();
			case 'mysqli':
				return $stmt->field_count;
			case 'mysql':
				return mysql_affected_rows($stmt);
		}
		return 0;
	}
	/**
	 * @brief Returns the number of rows of a statement
	 *
	 * @param object Statement object
	 * @return int Number of rows
	 */
	public static function num_rows($stmt) {
		if (!is_object($stmt)) {
			return 0;
		}
		switch (self::$dbo->driver) {
			case 'pdo':
				return $stmt->rowCount();
			case 'mysqli':
				return $stmt->num_rows;
			case 'mysql':
				return mysql_num_rows($stmt);
		}
		return 0;
	}

	/**
	 * @brief Fetch a single row
	 *
	 * @param object $stmt statement object
	 * @return array current row
	 */
	public static function fetch($stmt) {
		if (!is_object($stmt)) {
			return false;
		}

		switch (self::$dbo->driver) {
			case 'pdo':
				return $stmt->fetch(PDO::FETCH_ASSOC);
			case 'mysqli':
				if (get_class($stmt) == 'mysqli_result') {
					return $stmt->fetch_assoc();
				}

				// This code works, but is slow

				// Bind the result to a result array
				$cols = array();

				$cols_num = array();
				for ($x = 0; $x < $stmt->field_count; $x++) {
					$cols[] = &$cols_num[$x];
				}

				call_user_func_array(array($stmt, 'bind_result'), $cols);

				if (!$stmt->fetch()) {
					return false;
				}

				// The slow part:
				// We need to get the field names for the array keys
				// It seems that there is no better way to do this.
				$result = $stmt->result_metadata();
				$fields = $result->fetch_fields();

				$columns = array();
				foreach ($cols_num AS $param => $col) {
					$columns[$fields[$param]->name] = $col;
				}
				return $columns;
			case 'mysql':
				return mysql_fetch_array(self::$dbo->result, MYSQL_ASSOC);
		}
	}

	/**
	 * @brief Insert a row into a table
	 *
	 * @param string $table Table name
	 * @param array $param parameter array
	 * @param bool $on_duplicate_update Do an update on a duplicate entry
	 *
	 * @return boolean was the insert successfull?
	 */
	public static function insert($table, $param, $on_duplicate_update = false) {
		$sql = "INSERT INTO `".self::$dbo->escape($table)."` (`".implode("`, `", array_keys($param))."`) VALUES (".
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
		switch (self::$dbo->driver) {
			case 'pdo':
				$id = self::$dbo->db->lastInsertId();
				break;
			case 'mysqli':
				$id = self::$dbo->db->insert_id;
				break;
			case 'mysql':
				$id = mysql_insert_id(self::$dbo);
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
	 */
	public static function lock($table) {
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		self::e("SET autocommit=0");
		$success = self::e("LOCK TABLES `".self::$dbo->escape($table)."` WRITE");
		if (!$success) {
			self::e("SET autocommit=1");
		} else {
			self::$in_transaction = true;
		}
		return $success;
	}

	/**
	 * @brief Unlocks all locked tables
	 *
	 * @return boolean was the unlock successful?
	 */
	public static function unlock() {
		// See here: https://dev.mysql.com/doc/refman/5.7/en/lock-tables-and-transactions.html
		self::e("COMMIT");
		$success = self::e("UNLOCK TABLES");
		self::e("SET autocommit=1");
		self::$in_transaction = false;
		return $success;
	}

	/**
	 * @brief Starts a transaction
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function transaction() {
		if (!self::e('COMMIT')) {
			return false;
		}
		if (!self::e('START TRANSACTION')) {
			return false;
		}
		self::$in_transaction = true;
		return true;
	}

	/**
	 * @brief Does a commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function commit() {
		if (!self::e('COMMIT')) {
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
		if (!self::e('ROLLBACK')) {
			return false;
		}
		self::$in_transaction = false;
		return true;
	}

	/**
	 * @brief Build the array with the table relations
	 *
	 * The array is build from the database definitions in dbstructure.php
	 *
	 * This process must only be started once, since the value is cached.
	 */
	private static function build_relation_data() {
		$definition = db_definition();

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
	 * @param string $table Table name
	 * @param array $param parameter array
	 * @param boolean $in_process Internal use: Only do a commit after the last delete
	 * @param array $callstack Internal use: prevent endless loops
	 *
	 * @return boolean|array was the delete successfull? When $in_process is set: deletion data
	 */
	public static function delete($table, $param, $in_process = false, &$callstack = array()) {

		$commands = array();

		// Create a key for the loop prevention
		$key = $table.':'.implode(':', array_keys($param)).':'.implode(':', $param);

		// We quit when this key already exists in the callstack.
		if (isset($callstack[$key])) {
			return $commands;
		}

		$callstack[$key] = true;

		$table = self::$dbo->escape($table);

		$commands[$key] = array('table' => $table, 'param' => $param);

		// To speed up the whole process we cache the table relations
		if (count(self::$relation) == 0) {
			self::build_relation_data();
		}

		// Is there a relation entry for the table?
		if (isset(self::$relation[$table])) {
			// We only allow a simple "one field" relation.
			$field = array_keys(self::$relation[$table])[0];
			$rel_def = array_values(self::$relation[$table])[0];

			// Create a key for preventing double queries
			$qkey = $field.'-'.$table.':'.implode(':', array_keys($param)).':'.implode(':', $param);

			// When the search field is the relation field, we don't need to fetch the rows
			// This is useful when the leading record is already deleted in the frontend but the rest is done in the backend
			if ((count($param) == 1) && ($field == array_keys($param)[0])) {
				foreach ($rel_def AS $rel_table => $rel_fields) {
					foreach ($rel_fields AS $rel_field) {
						$retval = self::delete($rel_table, array($rel_field => array_values($param)[0]), true, $callstack);
						$commands = array_merge($commands, $retval);
					}
				}
			// We quit when this key already exists in the callstack.
			} elseif (!isset($callstack[$qkey])) {

				$callstack[$qkey] = true;

				// Fetch all rows that are to be deleted
				$data = self::select($table, array($field), $param);

				while ($row = self::fetch($data)) {
					// Now we accumulate the delete commands
					$retval = self::delete($table, array($field => $row[$field]), true, $callstack);
					$commands = array_merge($commands, $retval);
				}

				self::close($data);

				// Since we had split the delete command we don't need the original command anymore
				unset($commands[$key]);
			}
		}

		if (!$in_process) {
			// Now we finalize the process
			$do_transaction = !self::$in_transaction;

			if ($do_transaction) {
				self::transaction();
			}

			$compacted = array();
			$counter = array();

			foreach ($commands AS $command) {
				$condition = $command['param'];
				$array_element = each($condition);
				$array_key = $array_element['key'];
				if (is_int($array_key)) {
					$condition_string = " WHERE ".array_shift($condition);
				} else {
					$condition_string = " WHERE `".implode("` = ? AND `", array_keys($condition))."` = ?";
				}

				if ((count($command['param']) > 1) || is_int($array_key)) {
					$sql = "DELETE FROM `".$command['table']."`".$condition_string;
					logger(self::replace_parameters($sql, $condition), LOGGER_DATA);

					if (!self::e($sql, $condition)) {
						if ($do_transaction) {
							self::rollback();
						}
						return false;
					}
				} else {
					$key_table = $command['table'];
					$key_param = array_keys($command['param'])[0];
					$value = array_values($command['param'])[0];

					// Split the SQL queries in chunks of 100 values
					// We do the $i stuff here to make the code better readable
					$i = $counter[$key_table][$key_param];
					if (count($compacted[$key_table][$key_param][$i]) > 100) {
						++$i;
					}

					$compacted[$key_table][$key_param][$i][$value] = $value;
					$counter[$key_table][$key_param] = $i;
				}
			}
			foreach ($compacted AS $table => $values) {
				foreach ($values AS $field => $field_value_list) {
					foreach ($field_value_list AS $field_values) {
						$sql = "DELETE FROM `".$table."` WHERE `".$field."` IN (".
							substr(str_repeat("?, ", count($field_values)), 0, -2).");";

						logger(self::replace_parameters($sql, $field_values), LOGGER_DATA);

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

		return $commands;
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
	 * @param string $table Table name
	 * @param array $fields contains the fields that are updated
	 * @param array $condition condition array with the key values
	 * @param array|boolean $old_fields array with the old field values that are about to be replaced (true = update on duplicate)
	 *
	 * @return boolean was the update successfull?
	 */
	public static function update($table, $fields, $condition, $old_fields = array()) {

		$table = self::$dbo->escape($table);

		if (count($condition) > 0) {
			$array_element = each($condition);
			$array_key = $array_element['key'];
			if (is_int($array_key)) {
				$condition_string = " WHERE ".array_shift($condition);
			} else {
				$condition_string = " WHERE `".implode("` = ? AND `", array_keys($condition))."` = ?";
			}
		} else {
			$condition_string = "";
		}

		if (is_bool($old_fields)) {
			$do_insert = $old_fields;

			$old_fields = self::select($table, array(), $condition, array('limit' => 1));

			if (is_bool($old_fields)) {
				if ($do_insert) {
					$values = array_merge($condition, $fields);
					return self::insert($table, $values, $do_insert);
				}
				$old_fields = array();
			}
		}

		$do_update = (count($old_fields) == 0);

		foreach ($old_fields AS $fieldname => $content) {
			if (isset($fields[$fieldname])) {
				if ($fields[$fieldname] == $content) {
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
	 * @brief Select rows from a table
	 *
	 * @param string $table Table name
	 * @param array $fields array of selected fields
	 * @param array $condition array of fields for condition
	 * @param array $params array of several parameters
	 *
	 * @return boolean|object If "limit" is equal "1" only a single row is returned, else a query object is returned
	 *
	 * Example:
	 * $table = "item";
	 * $fields = array("id", "uri", "uid", "network");
	 *
	 * $condition = array("uid" => 1, "network" => 'dspr');
	 * or:
	 * $condition = array("`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr');
	 *
	 * $params = array("order" => array("id", "received" => true), "limit" => 1);
	 *
	 * $data = dba::select($table, $fields, $condition, $params);
	 */
	public static function select($table, $fields = array(), $condition = array(), $params = array()) {
		if ($table == '') {
			return false;
		}

		if (count($fields) > 0) {
			$select_fields = "`".implode("`, `", array_values($fields))."`";
		} else {
			$select_fields = "*";
		}

		if (count($condition) > 0) {
			$array_element = each($condition);
			$array_key = $array_element['key'];
			if (is_int($array_key)) {
				$condition_string = " WHERE ".array_shift($condition);
			} else {
				$condition_string = " WHERE `".implode("` = ? AND `", array_keys($condition))."` = ?";
			}
		} else {
			$condition_string = "";
		}

		$param_string = '';
		$single_row = false;

		if (isset($params['order'])) {
			$param_string .= " ORDER BY ";
			foreach ($params['order'] AS $fields => $order) {
				if (!is_int($fields)) {
					$param_string .= "`".$fields."` ".($order ? "DESC" : "ASC").", ";
				} else {
					$param_string .= "`".$order."`, ";
				}
			}
			$param_string = substr($param_string, 0, -2);
		}

		if (isset($params['limit']) && is_int($params['limit'])) {
			$param_string .= " LIMIT ".$params['limit'];
			$single_row = ($params['limit'] == 1);
		}

		if (isset($params['only_query']) && $params['only_query']) {
			$single_row = !$params['only_query'];
		}

		$sql = "SELECT ".$select_fields." FROM `".$table."`".$condition_string.$param_string;

		$result = self::p($sql, $condition);

		if (is_bool($result) || !$single_row) {
			return $result;
		} else {
			$row = self::fetch($result);
			self::close($result);
			return $row;
		}
	}


	/**
	 * @brief Fills an array with data from a query
	 *
	 * @param object $stmt statement object
	 * @return array Data array
	 */
	public static function inArray($stmt, $do_close = true) {
		if (is_bool($stmt)) {
			return $stmt;
		}

		$data = array();
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
		return self::$dbo->errorno;
	}

	/**
	 * @brief Returns the error message of the last query
	 *
	 * @return string Error message ('' if no error)
	 */
	public static function errorMessage() {
		return self::$dbo->error;
	}

	/**
	 * @brief Closes the current statement
	 *
	 * @param object $stmt statement object
	 * @return boolean was the close successfull?
	 */
	public static function close($stmt) {
		if (!is_object($stmt)) {
			return false;
		}

		switch (self::$dbo->driver) {
			case 'pdo':
				return $stmt->closeCursor();
			case 'mysqli':
				return $stmt->free_result();
				return $stmt->close();
			case 'mysql':
				return mysql_free_result($stmt);
		}
	}
}

function dbesc($str) {
	global $db;

	if ($db && $db->connected) {
		return($db->escape($str));
	} else {
		return(str_replace("'","\\'",$str));
	}
}

/**
 * @brief execute SQL query with printf style args - deprecated
 *
 * Please use the dba:: functions instead:
 * dba::select, dba::exists, dba::insert
 * dba::delete, dba::update, dba::p, dba::e
 *
 * @param $args Query parameters (1 to N parameters of different types)
 * @return array Query array
 */
function q($sql) {
	global $db;

	$args = func_get_args();
	unset($args[0]);

	if (!$db || !$db->connected) {
		return false;
	}

	$sql = $db->clean_query($sql);
	$sql = $db->any_value_fallback($sql);

	$stmt = @vsprintf($sql, $args);

	$ret = dba::p($stmt);

	if (is_bool($ret)) {
		return $ret;
	}

	$columns = dba::columnCount($ret);

	$data = dba::inArray($ret);

	if ((count($data) == 0) && ($columns == 0)) {
		return true;
	}

	return $data;
}

function dba_timer() {
	return microtime(true);
}
