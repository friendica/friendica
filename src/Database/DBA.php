<?php

namespace Friendica\Database;

use mysqli_result;
use mysqli_stmt;
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
	private static $dba;

	public static function init(Database $dba)
	{
		self::$dba = $dba;
	}

	/**
	 * Returns the current Database instance
	 *
	 * @return Database
	 */
	public static function getDb()
	{
		return self::$dba;
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
		return self::$dba->serverInfo();
	}

	/**
	 * @brief Returns the selected database name
	 *
	 * @return string
	 * @throws \Exception
	 */
	public static function databaseName()
	{
		return self::$dba->databaseName();
	}

	public static function escape($str)
	{
		return self::$dba->escape($str);
	}

	public static function connected()
	{
		return self::$dba->connected();
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
		return self::$dba->anyValueFallback($sql);
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
		return Database::cleanQuery($sql);
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
	public static function p($sql)
	{
		$params = self::getParam(func_get_args());

		return self::$dba->p($sql, $params);
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
	public static function e($sql)
	{
		$params = self::getParam(func_get_args());

		return self::$dba->e($sql, $params);
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
		self::$dba->exists($table, $condition);
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

		return self::$dba->fetchFirst($sql, $params);
	}

	/**
	 * @brief Returns the number of affected rows of the last statement
	 *
	 * @return int Number of rows
	 */
	public static function affectedRows()
	{
		return self::$dba->affectedRows();
	}

	/**
	 * @brief Returns the number of columns of a statement
	 *
	 * @param object Statement object
	 * @return int Number of columns
	 */
	public static function columnCount($stmt)
	{
		return self::$dba->columnCount($stmt);
	}
	/**
	 * @brief Returns the number of rows of a statement
	 *
	 * @param PDOStatement|mysqli_result|mysqli_stmt Statement object
	 * @return int Number of rows
	 */
	public static function numRows($stmt)
	{
		self::$dba->numRows($stmt);
	}

	/**
	 * @brief Fetch a single row
	 *
	 * @param mixed $stmt statement object
	 * @return array current row
	 */
	public static function fetch($stmt)
	{
		return self::$dba->fetch($stmt);
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
		self::$dba->insert($table, $param, $on_duplicate_update);
	}

	/**
	 * @brief Fetch the id of the last insert command
	 *
	 * @return integer Last inserted id
	 */
	public static function lastInsertId()
	{
		self::$dba->lastInsertId();
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
		return self::$dba->lock($table);
	}

	/**
	 * @brief Unlocks all locked tables
	 *
	 * @return boolean was the unlock successful?
	 * @throws \Exception
	 */
	public static function unlock()
	{
		return self::$dba->unlock();
	}

	/**
	 * @brief Starts a transaction
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function transaction()
	{
		return self::$dba->transaction();
	}

	/**
	 * @brief Does a commit
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function commit()
	{
		return self::$dba->commit();
	}

	/**
	 * @brief Does a rollback
	 *
	 * @return boolean Was the command executed successfully?
	 */
	public static function rollback()
	{
		return self::$dba->rollback();
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
		return self::$dba->delete($table, $conditions, $options, $callstack);
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
		return self::$dba->update($table, $fields, $condition, $old_fields);
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
		return self::$dba->selectFirst($table, $fields, $condition, $params);
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
		return self::$dba->select($table, $fields, $condition, $params);
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
		return self::$dba->count($table, $condition);
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
		return Database::buildCondition($condition);
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
		return self::$dba->toArray($stmt, $do_close);
	}

	/**
	 * @brief Returns the SQL parameter string built from the provided parameter array
	 *
	 * @param array $params
	 * @return string
	 */
	public static function buildParameter(array $params = [])
	{
		return Database::buildParameter($params);
	}

	/**
	 * @brief Returns the error number of the last query
	 *
	 * @return string Error number (0 if no error)
	 */
	public static function errorNo()
	{
		return self::$dba->errorNo();
	}

	/**
	 * @brief Returns the error message of the last query
	 *
	 * @return string Error message ('' if no error)
	 */
	public static function errorMessage()
	{
		return self::$dba->errorMessage();
	}

	/**
	 * @brief Closes the current statement
	 *
	 * @param object $stmt statement object
	 * @return boolean was the close successful?
	 */
	public static function close($stmt)
	{
		return self::$dba->close($stmt);
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
		return self::$dba->processlist();
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
		return self::$dba->isResult($array);
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
		self::$dba->escapeArray($arr, $add_quotation);
	}
}
