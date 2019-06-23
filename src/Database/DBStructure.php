<?php
/**
 * @file src/Database/DBStructure.php
 */

namespace Friendica\Database;

use Exception;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Util\DateTimeFormat;

require_once 'include/dba.php';

/**
 * @brief This class contain functions for the database management
 *
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

	/*
	 * Converts all tables from MyISAM to InnoDB
	 */
	public static function convertToInnoDB()
	{
		$r = q("SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `engine` = 'MyISAM' AND `table_schema` = '%s'",
			DBA::escape(DBA::databaseName()));

		if (!DBA::isResult($r)) {
			echo L10n::t('There are no tables on MyISAM.') . "\n";
			return;
		}

		foreach ($r AS $table) {
			$sql = sprintf("ALTER TABLE `%s` engine=InnoDB;", DBA::escape($table['TABLE_NAME']));
			echo $sql . "\n";

			$result = DBA::e($sql);
			if (!DBA::isResult($result)) {
				self::printUpdateError($sql);
			}
		}
	}

	/**
	 * @brief Print out database error messages
	 *
	 * @param string $message Message to be added to the error message
	 *
	 * @return string Error message
	 */
	private static function printUpdateError($message)
	{
		echo L10n::t("\nError %d occurred during database update:\n%s\n",
			DBA::errorNo(), DBA::errorMessage());

		return L10n::t('Errors encountered performing database changes: ') . $message . EOL;
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
	}

	/**
	 * Loads the database structure definition from the config/dbstructure.config.php file.
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

	private static function createTable($name, $structure, $verbose, $action)
	{
		$r = true;

		$engine = "";
		$comment = "";
		$sql_rows = [];
		$primary_keys = [];
		foreach ($structure["fields"] AS $fieldname => $field) {
			$sql_rows[] = "`" . DBA::escape($fieldname) . "` " . self::FieldCommand($field);
			if (!empty($field['primary'])) {
				$primary_keys[] = $fieldname;
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
	public static function update($basePath, $verbose, $action, $install = false, array $tables = null, array $definition = null)
	{
		if ($action && !$install) {
			Config::set('system', 'maintenance', 1);
			Config::set('system', 'maintenance_reason', L10n::t('%s: Database update', DateTimeFormat::utcNow() . ' ' . date('e')));
		}

		$errors = '';

		Logger::log('updating structure', Logger::DEBUG);

		// Get the current structure
		$database = [];

		if (is_null($tables)) {
			$tables = q("SHOW TABLES");
		}

		if (DBA::isResult($tables)) {
			foreach ($tables AS $table) {
				$table = current($table);

				Logger::log(sprintf('updating structure for table %s ...', $table), Logger::DEBUG);
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

				if (isset($database[$name]["table_status"]["Comment"])) {
					$structurecomment = defaults($structure, "comment", "");
					if ($database[$name]["table_status"]["Comment"] != $structurecomment) {
						$sql2 = "COMMENT = '" . DBA::escape($structurecomment) . "'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["Engine"]) && isset($structure['engine'])) {
					if ($database[$name]["table_status"]["Engine"] != $structure['engine']) {
						$sql2 = "ENGINE = '" . DBA::escape($structure['engine']) . "'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `" . $temp_name . "` " . $sql2;
						} else {
							$sql3 .= ", " . $sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["Collation"])) {
					if ($database[$name]["table_status"]["Collation"] != 'utf8mb4_general_ci') {
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
					$field_definition = defaults($database[$name]["fields"], $fieldname, ['Collation' => '']);

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
					if (!$install) {
						Config::set('system', 'maintenance_reason', L10n::t('%s: updating %s table.', DateTimeFormat::utcNow() . ' ' . date('e'), $name));
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

		if ($action && !$install) {
			Config::set('system', 'maintenance', 0);
			Config::set('system', 'maintenance_reason', '');

			if ($errors) {
				Config::set('system', 'dbupdate', self::UPDATE_FAILED);
			} else {
				Config::set('system', 'dbupdate', self::UPDATE_SUCCESSFUL);
			}
		}

		return $errors;
	}

	private static function tableStructure($table)
	{
		$structures = q("DESCRIBE `%s`", $table);

		$full_columns = q("SHOW FULL COLUMNS FROM `%s`", $table);

		$indexes = q("SHOW INDEX FROM `%s`", $table);

		$table_status = q("SHOW TABLE STATUS WHERE `name` = '%s'", $table);

		if (DBA::isResult($table_status)) {
			$table_status = $table_status[0];
		} else {
			$table_status = [];
		}

		$fielddata = [];
		$indexdata = [];

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
		if (DBA::isResult($structures)) {
			foreach ($structures AS $field) {
				// Replace the default size values so that we don't have to define them
				$search = ['tinyint(1)', 'tinyint(3) unsigned', 'tinyint(4)', 'smallint(5) unsigned', 'smallint(6)', 'mediumint(8) unsigned', 'mediumint(9)', 'bigint(20)', 'int(10) unsigned', 'int(11)'];
				$replace = ['boolean', 'tinyint unsigned', 'tinyint', 'smallint unsigned', 'smallint', 'mediumint unsigned', 'mediumint', 'bigint', 'int unsigned', 'int'];
				$field["Type"] = str_replace($search, $replace, $field["Type"]);

				$fielddata[$field["Field"]]["type"] = $field["Type"];
				if ($field["Null"] == "NO") {
					$fielddata[$field["Field"]]["not null"] = true;
				}

				if (isset($field["Default"])) {
					$fielddata[$field["Field"]]["default"] = $field["Default"];
				}

				if ($field["Extra"] != "") {
					$fielddata[$field["Field"]]["extra"] = $field["Extra"];
				}

				if ($field["Key"] == "PRI") {
					$fielddata[$field["Field"]]["primary"] = true;
				}
			}
		}
		if (DBA::isResult($full_columns)) {
			foreach ($full_columns AS $column) {
				$fielddata[$column["Field"]]["Collation"] = $column["Collation"];
				$fielddata[$column["Field"]]["comment"] = $column["Comment"];
			}
		}

		return ["fields" => $fielddata, "indexes" => $indexdata, "table_status" => $table_status];
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
	 * @param array  $columns          Columns Syntax for Rename: [ $old1 => [ $new1, $type1 ], $old2 => [ $new2, $type2 ], ... ] )
	 *                                 Syntax for Primary Key: [ $col1, $col2, ...] )
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
	 *    Check if a table exists
	 *
	 * @param string $table Table name
	 *
	 * @return boolean Does the table exist?
	 * @throws Exception
	 */
	public static function existsTable($table)
	{
		if (empty($table)) {
			return false;
		}

		$table = DBA::escape($table);

		$sql = "SHOW TABLES LIKE '" . $table . "';";

		$stmt = DBA::p($sql);

		if (is_bool($stmt)) {
			$retval = $stmt;
		} else {
			$retval = (DBA::numRows($stmt) > 0);
		}

		DBA::close($stmt);

		return $retval;
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
}
