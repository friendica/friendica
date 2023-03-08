<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Util\Writer;

use Exception;
use Friendica\App;
use Friendica\Database\Definition\DbaDefinition;

/**
 * SQL writer utility for the database definition
 */
class DbaDefinitionSqlWriter
{
	/**
	 * Creates a complete SQL definition bases on a give DBA Definition class
	 *
	 * @param DbaDefinition $definition The DBA definition class
	 *
	 * @return string The SQL definition as a string
	 *
	 * @throws Exception in case of parameter failures
	 */
	public static function create(DbaDefinition $definition): string
	{
		$sqlString = "-- ------------------------------------------\n";
		$sqlString .= "-- " . App::PLATFORM . " " . App::VERSION . " (" . App::CODENAME . ")\n";
		$sqlString .= "-- DB_UPDATE_VERSION " . DB_UPDATE_VERSION . "\n";
		$sqlString .= "-- ------------------------------------------\n\n\n";

		foreach ($definition->getAll() as $tableName => $tableStructure) {
			$sqlString .= "--\n";
			$sqlString .= "-- TABLE $tableName\n";
			$sqlString .= "--\n";
			$sqlString .= static::createTable($tableName, $tableStructure);
		}

		return $sqlString;
	}

	/**
	 * Creates the SQL definition of one table
	 *
	 * @param string $tableName The table name
	 * @param array  $tableStructure The table structure
	 *
	 * @return string The SQL definition
	 *
	 * @throws Exception in cases of structure failures
	 */
	public static function createTable(string $tableName, array $tableStructure): string
	{
		$engine       = '';
		$comment      = '';
		$sql_rows     = [];
		$primary_keys = [];
		$foreign_keys = [];

		foreach ($tableStructure['fields'] as $fieldName => $field) {
			$sql_rows[] = '`' . static::escape($fieldName) . '` ' . self::fieldCommand($field);
			if (!empty($field['primary'])) {
				$primary_keys[] = $fieldName;
			}
			if (!empty($field['foreign'])) {
				$foreign_keys[$fieldName] = $field;
			}
		}

		if (!empty($tableStructure['indexes'])) {
			foreach ($tableStructure['indexes'] as $indexName => $fieldNames) {
				$sql_index = self::createIndex($indexName, $fieldNames, '');
				if (!is_null($sql_index)) {
					$sql_rows[] = $sql_index;
				}
			}
		}

		foreach ($foreign_keys as $fieldName => $parameters) {
			$sql_rows[] = self::foreignCommand($fieldName, $parameters);
		}

		if (isset($tableStructure['engine'])) {
			$engine = ' ENGINE=' . $tableStructure['engine'];
		}

		if (isset($tableStructure['comment'])) {
			$comment = " COMMENT='" . static::escape($tableStructure['comment']) . "'";
		}

		$sql = implode(",\n\t", $sql_rows);

		$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (\n\t", static::escape($tableName)) . $sql .
			   "\n)" . $engine . " DEFAULT COLLATE utf8mb4_general_ci" . $comment;
		return $sql . ";\n\n";
	}

	/**
	 * Standard escaping for SQL definitions
	 *
	 * @param string $sqlString the SQL string to escape
	 *
	 * @return string escaped SQL string
	 */
	public static function escape(string $sqlString): string
	{
		return str_replace("'", "\\'", $sqlString);
	}

	/**
	 * Creates the SQL definition to add a foreign key
	 *
	 * @param string $keyName    The foreign key name
	 * @param array  $parameters The given parameters of the foreign key
	 *
	 * @return string The SQL definition
	 */
	public static function addForeignKey(string $keyName, array $parameters): string
	{
		return sprintf("ADD %s", static::foreignCommand($keyName, $parameters));
	}

	/**
	 * Creates the SQL definition to drop a foreign key
	 *
	 * @param string $keyName The foreign key name
	 *
	 * @return string The SQL definition
	 */
	public static function dropForeignKey(string $keyName): string
	{
		return sprintf("DROP FOREIGN KEY `%s`", $keyName);
	}

	/**
	 * Creates the SQL definition to drop an index
	 *
	 * @param string $indexName The index name
	 *
	 * @return string The SQL definition
	 */
	public static function dropIndex(string $indexName): string
	{
		return sprintf("DROP INDEX `%s`", static::escape($indexName));
	}

	/**
	 * Creates the SQL definition to add a table field
	 *
	 * @param string $fieldName  The table field name
	 * @param array  $parameters The parameters of the table field
	 *
	 * @return string The SQL definition
	 */
	public static function addTableField(string $fieldName, array $parameters): string
	{
		return sprintf("ADD `%s` %s", static::escape($fieldName), static::fieldCommand($parameters));
	}

	/**
	 * Creates the SQL definition to modify a table field
	 *
	 * @param string $fieldName  The table field name
	 * @param array  $parameters The paramters to modify
	 *
	 * @return string The SQL definition
	 */
	public static function modifyTableField(string $fieldName, array $parameters): string
	{
		return sprintf("MODIFY `%s` %s", static::escape($fieldName), self::fieldCommand($parameters, false));
	}

	/**
	 * Returns SQL statement for field
	 *
	 * @param array   $parameters Parameters for SQL statement
	 * @param boolean $create Whether to include PRIMARY KEY statement (unused)
	 * @return string SQL statement part
	 */
	public static function fieldCommand(array $parameters, bool $create = true): string
	{
		$fieldstruct = $parameters['type'];

		if (isset($parameters['Collation'])) {
			$fieldstruct .= ' COLLATE ' . $parameters['Collation'];
		}

		if (isset($parameters['not null'])) {
			$fieldstruct .= ' NOT NULL';
		}

		if (isset($parameters['default'])) {
			if (strpos(strtolower($parameters['type']), 'int') !== false) {
				$fieldstruct .= ' DEFAULT ' . $parameters['default'];
			} else {
				$fieldstruct .= " DEFAULT '" . $parameters['default'] . "'";
			}
		}
		if (isset($parameters['extra'])) {
			$fieldstruct .= ' ' . $parameters['extra'];
		}

		if (isset($parameters['comment'])) {
			$fieldstruct .= " COMMENT '" . static::escape($parameters['comment']) . "'";
		}

		/*if (($parameters['primary'] != '') && $create)
			$fieldstruct .= ' PRIMARY KEY';*/

		return $fieldstruct;
	}

	/**
	 * Creates the SQL definition to create an index
	 *
	 * @param string $indexName  The index name
	 * @param array  $fieldNames The field names of this index
	 * @param string $method     The method to create the index (default is ADD)
	 *
	 * @return string The SQL definition
	 * @throws Exception in cases the paramter contains invalid content
	 */
	public static function createIndex(string $indexName, array $fieldNames, string $method = 'ADD'): string
	{
		$method = strtoupper(trim($method));
		if ($method != '' && $method != 'ADD') {
			throw new Exception("Invalid parameter 'method' in self::createIndex(): '$method'");
		}

		if (in_array($fieldNames[0], ['UNIQUE', 'FULLTEXT'])) {
			$index_type = array_shift($fieldNames);
			$method .= " " . $index_type;
		}

		$names = "";
		foreach ($fieldNames as $fieldName) {
			if ($names != '') {
				$names .= ',';
			}

			if (preg_match('|(.+)\((\d+)\)|', $fieldName, $matches)) {
				$names .= "`" . static::escape($matches[1]) . "`(" . intval($matches[2]) . ")";
			} else {
				$names .= "`" . static::escape($fieldName) . "`";
			}
		}

		if ($indexName == 'PRIMARY') {
			return sprintf("%s PRIMARY KEY(%s)", $method, $names);
		}


		return sprintf("%s INDEX `%s` (%s)", $method, static::escape($indexName), $names);
	}

	/**
	 * Creates the SQL definition for foreign keys
	 *
	 * @param string $foreignKeyName The foreign key name
	 * @param array  $parameters     The parameters of the foreign key
	 *
	 * @return string The SQL definition
	 */
	public static function foreignCommand(string $foreignKeyName, array $parameters): string
	{
		$foreign_table = array_keys($parameters['foreign'])[0];
		$foreign_field = array_values($parameters['foreign'])[0];

		$sql = "FOREIGN KEY (`" . $foreignKeyName . "`) REFERENCES `" . $foreign_table . "` (`" . $foreign_field . "`)";

		if (!empty($parameters['foreign']['on update'])) {
			$sql .= " ON UPDATE " . strtoupper($parameters['foreign']['on update']);
		} else {
			$sql .= " ON UPDATE RESTRICT";
		}

		if (!empty($parameters['foreign']['on delete'])) {
			$sql .= " ON DELETE " . strtoupper($parameters['foreign']['on delete']);
		} else {
			$sql .= " ON DELETE CASCADE";
		}

		return $sql;
	}
}
