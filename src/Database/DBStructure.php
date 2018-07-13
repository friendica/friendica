<?php
/**
 * @file src/Database/DBStructure.php
 */
namespace Friendica\Database;

use dba;
use Friendica\Core\Config;
use Friendica\Core\L10n;

require_once 'boot.php';
require_once 'include/dba.php';
require_once 'include/enotify.php';
require_once 'include/text.php';

/**
 * @brief This class contain functions for the database management
 *
 * This class contains functions that doesn't need to know if pdo, mysqli or whatever is used.
 */
class DBStructure
{
	/*
	 * Converts all tables from MyISAM to InnoDB
	 */
	public static function convertToInnoDB() {
		$r = q("SELECT `TABLE_NAME` FROM `information_schema`.`tables` WHERE `engine` = 'MyISAM' AND `table_schema` = '%s'",
			dbesc(dba::database_name()));

		if (!DBM::is_result($r)) {
			echo L10n::t('There are no tables on MyISAM.')."\n";
			return;
		}

		foreach ($r AS $table) {
			$sql = sprintf("ALTER TABLE `%s` engine=InnoDB;", dbesc($table['TABLE_NAME']));
			echo $sql."\n";

			$result = dba::e($sql);
			if (!DBM::is_result($result)) {
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
		if (!DBM::is_result($adminlist)) {
			logger(sprintf('Cannot notify administrators about update_id=%d, error_message=%s', $update_id, $error_message), LOGGER_NORMAL);

			// Don't continue
			return;
		}

		// every admin could had different language
		foreach ($adminlist as $admin) {
			$lang = (($admin['language'])?$admin['language']:'en');
			L10n::pushLang($lang);

			$preamble = deindent(L10n::t("
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can't do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid."));
			$body = L10n::t("The error message is\n[pre]%s[/pre]");
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

		if (DBM::is_result($table_status)) {
			$table_status = $table_status[0];
		} else {
			$table_status = [];
		}

		$fielddata = [];
		$indexdata = [];

		if (DBM::is_result($indexes)) {
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
		if (DBM::is_result($structures)) {
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
		if (DBM::is_result($full_columns)) {
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
			self::createTable($name, $structure, true, false);

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
		echo L10n::t("\nError %d occurred during database update:\n%s\n",
			dba::errorNo(), dba::errorMessage());

		return L10n::t('Errors encountered performing database changes: ').$message.EOL;
	}

	/**
	 * Updates DB structure and returns eventual errors messages
	 *
	 * @param bool  $verbose
	 * @param bool  $action     Whether to actually apply the update
	 * @param bool  $install    Is this the initial update during the installation?
	 * @param array $tables     An array of the database tables
	 * @param array $definition An array of the definition tables
	 * @return string Empty string if the update is successful, error messages otherwise
	 */
	public static function update($verbose, $action, $install = false, array $tables = null, array $definition = null) {
		if ($action && !$install) {
			Config::set('system', 'maintenance', 1);
			Config::set('system', 'maintenance_reason', L10n::t('%s: Database update', DBM::date().' '.date('e')));
		}

		$errors = '';

		logger('updating structure', LOGGER_DEBUG);

		// Get the current structure
		$database = [];

		if (is_null($tables)) {
			$tables = q("SHOW TABLES");
		}

		if (DBM::is_result($tables)) {
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
			$is_new_table = false;
			$group_by = "";
			$sql3 = "";
			$is_unique = false;
			$temp_name = $name;
			if (!isset($database[$name])) {
				$r = self::createTable($name, $structure, $verbose, $action);
				if (!DBM::is_result($r)) {
					$errors .= self::printUpdateError($name);
				}
				$is_new_table = true;
			} else {
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

						$current_field_definition = dba::clean_query(implode(",", $field_definition));
						$new_field_definition = dba::clean_query(implode(",", $parameters));
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

				if (isset($database[$name]["table_status"]["Engine"]) && isset($structure['engine'])) {
					if ($database[$name]["table_status"]["Engine"] != $structure['engine']) {
						$sql2 = "ENGINE = '".dbesc($structure['engine'])."'";

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
					if (!$install) {
						Config::set('system', 'maintenance_reason', L10n::t('%s: updating %s table.', DBM::date().' '.date('e'), $name));
					}

					// Ensure index conversion to unique removes duplicates
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							dba::e("SET session old_alter_table=1;");
						} else {
							$r = dba::e("DROP TABLE IF EXISTS `".$temp_name."`;");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}

							$r = dba::e("CREATE TABLE `".$temp_name."` LIKE `".$name."`;");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
						}
					}

					$r = dba::e($sql3);
					if (!DBM::is_result($r)) {
						$errors .= self::printUpdateError($sql3);
					}
					if ($is_unique && ($temp_name != $name)) {
						if ($ignore != "") {
							dba::e("SET session old_alter_table=0;");
						} else {
							$r = dba::e("INSERT INTO `".$temp_name."` SELECT ".$field_list." FROM `".$name."`".$group_by.";");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = dba::e("DROP TABLE `".$name."`;");
							if (!DBM::is_result($r)) {
								$errors .= self::printUpdateError($sql3);
								return $errors;
							}
							$r = dba::e("RENAME TABLE `".$temp_name."` TO `".$name."`;");
							if (!DBM::is_result($r)) {
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
				Config::set('system', 'dbupdate', DB_UPDATE_FAILED);
			} else {
				Config::set('system', 'dbupdate', DB_UPDATE_SUCCESSFUL);
			}
		}

		return $errors;
	}

	private static function FieldCommand($parameters, $create = true) {
		$fieldstruct = $parameters["type"];

		if (isset($parameters["Collation"])) {
			$fieldstruct .= " COLLATE ".$parameters["Collation"];
		}

		if (isset($parameters["not null"])) {
			$fieldstruct .= " NOT NULL";
		}

		if (isset($parameters["default"])) {
			if (strpos(strtolower($parameters["type"]),"int")!==false) {
				$fieldstruct .= " DEFAULT ".$parameters["default"];
			} else {
				$fieldstruct .= " DEFAULT '".$parameters["default"]."'";
			}
		}
		if (isset($parameters["extra"])) {
			$fieldstruct .= " ".$parameters["extra"];
		}

		if (isset($parameters["comment"])) {
			$fieldstruct .= " COMMENT '".dbesc($parameters["comment"])."'";
		}

		/*if (($parameters["primary"] != "") && $create)
			$fieldstruct .= " PRIMARY KEY";*/

		return($fieldstruct);
	}

	private static function createTable($name, $structure, $verbose, $action) {
		$r = true;

		$engine = "";
		$comment = "";
		$sql_rows = [];
		$primary_keys = [];
		foreach ($structure["fields"] AS $fieldname => $field) {
			$sql_rows[] = "`".dbesc($fieldname)."` ".self::FieldCommand($field);
			if (x($field,'primary') && $field['primary']!='') {
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
			$comment = " COMMENT='" . dbesc($structure["comment"]) . "'";
		}

		$sql = implode(",\n\t", $sql_rows);

		$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (\n\t", dbesc($name)).$sql.
				"\n)" . $engine . " DEFAULT COLLATE utf8mb4_general_ci" . $comment;
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"name" => ["type" => "varchar(50)", "not null" => "1", "default" => "", "comment" => "addon base (file)name"],
						"version" => ["type" => "varchar(50)", "not null" => "1", "default" => "", "comment" => "currently unused"],
						"installed" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "currently always 1"],
						"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "currently unused"],
						"timestamp" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "file timestamp to check for reloads"],
						"plugin_admin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 = has admin config, 0 = has no admin config"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"name" => ["UNIQUE", "name"],
						]
				];
		$database["attach"] = [
				"comment" => "file attachments",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "generated index"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"hash" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "hash"],
						"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "filename of original"],
						"filetype" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "mimetype"],
						"filesize" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "size in bytes"],
						"data" => ["type" => "longblob", "not null" => "1", "comment" => "file data"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "creation time"],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "last edit time"],
						"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>"],
						"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
						"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
						"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
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
				"comment" => "Stores temporary data",
				"fields" => [
						"k" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "cache key"],
						"v" => ["type" => "mediumtext", "comment" => "cached serialized value"],
						"expires" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "datetime of cache expiration"],
						"updated" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "datetime of cache insertion"],
						],
				"indexes" => [
						"PRIMARY" => ["k"],
						"k_expires" => ["k", "expires"],
						]
				];
		$database["challenge"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"challenge" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"dfrn-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
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
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["client_id"],
						]
				];
		$database["config"] = [
				"comment" => "main configuration storage",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"cat" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => ""],
						"k" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 if the contact is the user him/her self"],
						"remote_self" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"rel" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "The kind of the relation between the user and the contact"],
						"duplex" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Network protocol of the contact"],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name that this contact is known by"],
						"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Nick- and user name of the contact"],
						"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"about" => ["type" => "text", "comment" => ""],
						"keywords" => ["type" => "text", "comment" => "public keywords (interests) of the contact"],
						"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"xmpp" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"attag" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"avatar" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "default" => "", "comment" => "Link to the profile photo of the contact"],
						"thumb" => ["type" => "varchar(255)", "default" => "", "comment" => "Link to the profile photo (thumb size)"],
						"micro" => ["type" => "varchar(255)", "default" => "", "comment" => "Link to the profile photo (micro size)"],
						"site-pubkey" => ["type" => "text", "comment" => ""],
						"issued-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"dfrn-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pubkey" => ["type" => "text", "comment" => "RSA public key 4096 bit"],
						"prvkey" => ["type" => "text", "comment" => "RSA private key 4096 bit"],
						"batch" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"request" => ["type" => "varchar(255)", "comment" => ""],
						"notify" => ["type" => "varchar(255)", "comment" => ""],
						"poll" => ["type" => "varchar(255)", "comment" => ""],
						"confirm" => ["type" => "varchar(255)", "comment" => ""],
						"poco" => ["type" => "varchar(255)", "comment" => ""],
						"aes_allow" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"ret-aes" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"usehub" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"subhub" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"hub-verify" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"last-update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of the last try to update the contact info"],
						"success_update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of the last successful contact update"],
						"failure_update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of the last failed update"],
						"name-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"uri-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"avatar-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"term-date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"last-item" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "date of the last post"],
						"priority" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"blocked" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"readonly" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "posts of the contact are readonly"],
						"writable" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"forum" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "contact is a forum"],
						"prv" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "contact is a private group"],
						"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"archive" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pending" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"rating" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"reason" => ["type" => "text", "comment" => ""],
						"closeness" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "99", "comment" => ""],
						"info" => ["type" => "mediumtext", "comment" => ""],
						"profile-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"bdyear" => ["type" => "varchar(4)", "not null" => "1", "default" => "", "comment" => ""],
						"bd" => ["type" => "date", "not null" => "1", "default" => "0001-01-01", "comment" => ""],
						"notify_new_posts" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"fetch_further_information" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"ffi_keyword_blacklist" => ["type" => "text", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_name" => ["uid", "name(190)"],
						"self_uid" => ["self", "uid"],
						"alias_uid" => ["alias(32)", "uid"],
						"pending_uid" => ["pending", "uid"],
						"blocked_uid" => ["blocked", "uid"],
						"uid_rel_network_poll" => ["uid", "rel", "network", "poll(64)", "archive"],
						"uid_network_batch" => ["uid", "network", "batch(64)"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this conversation"],
						"recips" => ["type" => "text", "comment" => "sender_handle;recipient_handle"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"creator" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "handle of creator"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "creation timestamp"],
						"updated" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "edited timestamp"],
						"subject" => ["type" => "text", "comment" => "subject of initial message"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid" => ["uid"],
						]
				];
		$database["conversation"] = [
				"comment" => "Raw data and structure information for messages",
				"fields" => [
						"item-uri" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "URI of the item"],
						"reply-to-uri" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "URI to which this item is a reply"],
						"conversation-uri" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "GNU Social conversation URI"],
						"conversation-href" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => "GNU Social conversation link"],
						"protocol" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "The protocol of the item"],
						"source" => ["type" => "mediumtext", "comment" => "Original source"],
						"received" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Receiving date"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact_id (ID of the contact in contact table)"],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "creation time"],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "last edit time"],
						"start" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "event start time"],
						"finish" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "event end time"],
						"summary" => ["type" => "text", "comment" => "short description or title of the event"],
						"desc" => ["type" => "text", "comment" => "event description"],
						"location" => ["type" => "text", "comment" => "event location"],
						"type" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "comment" => "event or birthday"],
						"nofinish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if event does have no end this is 1"],
						"adjust" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "adjust to timezone of the recipient (0 or 1)"],
						"ignore" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "0 or 1"],
						"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
						"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
						"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
						"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_start" => ["uid", "start"],
						]
				];
		$database["fcontact"] = [
				"comment" => "Diaspora compatible contacts - used in the Diaspora implementation",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "unique id"],
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
						"priority" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Local User id"],
						"gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => "gcontact.id of ignored contact"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name that this contact is known by"],
						"nick" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Nick- and user name of the contact"],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Link to the contacts profile page"],
						"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Link to the profile photo"],
						"connect" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"updated" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"last_contact" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"last_failure" => ["type" => "datetime", "default" => NULL_DATE, "comment" => ""],
						"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"about" => ["type" => "text", "comment" => ""],
						"keywords" => ["type" => "text", "comment" => "puplic keywords (interests)"],
						"gender" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"birthday" => ["type" => "varchar(32)", "not null" => "1", "default" => "0001-01-01", "comment" => ""],
						"community" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 if contact is forum account"],
						"contact-type" => ["type" => "tinyint", "not null" => "1", "default" => "-1", "comment" => ""],
						"hide" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 = should be hidden from search"],
						"nsfw" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 = contact posts nsfw content"],
						"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "social network protocol"],
						"addr" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"notify" => ["type" => "varchar(255)", "comment" => ""],
						"alias" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"generation" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"server_url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "baseurl of the contacts server"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"nurl" => ["UNIQUE", "nurl(190)"],
						"name" => ["name(64)"],
						"nick" => ["nick(32)"],
						"addr" => ["addr(64)"],
						"hide_network_updated" => ["hide", "network", "updated"],
						"updated" => ["updated"],
						]
				];
		$database["glink"] = [
				"comment" => "'friends of friends' linkages derived from poco",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"gcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
						"zcid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gcontact" => "id"], "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 indicates the member list is not private"],
						"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 indicates the group has been deleted"],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "human readable name of group"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid" => ["uid"],
						]
				];
		$database["group_member"] = [
				"comment" => "privacy groups, member info",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"gid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["group" => "id"], "comment" => "groups.id of the associated group"],
						"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id of the member assigned to the associated group"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nurl" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"version" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"site_name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"info" => ["type" => "text", "comment" => ""],
						"register_policy" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => ""],
						"registered-users" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Number of registered users"],
						"poco" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"noscrape" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
						"platform" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"relay-subscribe" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Has the server subscribed to the relay system"],
						"relay-scope" => ["type" => "varchar(10)", "not null" => "1", "default" => "", "comment" => "The scope of messages that the server wants to get"],
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
		$database["gserver-tag"] = [
				"comment" => "Tags that the server has subscribed",
				"fields" => [
						"gserver-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["gserver" => "id"], "primary" => "1", "comment" => "The id of the gserver"],
						"tag" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "primary" => "1", "comment" => "Tag that the server has subscribed"],
						],
				"indexes" => [
						"PRIMARY" => ["gserver-id", "tag"],
						"tag" => ["tag"],
						]
				];
		$database["hook"] = [
				"comment" => "addon hook registry",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"hook" => ["type" => "varbinary(100)", "not null" => "1", "default" => "", "comment" => "name of hook"],
						"file" => ["type" => "varbinary(200)", "not null" => "1", "default" => "", "comment" => "relative filename of hook handler"],
						"function" => ["type" => "varbinary(200)", "not null" => "1", "default" => "", "comment" => "function name of hook handler"],
						"priority" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => "not yet implemented - can be used to sort conflicts in hook handling by calling handlers in priority order"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"hook_file_function" => ["UNIQUE", "hook", "file", "function"],
						]
				];
		$database["intro"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"fid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["fcontact" => "id"], "comment" => ""],
						"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
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
				"comment" => "Structure for all posts",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "relation" => ["thread" => "iid"]],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this item"],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"uri-hash" => ["type" => "char(80)", "not null" => "1", "default" => "", "comment" => "RIPEMD-256 hash from uri"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner id which owns this copy of the item"],
						"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id"],
						"type" => ["type" => "varchar(20)", "not null" => "1", "default" => "", "comment" => ""],
						"wall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "This item was posted to the wall of uid"],
						"gravity" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"parent" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => "item.id of the parent to this item if it is a reply of some form; otherwise this must be set to the id of this item"],
						"parent-uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "uri of the parent to this item"],
						"extid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"thr-parent" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "If the parent of this item is not the top-level item in the conversation, the uri of the immediate parent; otherwise set to parent-uri"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Creation timestamp."],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of last edit (default is created)"],
						"commented" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of last comment/reply to this item"],
						"received" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "datetime"],
						"changed" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date that something in the conversation changed, indicating clients should fetch the conversation again"],
						"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Link to the contact table with uid=0 of the owner of this item"],
						"owner-name" => ["type" => "varchar(255)", "comment" => "Name of the owner of this item"],
						"owner-link" => ["type" => "varchar(255)", "comment" => "Link to the profile page of the owner of this item"],
						"owner-avatar" => ["type" => "varchar(255)", "comment" => "Link to the avatar picture of the owner of this item"],
						"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Link to the contact table with uid=0 of the author of this item"],
						"author-name" => ["type" => "varchar(255)", "comment" => "Name of the author of this item"],
						"author-link" => ["type" => "varchar(255)", "comment" => "Link to the profile page of the author of this item"],
						"author-avatar" => ["type" => "varchar(255)", "comment" => "Link to the avatar picture of the author of this item"],
						"icid" => ["type" => "int unsigned", "relation" => ["item-content" => "id"], "comment" => "Id of the item-content table entry that contains the whole item content"],
						"iaid" => ["type" => "int unsigned", "relation" => ["item-activity" => "id"], "comment" => "Id of the item-activity table entry that contains the activity data"],
						"title" => ["type" => "varchar(255)", "comment" => "item title"],
						"content-warning" => ["type" => "varchar(255)", "comment" => ""],
						"body" => ["type" => "mediumtext", "comment" => "item body content"],
						"app" => ["type" => "varchar(255)", "comment" => "application which generated this item"],
						"verb" => ["type" => "varchar(100)", "comment" => "ActivityStreams verb"],
						"object-type" => ["type" => "varchar(100)", "comment" => "ActivityStreams object type"],
						"object" => ["type" => "text", "comment" => "JSON encoded object structure unless it is an implied object (normal post)"],
						"target-type" => ["type" => "varchar(100)", "comment" => "ActivityStreams target type if applicable (URI)"],
						"target" => ["type" => "text", "comment" => "JSON encoded target structure if used"],
						"postopts" => ["type" => "text", "comment" => "External post connectors add their network name to this comma-separated string to identify that they should be delivered to these networks during delivery"],
						"plink" => ["type" => "varchar(255)", "comment" => "permalink or URL to a displayable copy of the message at its source"],
						"resource-id" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Used to link other tables to items, it identifies the linked resource (e.g. photo) and if set must also set resource_type"],
						"event-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["event" => "id"], "comment" => "Used to link to the event.id"],
						"tag" => ["type" => "mediumtext", "comment" => ""],
						"attach" => ["type" => "mediumtext", "comment" => "JSON structure representing attachments to this item"],
						"inform" => ["type" => "mediumtext", "comment" => ""],
						"file" => ["type" => "mediumtext", "comment" => ""],
						"location" => ["type" => "varchar(255)", "comment" => "text location where this item originated"],
						"coord" => ["type" => "varchar(255)", "comment" => "longitude/latitude pair representing location where this item originated"],
						"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
						"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
						"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
						"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
						"private" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "distribution is restricted"],
						"pubmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"moderated" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"visible" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"starred" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item has been favourited"],
						"bookmark" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item has been bookmarked"],
						"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => "item has not been seen"],
						"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item has been deleted"],
						"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "item originated at this site"],
						"forum_mode" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"mention" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "The owner of this item was mentioned in it"],
						"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Network from where the item comes from"],
						"rendered-hash" => ["type" => "varchar(32)", "comment" => ""],
						"rendered-html" => ["type" => "mediumtext", "comment" => "item.body converted to html"],
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
						"uid_commented" => ["uid","commented"],
						"uid_unseen_contactid" => ["uid","unseen","contact-id"],
						"uid_network_received" => ["uid","network","received"],
						"uid_network_commented" => ["uid","network","commented"],
						"uid_thrparent" => ["uid","thr-parent(190)"],
						"uid_parenturi" => ["uid","parent-uri(190)"],
						"uid_contactid_created" => ["uid","contact-id","created"],
						"authorid_created" => ["author-id","created"],
						"ownerid" => ["owner-id"],
						"uid_uri" => ["uid", "uri(190)"],
						"resource-id" => ["resource-id"],
						"deleted_changed" => ["deleted","changed"],
						"uid_wall_changed" => ["uid","wall","changed"],
						"uid_eventid" => ["uid","event-id"],
						"icid" => ["icid"],
						"iaid" => ["iaid"],
						]
				];
		$database["item-activity"] = [
				"comment" => "Activities for items",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "relation" => ["thread" => "iid"]],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"uri-hash" => ["type" => "char(80)", "not null" => "1", "default" => "", "comment" => "RIPEMD-256 hash from uri"],
						"activity" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uri-hash" => ["UNIQUE", "uri-hash"],
						"uri" => ["uri(191)"],
						]
				];
		$database["item-content"] = [
				"comment" => "Content for all posts",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "relation" => ["thread" => "iid"]],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"uri-plink-hash" => ["type" => "char(80)", "not null" => "1", "default" => "", "comment" => "RIPEMD-256 hash from uri"],
						"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "item title"],
						"content-warning" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"body" => ["type" => "mediumtext", "comment" => "item body content"],
						"location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "text location where this item originated"],
						"coord" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "longitude/latitude pair representing location where this item originated"],
						"language" => ["type" => "text", "comment" => "Language information about this post"],
						"app" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "application which generated this item"],
						"rendered-hash" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => ""],
						"rendered-html" => ["type" => "mediumtext", "comment" => "item.body converted to html"],
						"object-type" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams object type"],
						"object" => ["type" => "text", "comment" => "JSON encoded object structure unless it is an implied object (normal post)"],
						"target-type" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams target type if applicable (URI)"],
						"target" => ["type" => "text", "comment" => "JSON encoded target structure if used"],
						"plink" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "permalink or URL to a displayable copy of the message at its source"],
						"verb" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => "ActivityStreams verb"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uri-plink-hash" => ["UNIQUE", "uri-plink-hash"],
						"uri" => ["uri(191)"],
						]
				];
		$database["locks"] = [
				"comment" => "",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"name" => ["type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => ""],
						"locked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Process ID"],
						"expires" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "datetime of cache expiration"],
				],
				"indexes" => [
						"PRIMARY" => ["id"],
						"name_expires" => ["name", "expires"]
						]
				];
		$database["mail"] = [
				"comment" => "private messages",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this private message"],
						"from-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "name of the sender"],
						"from-photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "contact photo link of the sender"],
						"from-url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "profile linke of the sender"],
						"contact-id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "relation" => ["contact" => "id"], "comment" => "contact.id"],
						"convid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["conv" => "id"], "comment" => "conv.id"],
						"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"body" => ["type" => "mediumtext", "comment" => ""],
						"seen" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if message visited it is 1"],
						"reply" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"replied" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"unknown" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if sender not in the contact table this is 1"],
						"uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"parent-uri" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "creation time of the private message"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"server" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"port" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"ssltype" => ["type" => "varchar(16)", "not null" => "1", "default" => "", "comment" => ""],
						"mailbox" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"user" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pass" => ["type" => "text", "comment" => ""],
						"reply_to" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"action" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"mid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_mid" => ["UNIQUE", "uid","mid"],
						]
				];
		$database["notify"] = [
				"comment" => "notifications",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"hash" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => ""],
						"type" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"photo" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"msg" => ["type" => "mediumtext", "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"link" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"iid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => "item.id"],
						"parent" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"seen" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"verb" => ["type" => "varchar(100)", "not null" => "1", "default" => "", "comment" => ""],
						"otype" => ["type" => "varchar(10)", "not null" => "1", "default" => "", "comment" => ""],
						"name_cache" => ["type" => "tinytext", "comment" => "Cached bbcode parsing of name"],
						"msg_cache" => ["type" => "mediumtext", "comment" => "Cached bbcode parsing of msg"]
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"notify-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["notify" => "id"], "comment" => ""],
						"master-parent-item" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"parent-item" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"receiver-uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["oembed"] = [
				"comment" => "cache for OEmbed queries",
				"fields" => [
						"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "page url"],
						"maxwidth" => ["type" => "mediumint unsigned", "not null" => "1", "primary" => "1", "comment" => "Maximum width passed to Oembed"],
						"content" => ["type" => "mediumtext", "comment" => "OEmbed data of the page"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "datetime of creation"],
						],
				"indexes" => [
						"PRIMARY" => ["url", "maxwidth"],
						"created" => ["created"],
						]
				];
		$database["openwebauth-token"] = [
				"comment" => "Store OpenWebAuth token to verify contacts",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"type" => ["type" => "varchar(32)", "not null" => "1", "default" => "", "comment" => "Verify type"],
						"token" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "A generated token"],
						"meta" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "datetime of creation"],
					],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["parsed_url"] = [
				"comment" => "cache for 'parse_url' queries",
				"fields" => [
						"url" => ["type" => "varbinary(255)", "not null" => "1", "primary" => "1", "comment" => "page url"],
						"guessing" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => "is the 'guessing' mode active?"],
						"oembed" => ["type" => "boolean", "not null" => "1", "default" => "0", "primary" => "1", "comment" => "is the data the result of oembed?"],
						"content" => ["type" => "mediumtext", "comment" => "page data"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "datetime of creation"],
						],
				"indexes" => [
						"PRIMARY" => ["url", "guessing", "oembed"],
						"created" => ["created"],
						]
				];
		$database["participation"] = [
				"comment" => "Storage for participation messages from Diaspora",
				"fields" => [
						"iid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "relation" => ["item" => "id"], "comment" => ""],
						"server" => ["type" => "varchar(60)", "not null" => "1", "primary" => "1", "comment" => ""],
						"cid" => ["type" => "int unsigned", "not null" => "1", "relation" => ["contact" => "id"], "comment" => ""],
						"fid" => ["type" => "int unsigned", "not null" => "1", "relation" => ["fcontact" => "id"], "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["iid", "server"]
						]
				];
		$database["pconfig"] = [
				"comment" => "personal (per user) configuration storage",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"cat" => ["type" => "varbinary(50)", "not null" => "1", "default" => "", "comment" => ""],
						"k" => ["type" => "varbinary(100)", "not null" => "1", "default" => "", "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id"],
						"guid" => ["type" => "char(16)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this photo"],
						"resource-id" => ["type" => "char(32)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "creation date"],
						"edited" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "last edited date"],
						"title" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"desc" => ["type" => "text", "comment" => ""],
						"album" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "The name of the album to which the photo belongs"],
						"filename" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"type" => ["type" => "varchar(30)", "not null" => "1", "default" => "image/jpeg"],
						"height" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"width" => ["type" => "smallint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"datasize" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"data" => ["type" => "mediumblob", "not null" => "1", "comment" => ""],
						"scale" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"profile" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"allow_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed contact.id '<19><78>'"],
						"allow_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of allowed groups"],
						"deny_cid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied contact.id"],
						"deny_gid" => ["type" => "mediumtext", "comment" => "Access Control - list of denied groups"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"contactid" => ["contact-id"],
						"uid_contactid" => ["uid", "contact-id"],
						"uid_profile" => ["uid", "profile"],
						"uid_album_scale_created" => ["uid", "album(32)", "scale", "created"],
						"uid_album_resource-id_created" => ["uid", "album(32)", "resource-id", "created"],
						"resource-id" => ["resource-id"],
						]
				];
		$database["poll"] = [
				"comment" => "Currently unused table for storing poll results",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"poll_id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["poll" => "id"]],
						"choice" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"poll_id" => ["poll_id"],
						]
				];
		$database["process"] = [
				"comment" => "Currently running system processes",
				"fields" => [
						"pid" => ["type" => "int unsigned", "not null" => "1", "primary" => "1", "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "Owner User id"],
						"profile-name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name of the profile"],
						"is-default" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Mark this profile as default profile"],
						"hide-friends" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Hide friend list from viewers of this profile"],
						"name" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"pdesc" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Title or description"],
						"dob" => ["type" => "varchar(32)", "not null" => "1", "default" => "0000-00-00", "comment" => "Day of birth"],
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
						"publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "publish default profile in local directory"],
						"net-publish" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "publish profile in global directory"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"uid_is-default" => ["uid", "is-default"],
						]
				];
		$database["profile_check"] = [
				"comment" => "DFRN remote auth use",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "contact.id"],
						"dfrn_id" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"sec" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["push_subscriber"] = [
				"comment" => "Used for OStatus: Contains feed subscribers",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"callback_url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"topic" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"nickname" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"push" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Retrial counter"],
						"last_update" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of last successful trial"],
						"next_try" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Next retrial date"],
						"renewed" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of last subscription renewal"],
						"secret" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"next_try" => ["next_try"],
						]
				];
		$database["queue"] = [
				"comment" => "Queue for messages that couldn't be delivered",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"cid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Message receiver"],
						"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => "Receiver's network"],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Unique GUID of the message"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date, when the message was created"],
						"last" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Date of last trial"],
						"next" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Next retrial date"],
						"retrial" => ["type" => "tinyint", "not null" => "1", "default" => "0", "comment" => "Retrial counter"],
						"content" => ["type" => "mediumtext", "comment" => ""],
						"batch" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"last" => ["last"],
						"next" => ["next"],
						]
				];
		$database["register"] = [
				"comment" => "registrations requiring admin approval",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"hash" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
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
						"id" => ["type" => "bigint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"sid" => ["type" => "varbinary(255)", "not null" => "1", "default" => "", "comment" => ""],
						"data" => ["type" => "text", "comment" => ""],
						"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"iid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => "item.id"],
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
						"tid" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => ""],
						"oid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["item" => "id"], "comment" => ""],
						"otype" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"term" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"url" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"guid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"received" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => ""],
						"global" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
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
						"iid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["item" => "id"], "comment" => "sequential ID"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						"contact-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => ""],
						"owner-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Item owner"],
						"author-id" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "relation" => ["contact" => "id"], "comment" => "Item author"],
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
						"starred" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"ignored" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"bookmark" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"unseen" => ["type" => "boolean", "not null" => "1", "default" => "1", "comment" => ""],
						"deleted" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"origin" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"forum_mode" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"mention" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"network" => ["type" => "char(4)", "not null" => "1", "default" => "", "comment" => ""],
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
						"private_wall_origin_commented" => ["private","wall","origin","commented"],
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
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "User id"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						]
				];
		$database["user"] = [
				"comment" => "The local users",
				"fields" => [
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"parent-uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "relation" => ["user" => "uid"], "comment" => "The parent user that has full control about this user"],
						"guid" => ["type" => "varchar(64)", "not null" => "1", "default" => "", "comment" => "A unique identifier for this user"],
						"username" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Name that this user is known by"],
						"password" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "encrypted password"],
						"legacy_password" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Is the password hash double-hashed?"],
						"nickname" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "nick- and user name"],
						"email" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "the users email address"],
						"openid" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => ""],
						"timezone" => ["type" => "varchar(128)", "not null" => "1", "default" => "", "comment" => "PHP-legal timezone"],
						"language" => ["type" => "varchar(32)", "not null" => "1", "default" => "en", "comment" => "default language"],
						"register_date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "timestamp of registration"],
						"login_date" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "timestamp of last login"],
						"default-location" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "Default for item.location"],
						"allow_location" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 allows to display the location"],
						"theme" => ["type" => "varchar(255)", "not null" => "1", "default" => "", "comment" => "user theme preference"],
						"pubkey" => ["type" => "text", "comment" => "RSA public key 4096 bit"],
						"prvkey" => ["type" => "text", "comment" => "RSA private key 4096 bit"],
						"spubkey" => ["type" => "text", "comment" => ""],
						"sprvkey" => ["type" => "text", "comment" => ""],
						"verified" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "user is verified through email"],
						"blocked" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "1 for user is blocked"],
						"blockwall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Prohibit contacts to post to the profile page of the user"],
						"hidewall" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Hide profile details from unkown viewers"],
						"blocktags" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Prohibit contacts to tag the post of this user"],
						"unkmail" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Permit unknown people to send private mails to this user"],
						"cntunkmail" => ["type" => "int unsigned", "not null" => "1", "default" => "10", "comment" => ""],
						"notify-flags" => ["type" => "smallint unsigned", "not null" => "1", "default" => "65535", "comment" => "email notification options"],
						"page-flags" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "page/profile type"],
						"account-type" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"prvnets" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"pwdreset" => ["type" => "varchar(255)", "comment" => "Password reset request token"],
						"pwdreset_time" => ["type" => "datetime", "comment" => "Timestamp of the last password reset request"],
						"maxreq" => ["type" => "int unsigned", "not null" => "1", "default" => "10", "comment" => ""],
						"expire" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"account_removed" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "if 1 the account is removed"],
						"account_expired" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => ""],
						"account_expires_on" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "timestamp when account expires and will be deleted"],
						"expire_notification_sent" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "timestamp of last warning of account expiration"],
						"def_gid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => ""],
						"allow_cid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
						"allow_gid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
						"deny_cid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
						"deny_gid" => ["type" => "mediumtext", "comment" => "default permission for this user"],
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
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "sequential ID"],
						"username" => ["type" => "varchar(255)", "not null" => "1", "comment" => ""],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"username" => ["username(32)"],
						]
				];
		$database["user-item"] = [
				"comment" => "User specific item data",
				"fields" => [
						"iid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["item" => "id"], "comment" => "Item id"],
						"uid" => ["type" => "mediumint unsigned", "not null" => "1", "default" => "0", "primary" => "1", "relation" => ["user" => "uid"], "comment" => "User id"],
						"hidden" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marker to hide an item from the user"],
						],
				"indexes" => [
						"PRIMARY" => ["uid", "iid"],
						]
				];
		$database["worker-ipc"] = [
				"comment" => "Inter process communication between the frontend and the worker",
				"fields" => [
						"key" => ["type" => "int", "not null" => "1", "primary" => "1", "comment" => ""],
						"jobs" => ["type" => "boolean", "comment" => "Flag for outstanding jobs"],
						],
				"indexes" => [
						"PRIMARY" => ["key"],
						],
				"engine" => "MEMORY",
				];

		$database["workerqueue"] = [
				"comment" => "Background tasks queue entries",
				"fields" => [
						"id" => ["type" => "int unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1", "comment" => "Auto incremented worker task id"],
						"parameter" => ["type" => "mediumblob", "comment" => "Task command"],
						"priority" => ["type" => "tinyint unsigned", "not null" => "1", "default" => "0", "comment" => "Task priority"],
						"created" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Creation date"],
						"pid" => ["type" => "int unsigned", "not null" => "1", "default" => "0", "comment" => "Process id of the worker"],
						"executed" => ["type" => "datetime", "not null" => "1", "default" => NULL_DATE, "comment" => "Execution date"],
						"done" => ["type" => "boolean", "not null" => "1", "default" => "0", "comment" => "Marked 1 when the task was done - will be deleted later"],
						],
				"indexes" => [
						"PRIMARY" => ["id"],
						"pid" => ["pid"],
						"parameter" => ["parameter(64)"],
						"priority_created" => ["priority", "created"],
						"done_executed" => ["done", "executed"],
						]
				];

		\Friendica\Core\Addon::callHooks('dbstructure_definition', $database);

		return $database;
	}
}
