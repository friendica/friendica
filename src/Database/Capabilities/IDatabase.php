<?php

namespace Friendica\Database\Capabilities;

use Friendica\Database\DatabaseException;
use Friendica\Database\DatabaseUtils;
use InvalidArgumentException;

interface IDatabase
{
	const INSERT_DEFAULT = 0;
	const INSERT_UPDATE  = 1;
	const INSERT_IGNORE  = 2;

	/**
	 * Select rows from a table and fills an array with the data
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return array Data array
	 * @throws DatabaseException
	 * @see   static::select
	 */
	public function selectToArray(string $table, array $fields = [], array $condition = [], array $params = []): array;

	/**
	 * Retrieve a single record from a table and returns it in an associative array
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return bool|array
	 * @throws DatabaseException
	 * @see IDatabase::select()
	 */
	public function selectFirst(string $table, array $fields = [], array $condition = [], array $params = []);

	/**
	 * Select rows from a table
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $fields    Array of selected fields, empty for all
	 * @param array  $condition Array of fields for condition
	 * @param array  $params    Array of several parameters
	 *
	 * @return false|IDatabaseResult|IDatabaseError
	 * @throws DatabaseException
	 * @see DatabaseUtils::buildTableString()
	 *
	 * Example:
	 * $table = 'post';
	 * or:
	 * $table = ['schema' => 'table'];
	 * @see DatabaseUtils::buildTableString()
	 *
	 * $fields = ['id', 'uri', 'uid', 'network'];
	 *
	 * $condition = ['uid' => 1, 'network' => 'dspr', 'blocked' => true];
	 * or:
	 * $condition = ['`uid` = ? AND `network` IN (?, ?)', 1, 'dfrn', 'dspr'];
	 * @see DatabaseUtils::buildCondition()
	 *
	 * $params = ['order' => ['id', 'received' => true, 'created' => 'ASC'), 'limit' => 10];
	 * @see DatabaseUtils::buildParameter()
	 *
	 * $data = IDatabase::select($table, $fields, $condition, $params);
	 *
	 */
	public function select(string $table, array $fields = [], array $condition = [], array $params = []);

	/**
	 * Insert a row into a table. Field value objects will be cast as string.
	 *
	 * @param string $table          Table name in format [schema.]table
	 * @param array  $param          parameter array
	 * @param int    $duplicate_mode What to do on a duplicated entry
	 *
	 * @return int|false the inserted ID or false if not inserted
	 * @throws DatabaseException
	 */
	public function insert(string $table, array $param, int $duplicate_mode = self::INSERT_DEFAULT);

	/**
	 * Check if data exists
	 *
	 * @param string $table     Table name in format [schema.]table
	 * @param array  $condition Array of fields for condition (if empty, check if the table exists)
	 *
	 * @return boolean Are there rows for that condition?
	 * @throws DatabaseException
	 */
	public function exists(string $table, array $condition = []): bool;

	/**
	 * Delete a row from a table
	 *
	 * @param string $table      Table name
	 * @param array  $conditions Field condition(s)
	 *
	 * @return boolean was the deletion successful?
	 * @throws DatabaseException
	 */
	public function delete(string $table, array $conditions): bool;

	/**
	 * Inserts a row with the provided data in the provided table.
	 * If the data corresponds to an existing row through a UNIQUE or PRIMARY index constraints, it updates the row
	 * instead.
	 *
	 * @param string $table Table name in format [schema.]table
	 * @param array  $param parameter array
	 *
	 * @return boolean was the insert successful?
	 * @throws DatabaseException
	 */
	public function replace(string $table, array $param): bool;

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
	 * $count = IDatabase::count($table, $condition);
	 * @throws DatabaseException
	 * @throws InvalidArgumentException
	 */
	public function count(string $table, array $condition = [], array $params = []): int;

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
	 * @param array|boolean $old_fields array with the old field values that are about to be replaced (true = update on
	 *                                  duplicate, false = don't update identical fields)
	 * @param array         $params     Parameters: "ignore" If set to "true" then the update is done with the ignore
	 *                                  parameter
	 *
	 * @return boolean was the update successfull?
	 * @throws DatabaseException
	 * @todo Implement "bool $update_on_duplicate" to avoid mixed type for $old_fields
	 */
	public function update(string $table, array $fields, array $condition, $old_fields = [], array $params = []): bool;
}
