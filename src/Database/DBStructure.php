<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

namespace Friendica\Database;

use Exception;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Util\DateTimeFormat;

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
	 * Database structure definition loaded from config/dbstructure.config.php
	 *
	 * @var array
	 */
	private static $definition = [];

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

		DI::config()->set('system', 'build', $version);
		echo DI::l10n()->t('The database version had been set to %s.', $version);
	}

	/**
	 * Drop unused tables
	 *
	 * @param boolean $execute
	 * @return void
	 */
	public static function dropTables(bool $execute)
	{
		$postupdate = DI::config()->get("system", "post_update_version", PostUpdate::VERSION);
		if ($postupdate < PostUpdate::VERSION) {
			echo DI::l10n()->t('The post update is at version %d, it has to be at %d to safely drop the tables.', $postupdate, PostUpdate::VERSION);
			return;
		}

		$old_tables = ['fserver', 'gcign', 'gcontact', 'gcontact-relation', 'gfollower' ,'glink', 'item-delivery-data',
			'item-activity', 'item-content', 'item_id', 'participation', 'poll', 'poll_result', 'queue', 'retriever_rule',
			'deliverq', 'dsprphotoq', 'ffinder', 'sign', 'spam', 'term', 'user-item', 'thread', 'item', 'challenge',
			'auth_codes', 'clients', 'tokens', 'profile_check'];

		$tables = DBA::selectToArray(['INFORMATION_SCHEMA' => 'TABLES'], ['TABLE_NAME'],
			['TABLE_SCHEMA' => DBA::databaseName(), 'TABLE_TYPE' => 'BASE TABLE']);

		if (empty($tables)) {
			echo DI::l10n()->t('No unused tables found.');
			return;
		}

		if (!$execute) {
			echo DI::l10n()->t('These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":') . "\n\n";
		}

		foreach ($tables as $table) {
			if (in_array($table['TABLE_NAME'], $old_tables)) {
				if ($execute) {
					$sql = 'DROP TABLE ' . DBA::quoteIdentifier($table['TABLE_NAME']) . ';';
					echo $sql . "\n";

					$result = DBA::e($sql);
					if (!DBA::isResult($result)) {
						self::printUpdateError($sql);
					}
				} else {
					echo $table['TABLE_NAME'] . "\n";
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
			['information_schema' => 'tables'],
			['table_name'],
			['engine' => 'MyISAM', 'table_schema' => DBA::databaseName()]
		);

		$tables = array_merge($tables, DBA::selectToArray(
			['information_schema' => 'tables'],
			['table_name'],
			['engine' => 'InnoDB', 'ROW_FORMAT' => ['COMPACT', 'REDUNDANT'], 'table_schema' => DBA::databaseName()]
		));

		if (!DBA::isResult($tables)) {
			echo DI::l10n()->t('There are no tables on MyISAM or InnoDB with the Antelope file format.') . "\n";
			return;
		}

		foreach ($tables AS $table) {
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
	private static function printUpdateError($message)
	{
		echo DI::l10n()->t("\nError %d occurred during database update:\n%s\n",
			DBA::errorNo(), DBA::errorMessage());

		return DI::l10n()->t('Errors encountered performing database changes: ') . $message . EOL;
	}

	public static function writeStructure()
	{
		$tables = [];
		foreach (self::definition(null) as $name => $definition) {
			$indexes  = [[
				'name'   => 'Name',
				'fields' => 'Fields',
			],
			[
				'name'   => '-',
				'fields' => '-',
			]];

			$lengths = ['name' => 4, 'fields' => 6];
			foreach ($definition['indexes'] as $key => $value) {
				$fieldlist = implode(', ', $value);
				$indexes[] = ['name' => $key, 'fields' => $fieldlist];
				$lengths['name']   = max($lengths['name'], strlen($key));
				$lengths['fields'] = max($lengths['fields'], strlen($fieldlist));
			}

			array_walk_recursive($indexes, function(&$value, $key) use ($lengths)
			{
				$value = str_pad($value, $lengths[$key], $value === '-' ? '-' : ' ');
			});

			$foreign = [];
			$fields  = [[
				'name'    => 'Field',
				'comment' => 'Description',
				'type'    => 'Type',
				'null'    => 'Null',
				'primary' => 'Key',
				'default' => 'Default',
				'extra'   => 'Extra',
			],
			[
				'name'    => '-',
				'comment' => '-',
				'type'    => '-',
				'null'    => '-',
				'primary' => '-',
				'default' => '-',
				'extra'   => '-',
			]];
			$lengths = [
				'name'    => 5,
				'comment' => 11,
				'type'    => 4,
				'null'    => 4,
				'primary' => 3,
				'default' => 7,
				'extra'   => 5,
			];
			foreach ($definition['fields'] as $key => $value) {
				$field = [];
				$field['name']    = $key;
				$field['comment'] = $value['comment'] ?? '';
				$field['type']    = $value['type'];
				$field['null']    = ($value['not null'] ?? false) ? 'NO' : 'YES';
				$field['primary'] = ($value['primary'] ?? false) ? 'PRI' : '';
				$field['default'] = $value['default'] ?? 'NULL';
				$field['extra']   = $value['extra'] ?? '';

				foreach ($field as $fieldname => $fieldvalue) {
					$lengths[$fieldname] = max($lengths[$fieldname] ?? 0, strlen($fieldvalue));
				}
				$fields[] = $field;

				if (!empty($value['foreign'])) {
					$foreign[] = [
						'field'       => $key,
						'targettable' => array_keys($value['foreign'])[0],
						'targetfield' => array_values($value['foreign'])[0]
					];
				}
			}

			array_walk_recursive($fields, function(&$value, $key) use ($lengths)
			{
				$value = str_pad($value, $lengths[$key], $value === '-' ? '-' : ' ');
			});

			$tables[] = ['name' => $name, 'comment' => $definition['comment']];
			$content = Renderer::replaceMacros(Renderer::getMarkupTemplate('structure.tpl'), [
				'$name'    => $name,
				'$comment' => $definition['comment'],
				'$fields'  => $fields,
				'$indexes' => $indexes,
				'$foreign' => $foreign,
			]);
			$filename = DI::basePath() . '/doc/database/db_' . $name . '.md';
			file_put_contents($filename, $content);
		}
		asort($tables);
		$content = Renderer::replaceMacros(Renderer::getMarkupTemplate('tables.tpl'), [
			'$tables'  => $tables,
		]);
		$filename = DI::basePath() . '/doc/database.md';
		file_put_contents($filename, $content);
	}

	public static function printStructure($basePath)
	{
		$database = self::definition($basePath, false);

		echo "-- ------------------------------------------\n";
		echo "-- " . FRIENDICA_PLATFORM . " " . FRIENDICA_VERSION . " (" . FRIENDICA_CODENAME, ")\n";
		echo "-- DB_UPDATE_VERSION " . DB_UPDATE_VERSION . "\n";
		echo "-- ------------------------------------------\n\n\n";
		foreach ($database AS $name => $structure) {
			echo "--\n";
			echo "-- TABLE $name\n";
			echo "--\n";
			self::createTable($name, $structure, true, false);

			echo "\n";
		}

		View::printStructure($basePath);
	}

	/**
	 * Loads the database structure definition from the static/dbstructure.config.php file.
	 * On first pass, defines DB_UPDATE_VERSION constant.
	 *
	 * @see static/dbstructure.config.php
	 * @param boolean $with_addons_structure Whether to tack on addons additional tables
	 * @param string  $basePath              The base path of this application
	 * @return array
	 * @throws Exception
	 */
	public static function definition($basePath, $with_addons_structure = true)
	{
		if (!self::$definition) {
			if (empty($basePath)) {
				$basePath = DI::app()->getBasePath();
			}

			$filename = $basePath . '/static/dbstructure.config.php';

			if (!is_readable($filename)) {
				throw new Exception('Missing database structure config file static/dbstructure.config.php');
			}

			$definition = require $filename;

			if (!$definition) {
				throw new Exception('Corrupted database structure config file static/dbstructure.config.php');
			}

			self::$definition = $definition;
		} else {
			$definition = self::$definition;
		}

		if ($with_addons_structure) {
			Hook::callAll('dbstructure_definition', $definition);
		}

		return $definition;
	}

	/**
	 * Get field data for the given table
	 *
	 * @param string $table
	 * @param array $data data fields
	 * @return array fields for the given
	 */
	public static function getFieldsForTable(string $table, array $data = [])
	{
		$definition = DBStructure::definition('', false);
		if (empty($definition[$table])) {
			return [];
		}

		$fieldnames = array_keys($definition[$table]['fields']);

		$fields = [];

		// Assign all field that are present in the table
		foreach ($fieldnames as $field) {
			if (isset($data[$field])) {
				// Limit the length of varchar, varbinary, char and binrary fields
				if (is_string($data[$field]) && preg_match("/char\((\d*)\)/", $definition[$table]['fields'][$field]['type'], $result)) {
					$data[$field] = mb_substr($data[$field], 0, $result[1]);
				} elseif (is_string($data[$field]) && preg_match("/binary\((\d*)\)/", $definition[$table]['fields'][$field]['type'], $result)) {
					$data[$field] = substr($data[$field], 0, $result[1]);
				}
				$fields[$field] = $data[$field];
			}
		}

		return $fields;
	}

	private static function createTable($name, $structure, $verbose, $action)
	{
		$r = true;

		$engine = "";
		$comment = "";
		$sql_rows = [];
		$primary_keys = [];
		$foreign_keys = [];

		foreach ($structure["fields"] AS $fieldname => $field) {
			$sql_rows[] = "`" . DBA::escape($fieldname) . "` " . self::FieldCommand($field);
			if (!empty($field['primary'])) {
				$primary_keys[] = $fieldname;
			}
			if (!empty($field['foreign'])) {
				$foreign_keys[$fieldname] = $field;
			}
		}

		if (!empty($structure["indexes"])) {
			foreach ($structure["indexes"] AS $indexname => $fieldnames) {
				$sql_index = self::createIndex($indexname, $fieldnames, "");
				if (!is_null($sql_index)) {
					$sql_rows[] = $sql_index;
				}
			}
		}

		foreach ($foreign_keys AS $fieldname => $parameters) {
			$sql_rows[] = self::foreignCommand($name, $fieldname, $parameters);
		}

		if (isset($structure["engine"])) {
			$engine = " ENGINE=" . $structure["engine"];
		}

		if (isset($structure["comment"])) {
			$comment = " COMMENT='" . DBA::escape($structure["comment"]) . "'";
		}

		$sql = implode(",\n\t", $sql_rows);

		$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (\n\t", DBA::escape($name)) . $sql .
			"\n)" . $engine . " DEFAULT COLLATE utf8mb4_general_ci" . $comment;
		if ($verbose) {
			echo $sql . ";\n";
		}

		if ($action) {
			$r = DBA::e($sql);
		}

		return $r;
	}

	private static function FieldCommand($parameters, $create = true)
	{
		$fieldstruct = $parameters["type"];

		if (isset($parameters["Collation"])) {
			$fieldstruct .= " COLLATE " . $parameters["Collation"];
		}

		if (isset($parameters["not null"])) {
			$fieldstruct .= " NOT NULL";
		}

		if (isset($parameters["default"])) {
			if (strpos(strtolower($parameters["type"]), "int") !== false) {
				$fieldstruct .= " DEFAULT " . $parameters["default"];
			} else {
				$fieldstruct .= " DEFAULT '" . $parameters["default"] . "'";
			}
		}
		if (isset($parameters["extra"])) {
			$fieldstruct .= " " . $parameters["extra"];
		}

		if (isset($parameters["comment"])) {
			$fieldstruct .= " COMMENT '" . DBA::escape($parameters["comment"]) . "'";
		}

		/*if (($parameters["primary"] != "") && $create)
			$fieldstruct .= " PRIMARY KEY";*/

		return ($fieldstruct);
	}

	private static function createIndex($indexname, $fieldnames, $method = "ADD")
	{
		$method = strtoupper(trim($method));
		if ($method != "" && $method != "ADD") {
			throw new Exception("Invalid parameter 'method' in self::createIndex(): '$method'");
		}

		if (in_array($fieldnames[0], ["UNIQUE", "FULLTEXT"])) {
			$index_type = array_shift($fieldnames);
			$method .= " " . $index_type;
		}

		$names = "";
		foreach ($fieldnames AS $fieldname) {
			if ($names != "") {
				$names .= ",";
			}

			if (preg_match('|(.+)\((\d+)\)|', $fieldname, $matches)) {
				$names .= "`" . DBA::escape($matches[1]) . "`(" . intval($matches[2]) . ")";
			} else {
				$names .= "`" . DBA::escape($fieldname) . "`";
			}
		}

		if ($indexname == "PRIMARY") {
			return sprintf("%s PRIMARY KEY(%s)", $method, $names);
		}


		$sql = sprintf("%s INDEX `%s` (%s)", $method, DBA::escape($indexname), $names);
		return ($sql);
	}

	/**
	 * Perform a database structure dryrun (means: just simulating)
	 *
	 * @throws Exception
	 */
	public static function dryRun()
	{
		self::update(DI::app()->getBasePath(), true, false);
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
	public static function performUpdate(bool $enable_maintenance_mode = true, bool $verbose = false)
	{
		if ($enable_maintenance_mode) {
			DI::config()->set('system', 'maintenance', 1);
		}

		$status = self::update(DI::app()->getBasePath(), $verbose, true);

		if ($enable_maintenance_mode) {
			DI::config()->set('system', 'maintenance', 0);
			DI::config()->set('system', 'maintenance_reason', '');
		}

		return $status;
	}

	/**
	 * Updates DB structure from the installation and returns eventual errors messages
	 *
	 * @param string $basePath   The base path of this application
	 *
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws Exception
	 */
	public static function install(string $basePath)
	{
		return self::update($basePath, false, true, true);
	}

	/**
	 * Explicit updates the Lock table
	 * This is necessary in case of self-updating the lock table
	 *
	 * @param string  $basePath
	 * @param boolean $verbose
	 * @param boolean $action
	 * @param boolean $install
	 *
	 * @throws Exception
	 */
	public static function updateLockTable(string $basePath, bool $verbose, bool $action, bool $install)
	{
		if (DBStructure::existsTable('host')) {
			$lockTable = [['tables_in_friendica' => 'host']];
		} else {
			$lockTable = [];
		}

		$definition          = self::definition($basePath);
		$lockTableDefinition = ['host' => $definition['host']];

		self::update($basePath, $verbose, $action, $install, $lockTable, $lockTableDefinition);

		$lockTable           = [['tables_in_friendica' => 'locks']];
		$definition          = self::definition($basePath);
		$lockTableDefinition = ['locks' => $definition['locks']];

		self::update($basePath, $verbose, $action, $install, $lockTable, $lockTableDefinition);
	}

	/**
	 * Updates DB structure and returns eventual errors messages
	 *
	 * @param string $basePath   The base path of this application
	 * @param bool   $verbose
	 * @param bool   $action     Whether to actually apply the update
	 * @param bool   $install    Is this the initial update during the installation?
	 * @param array  $tables     An array of the database tables
	 * @param array  $definition An array of the definition tables
	 * @return string Empty string if the update is successful, error messages otherwise
	 * @throws Exception
	 */
	private static function update($basePath, $verbose, $action, $install = false, array $tables = null, array $definition = null)
	{
		$in_maintenance_mode = DI::config()->get('system', 'maintenance');

		if ($action && !$install && self::isUpdating()) {
			return DI::l10n()->t('Another database update is currently running.');
		}

		if ($in_maintenance_mode) {
			DI::config()->set('system', 'maintenance_reason', DI::l10n()->t('%s: Database update', DateTimeFormat::utcNow() . ' ' . date('e')));
		}

		// If true, the update routine just updates the tables and no other steps, like initial values, views, ..
		$straightUpdate = !is_null($tables) && !is_null($definition);

		// ensure that all initial values exist. This test has to be done prior and after the structure check.
		// Prior is needed if the specific tables already exists - after is needed when they had been created.
		if ($straightUpdate) {
			self::checkInitialValues();
		}

		$errors = '';

		Logger::info('updating structure');

		// Get the current structure
		$database = [];

		if (is_null($tables)) {
			$tables = DBA::toArray(DBA::p("SHOW TABLES"));
		}

		if (DBA::isResult($tables)) {
			foreach ($tables AS $table) {
				$table = current($table);

				Logger::info('updating structure', ['table' => $table]);
				$database[$table] = self::tableStructure($table);
			}
		}

		// Get the definition
		if (is_null($definition)) {
			$definition = self::definition($basePath);
		}

		// MySQL >= 5.7.4 doesn't support the IGNORE keyword in ALTER TABLE statements
		if ((version_compare(DBA::serverInfo(), '5.7.4') >= 0) &&
			!(strpos(DBA::serverInfo(), 'MariaDB') !== false)) {
			$ignore = '';
		} else {
			$ignore = ' IGNORE';
		}

		// Compare it
		foreach ($definition AS $name => $structure) {
			$is_new_table = false;
			$group_by = "";
			$sql3 = "";
			$is_unique = false;
			$temp_name = $name;
			if (!isset($database[$name])) {
				$r = self::createTable($name, $structure, $verbose, $action);
				if (!DBA::isResult($r)) {
					$errors .= self::printUpdateError($name);
				}
				$is_new_table = true;
			} else {
				foreach ($structure["indexes"] AS $indexname => $fieldnames) {
					if (isset($database[$name]["indexes"][$indexname])) {
						$current_index_definition = implode(",", $database[$name]["indexes"][$indexname]);
					} else {
						$current_index_definition = "__NOT_SET__";
					}
					$new_index_definition = implode(",", $fieldnames);
					if ($current_index_definition != $new_index_definition) {
						if ($fieldnames[0] == "UNIQUE") {
							$is_unique = true;
							if ($ignore == "") {
								$temp_name = "temp-" . $name;
							}
						}
					}
				}

				/*
				 * Drop the index if it isn't present in the definition
				 * or the definition differ from current status
				 * and index name doesn't start with "local_"
				 */
				foreach ($database[$name]["indexes"] as $indexname => $fieldnames) {
					$current_index_definition = implode(",", $fieldnames);
					if (isset($structure["indexes"][$indexname])) {
						$new_index_definition = implode(",", $structure["indexes"][$indexname]);
					} else {
						$new_index_definition = "__NOT_SET__";
					}
					if ($current_index_definition != $new_index_definition && substr($indexname, 0, 6) != 'local_') {
						$sql2 = self::dropIndex($indexname);
						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}
				// Compare the field structure field by field
				foreach ($structure["fields"] AS $fieldname => $parameters) {
					if (!isset($database[$name]["fields"][$fieldname])) {
						$sql2 = self::addTableField($fieldname, $parameters);
						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					} else {
						// Compare the field definition
						$field_definition = $database[$name]["fields"][$fieldname];

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
						$new_field_definition = DBA::cleanQuery(implode(",", $parameters));
						if ($current_field_definition != $new_field_definition) {
							$sql2 = self::modifyTableField($fieldname, $parameters);
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
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
				foreach ($structure["indexes"] AS $indexname => $fieldnames) {
					if (isset($database[$name]["indexes"][$indexname])) {
						$current_index_definition = implode(",", $database[$name]["indexes"][$indexname]);
					} else {
						$current_index_definition = "__NOT_SET__";
					}
					$new_index_definition = implode(",", $fieldnames);
					if ($current_index_definition != $new_index_definition) {
						$sql2 = self::createIndex($indexname, $fieldnames);

						// Fetch the "group by" fields for unique indexes
						$group_by = self::groupBy($fieldnames);
						if ($sql2 != "") {
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
							} else {
								$sql3 .= ", " . $sql2;
							}
						}
					}
				}

				$existing_foreign_keys = $database[$name]['foreign_keys'];

				// Foreign keys
				// Compare the field structure field by field
				foreach ($structure["fields"] AS $fieldname => $parameters) {
					if (empty($parameters['foreign'])) {
						continue;
					}

					$constraint = self::getConstraintName($name, $fieldname, $parameters);

					unset($existing_foreign_keys[$constraint]);

					if (empty($database[$name]['foreign_keys'][$constraint])) {
						$sql2 = self::addForeignKey($name, $fieldname, $parameters);

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				foreach ($existing_foreign_keys as $param) {
					$sql2 = self::dropForeignKey($param['CONSTRAINT_NAME']);

					if ($sql3 == "") {
						$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
					} else {
						$sql3 .= ", " . $sql2;
					}
				}

				if (isset($database[$name]["table_status"]["TABLE_COMMENT"])) {
					$structurecomment = $structure["comment"] ?? '';
					if ($database[$name]["table_status"]["TABLE_COMMENT"] != $structurecomment) {
						$sql2 = "COMMENT = '" . DBA::escape($structurecomment) . "'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["ENGINE"]) && isset($structure['engine'])) {
					if ($database[$name]["table_status"]["ENGINE"] != $structure['engine']) {
						$sql2 = "ENGINE = '" . DBA::escape($structure['engine']) . "'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["TABLE_COLLATION"])) {
					if ($database[$name]["table_status"]["TABLE_COLLATION"] != 'utf8mb4_general_ci') {
						$sql2 = "DEFAULT COLLATE utf8mb4_general_ci";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
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
				foreach ($structure["fields"] AS $fieldname => $parameters) {
					// Compare the field definition
					$field_definition = ($database[$name]["fields"][$fieldname] ?? '') ?: ['Collation' => ''];

					// Define the default collation if not given
					if (!isset($parameters['Collation']) && !empty($field_definition['Collation'])) {
						$parameters['Collation'] = 'utf8mb4_general_ci';
					} else {
						$parameters['Collation'] = null;
					}

					if ($field_definition['Collation'] != $parameters['Collation']) {
						$sql2 = self::modifyTableField($fieldname, $parameters);
						if (($sql3 == "") || (substr($sql3, -2, 2) == "; ")) {
							$sql3 .= "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
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

				$field_list = '';
				if ($is_unique && $ignore == '') {
					foreach ($database[$name]["fields"] AS $fieldname => $parameters) {
						$field_list .= 'ANY_VALUE(`' . $fieldname . '`),';
					}
					$field_list = rtrim($field_list, ',');
				}

				if ($verbose) {
					// Ensure index conversion to unique removes duplicates
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							echo "SET session old_alter_table=1;\n";
						} else {
							echo "DROP TABLE IF EXISTS `" . $temp_name . "`;\n";
							echo "CREATE TABLE `" . $temp_name . "` LIKE `" . $name . "`;\n";
						}
					}

					echo $sql3 . "\n";

					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							echo "SET session old_alter_table=0;\n";
						} else {
							echo "INSERT INTO `" . $temp_name . "` SELECT " . DBA::anyValueFallback($field_list) . " FROM `" . $name . "`" . $group_by . ";\n";
							echo "DROP TABLE `" . $name . "`;\n";
							echo "RENAME TABLE `" . $temp_name . "` TO `" . $name . "`;\n";
						}
					}
				}

				if ($action) {
					if ($in_maintenance_mode) {
						DI::config()->set('system', 'maintenance_reason', DI::l10n()->t('%s: updating %s table.', DateTimeFormat::utcNow() . ' ' . date('e'), $name));
					}

					// Ensure index conversion to unique removes duplicates
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							DBA::e("SET session old_alter_table=1;");
						} else {
							$r = DBA::e("DROP TABLE IF EXISTS `" . $temp_name . "`;");
							if (!DBA::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}

							$r = DBA::e("CREATE TABLE `" . $temp_name . "` LIKE `" . $name . "`;");
							if (!DBA::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
						}
					}

					$r = DBA::e($sql3);
					if (!DBA::isResult($r)) {
						$errors .= self::printUpdateError($sql3);
					}
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							DBA::e("SET session old_alter_table=0;");
						} else {
							$r = DBA::e("INSERT INTO `" . $temp_name . "` SELECT " . $field_list . " FROM `" . $name . "`" . $group_by . ";");
							if (!DBA::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = DBA::e("DROP TABLE `" . $name . "`;");
							if (!DBA::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = DBA::e("RENAME TABLE `" . $temp_name . "` TO `" . $name . "`;");
							if (!DBA::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
						}
					}
				}
			}
		}

		if ($straightUpdate) {
			View::create(false, $action);

			self::checkInitialValues();
		}

		if ($action && !$install) {
			if ($errors) {
				DI::config()->set('system', 'dbupdate', self::UPDATE_FAILED);
			} else {
				DI::config()->set('system', 'dbupdate', self::UPDATE_SUCCESSFUL);
			}
		}

		return $errors;
	}

	private static function tableStructure($table)
	{
		// This query doesn't seem to be executable as a prepared statement
		$indexes = DBA::toArray(DBA::p("SHOW INDEX FROM " . DBA::quoteIdentifier($table)));

		$fields = DBA::selectToArray(['INFORMATION_SCHEMA' => 'COLUMNS'],
			['COLUMN_NAME', 'COLUMN_TYPE', 'IS_NULLABLE', 'COLUMN_DEFAULT', 'EXTRA',
			'COLUMN_KEY', 'COLLATION_NAME', 'COLUMN_COMMENT'],
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?",
			DBA::databaseName(), $table]);

		$foreign_keys = DBA::selectToArray(['INFORMATION_SCHEMA' => 'KEY_COLUMN_USAGE'],
			['COLUMN_NAME', 'CONSTRAINT_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'],
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ? AND `REFERENCED_TABLE_SCHEMA` IS NOT NULL",
			DBA::databaseName(), $table]);

		$table_status = DBA::selectFirst(['INFORMATION_SCHEMA' => 'TABLES'],
			['ENGINE', 'TABLE_COLLATION', 'TABLE_COMMENT'],
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ?",
			DBA::databaseName(), $table]);

		$fielddata = [];
		$indexdata = [];
		$foreigndata = [];

		if (DBA::isResult($foreign_keys)) {
			foreach ($foreign_keys as $foreign_key) {
				$parameters = ['foreign' => [$foreign_key['REFERENCED_TABLE_NAME'] => $foreign_key['REFERENCED_COLUMN_NAME']]];
				$constraint = self::getConstraintName($table, $foreign_key['COLUMN_NAME'], $parameters);
				$foreigndata[$constraint] = $foreign_key;
			}
		}

		if (DBA::isResult($indexes)) {
			foreach ($indexes AS $index) {
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
			foreach ($fields AS $field) {
				$search = ['tinyint(1)', 'tinyint(3) unsigned', 'tinyint(4)', 'smallint(5) unsigned', 'smallint(6)', 'mediumint(8) unsigned', 'mediumint(9)', 'bigint(20)', 'int(10) unsigned', 'int(11)'];
				$replace = ['boolean', 'tinyint unsigned', 'tinyint', 'smallint unsigned', 'smallint', 'mediumint unsigned', 'mediumint', 'bigint', 'int unsigned', 'int'];
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
				$fielddata[$field['COLUMN_NAME']]['comment'] = $field['COLUMN_COMMENT'];
			}
		}

		return ["fields" => $fielddata, "indexes" => $indexdata,
			"foreign_keys" => $foreigndata, "table_status" => $table_status];
	}

	private static function dropIndex($indexname)
	{
		$sql = sprintf("DROP INDEX `%s`", DBA::escape($indexname));
		return ($sql);
	}

	private static function addTableField($fieldname, $parameters)
	{
		$sql = sprintf("ADD `%s` %s", DBA::escape($fieldname), self::FieldCommand($parameters));
		return ($sql);
	}

	private static function modifyTableField($fieldname, $parameters)
	{
		$sql = sprintf("MODIFY `%s` %s", DBA::escape($fieldname), self::FieldCommand($parameters, false));
		return ($sql);
	}

	private static function getConstraintName(string $tablename, string $fieldname, array $parameters)
	{
		$foreign_table = array_keys($parameters['foreign'])[0];
		$foreign_field = array_values($parameters['foreign'])[0];

		return $tablename . "-" . $fieldname. "-" . $foreign_table. "-" . $foreign_field;
	}

	private static function foreignCommand(string $tablename, string $fieldname, array $parameters) {
		$foreign_table = array_keys($parameters['foreign'])[0];
		$foreign_field = array_values($parameters['foreign'])[0];

		$sql = "FOREIGN KEY (`" . $fieldname . "`) REFERENCES `" . $foreign_table . "` (`" . $foreign_field . "`)";

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

	private static function addForeignKey(string $tablename, string $fieldname, array $parameters)
	{
		return sprintf("ADD %s", self::foreignCommand($tablename, $fieldname, $parameters));
	}

	private static function dropForeignKey(string $constraint)
	{
		return sprintf("DROP FOREIGN KEY `%s`", $constraint);
	}

	/**
	 * Constructs a GROUP BY clause from a UNIQUE index definition.
	 *
	 * @param array $fieldnames
	 * @return string
	 */
	private static function groupBy(array $fieldnames)
	{
		if ($fieldnames[0] != "UNIQUE") {
			return "";
		}

		array_shift($fieldnames);

		$names = "";
		foreach ($fieldnames AS $fieldname) {
			if ($names != "") {
				$names .= ",";
			}

			if (preg_match('|(.+)\((\d+)\)|', $fieldname, $matches)) {
				$names .= "`" . DBA::escape($matches[1]) . "`";
			} else {
				$names .= "`" . DBA::escape($fieldname) . "`";
			}
		}

		$sql = sprintf(" GROUP BY %s", $names);
		return $sql;
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
	public static function rename($table, $columns, $type = self::RENAME_COLUMN)
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

		$sql .= ";";

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
	public static function existsColumn($table, $columns = [])
	{
		if (empty($table)) {
			return false;
		}

		if (is_null($columns) || empty($columns)) {
			return self::existsTable($table);
		}

		$table = DBA::escape($table);

		foreach ($columns AS $column) {
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
	 * @param string $table
	 * @param string $field
	 * @return boolean
	 */
	public static function existsForeignKeyForField(string $table, string $field)
	{
		return DBA::exists(['INFORMATION_SCHEMA' => 'KEY_COLUMN_USAGE'],
			["`TABLE_SCHEMA` = ? AND `TABLE_NAME` = ? AND `COLUMN_NAME` = ? AND `REFERENCED_TABLE_SCHEMA` IS NOT NULL",
			DBA::databaseName(), $table, $field]);
	}
	/**
	 *    Check if a table exists
	 *
	 * @param string|array $table Table name
	 *
	 * @return boolean Does the table exist?
	 * @throws Exception
	 */
	public static function existsTable($table)
	{
		if (empty($table)) {
			return false;
		}

		if (is_array($table)) {
			$condition = ['table_schema' => key($table), 'table_name' => current($table)];
		} else {
			$condition = ['table_schema' => DBA::databaseName(), 'table_name' => $table];
		}

		$result = DBA::exists(['information_schema' => 'tables'], $condition);

		return $result;
	}

	/**
	 * Returns the columns of a table
	 *
	 * @param string $table Table name
	 *
	 * @return array An array of the table columns
	 * @throws Exception
	 */
	public static function getColumns($table)
	{
		$stmtColumns = DBA::p("SHOW COLUMNS FROM `" . $table . "`");
		return DBA::toArray($stmtColumns);
	}

	/**
	 * Check if initial database values do exist - or create them
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
				DBA::insert('verb', ['name' => '']);
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
				"verified" => true,
				"page-flags" => User::PAGE_FLAGS_SOAPBOX,
				"account-type" => User::ACCOUNT_TYPE_RELAY,
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
			DBA::insert('contact', ['nurl' => '']);
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
			DBA::insert('tag', ['name' => '']);
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
				DBA::insert('permissionset', ['allow_cid' => '', 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '']);
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
						'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => ''];
					DBA::insert('permissionset', $fields);
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
	private static function isUpdating()
	{
		$isUpdate = false;

		$processes = DBA::select(['information_schema' => 'processlist'], ['info'],
			['db' => DBA::databaseName(), 'command' => ['Query', 'Execute']]);

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
