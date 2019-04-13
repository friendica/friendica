<?php

namespace Friendica\Database;

use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Core\Logger;
use Friendica\Database\Driver\IDriver;
use Friendica\Util\DateTimeFormat;

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
	private static $connection;
	private static $driver;
	private static $error = false;
	private static $errorno = 0;
	private static $in_transaction = false;

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
		self::$db->getDriver()->disconnect();
	}

	/**
	 * Perform a reconnect of an existing database connection
	 */
	public static function reconnect()
	{
		return self::$db->getDriver()->reconnect();
	}

	/**
	 * Return the database object.
	 *
	 * @return IDriver
	 */
	public static function getConnection()
	{
		return self::$db->getDriver();
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
		return self::$db->getDriver()->getServerInfo();
	}

	/**
	 * @brief Returns the selected database name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function databaseName()
	{
		return self::$db->getDatabaseName();
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

	public static function escape($sql)
	{
		return self::$db->getDriver()->escape($sql);
	}

	public static function connected()
	{
		return self::$db->getDriver()->isConnected(true);
	}

	public static function p($sql)
	{
		$params = Util::getParameters(func_get_args());

		return self::$db->prepared($sql, $params);
	}


	public static function e($sql)
	{
		$params = Util::getParameters(func_get_args());

		return self::$db->execute($sql, $params);
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

	public static function affectedRows()
	{
		return self::$db->getAffectedRows();
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

	public static function fetch($stmt)
	{
		return self::$db->fetch($stmt);
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

	public static function transaction()
	{
		self::$db->transaction();
	}

	public static function commit()
	{
		return self::$db->commit();
	}

	public static function rollback()
	{
		return self::$db->rollback();
	}

	public static function delete($table, array $conditions, array $options = [])
	{
		return self::$db->delete($table, $conditions, (isset($options['cascade']) ? $options['cascade'] : true));
	}

	public static function update($table, $fields, $condition, $old_fields = [])
	{
		return self::$db->select($table, $fields, $condition, $old_fields);
	}

	public static function selectFirst($table, array $fields = [], array $condition = [], $params = [])
	{
		return self::$db->selectFirst($table, $fields, $condition, $params);
	}

	public static function select($table, array $fields = [], array $condition = [], array $params = [])
	{
		return self::$db->select($table, $fields, $condition, $params);
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

	public static function close($stmt)
	{
		self::$db->close($stmt);
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
