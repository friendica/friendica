<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Database;

use Exception;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Writer\DbaDefinitionSqlWriter;

/**
 * This class contains functions that doesn't need to know if pdo, mysqli or whatever is used.
 */
class DBStructure
{
	const UPDATE_NOT_CHECKED = 0; // Database check wasn't executed before
	const UPDATE_SUCCESSFUL  = 1; // Database check was successful
	const UPDATE_FAILED      = 2; // Database check failed

	const RENAME_COLUMN      = 0;
	const RENAME_PRIMARY_KEY = 1;

	/**
	 * Set a database version to trigger update functions
	 *
	 * @param string $version
	 * @return void
	 */
	public static function setDatabaseVersion(string $version)
	{
		if (!is_numeric($version)) {
			throw new \Asika\SimpleConsole\CommandArgsException('The version number must be numeric');
		}

		DI::keyValue()->set('build', $version);
		echo DI::l10n()->t('The database version had been set to %s.', $version);
	}

	/**
	 * Drops a specific table
	 *
	 * @param string $table the table name
	 *
	 * @return bool true if possible, otherwise false
	 */
	public static function dropTable(string $table): bool
	{
		return DBA::isResult(DBA::e('DROP TABLE ' . DBA::quoteIdentifier($table) . ';'));
	}

	/**
	 * Drop unused tables
	 *
	 * @param boolean $execute
	 * @return void
	 */
	public static function dropTables(bool $execute)
	{
		$postupdate = DI::keyValue()->get('post_update_version') ?? PostUpdate::VERSION;
		if ($postupdate < PostUpdate::VERSION) {
			echo DI::l10n()->t('The post update is at version %d, it has to be at %d to safely drop the tables.', $postupdate, PostUpdate::VERSION);
			return;
		}

		$old_tables = ['fserver', 'gcign', 'gcontact', 'gcontact-relation', 'gfollower' ,'glink', 'item-delivery-data',
			'item-activity', 'item-content', 'item_id', 'participation', 'poll', 'poll_result', 'queue', 'retriever_rule',
			'deliverq', 'dsprphotoq', 'ffinder', 'sign', 'spam', 'term', 'user-item', 'thread', 'item', 'challenge',
			'auth_codes', 'tokens', 'clients', 'profile_check', 'host', 'conversation', 'fcontact', 'addon', 'push_subscriber'];

		$tables = DBA::selectToArray(
			'INFORMATION_SCHEMA.TABLES',
			['TABLE_NAME'],
			['TABLE_SCHEMA' => DBA::databaseName(), 'TABLE_TYPE' => 'BASE TABLE']
		);

		if (empty($tables)) {
			echo DI::l10n()->t('No unused tables found.');
			return;
		}

		if (!$execute) {
			echo DI::l10n()->t('These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":') . "\n\n";
		}

		foreach ($old_tables as $table) {
			if (in_array($table, array_column($tables, 'TABLE_NAME'))) {
				if ($execute) {
					$sql = 'DROP TABLE ' . DBA::quoteIdentifier($table) . ';';
					echo $sql . "\n";

					if (!static::dropTable($table)) {
						self::printUpdateError($sql);
					}
				} else {
					echo $table . "\n";
				}
			}
		}
	}

	/**
	 * Converts all tables from MyISAM/InnoDB Antelope to InnoDB Barracuda
	 */
	public static function convertToInnoDB()
	{
		$tables = DBA::selectToArray(
			'information_schema.tables',
			['table_name'],
			['engine' => 'MyISAM', 'table_schema' => DBA::databaseName()]
		);

		$tables = array_merge($tables, DBA::selectToArray(
			'information_schema.tables',
			['table_name'],
			['engine' => 'InnoDB', 'ROW_FORMAT' => ['COMPACT', 'REDUNDANT'], 'table_schema' => DBA::databaseName()]
		));

		if (!DBA::isResult($tables)) {
			echo DI::l10n()->t('There are no tables on MyISAM or InnoDB with the Antelope file format.') . "\n";
			return;
		}

		foreach ($tables as $table) {
			$sql = "ALTER TABLE " . DBA::quoteIdentifier($table['table_name']) . " ENGINE=InnoDB ROW_FORMAT=DYNAMIC;";
			echo $sql . "\n";

			$result = DBA::e($sql);
			if (!DBA::isResult($result)) {
				self::printUpdateError($sql);
			}
		}
	}

	/**
	 * Print out database error messages
	 *
	 * @param string $message Message to be added to the error message
	 *
	 * @return string Error message
	 */
	private static function printUpdateError(string $message): string
	{
		echo DI::l10n()->t(
			"\nError %d occurred during database update:\n%s\n",
			DBA::errorNo(),
			DBA::errorMessage()
		);

		return DI::l10n()->t('Errors encountered performing database changes: ') . $message . '<br />';
	}

	/**
	 * Perform a database structure dryrun (means: just simulating)
	 *
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws Exception
	 */
	public static function dryRun(): string
	{
		return self::update(true, false);
	}

	/**
	 * Updates DB structure and returns eventual errors messages
	 *
	 * @param bool $enable_maintenance_mode Set the maintenance mode
	 * @param bool $verbose                 Display the SQL commands
	 *
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws Exception
	 */
	public static function performUpdate(bool $enable_maintenance_mode = true, bool $verbose = false): string
	{
		if ($enable_maintenance_mode) {
			DI::config()->set('system', 'maintenance', true);
		}

		$status = self::update($verbose, true);

		if ($enable_maintenance_mode) {
			DI::config()->beginTransaction()
						->set('system', 'maintenance', false)
						->delete('system', 'maintenance_reason')
						->commit();
		}

		return $status;
	}

	/**
	 * Returns the current status of the Update
	 *
	 * @return int
	 */
	public static function getUpdateStatus(): int
	{
		return (int)DI::config()->get('system', 'dbupdate') ?? static::UPDATE_NOT_CHECKED;
	}

	/**
	 * Updates DB structure from the installation and returns eventual errors messages
	 *
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws Exception
	 */
	public static function install(): string
	{
		return self::update(false, true, true);
	}

	/**
	 * Updates DB structure and returns eventual errors messages
	 *
	 * @param bool   $verbose
	 * @param bool   $action     Whether to actually apply the update
	 * @param bool   $install    Is this the initial update during the installation?
	 * @param array  $tables     An array of the database tables
	 * @param array  $definition An array of the definition tables
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws Exception
	 */
	private static function update(bool $verbose, bool $action, bool $install = false, array $tables = null, array $definition = null): string
	{
		$in_maintenance_mode = DI::config()->get('system', 'maintenance');

		if ($action && !$install && self::isUpdating()) {
			return DI::l10n()->t('Another database update is currently running.');
		}

		if ($in_maintenance_mode) {
			DI::config()->set('system', 'maintenance_reason', DI::l10n()->t('%s: Database update', DateTimeFormat::utcNow() . ' ' . date('e')));
		}

		// ensure that all initial values exist. This test has to be done prior and after the structure check.
		// Prior is needed if the specific tables already exists - after is needed when they had been created.
		self::checkInitialValues();

		$errors = '';

		DI::logger()->info('updating structure');

		// Get the current structure
		$database = [];

		if (is_null($tables)) {
			$tables = DBA::toArray(DBA::p("SHOW TABLES"));
		}

		if (DBA::isResult($tables)) {
			foreach ($tables as $table) {
				$table = current($table);

				DI::logger()->info('updating structure', ['table' => $table]);
				$database[$table] = self::tableStructure($table);
			}
		}

		// Get the definition
		if (is_null($definition)) {
			// just for Update purpose, reload the DBA definition with addons to explicit get the whole definition
			$definition = DI::dbaDefinition()->load(true)->getAll();
		}

		// MySQL >= 5.7.4 doesn't support the IGNORE keyword in ALTER TABLE statements
		if ((version_compare(DBA::serverInfo(), '5.7.4') >= 0) &&
			!(strpos(DBA::serverInfo(), 'MariaDB') !== false)) {
			$ignore = '';
		} else {
			$ignore = ' IGNORE';
		}

		// Compare it
		foreach ($definition as $name => $structure) {
			$is_new_table = false;
			$sql3         = "";
			if (!isset($database[$name])) {
				$sql = DbaDefinitionSqlWriter::createTable($name, $structure);
				if ($verbose) {
					echo $sql;
				}
				if ($action) {
					$r = DBA::e($sql);
					if (!DBA::isResult($r)) {
						$errors .= self::printUpdateError($name);
					}
				}
				$is_new_table = true;
			} else {
				/*
				 * Drop the index if it isn't present in the definition
				 * or the definition differ from current status
				 * and index name doesn't start with "local_"
				 */
				foreach ($database[$name]["indexes"] as $indexName => $fieldNames) {
					$current_index_definition = implode(",", $fieldNames);
					if (isset($structure["indexes"][$indexName])) {
						$new_index_definition = implode(",", $structure["indexes"][$indexName]);
					} else {
						$new_index_definition = "__NOT_SET__";
					}
					if ($current_index_definition != $new_index_definition && substr($indexName, 0, 6) != 'local_') {
						$sql2 = DbaDefinitionSqlWriter::dropIndex($indexName);
						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}
				// Compare the field structure field by field
				foreach ($structure["fields"] as $fieldName => $parameters) {
					if (!isset($database[$name]["fields"][$fieldName])) {
						$sql2 = DbaDefinitionSqlWriter::addTableField($fieldName, $parameters);
						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					} else {
						// Compare the field definition
						$field_definition = $database[$name]["fields"][$fieldName];

						// Remove the relation data that is used for the referential integrity
						unset($parameters['relation']);
						unset($parameters['foreign']);

						// We change the collation after the indexes had been changed.
						// This is done to avoid index length problems.
						// So here we always ensure that there is no need to change it.
						unset($parameters['Collation']);
						unset($field_definition['Collation']);

						// Only update the comment when it is defined
						if (!isset($parameters['comment'])) {
							$parameters['comment'] = "";
						}

						$current_field_definition = DBA::cleanQuery(implode(",", $field_definition));
						$new_field_definition     = DBA::cleanQuery(implode(",", $parameters));
						if ($current_field_definition != $new_field_definition) {
							$sql2 = DbaDefinitionSqlWriter::modifyTableField($fieldName, $parameters);
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
							} else {
								$sql3 .= ", " . $sql2;
							}
						}
					}
				}
			}

			/*
			 * Create the index if the index don't exists in database
			 * or the definition differ from the current status.
			 * Don't create keys if table is new
			 */
			if (!$is_new_table) {
				foreach ($structure["indexes"] as $indexName => $fieldNames) {
					if (isset($database[$name]["indexes"][$indexName])) {
						$current_index_definition = implode(",", $database[$name]["indexes"][$indexName]);
					} else {
						$current_index_definition = "__NOT_SET__";
					}
					$new_index_definition = implode(",", $fieldNames);
					if ($current_index_definition != $new_index_definition) {
						$sql2 = DbaDefinitionSqlWriter::createIndex($indexName, $fieldNames);

						if ($sql2 != "") {
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
							} else {
								$sql3 .= ", " . $sql2;
							}
						}
					}
				}

				$existing_foreign_keys = $database[$name]['foreign_keys'];

				// Foreign keys
				// Compare the field structure field by field
				foreach ($structure["fields"] as $fieldName => $parameters) {
					if (empty($parameters['foreign'])) {
						continue;
					}

					$constraint = self::getConstraintName($name, $fieldName, $parameters);

					unset($existing_foreign_keys[$constraint]);

					if (empty($database[$name]['foreign_keys'][$constraint])) {
						$sql2 = DbaDefinitionSqlWriter::addForeignKey($fieldName, $parameters);

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				foreach ($existing_foreign_keys as $param) {
					$sql2 = DbaDefinitionSqlWriter::dropForeignKey($param['CONSTRAINT_NAME']);

					if ($sql3 == "") {
						$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
					} else {
						$sql3 .= ", " . $sql2;
					}
				}

				if (isset($database[$name]["table_status"]["TABLE_COMMENT"])) {
					$structurecomment = $structure["comment"] ?? '';
					if ($database[$name]["table_status"]["TABLE_COMMENT"] != $structurecomment) {
						$sql2 = "COMMENT = '" . DBA::escape($structurecomment) . "'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["ENGINE"]) && isset($structure['engine'])) {
					if ($database[$name]["table_status"]["ENGINE"] != $structure['engine']) {
						$sql2 = "ENGINE = '" . DBA::escape($structure['engine']) . "'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["TABLE_COLLATION"])) {
					if ($database[$name]["table_status"]["TABLE_COLLATION"] != 'utf8mb4_general_ci') {
						$sql2 = "DEFAULT COLLATE utf8mb4_general_ci";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				if ($sql3 != "") {
					$sql3 .= "; ";
				}

				// Now have a look at the field collations
				// Compare the field structure field by field
				foreach ($structure["fields"] as $fieldName => $parameters) {
					// Compare the field definition
					$field_definition = ($database[$name]["fields"][$fieldName] ?? '') ?: ['Collation' => ''];

					// Define the default collation if not given
					if (!isset($parameters['Collation']) && !empty($field_definition['Collation'])) {
						$parameters['Collation'] = 'utf8mb4_general_ci';
					} else {
						$parameters['Collation'] = null;
					}

					if ($field_definition['Collation'] != $parameters['Collation']) {
						$sql2 = DbaDefinitionSqlWriter::modifyTableField($fieldName, $parameters);
						if (($sql3 == "") || (substr($sql3, -2, 2) == "; ")) {
							$sql3 .= "ALTER" . $ignore . " TABLE `" . $name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}
			}

			if ($sql3 != "") {
				if (substr($sql3, -2, 2) != "; ") {
					$sql3 .= ";";
				}

				if ($verbose) {
					echo $sql3 . "\n";
				}

				if ($action) {
					if ($in_maintenance_mode) {
						DI::config()->set('system', 'maintenance_reason', DI::l10n()->t('%s: updating %s table.', DateTimeFormat::utcNow() . ' ' . date('e'), $name));
					}

					$r = DBA::e($sql3);
					if (!DBA::isResult($r)) {
						$errors .= self::printUpdateError($sql3);
					}
				}
			}
		}

		View::create(false, $action);

		self::checkInitialValues();

		if ($action && !$install) {
			if ($errors) {
				DI::config()->set('system', 'dbupdate', self::UPDATE_FAILED);
			} else {
				DI::config()->set('system', 'dbupdate', self::UPDATE_SUCCESSFUL);
			}
		}

		return $errors;
	}

	/**
	 * Returns an array with table structure information
	 *
	 * @param string $table Name of table
	 * @return array Table structure information
	 */
	private static function tableStructure(string $table): array
	{
		// This query doesn't seem to be executable as a prepared statement
		$indexes = DBA::toArray(DBA::p("SHOW INDEX FROM " . DBA::quoteIdentifier($table)));

		$fields = DBA::selectToArray(
			'INFORMATION_SCHEMA.COLUMNS',
			['COLUMN_NAME', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'EXTRA',
				'COLUMN_KEY', 'COLLATION_NAME', 'COLUMN_COMMENT'],
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?",
				DBA::databaseName(), $table]
		);

		$foreign_keys = DBA::selectToArray(
			'INFORMATION_SCHEMA.KEY_COLUMN_USAGE',
			['COLUMN_NAME', 'CONSTRAINT_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'],
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ? AND `REFERENCED_TABLE_SCHEMA` IS NOT NULL",
				DBA::databaseName(), $table]
		);

		$table_status = DBA::selectFirst(
			'INFORMATION_SCHEMA.TABLES',
			['ENGINE', 'TABLE_COLLATION', 'TABLE_COMMENT'],
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?",
				DBA::databaseName(), $table]
		);

		$fielddata   = [];
		$indexdata   = [];
		$foreigndata = [];

		if (DBA::isResult($foreign_keys)) {
			foreach ($foreign_keys as $foreign_key) {
				$parameters               = ['foreign' => [$foreign_key['REFERENCED_TABLE_NAME'] => $foreign_key['REFERENCED_COLUMN_NAME']]];
				$constraint               = self::getConstraintName($table, $foreign_key['COLUMN_NAME'], $parameters);
				$foreigndata[$constraint] = $foreign_key;
			}
		}

		if (DBA::isResult($indexes)) {
			foreach ($indexes as $index) {
				if ($index["Key_name"] != "PRIMARY" && $index["Non_unique"] == "0" && !isset($indexdata[$index["Key_name"]])) {
					$indexdata[$index["Key_name"]] = ["UNIQUE"];
				}

				if ($index["Index_type"] == "FULLTEXT" && !isset($indexdata[$index["Key_name"]])) {
					$indexdata[$index["Key_name"]] = ["FULLTEXT"];
				}

				$column = $index["Column_name"];

				if ($index["Sub_part"] != "") {
					$column .= "(" . $index["Sub_part"] . ")";
				}

				$indexdata[$index["Key_name"]][] = $column;
			}
		}

		$fielddata = [];
		if (DBA::isResult($fields)) {
			foreach ($fields as $field) {
				$search               = ['tinyint(1)', 'tinyint(3) unsigned', 'tinyint(4)', 'smallint(5) unsigned', 'smallint(6)', 'mediumint(8) unsigned', 'mediumint(9)', 'bigint(20)', 'int(10) unsigned', 'int(11)'];
				$replace              = ['boolean', 'tinyint unsigned', 'tinyint', 'smallint unsigned', 'smallint', 'mediumint unsigned', 'mediumint', 'bigint', 'int unsigned', 'int'];
				$field['COLUMN_TYPE'] = str_replace($search, $replace, $field['COLUMN_TYPE']);

				$fielddata[$field['COLUMN_NAME']]['type'] = $field['COLUMN_TYPE'];

				if ($field['IS_NULLABLE'] == 'NO') {
					$fielddata[$field['COLUMN_NAME']]['not null'] = true;
				}

				if (isset($field['COLUMN_DEFAULT']) && ($field['COLUMN_DEFAULT'] != 'NULL')) {
					$fielddata[$field['COLUMN_NAME']]['default'] = trim($field['COLUMN_DEFAULT'], "'");
				}

				if (!empty($field['EXTRA'])) {
					$fielddata[$field['COLUMN_NAME']]['extra'] = $field['EXTRA'];
				}

				if ($field['COLUMN_KEY'] == 'PRI') {
					$fielddata[$field['COLUMN_NAME']]['primary'] = true;
				}

				$fielddata[$field['COLUMN_NAME']]['Collation'] = $field['COLLATION_NAME'];
				$fielddata[$field['COLUMN_NAME']]['comment']   = $field['COLUMN_COMMENT'];
			}
		}

		return [
			'fields'       => $fielddata,
			'indexes'      => $indexdata,
			'foreign_keys' => $foreigndata,
			'table_status' => $table_status
		];
	}

	private static function getConstraintName(string $tableName, string $fieldName, array $parameters): string
	{
		$foreign_table = array_keys($parameters['foreign'])[0];
		$foreign_field = array_values($parameters['foreign'])[0];

		return $tableName . '-' . $fieldName. '-' . $foreign_table. '-' . $foreign_field;
	}

	/**
	 * Renames columns or the primary key of a table
	 *
	 * @todo You cannot rename a primary key if "auto increment" is set
	 *
	 * @param string $table            Table name
	 * @param array  $columns          Columns Syntax for Rename: [ $old1 => [ $new1, $type1 ], $old2 => [ $new2, $type2 ], ... ]
	 *                                 Syntax for Primary Key: [ $col1, $col2, ...]
	 * @param int    $type             The type of renaming (Default is Column)
	 *
	 * @return boolean Was the renaming successful?
	 * @throws Exception
	 */
	public static function rename(string $table, array $columns, int $type = self::RENAME_COLUMN): bool
	{
		if (empty($table) || empty($columns)) {
			return false;
		}

		if (!is_array($columns)) {
			return false;
		}

		$table = DBA::escape($table);

		$sql = "ALTER TABLE `" . $table . "`";
		switch ($type) {
			case self::RENAME_COLUMN:
				if (!self::existsColumn($table, array_keys($columns))) {
					return false;
				}
				$sql .= implode(',', array_map(
					function ($to, $from) {
						return " CHANGE `" . $from . "` `" . $to[0] . "` " . $to[1];
					},
					$columns,
					array_keys($columns)
				));
				break;
			case self::RENAME_PRIMARY_KEY:
				if (!self::existsColumn($table, $columns)) {
					return false;
				}
				$sql .= " DROP PRIMARY KEY, ADD PRIMARY KEY(`" . implode('`, `', $columns) . "`)";
				break;
			default:
				return false;
		}

		$sql .= ';';

		$stmt = DBA::p($sql);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = true;
		}

		DBA::close($stmt);

		return $retval;
	}

	/**
	 *    Check if the columns of the table exists
	 *
	 * @param string $table   Table name
	 * @param array  $columns Columns to check ( Syntax: [ $col1, $col2, .. ] )
	 *
	 * @return boolean Does the table exist?
	 * @throws Exception
	 */
	public static function existsColumn(string $table, array $columns = []): bool
	{
		if (empty($table)) {
			return false;
		}

		if (is_null($columns) || empty($columns)) {
			return self::existsTable($table);
		}

		$table = DBA::escape($table);

		foreach ($columns as $column) {
			$sql = "SHOW COLUMNS FROM `" . $table . "` LIKE '" . $column . "';";

			$stmt = DBA::p($sql);

			if (is_bool($stmt)) {
				$retval = $stmt;
			} else {
				$retval = (DBA::numRows($stmt) > 0);
			}

			DBA::close($stmt);

			if (!$retval) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a foreign key exists for the given table field
	 *
	 * @param string $table Table name
	 * @param string $field Field name
	 * @return boolean Wether a foreign key exists
	 */
	public static function existsForeignKeyForField(string $table, string $field): bool
	{
		return DBA::exists(
			'INFORMATION_SCHEMA.KEY_COLUMN_USAGE',
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ? AND `COLUMN_NAME` = ? AND `REFERENCED_TABLE_SCHEMA` IS NOT NULL",
				DBA::databaseName(), $table, $field]
		);
	}

	/**
	 * Check if a table exists
	 *
	 * @param string $table Single table name (please loop yourself)
	 * @return boolean Does the table exist?
	 * @throws Exception
	 */
	public static function existsTable(string $table): bool
	{
		if (empty($table)) {
			return false;
		}

		$condition = ['table_schema' => DBA::databaseName(), 'table_name' => $table];

		return DBA::exists('information_schema.tables', $condition);
	}

	/**
	 * Returns the columns of a table
	 *
	 * @param string $table Table name
	 *
	 * @return array An array of the table columns
	 * @throws Exception
	 */
	public static function getColumns(string $table): array
	{
		$stmtColumns = DBA::p("SHOW COLUMNS FROM `" . $table . "`");
		return DBA::toArray($stmtColumns);
	}

	/**
	 * Check if initial database values do exist - or create them
	 *
	 * @param bool $verbose Whether to output messages
	 * @return void
	 */
	public static function checkInitialValues(bool $verbose = false)
	{
		if (self::existsTable('verb')) {
			if (!DBA::exists('verb', ['id' => 1])) {
				foreach (Item::ACTIVITIES as $index => $activity) {
					DBA::insert('verb', ['id' => $index + 1, 'name' => $activity], Database::INSERT_IGNORE);
				}
				if ($verbose) {
					echo "verb: activities added\n";
				}
			} elseif ($verbose) {
				echo "verb: activities already added\n";
			}

			if (!DBA::exists('verb', ['id' => 0])) {
				DBA::insert('verb', ['name' => ''], Database::INSERT_IGNORE);
				$lastid = DBA::lastInsertId();
				if ($lastid != 0) {
					DBA::update('verb', ['id' => 0], ['id' => $lastid]);
					if ($verbose) {
						echo "Zero verb added\n";
					}
				}
			} elseif ($verbose) {
				echo "Zero verb already added\n";
			}
		} elseif ($verbose) {
			echo "verb: Table not found\n";
		}

		if (self::existsTable('user') && !DBA::exists('user', ['uid' => 0])) {
			$user = [
				'verified'     => true,
				'page-flags'   => User::PAGE_FLAGS_SOAPBOX,
				'account-type' => User::ACCOUNT_TYPE_RELAY,
			];
			DBA::insert('user', $user);
			$lastid = DBA::lastInsertId();
			if ($lastid != 0) {
				DBA::update('user', ['uid' => 0], ['uid' => $lastid]);
				if ($verbose) {
					echo "Zero user added\n";
				}
			}
		} elseif (self::existsTable('user') && $verbose) {
			echo "Zero user already added\n";
		} elseif ($verbose) {
			echo "user: Table not found\n";
		}

		if (self::existsTable('contact') && !DBA::exists('contact', ['id' => 0])) {
			DBA::insert('contact', ['nurl' => ''], Database::INSERT_IGNORE);
			$lastid = DBA::lastInsertId();
			if ($lastid != 0) {
				DBA::update('contact', ['id' => 0], ['id' => $lastid]);
				if ($verbose) {
					echo "Zero contact added\n";
				}
			}
		} elseif (self::existsTable('contact') && $verbose) {
			echo "Zero contact already added\n";
		} elseif ($verbose) {
			echo "contact: Table not found\n";
		}

		if (self::existsTable('tag') && !DBA::exists('tag', ['id' => 0])) {
			DBA::insert('tag', ['name' => ''], Database::INSERT_IGNORE);
			$lastid = DBA::lastInsertId();
			if ($lastid != 0) {
				DBA::update('tag', ['id' => 0], ['id' => $lastid]);
				if ($verbose) {
					echo "Zero tag added\n";
				}
			}
		} elseif (self::existsTable('tag') && $verbose) {
			echo "Zero tag already added\n";
		} elseif ($verbose) {
			echo "tag: Table not found\n";
		}

		if (self::existsTable('permissionset')) {
			if (!DBA::exists('permissionset', ['id' => 0])) {
				DBA::insert('permissionset', ['allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => ''], Database::INSERT_IGNORE);
				$lastid = DBA::lastInsertId();
				if ($lastid != 0) {
					DBA::update('permissionset', ['id' => 0], ['id' => $lastid]);
					if ($verbose) {
						echo "Zero permissionset added\n";
					}
				}
			} elseif ($verbose) {
				echo "Zero permissionset already added\n";
			}
			if (self::existsTable('item') && !self::existsForeignKeyForField('item', 'psid')) {
				$sets = DBA::p("SELECT `psid`, `item`.`uid`, `item`.`private` FROM `item`
					LEFT JOIN `permissionset` ON `permissionset`.`id` = `item`.`psid`
					WHERE `permissionset`.`id` IS NULL AND NOT `psid` IS NULL");
				while ($set = DBA::fetch($sets)) {
					if (($set['private'] == Item::PRIVATE) && ($set['uid'] != 0)) {
						$owner = User::getOwnerDataById($set['uid']);
						if ($owner) {
							$permission = '<' . $owner['id'] . '>';
						} else {
							$permission = '<>';
						}
					} else {
						$permission = '';
					}
					$fields = ['id' => $set['psid'], 'uid' => $set['uid'], 'allow_cid' => $permission,
						'allow_gid'    => '', 'deny_cid' => '', 'deny_gid' => ''];
					DBA::insert('permissionset', $fields, Database::INSERT_IGNORE);
				}
				DBA::close($sets);
			}
		} elseif ($verbose) {
			echo "permissionset: Table not found\n";
		}

		if (self::existsTable('tokens') && self::existsTable('clients') && !self::existsForeignKeyForField('tokens', 'client_id')) {
			$tokens = DBA::p("SELECT `tokens`.`id` FROM `tokens`
				LEFT JOIN `clients` ON `clients`.`client_id` = `tokens`.`client_id`
				WHERE `clients`.`client_id` IS NULL");
			while ($token = DBA::fetch($tokens)) {
				DBA::delete('tokens', ['id' => $token['id']]);
			}
			DBA::close($tokens);
		}
	}

	/**
	 * Checks if a database update is currently running
	 *
	 * @return boolean
	 */
	private static function isUpdating(): bool
	{
		$isUpdate = false;

		$processes = DBA::select('information_schema.processlist', ['info'], [
			'db'      => DBA::databaseName(),
			'command' => ['Query', 'Execute']
		]);

		while ($process = DBA::fetch($processes)) {
			$parts = explode(' ', $process['info']);
			if (in_array(strtolower(array_shift($parts)), ['alter', 'create', 'drop', 'rename'])) {
				$isUpdate = true;
			}
		}

		DBA::close($processes);

		return $isUpdate;
	}
}
