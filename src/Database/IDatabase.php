<?php

namespace Friendica\Database;

interface IDatabase
{
	/**
	 * Returns if the database is connected
	 * @return bool
	 */
	function isConnected();

	/**
	 * Disconnects the current database connection
	 */
	function disconnect();

	/**
	 * Perform a reconnect of an existing database connection
	 *
	 * @return bool Wsa the reconnect successful?
	 */
	function reconnect();

	/**
	 * Return the database object.
	 * @return mixed
	 */
	function getConnection();

	/**
	 * Returns the server version string
	 * @return string
	 */
	function serverInfo();

	/**
	 * Returns the selected database name
	 * @return string
	 */
	function databaseName();

	/**
	 * Check if data exists
	 *
	 * @param string $table     The table name
	 * @param array  $condition An array of fields for condition
	 *
	 * @return bool Are there rows for that condition?
	 */
	function exists($table, array $condition);

	/**
	 * Counts the rows from a table satisfying the provided condition
	 *
	 * Example:
	 * $table = 'item';
	 *
	 * $condition = ['uid' => 1, 'network' => 'dspr'];
	 * or:
	 * $condition = ['`uid` = ? AND `network` IN (?, ?)', 1, 'dfrn', 'dspr'];
	 *
	 * $count = IDatabase->count($table, $condition);
	 *
	 * @param string $table     The table name
	 * @param array  $condition An array of fields for condition
	 *
	 * @return int The counted rows
	 */
	function count($table, array $condition = []);

	/**
	 * Fetch a single row
	 * @param mixed   $stmt A statement object
	 *
	 * @return array The current row
	 */
	function fetch($stmt);

	/**
	 * Starts a transaction
	 *
	 * @return bool Was the command executed successfully?
	 */
	function transaction();

	/**
	 * Does a commit
	 *
	 * @return bool Was the command executed successfully?
	 */
	function commit();

	/**
	 * Does a rollback
	 *
	 * @return bool Was the command executed successfully?
	 */
	function rollback();

	/**
	 * Insert a row into a table
	 *
	 * @param string $table               The table name
	 * @param array  $param               An array of fields for inserting
	 * @param bool   $on_duplicate_update Do an update on a duplicate entry
	 *
	 * @return bool Was the insert successful?
	 */
	function insert($table, array $param, $on_duplicate_update = false);

	/**
	 * Delete a row from a table
	 *
	 * @param string $table      The table name
	 * @param array  $conditions An array of fields for condition
	 * @param bool   $cascade    If true we delete records in other tables that depend on the one we're deleting through
	 *                           relations (default: true)
	 *
	 * @return bool Was the delete successful?
	 */
	function delete($table, array $conditions, $cascade = false);

	/**
	 * Updates rows in the database.
	 *
	 * When $old_fields is set to an array, the system will only do an update if the fields in that array changed.
	 *
	 * Attention:
	 * Only the values in $old_fields are compared. This is intentional behaviour.
	 *
	 * Example:
	 * We include the timestamp field in $fields but not in $old_fields.
	 * Then the row will only get the new timestamp when the other fields had changed.
	 *
	 * When $old_fields is set to a boolean value, the system will do this compare itself.
	 * When $old_fields is set to "true", the system will do an insert if the row doesn't exist.
	 *
	 * Attention:
	 * Only set $old_fields to a boolean value when you are sure that you will update a single row.
	 * When you set $old_fields to "true", then $fields must contain all relevant fields!
	 *
	 * @param string      $table      The table name
	 * @param array       $fields     An array of fields for updating
	 * @param array       $condition  An array of fields for condition
	 * @param array|bool  $old_fields An array of old fields that are about to be replaced (true = update on duplicate)
	 *
	 * @return bool Was the update successful?
	 */
	function update($table, array $fields, array $condition, $old_fields = []);

	/**
	 * Select rows from a table.
	 *
	 * Example:
	 * $table = "item";
	 * $fields = ['id', 'uri', 'uid', 'network'];
	 *
	 * $condition = ['uid' => 1, 'network' => 'dspr'];
	 * or:
	 * $condition = ['`uid` = ? AND `network` IN (?, ?)', 1, 'dfrn', 'dspr');
	 *
	 * $params = ['order' => ['id', 'received' => true), 'limit' => 10];
	 *
	 * $data = IDatabase->select($table, $fields, $condition, $params);
	 *
	 * @param string $table     The table name
	 * @param array  $fields    An array of fields for selecting, empty for all
	 * @param array  $condition An array of fields for condition
	 * @param array  $params    An array of fields of several parameters
	 *
	 * @return bool|object The result object or "false" if nothing was found.
	 */
	function select($table, array $fields = [], array $condition = [], array $params = []);

	/**
	 * Retrieve a single record from a table and returns it in an associative array
	 *
	 * @param string $table     The table name
	 * @param array  $fields    An array of fields for selecting, empty for all
	 * @param array  $condition An array of fields for condition
	 * @param array  $params    An array of fields of several parameters
	 *
	 * @return bool|array The result array or "false" if nothing was found.
	 */
	function selectFirst($table, array $fields = [], array $condition = [], array $params = []);
}
