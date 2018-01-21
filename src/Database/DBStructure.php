<?php
/**
 * @file src/Database/DBStructure.php
 */
namespace Friendica\Database;

use Friendica\Core\Config;
use Friendica\Database\DBM;
use dba;

require_once "boot.php";
require_once 'include/dba.php';
require_once 'include/enotify.php';
require_once "include/text.php";

/**
 * @brief This class contain functions for the database management
 *
 * This class contains functions that doesn't need to know if pdo, mysqli or whatever is used.
 */
class DBStructure {
	/*
	 * Converts all tables from MyISAM to InnoDB
	 */
	public static function convertToInnoDB() {
		$r = q("SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `engine` = 'MyISAM' AND `table_schema` = '%s'",
			dbesc(dba::database_name()));

		if (!DBM::isResult($r)) {
			echo t('There are no tables on MyISAM.')."\n";
			return;
		}

		foreach ($r AS $table) {
			$sql = sprintf("ALTER TABLE `%s` engine=InnoDB;", dbesc($table['TABLE_NAME']));
			echo $sql."\n";

			$result = dba::e($sql);
			if (!DBM::isResult($result)) {
				self::printUpdateError($sql);
			}
		}
	}

	/*
	 * send the email and do what is needed to do on update fails
	 *
	 * @param update_id		(int) number of failed update
	 * @param error_message	(str) error message
	 */
	public static function updateFail($update_id, $error_message) {
		$a = get_app();

		//send the administrators an e-mail
		$admin_mail_list = "'".implode("','", array_map(dbesc, explode(",", str_replace(" ", "", $a->config['admin_email']))))."'";
		$adminlist = q("SELECT uid, language, email FROM user WHERE email IN (%s)",
			$admin_mail_list
		);

		// No valid result?
		if (!DBM::isResult($adminlist)) {
			logger(sprintf('Cannot notify administrators about update_id=%d, error_message=%s', $update_id, $error_message), LOGGER_NORMAL);

			// Don't continue
			return;
		}

		// every admin could had different language
		foreach ($adminlist as $admin) {
			$lang = (($admin['language'])?$admin['language']:'en');
			push_lang($lang);

			$preamble = deindent(t("
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can't do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid."));
			$body = t("The error message is\n[pre]%s[/pre]");
			$preamble = sprintf($preamble, $update_id);
			$body = sprintf($body, $error_message);

			notification([
				'type' => SYSTEM_EMAIL,
				'to_email' => $admin['email'],
				'preamble' => $preamble,
				'body' => $body,
				'language' => $lang]
			);
		}

		//try the logger
		logger("CRITICAL: Database structure update failed: ".$error_message);
	}


	private static function tableStructure($table) {
		$structures = q("DESCRIBE `%s`", $table);

		$full_columns = q("SHOW FULL COLUMNS FROM `%s`", $table);

		$indexes = q("SHOW INDEX FROM `%s`", $table);

		$table_status = q("SHOW TABLE STATUS WHERE `name` = '%s'", $table);

		if (DBM::isResult($table_status)) {
			$table_status = $table_status[0];
		} else {
			$table_status = [];
		}

		$fielddata = [];
		$indexdata = [];

		if (DBM::isResult($indexes)) {
			foreach ($indexes AS $index) {
				if ($index['Key_name'] != 'PRIMARY' && $index['Non_unique'] == '0' && !isset($indexdata[$index["Key_name"]])) {
					$indexdata[$index["Key_name"]] = ['UNIQUE'];
				}

				$column = $index["Column_name"];

				if ($index["Sub_part"] != "") {
					$column .= "(".$index["Sub_part"].")";
				}

				$indexdata[$index["Key_name"]][] = $column;
			}
		}
		if (DBM::isResult($structures)) {
			foreach ($structures AS $field) {
				// Replace the default size values so that we don't have to define them
				$search = ['tinyint(1)', 'tinyint(4)', 'smallint(5) unsigned', 'smallint(6)', 'mediumint(9)', 'bigint(20)', 'int(11)'];
				$replace = ['boolean', 'tinyint', 'smallint unsigned', 'smallint', 'mediumint', 'bigint', 'int'];
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
		if (DBM::isResult($full_columns)) {
			foreach ($full_columns AS $column) {
				$fielddata[$column["Field"]]["Collation"] = $column["Collation"];
				$fielddata[$column["Field"]]["comment"] = $column["Comment"];
			}
		}

		return ["fields" => $fielddata, "indexes" => $indexdata, "table_status" => $table_status];
	}

	public static function printStructure() {
		$database = self::definition();

		echo "-- ------------------------------------------\n";
		echo "-- ".FRIENDICA_PLATFORM." ".FRIENDICA_VERSION." (".FRIENDICA_CODENAME,")\n";
		echo "-- DB_UPDATE_VERSION ".DB_UPDATE_VERSION."\n";
		echo "-- ------------------------------------------\n\n\n";
		foreach ($database AS $name => $structure) {
			echo "--\n";
			echo "-- TABLE $name\n";
			echo "--\n";
			self::createTable($name, $structure['fields'], true, false, $structure["indexes"]);

			echo "\n";
		}
	}

	/**
	 * @brief Print out database error messages
	 *
	 * @param string $message Message to be added to the error message
	 *
	 * @return string Error message
	 */
	private static function printUpdateError($message) {
		echo sprintf(t("\nError %d occurred during database update:\n%s\n"),
			dba::errorNo(), dba::errorMessage());

		return t('Errors encountered performing database changes: ').$message.EOL;
	}

	/**
	 * Updates DB structure and returns eventual errors messages
	 *
	 * @param bool  $verbose
	 * @param bool  $action     Whether to actually apply the update
	 * @param array $tables     An array of the database tables
	 * @param array $definition An array of the definition tables
	 * @return string Empty string if the update is successful, error messages otherwise
	 */
	public static function update($verbose, $action, array $tables = null, array $definition = null) {
		if ($action) {
			Config::set('system', 'maintenance', 1);
			Config::set('system', 'maintenance_reason', sprintf(t(': Database update'), DBM::date().' '.date('e')));
		}

		$errors = '';

		logger('updating structure', LOGGER_DEBUG);

		// Get the current structure
		$database = [];

		if (is_null($tables)) {
			$tables = q("SHOW TABLES");
		}

		if (DBM::isResult($tables)) {
			foreach ($tables AS $table) {
				$table = current($table);

				logger(sprintf('updating structure for table %s ...', $table), LOGGER_DEBUG);
				$database[$table] = self::tableStructure($table);
			}
		}

		// Get the definition
		if (is_null($definition)) {
			$definition = self::definition();
		}

		// MySQL >= 5.7.4 doesn't support the IGNORE keyword in ALTER TABLE statements
		if ((version_compare(dba::server_info(), '5.7.4') >= 0) &&
			!(strpos(dba::server_info(), 'MariaDB') !== false)) {
			$ignore = '';
		} else {
			$ignore = ' IGNORE';
		}

		// Compare it
		foreach ($definition AS $name => $structure) {
			$is_new_table = False;
			$group_by = "";
			$sql3 = "";
			if (!isset($database[$name])) {
				$r = self::createTable($name, $structure["fields"], $verbose, $action, $structure['indexes']);
				if (!DBM::isResult($r)) {
					$errors .= self::printUpdateError($name);
				}
				$is_new_table = True;
			} else {
				$is_unique = false;
				$temp_name = $name;

				foreach ($structure["indexes"] AS $indexname => $fieldnames) {
					if (isset($database[$name]["indexes"][$indexname])) {
						$current_index_definition = implode(",",$database[$name]["indexes"][$indexname]);
					} else {
						$current_index_definition = "__NOT_SET__";
					}
					$new_index_definition = implode(",",$fieldnames);
					if ($current_index_definition != $new_index_definition) {
						if ($fieldnames[0] == "UNIQUE") {
							$is_unique = true;
							if ($ignore == "") {
								$temp_name = "temp-".$name;
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
					$current_index_definition = implode(",",$fieldnames);
					if (isset($structure["indexes"][$indexname])) {
						$new_index_definition = implode(",",$structure["indexes"][$indexname]);
					} else {
						$new_index_definition = "__NOT_SET__";
					}
					if ($current_index_definition != $new_index_definition && substr($indexname, 0, 6) != 'local_') {
						$sql2=self::dropIndex($indexname);
						if ($sql3 == "") {
							$sql3 = "ALTER".$ignore." TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
						}
					}
				}
				// Compare the field structure field by field
				foreach ($structure["fields"] AS $fieldname => $parameters) {
					if (!isset($database[$name]["fields"][$fieldname])) {
						$sql2=self::addTableField($fieldname, $parameters);
						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
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

						$current_field_definition = implode(",", $field_definition);
						$new_field_definition = implode(",", $parameters);
						if ($current_field_definition != $new_field_definition) {
							$sql2 = self::modifyTableField($fieldname, $parameters);
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
							} else {
								$sql3 .= ", ".$sql2;
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
						$current_index_definition = implode(",",$database[$name]["indexes"][$indexname]);
					} else {
						$current_index_definition = "__NOT_SET__";
					}
					$new_index_definition = implode(",",$fieldnames);
					if ($current_index_definition != $new_index_definition) {
						$sql2 = self::createIndex($indexname, $fieldnames);

						// Fetch the "group by" fields for unique indexes
						if ($fieldnames[0] == "UNIQUE") {
							$group_by = self::groupBy($indexname, $fieldnames);
						}
						if ($sql2 != "") {
							if ($sql3 == "") {
								$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
							} else {
								$sql3 .= ", ".$sql2;
							}
						}
					}
				}

				if (isset($database[$name]["table_status"]["Comment"])) {
					if ($database[$name]["table_status"]["Comment"] != $structure['comment']) {
						$sql2 = "COMMENT = '".dbesc($structure['comment'])."'";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
						}
					}
				}

				if (isset($database[$name]["table_status"]["Collation"])) {
					if ($database[$name]["table_status"]["Collation"] != 'utf8mb4_general_ci') {
						$sql2 = "DEFAULT COLLATE utf8mb4_general_ci";

						if ($sql3 == "") {
							$sql3 = "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
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
					$field_definition = $database[$name]["fields"][$fieldname];

					// Define the default collation if not given
					if (!isset($parameters['Collation']) && !is_null($field_definition['Collation'])) {
						$parameters['Collation'] = 'utf8mb4_general_ci';
					} else {
						$parameters['Collation'] = null;
					}

					if ($field_definition['Collation'] != $parameters['Collation']) {
						$sql2 = self::modifyTableField($fieldname, $parameters);
						if (($sql3 == "") || (substr($sql3, -2, 2) == "; ")) {
							$sql3 .= "ALTER" . $ignore . " TABLE `".$temp_name."` ".$sql2;
						} else {
							$sql3 .= ", ".$sql2;
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
							echo "DROP TABLE IF EXISTS `".$temp_name."`;\n";
							echo "CREATE TABLE `".$temp_name."` LIKE `".$name."`;\n";
						}
					}

					echo $sql3."\n";

					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							echo "SET session old_alter_table=0;\n";
						} else {
							echo "INSERT INTO `".$temp_name."` SELECT ".dba::any_value_fallback($field_list)." FROM `".$name."`".$group_by.";\n";
							echo "DROP TABLE `".$name."`;\n";
							echo "RENAME TABLE `".$temp_name."` TO `".$name."`;\n";
						}
					}
				}

				if ($action) {
					Config::set('system', 'maintenance_reason', sprintf(t('%s: updating %s table.'), DBM::date().' '.date('e'), $name));

					// Ensure index conversion to unique removes duplicates
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							dba::e("SET session old_alter_table=1;");
						} else {
							dba::e("DROP TABLE IF EXISTS `".$temp_name."`;");
							if (!DBM::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}

							$r = dba::e("CREATE TABLE `".$temp_name."` LIKE `".$name."`;");
							if (!DBM::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
						}
					}

					$r = dba::e($sql3);
					if (!DBM::isResult($r)) {
						$errors .= self::printUpdateError($sql3);
					}
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							dba::e("SET session old_alter_table=0;");
						} else {
							$r = dba::e("INSERT INTO `".$temp_name."` SELECT ".$field_list." FROM `".$name."`".$group_by.";");
							if (!DBM::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = dba::e("DROP TABLE `".$name."`;");
							if (!DBM::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = dba::e("RENAME TABLE `".$temp_name."` TO `".$name."`;");
							if (!DBM::isResult($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
						}
					}
				}
			}
		}

		if ($action) {
			Config::set('system', 'maintenance', 0);
			Config::set('system', 'maintenance_reason', '');
		}

		if ($errors) {
			Config::set('system', 'dbupdate', DB_UPDATE_FAILED);
		} else {
			Config::set('system', 'dbupdate', DB_UPDATE_SUCCESSFUL);
		}

		return $errors;
	}

	private static function FieldCommand($parameters, $create = true) {
		$fieldstruct = $parameters["type"];

		if (!is_null($parameters["Collation"])) {
			$fieldstruct .= " COLLATE ".$parameters["Collation"];
		}

		if ($parameters["not null"]) {
			$fieldstruct .= " NOT NULL";
		}

		if (isset($parameters["default"])) {
			if (strpos(strtolower($parameters["type"]),"int")!==false) {
				$fieldstruct .= " DEFAULT ".$parameters["default"];
			} else {
				$fieldstruct .= " DEFAULT '".$parameters["default"]."'";
			}
		}
		if ($parameters["extra"] != "") {
			$fieldstruct .= " ".$parameters["extra"];
		}

		if (!is_null($parameters["comment"])) {
			$fieldstruct .= " COMMENT '".dbesc($parameters["comment"])."'";
		}

		/*if (($parameters["primary"] != "") && $create)
			$fieldstruct .= " PRIMARY KEY";*/

		return($fieldstruct);
	}

	private static function createTable($name, $fields, $verbose, $action, $indexes=null) {
		$r = true;

		$sql_rows = [];
		$primary_keys = [];
		foreach ($fields AS $fieldname => $field) {
			$sql_rows[] = "`".dbesc($fieldname)."` ".self::FieldCommand($field);
			if (x($field,'primary') && $field['primary']!='') {
				$primary_keys[] = $fieldname;
			}
		}

		if (!is_null($indexes)) {
			foreach ($indexes AS $indexname => $fieldnames) {
				$sql_index = self::createIndex($indexname, $fieldnames, "");
				if (!is_null($sql_index)) {
					$sql_rows[] = $sql_index;
				}
			}
		}

		$sql = implode(",\n\t", $sql_rows);

		$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (\n\t", dbesc($name)).$sql."\n) DEFAULT COLLATE utf8mb4_general_ci";
		if ($verbose) {
			echo $sql.";\n";
		}

		if ($action) {
			$r = dba::e($sql);
		}

		return $r;
	}

	private static function addTableField($fieldname, $parameters) {
		$sql = sprintf("ADD `%s` %s", dbesc($fieldname), self::FieldCommand($parameters));
		return($sql);
	}

	private static function modifyTableField($fieldname, $parameters) {
		$sql = sprintf("MODIFY `%s` %s", dbesc($fieldname), self::FieldCommand($parameters, false));
		return($sql);
	}

	private static function dropIndex($indexname) {
		$sql = sprintf("DROP INDEX `%s`", dbesc($indexname));
		return($sql);
	}

	private static function createIndex($indexname, $fieldnames, $method = "ADD") {
		$method = strtoupper(trim($method));
		if ($method!="" && $method!="ADD") {
			throw new \Exception("Invalid parameter 'method' in self::createIndex(): '$method'");
		}

		if ($fieldnames[0] == "UNIQUE") {
			array_shift($fieldnames);
			$method .= ' UNIQUE';
		}

		$names = "";
		foreach ($fieldnames AS $fieldname) {
			if ($names != "") {
				$names .= ",";
			}

			if (preg_match('|(.+)\((\d+)\)|', $fieldname, $matches)) {
				$names .= "`".dbesc($matches[1])."`(".intval($matches[2]).")";
			} else {
				$names .= "`".dbesc($fieldname)."`";
			}
		}

		if ($indexname == "PRIMARY") {
			return sprintf("%s PRIMARY KEY(%s)", $method, $names);
		}


		$sql = sprintf("%s INDEX `%s` (%s)", $method, dbesc($indexname), $names);
		return($sql);
	}

	private static function groupBy($indexname, $fieldnames) {
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
				$names .= "`".dbesc($matches[1])."`";
			} else {
				$names .= "`".dbesc($fieldname)."`";
			}
		}

		$sql = sprintf(" GROUP BY %s", $names);
		return $sql;
	}

	public static function definition() {
		$database = [];

		$database["addon"] = [
				"comment" => "registered addons",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"name" => ["type" => "varchar(190)", "not null" => "1", "default" => "", "comment" => ""],
						"version" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"installed" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"timestamp" => ["type" => "bigint", "not null" => "1", "default" => "0", "comment" => ""],
						"plugin_admin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"name" => ["UNIQUE", "name"],
						]
				];
		$database["attach"] = [
				"comment" => "file attachments",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"hash" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""],
						"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"filetype" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""],
						"filesize" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"data" => ["type" => "longblob", "not null" => "1", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"allow_cid" => ["type" => "mediumtext", "comment" => ""],
						"allow_gid" => ["type" => "mediumtext", "comment" => ""],
						"deny_cid" => ["type" => "mediumtext", "comment" => ""],
						"deny_gid" => ["type" => "mediumtext", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["auth_codes"] = [
				"comment" => "OAuth usage",
				"fields" => [
						"id" => ["type" => "varchar(40)", "not null" => "1", "primary" => "1", "comment" => ""],
						"client_id" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "relation" => ["clients" => "client_id"], "comment" => ""],
						"redirect_uri" => ["type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""],
						"expires" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"scope" => ["type" => "varchar(250)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["cache"] = [
				"comment" => "Used to store different data that doesn't to be stored for a long time",
				"fields" => [
						"k" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""],
						"v" => ["type" => "mediumtext", "comment" => ""],
						"expire_mode" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"updated" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["k"],
						"expire_mode_updated" => ["expire_mode", "updated"],
						]
				];
		$database["challenge"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"challenge" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"dfrn-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"expire" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"type" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"last_update" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["clients"] = [
				"comment" => "OAuth usage",
				"fields" => [
						"client_id" => ["type" => "varchar(20)", "not null" => "1", "primary" => "1", "comment" => ""],
						"pw" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "comment" => ""],
						"redirect_uri" => ["type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""],
						"name" => ["type" => "text", "comment" => ""],
						"icon" => ["type" => "text", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["client_id"],
						]
				];
		$database["config"] = [
				"comment" => "main configuration storage",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"cat" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"k" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"v" => ["type" => "mediumtext", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"cat_k" => ["UNIQUE", "cat", "k"],
						]
				];
		$database["contact"] = [
				"comment" => "contact table",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"remote_self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"rel" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"duplex" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"about" => ["type" => "text", "comment" => ""],
						"keywords" => ["type" => "text", "comment" => ""],
						"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"xmpp" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"attag" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"avatar" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "text", "comment" => ""],
						"thumb" => ["type" => "text", "comment" => ""],
						"micro" => ["type" => "text", "comment" => ""],
						"site-pubkey" => ["type" => "text", "comment" => ""],
						"issued-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"dfrn-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pubkey" => ["type" => "text", "comment" => ""],
						"prvkey" => ["type" => "text", "comment" => ""],
						"batch" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"request" => ["type" => "text", "comment" => ""],
						"notify" => ["type" => "text", "comment" => ""],
						"poll" => ["type" => "text", "comment" => ""],
						"confirm" => ["type" => "text", "comment" => ""],
						"poco" => ["type" => "text", "comment" => ""],
						"aes_allow" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"ret-aes" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"usehub" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"subhub" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"hub-verify" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"last-update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"success_update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"failure_update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"name-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"uri-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"avatar-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"term-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"last-item" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"priority" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"blocked" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"readonly" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"writable" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"forum" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"prv" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"archive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pending" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"rating" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"reason" => ["type" => "text", "comment" => ""],
						"closeness" => ["type" => "tinyint", "not null" => "1", "default" => "99", "comment" => ""],
						"info" => ["type" => "mediumtext", "comment" => ""],
						"profile-id" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"bdyear" => ["type" => "varchar(4)", "not null" => "1", "default" => "", "comment" => ""],
						"bd" => ["type" => "date", "not null" => "1", "default" => "0001-01-01", "comment" => ""],
						"notify_new_posts" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"fetch_further_information" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"ffi_keyword_blacklist" => ["type" => "text", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_name" => ["uid", "name(190)"],
						"self_uid" => ["self", "uid"],
						"alias_uid" => ["alias(32)", "uid"],
						"pending_uid" => ["pending", "uid"],
						"blocked_uid" => ["blocked", "uid"],
						"uid_rel_network_poll" => ["uid", "rel", "network(4)", "poll(64)", "archive"],
						"uid_network_batch" => ["uid", "network(4)", "batch(64)"],
						"addr_uid" => ["addr(32)", "uid"],
						"nurl_uid" => ["nurl(32)", "uid"],
						"nick_uid" => ["nick(32)", "uid"],
						"dfrn-id" => ["dfrn-id(64)"],
						"issued-id" => ["issued-id(64)"],
						]
				];
		$database["conv"] = [
				"comment" => "private messages",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"recips" => ["type" => "text", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"creator" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"updated" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"subject" => ["type" => "text", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid" => ["uid"],
						]
				];
		$database["conversation"] = [
				"comment" => "Raw data and structure information for messages",
				"fields" => [
						"item-uri" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""],
						"reply-to-uri" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"conversation-uri" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"conversation-href" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"protocol" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"source" => ["type" => "mediumtext", "comment" => ""],
						"received" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["item-uri"],
						"conversation-uri" => ["conversation-uri"],
						"received" => ["received"],
						]
				];
		$database["event"] = [
				"comment" => "Events",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"cid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"start" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"finish" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"summary" => ["type" => "text", "comment" => ""],
						"desc" => ["type" => "text", "comment" => ""],
						"location" => ["type" => "text", "comment" => ""],
						"type" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nofinish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"adjust" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"allow_cid" => ["type" => "mediumtext", "comment" => ""],
						"allow_gid" => ["type" => "mediumtext", "comment" => ""],
						"deny_cid" => ["type" => "mediumtext", "comment" => ""],
						"deny_gid" => ["type" => "mediumtext", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_start" => ["uid", "start"],
						]
				];
		$database["fcontact"] = [
				"comment" => "Diaspora compatible contacts - used in the Diaspora implementation",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"request" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"batch" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"notify" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"poll" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"confirm" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"priority" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pubkey" => ["type" => "text", "comment" => ""],
						"updated" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"addr" => ["addr(32)"],
						"url" => ["UNIQUE", "url(190)"],
						]
				];
		$database["fsuggest"] = [
				"comment" => "friend suggestion stuff",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"cid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"request" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"note" => ["type" => "text", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["gcign"] = [
				"comment" => "contacts ignored by friend suggestions",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"gcid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid" => ["uid"],
						"gcid" => ["gcid"],
						]
				];
		$database["gcontact"] = [
				"comment" => "global contacts",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"connect" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"updated" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"last_contact" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"last_failure" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"about" => ["type" => "text", "comment" => ""],
						"keywords" => ["type" => "text", "comment" => ""],
						"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"birthday" => ["type" => "varchar(32)", "not null" => "1", "default" => "0001-01-01", "comment" => ""],
						"community" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "-1", "comment" => ""],
						"hide" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"nsfw" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"notify" => ["type" => "text", "comment" => ""],
						"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"generation" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"server_url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"nurl" => ["UNIQUE", "nurl(190)"],
						"name" => ["name(64)"],
						"nick" => ["nick(32)"],
						"addr" => ["addr(64)"],
						"hide_network_updated" => ["hide", "network(4)", "updated"],
						"updated" => ["updated"],
						]
				];
		$database["glink"] = [
				"comment" => "'friends of friends' linkages derived from poco",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"cid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"gcid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
						"zcid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
						"updated" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"cid_uid_gcid_zcid" => ["UNIQUE", "cid","uid","gcid","zcid"],
						"gcid" => ["gcid"],
						]
				];
		$database["group"] = [
				"comment" => "privacy groups, group info",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid" => ["uid"],
						]
				];
		$database["group_member"] = [
				"comment" => "privacy groups, member info",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"gid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["group" => "id"], "comment" => ""],
						"contact-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"contactid" => ["contact-id"],
						"gid_contactid" => ["UNIQUE", "gid", "contact-id"],
						]
				];
		$database["gserver"] = [
				"comment" => "Global servers",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"version" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"site_name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"info" => ["type" => "text", "comment" => ""],
						"register_policy" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"registered-users" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"poco" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"noscrape" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"network" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"platform" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"last_poco_query" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"last_contact" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"last_failure" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"nurl" => ["UNIQUE", "nurl(190)"],
						]
				];
		$database["hook"] = [
				"comment" => "addon hook registry",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"hook" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"file" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"function" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"priority" => ["type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"hook_file_function" => ["UNIQUE", "hook(50)","file(80)","function(60)"],
						]
				];
		$database["intro"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"fid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["fcontact" => "id"], "comment" => ""],
						"contact-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"knowyou" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"duplex" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"note" => ["type" => "text", "comment" => ""],
						"hash" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"datetime" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"blocked" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["item"] = [
				"comment" => "All posts",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "relation" => ["thread" => "iid"]],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"contact-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"gcontact-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
						"type" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"gravity" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"parent" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"parent-uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"extid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"thr-parent" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"commented" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"received" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"changed" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"owner-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"owner-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"owner-link" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"owner-avatar" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"author-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"author-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"author-link" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"author-avatar" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"body" => ["type" => "mediumtext", "comment" => ""],
						"app" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"verb" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"object-type" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"object" => ["type" => "text", "comment" => ""],
						"target-type" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"target" => ["type" => "text", "comment" => ""],
						"postopts" => ["type" => "text", "comment" => ""],
						"plink" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"resource-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"event-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["event" => "id"], "comment" => ""],
						"tag" => ["type" => "mediumtext", "comment" => ""],
						"attach" => ["type" => "mediumtext", "comment" => ""],
						"inform" => ["type" => "mediumtext", "comment" => ""],
						"file" => ["type" => "mediumtext", "comment" => ""],
						"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"coord" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"allow_cid" => ["type" => "mediumtext", "comment" => ""],
						"allow_gid" => ["type" => "mediumtext", "comment" => ""],
						"deny_cid" => ["type" => "mediumtext", "comment" => ""],
						"deny_gid" => ["type" => "mediumtext", "comment" => ""],
						"private" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"moderated" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"spam" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"starred" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"bookmark" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"forum_mode" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"mention" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"rendered-hash" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"rendered-html" => ["type" => "mediumtext", "comment" => ""],
						"global" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"guid" => ["guid(191)"],
						"uri" => ["uri(191)"],
						"parent" => ["parent"],
						"parent-uri" => ["parent-uri(191)"],
						"extid" => ["extid(191)"],
						"uid_id" => ["uid","id"],
						"uid_contactid_id" => ["uid","contact-id","id"],
						"uid_created" => ["uid","created"],
						"uid_unseen_contactid" => ["uid","unseen","contact-id"],
						"uid_network_received" => ["uid","network(4)","received"],
						"uid_network_commented" => ["uid","network(4)","commented"],
						"uid_thrparent" => ["uid","thr-parent(190)"],
						"uid_parenturi" => ["uid","parent-uri(190)"],
						"uid_contactid_created" => ["uid","contact-id","created"],
						"authorid_created" => ["author-id","created"],
						"ownerid" => ["owner-id"],
						"uid_uri" => ["uid", "uri(190)"],
						"resource-id" => ["resource-id(191)"],
						"contactid_allowcid_allowpid_denycid_denygid" => ["contact-id","allow_cid(10)","allow_gid(10)","deny_cid(10)","deny_gid(10)"], //
						"uid_type_changed" => ["uid","type(190)","changed"],
						"contactid_verb" => ["contact-id","verb(190)"],
						"deleted_changed" => ["deleted","changed"],
						"uid_wall_changed" => ["uid","wall","changed"],
						"uid_eventid" => ["uid","event-id"],
						"uid_authorlink" => ["uid","author-link(190)"],
						"uid_ownerlink" => ["uid","owner-link(190)"],
						]
				];
		$database["locks"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"name" => ["type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => ""],
						"locked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pid" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["mail"] = [
				"comment" => "private messages",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"from-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"from-photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"from-url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"contact-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "relation" => ["contact" => "id"], "comment" => ""],
						"convid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["conv" => "id"], "comment" => ""],
						"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"body" => ["type" => "mediumtext", "comment" => ""],
						"seen" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"reply" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"replied" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"unknown" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"parent-uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_seen" => ["uid", "seen"],
						"convid" => ["convid"],
						"uri" => ["uri(64)"],
						"parent-uri" => ["parent-uri(64)"],
						"contactid" => ["contact-id(32)"],
						]
				];
		$database["mailacct"] = [
				"comment" => "Mail account data for fetching mails",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"server" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"port" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"ssltype" => ["type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""],
						"mailbox" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"user" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pass" => ["type" => "text", "comment" => ""],
						"reply_to" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"action" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"movetofolder" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"last_check" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["manage"] = [
				"comment" => "table of accounts that can manage each other",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"mid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_mid" => ["UNIQUE", "uid","mid"],
						]
				];
		$database["notify"] = [
				"comment" => "notifications",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"hash" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""],
						"type" => ["type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"msg" => ["type" => "mediumtext", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"link" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"iid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"parent" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"seen" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"verb" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"otype" => ["type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""],
						"name_cache" => ["type" => "tinytext", "comment" => ""],
						"msg_cache" => ["type" => "mediumtext", "comment" => ""]
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"hash_uid" => ["hash", "uid"],
						"seen_uid_date" => ["seen", "uid", "date"],
						"uid_date" => ["uid", "date"],
						"uid_type_link" => ["uid", "type", "link(190)"],
						]
				];
		$database["notify-threads"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"notify-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["notify" => "id"], "comment" => ""],
						"master-parent-item" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"parent-item" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"receiver-uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["oembed"] = [
				"comment" => "cache for OEmbed queries",
				"fields" => [
						"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""],
						"maxwidth" => ["type" => "mediumint", "not null" => "1", "primary" => "1", "comment" => ""],
						"content" => ["type" => "mediumtext", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["url", "maxwidth"],
						"created" => ["created"],
						]
				];
		$database["parsed_url"] = [
				"comment" => "cache for 'parse_url' queries",
				"fields" => [
						"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => ""],
						"guessing" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => ""],
						"oembed" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => ""],
						"content" => ["type" => "mediumtext", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["url", "guessing", "oembed"],
						"created" => ["created"],
						]
				];
		$database["participation"] = [
				"comment" => "Storage for participation messages from Diaspora",
				"fields" => [
						"iid" => ["type" => "int", "not null" => "1", "primary" => "1", "relation" => ["item" => "id"], "comment" => ""],
						"server" => ["type" => "varchar(60)", "not null" => "1", "primary" => "1", "comment" => ""],
						"cid" => ["type" => "int", "not null" => "1", "relation" => ["contact" => "id"], "comment" => ""],
						"fid" => ["type" => "int", "not null" => "1", "relation" => ["fcontact" => "id"], "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["iid", "server"]
						]
				];
		$database["pconfig"] = [
				"comment" => "personal (per user) configuration storage",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"cat" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"k" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"v" => ["type" => "mediumtext", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_cat_k" => ["UNIQUE", "uid", "cat", "k"],
						]
				];
		$database["photo"] = [
				"comment" => "photo storage",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"contact-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"guid" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""],
						"resource-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"desc" => ["type" => "text", "comment" => ""],
						"album" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"type" => ["type" => "varchar(128)", "not null" => "1", "default" => "image/jpeg"],
						"height" => ["type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""],
						"width" => ["type" => "smallint", "not null" => "1", "default" => "0", "comment" => ""],
						"datasize" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"data" => ["type" => "mediumblob", "not null" => "1", "comment" => ""],
						"scale" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"profile" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"allow_cid" => ["type" => "mediumtext", "comment" => ""],
						"allow_gid" => ["type" => "mediumtext", "comment" => ""],
						"deny_cid" => ["type" => "mediumtext", "comment" => ""],
						"deny_gid" => ["type" => "mediumtext", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"contactid" => ["contact-id"],
						"uid_contactid" => ["uid", "contact-id"],
						"uid_profile" => ["uid", "profile"],
						"uid_album_scale_created" => ["uid", "album(32)", "scale", "created"],
						"uid_album_resource-id_created" => ["uid", "album(32)", "resource-id(64)", "created"],
						"resource-id" => ["resource-id(64)"],
						]
				];
		$database["poll"] = [
				"comment" => "Currently unused table for storing poll results",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"q0" => ["type" => "text", "comment" => ""],
						"q1" => ["type" => "text", "comment" => ""],
						"q2" => ["type" => "text", "comment" => ""],
						"q3" => ["type" => "text", "comment" => ""],
						"q4" => ["type" => "text", "comment" => ""],
						"q5" => ["type" => "text", "comment" => ""],
						"q6" => ["type" => "text", "comment" => ""],
						"q7" => ["type" => "text", "comment" => ""],
						"q8" => ["type" => "text", "comment" => ""],
						"q9" => ["type" => "text", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid" => ["uid"],
						]
				];
		$database["poll_result"] = [
				"comment" => "data for polls - currently unused",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"poll_id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["poll" => "id"]],
						"choice" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"poll_id" => ["poll_id"],
						]
				];
		$database["process"] = [
				"comment" => "Currently running system processes",
				"fields" => [
						"pid" => ["type" => "int", "not null" => "1", "primary" => "1", "comment" => ""],
						"command" => ["type" => "varbinary(32)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["pid"],
						"command" => ["command"],
						]
				];
		$database["profile"] = [
				"comment" => "user profiles data",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"profile-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"is-default" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"hide-friends" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pdesc" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"dob" => ["type" => "varchar(32)", "not null" => "1", "default" => "0001-01-01", "comment" => ""],
						"address" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"locality" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"region" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"postal-code" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"country-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"hometown" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"marital" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"with" => ["type" => "text", "comment" => ""],
						"howlong" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"sexual" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"politic" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"religion" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pub_keywords" => ["type" => "text", "comment" => ""],
						"prv_keywords" => ["type" => "text", "comment" => ""],
						"likes" => ["type" => "text", "comment" => ""],
						"dislikes" => ["type" => "text", "comment" => ""],
						"about" => ["type" => "text", "comment" => ""],
						"summary" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"music" => ["type" => "text", "comment" => ""],
						"book" => ["type" => "text", "comment" => ""],
						"tv" => ["type" => "text", "comment" => ""],
						"film" => ["type" => "text", "comment" => ""],
						"interest" => ["type" => "text", "comment" => ""],
						"romance" => ["type" => "text", "comment" => ""],
						"work" => ["type" => "text", "comment" => ""],
						"education" => ["type" => "text", "comment" => ""],
						"contact" => ["type" => "text", "comment" => ""],
						"homepage" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"xmpp" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"thumb" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"net-publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_is-default" => ["uid", "is-default"],
						]
				];
		$database["profile_check"] = [
				"comment" => "DFRN remote auth use",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"cid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"dfrn_id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"sec" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"expire" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["push_subscriber"] = [
				"comment" => "Used for OStatus: Contains feed subscribers",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"callback_url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"topic" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nickname" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"push" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"last_update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"secret" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["queue"] = [
				"comment" => "Queue for messages that couldn't be delivered",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"cid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"network" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"last" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"content" => ["type" => "mediumtext", "comment" => ""],
						"batch" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"cid" => ["cid"],
						"created" => ["created"],
						"last" => ["last"],
						"network" => ["network"],
						"batch" => ["batch"],
						]
				];
		$database["register"] = [
				"comment" => "registrations requiring admin approval",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"hash" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"password" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"language" => ["type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""],
						"note" => ["type" => "text", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["search"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"term" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid" => ["uid"],
						]
				];
		$database["session"] = [
				"comment" => "web session storage",
				"fields" => [
						"id" => ["type" => "bigint", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"sid" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"data" => ["type" => "text", "comment" => ""],
						"expire" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"sid" => ["sid(64)"],
						"expire" => ["expire"],
						]
				];
		$database["sign"] = [
				"comment" => "Diaspora signatures",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"iid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"signed_text" => ["type" => "mediumtext", "comment" => ""],
						"signature" => ["type" => "text", "comment" => ""],
						"signer" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"iid" => ["UNIQUE", "iid"],
						]
				];
		$database["term"] = [
				"comment" => "item taxonomy (categories, tags, etc.) table",
				"fields" => [
						"tid" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"oid" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"otype" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"term" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"received" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"global" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"aid" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["tid"],
						"oid_otype_type_term" => ["oid","otype","type","term(32)"],
						"uid_otype_type_term_global_created" => ["uid","otype","type","term(32)","global","created"],
						"uid_otype_type_url" => ["uid","otype","type","url(64)"],
						"guid" => ["guid(64)"],
						]
				];
		$database["thread"] = [
				"comment" => "Thread related data",
				"fields" => [
						"iid" => ["type" => "int", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["item" => "id"], "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"contact-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"gcontact-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
						"owner-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"author-id" => ["type" => "int", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"commented" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"received" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"changed" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"private" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"moderated" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"spam" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"starred" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"ignored" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"bookmark" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"forum_mode" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"mention" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["iid"],
						"uid_network_commented" => ["uid","network","commented"],
						"uid_network_created" => ["uid","network","created"],
						"uid_contactid_commented" => ["uid","contact-id","commented"],
						"uid_contactid_created" => ["uid","contact-id","created"],
						"contactid" => ["contact-id"],
						"ownerid" => ["owner-id"],
						"authorid" => ["author-id"],
						"uid_created" => ["uid","created"],
						"uid_commented" => ["uid","commented"],
						"uid_wall_created" => ["uid","wall","created"],
						"private_wall_commented" => ["private","wall","commented"],
						]
				];
		$database["tokens"] = [
				"comment" => "OAuth usage",
				"fields" => [
						"id" => ["type" => "varchar(40)", "not null" => "1", "primary" => "1", "comment" => ""],
						"secret" => ["type" => "text", "comment" => ""],
						"client_id" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "relation" => ["clients" => "client_id"]],
						"expires" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"scope" => ["type" => "varchar(200)", "not null" => "1", "default" => "", "comment" => ""],
						"uid" => ["type" => "mediumint", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["user"] = [
				"comment" => "The local users",
				"fields" => [
						"uid" => ["type" => "mediumint", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"guid" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""],
						"username" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"password" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nickname" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"email" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"openid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"timezone" => ["type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => ""],
						"language" => ["type" => "varchar(32)", "not null" => "1", "default" => "en", "comment" => ""],
						"register_date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"login_date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"default-location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"allow_location" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"theme" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pubkey" => ["type" => "text", "comment" => ""],
						"prvkey" => ["type" => "text", "comment" => ""],
						"spubkey" => ["type" => "text", "comment" => ""],
						"sprvkey" => ["type" => "text", "comment" => ""],
						"verified" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"blocked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"blockwall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"hidewall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"blocktags" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"unkmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"cntunkmail" => ["type" => "int", "not null" => "1", "default" => "10", "comment" => ""],
						"notify-flags" => ["type" => "smallint unsigned", "not null" => "1", "default" => "65535", "comment" => ""],
						"page-flags" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"account-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"prvnets" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pwdreset" => ["type" => "varchar(255)", "comment" => "Password reset request token"],
						"pwdreset_time" => ["type" => "datetime", "comment" => "Timestamp of the last password reset request"],
						"maxreq" => ["type" => "int", "not null" => "1", "default" => "10", "comment" => ""],
						"expire" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"account_removed" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"account_expired" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"account_expires_on" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"expire_notification_sent" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"def_gid" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => ""],
						"allow_cid" => ["type" => "mediumtext", "comment" => ""],
						"allow_gid" => ["type" => "mediumtext", "comment" => ""],
						"deny_cid" => ["type" => "mediumtext", "comment" => ""],
						"deny_gid" => ["type" => "mediumtext", "comment" => ""],
						"openidserver" => ["type" => "text", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["uid"],
						"nickname" => ["nickname(32)"],
						]
				];
		$database["userd"] = [
				"comment" => "Deleted usernames",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"username" => ["type" => "varchar(255)", "not null" => "1", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"username" => ["username(32)"],
						]
				];
		$database["workerqueue"] = [
				"comment" => "Background tasks queue entries",
				"fields" => [
						"id" => ["type" => "int", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Auto incremented worker task id"],
						"parameter" => ["type" => "text", "comment" => "Task command"],
						"priority" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Task priority"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Creation date"],
						"pid" => ["type" => "int", "not null" => "1", "default" => "0", "comment" => "Process id of the worker"],
						"executed" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Execution date"],
						"done" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marked when the task was done, will be deleted later"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"pid" => ["pid"],
						"parameter" => ["parameter(64)"],
						"priority_created" => ["priority", "created"],
						"executed" => ["executed"],
						]
				];

		return $database;
	}
}
