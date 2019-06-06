<?php

namespace Friendica\Database;

use mysqli;
use mysqli_result;
use mysqli_stmt;
use PDO;
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

	/**
	 * @var Database
	 */
	private static $database;

	public static function init(Database $database)
	{
		self::$database = $database;
	}

	public static function connect()
	{
		return self::$database->connect();
	}

	/**
	 * Disconnects the current database connection
	 */
	public static function disconnect()
	{
		self::$database->disconnect();
	}

	/**
	 * Perform a reconnect of an existing database connection
	 */
	public static function reconnect()
	{
		return self::$database->reconnect();
	}

	/**
	 * Return the database object.
	 * @return PDO|mysqli
	 */
	public static function getConnection()
	{
		return self::$database->getConnection();
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
		return self::$database->serverInfo();
	}

	/**
	 * @brief Returns the selected database name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function databaseName()
	{
		return self::$database->databaseName();
	}

	public static function escape($str)
	{
		return self::$database->escape($str);
	}

	public static function connected()
	{
		return self::$database->connected();
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
	public static function anyValueFallback($sql)
	{
		return self::$database->anyValueFallback($sql);
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
	public static function cleanQuery($sql)
	{
		$search = ["\t", "\n", "\r", "  "];
		$replace = [' ', ' ', ' ', ' '];
		do {
			$oldsql = $sql;
			$sql = str_replace($search, $replace, $sql);
		} while ($oldsql != $sql);

		return $sql;
	}

	/**
	 * @brief Convert parameter array to an universal form
	 * @param array $args Parameter array
	 * @return array universalized parameter array
	 */
	public static function getParam($args)
	{
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
	public static function p($sql)
	{
		$params = self::getParam(func_get_args());

		return self::$database->p($sql, $params);
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

		$params = self::getParam(func_get_args());

		return self::$database->e($sql, $params);
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
	public static function exists($table, $condition)
	{
		return self::$database->exists($table, $condition);
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
	public static function fetchFirst($sql)
	{
		$params = self::getParam(func_get_args());

		return self::$database->fetchFirst($sql, $params);
	}

	/**
	 * @brief Returns the number of affected rows of the last statement
	 *
	 * @return int Number of rows
	 */
	public static function affectedRows()
	{
		return self::$database->affectedRows();
	}

	/**
	 * @brief Returns the number of columns of a statement
	 *
	 * @param object Statement object
	 * @return int Number of columns
	 */
	public static function columnCount($stmt)
	{
		return self::$database->columnCount($stmt);
	}
	/**
	 * @brief Returns the number of rows of a statement
	 *
	 * @param PDOStatement|mysqli_result|mysqli_stmt Statement object
	 * @return int Number of rows
	 */
	public static function numRows($stmt)
	{
		return self::$database->numRows($stmt);
	}

	/**
	 * @brief Fetch a single row
	 *
	 * @param mixed $stmt statement object
	 * @return array current row
	 */
	public static function fetch($stmt)
	{
		return self::$database->fetch($stmt);
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
	public static function insert($table, $param, $on_duplicate_update = false)
	{
		return self::$database->insert($table, $param, $on_duplicate_update);
	}

	/**
	 * @brief Fetch the id of the last insert command
	 *
	 * @return integer Last inserted id
	 */
	public static function lastInsertId()
	{
		return self::$database->lastInsertId();
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
	public static function lock($table)
	{
		return self::$database->lock($table);
	}

	/**
	 * @brief Unlocks all locked tables
	 *
	 * @return boolean was the unlock successful?
	 * @throws \Exception
	 */
	public static function unlock()
	{
		return self::$database->unlock();
	}

	/**
	 * @brief Starts a transaction
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function transaction()
	{
		return self::$database->transaction();
	}

	/**
	 * @brief Does a commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function commit()
	{
		return self::$database->commit();
	}

	/**
	 * @brief Does a rollback
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function rollback()
	{
		return self::$database->rollback();
	}

	/**
	 * @brief Delete a row from a table
	 *
	 * @param string $table      Table name
	 * @param array  $conditions Field condition(s)
	 * @param array  $options
	 *                           - cascade: If true we delete records in other tables that depend on the one we're deleting through
	 *                           relations (default: true)
	 *
	 * @return boolean was the delete successful?
	 * @throws \Exception
	 */
	public static function delete($table, array $conditions, array $options = [])
	{
		return self::$database->delete($table, $conditions, $options);
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
	public static function update($table, $fields, $condition, $old_fields = [])
	{
		return self::$database->update($table, $fields, $condition, $old_fields);
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
		return self::$database->selectFirst($table, $fields, $condition, $params);
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
		return self::$database->select($table, $fields, $condition, $params);
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
		return self::$database->count($table, $condition);
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
					} elseif (is_null($value)) {
						$condition_string .= "`" . $field . "` IS NULL";
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
		$groupby_string = '';
		if (isset($params['group_by'])) {
			$groupby_string = " GROUP BY ";
			foreach ($params['group_by'] as $fields) {
				$groupby_string .= "`" . $fields . "`, ";
			}
			$groupby_string = substr($groupby_string, 0, -2);
		}

		$order_string = '';
		if (isset($params['order'])) {
			$order_string = " ORDER BY ";
			foreach ($params['order'] AS $fields => $order) {
				if ($order === 'RAND()') {
					$order_string .= "RAND(), ";
				} elseif (!is_int($fields)) {
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

		return $groupby_string . $order_string . $limit_string;
	}

	/**
	 * @brief Fills an array with data from a query
	 *
	 * @param object $stmt statement object
	 * @param bool   $do_close
	 * @return array Data array
	 */
	public static function toArray($stmt, $do_close = true)
	{
		return self::$database->toArray($stmt, $do_close);
	}

	/**
	 * @brief Returns the error number of the last query
	 *
	 * @return string Error number (0 if no error)
	 */
	public static function errorNo()
	{
		return self::$database->errorNo();
	}

	/**
	 * @brief Returns the error message of the last query
	 *
	 * @return string Error message ('' if no error)
	 */
	public static function errorMessage()
	{
		return self::$database->errorMessage();
	}

	/**
	 * @brief Closes the current statement
	 *
	 * @param object $stmt statement object
	 * @return boolean was the close successful?
	 */
	public static function close($stmt)
	{
		return self::$database->close($stmt);
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
		return self::$database->processlist();
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
		return self::$database->isResult($array);
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
		return self::$database->escapeArray($arr, $add_quotation);
	}
}
