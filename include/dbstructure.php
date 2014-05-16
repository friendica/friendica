<?php

require_once("boot.php");

function dbstructure_run(&$argv, &$argc) {
	global $a, $db;

	if(is_null($a)){
		$a = new App;
	}

	if(is_null($db)) {
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		        unset($db_host, $db_user, $db_pass, $db_data);
	}

	load_config('config');
	load_config('system');

	update_structure($a);
}

if (array_search(__file__,get_included_files())===0){
	dbstructure_run($argv,$argc);
	killme();
}

function table_structure($table) {
	$structures = q("DESCRIBE `%s`", $table);

	$indexes = q("SHOW INDEX FROM `%s`", $table);

	$fielddata = array();
	$indexdata = array();

	if (is_array($indexes))
		foreach ($indexes AS $index) {
			if ($index["Index_type"] == "FULLTEXT")
				continue;

			$column = $index["Column_name"];
			if ($index["Sub_part"] != "")
				$column .= "(".$index["Sub_part"].")";

			$indexdata[$index["Key_name"]][] = $column;
		}

	if (is_array($structures)) {
		foreach($structures AS $field) {
			$fielddata[$field["Field"]]["type"] = $field["Type"];
			if ($field["Null"] == "NO")
				$fielddata[$field["Field"]]["not null"] = true;

			if ($field["Default"] != "")
				$fielddata[$field["Field"]]["default"] = $field["Default"];

			if ($field["Extra"] != "")
				$fielddata[$field["Field"]]["extra"] = $field["Extra"];

			if ($field["Key"] == "PRI")
				$fielddata[$field["Field"]]["primary"] = true;
		}
	}

	return(array("fields"=>$fielddata, "indexes"=>$indexdata));
}

function print_structure($db) {
	foreach ($db AS $name => $structure) {
		echo "\t".'$db["'.$name."\"] = array(\n";

		echo "\t\t\t".'"fields" => array('."\n";
		foreach ($structure["fields"] AS $fieldname => $parameters) {
			echo "\t\t\t\t\t".'"'.$fieldname.'" => array(';

			$data = "";
			foreach ($parameters AS $name => $value) {
				if ($data != "")
					$data .= ", ";
				$data .= '"'.$name.'" => "'.$value.'"';
			}

			echo $data."),\n";
		}
		echo "\t\t\t\t\t),\n";
		echo "\t\t\t".'"indexes" => array('."\n";
		foreach ($structure["indexes"] AS $indexname => $fieldnames) {
			echo "\t\t\t\t\t".'"'.$indexname.'" => array("'.implode($fieldnames, '","').'"'."),\n";
		}
		echo "\t\t\t\t\t)\n";
		echo "\t\t\t);\n";
	}
}

function update_structure($a) {

	// Get the current structure
	$db = array();

	$tables = q("show tables");

	foreach ($tables AS $table) {
		$table = current($table);

		$db[$table] = table_structure($table);
	}

	// Get the definition
	$definition = db_definition();

	// Compare it
	foreach ($definition AS $name => $structure) {
		if (!isset($db[$name]))
			db_create_table($name, $structure["fields"]);
		else {
			// Compare the field structure field by field
			foreach ($structure["fields"] AS $fieldname => $parameters) {
				if (!isset($db[$name]["fields"][$fieldname]))
					db_add_table_field($name, $fieldname, $parameters);
				else {
					// Compare the field definition
					$current_field_definition = implode($db[$name]["fields"][$fieldname]);
					$new_field_definition = implode($parameters);
					if ($current_field_definition != $new_field_definition)
						db_modify_table_field($name, $fieldname, $parameters);
				}
			}
		}

		// Drop the index if it isn't present in the definition
		if (isset($db[$name]))
			foreach ($db[$name]["indexes"] AS $indexname => $fieldnames)
				if (!isset($structure["indexes"][$indexname]))
					db_drop_index($name, $indexname);

		// Create the index
		foreach ($structure["indexes"] AS $indexname => $fieldnames)
			if (!isset($db[$name]["indexes"][$indexname]))
				db_create_index($name, $indexname, $fieldnames);
	}
}

function db_field_command($parameters, $create = true) {
	$fieldstruct = $parameters["type"];

	if ($parameters["not null"])
		$fieldstruct .= " NOT NULL";

	if ($parameters["default"] != "")
		$fieldstruct .= " DEFAULT '".$parameters["default"]."'";

	if ($parameters["extra"] != "")
		$fieldstruct .= " ".$parameters["extra"];

	if (($parameters["primary"] != "") AND $create)
		$fieldstruct .= " PRIMARY KEY";

	return($fieldstruct);
}

function db_create_table($name, $fields) {
	$sql = "";
	foreach($fields AS $fieldname => $field) {
		if ($sql != "")
			$sql .= ",\n";

		$sql .= "`".dbesc($fieldname)."` ".db_field_command($field);
	}

	$sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (\n", dbesc($name)).$sql."\n) DEFAULT CHARSET=utf8";
	echo $sql.";\n";
	//$ret = q($sql);
}

function db_add_table_field($name, $fieldname, $parameters) {
	$sql = sprintf("ALTER TABLE `%s` ADD `%s` %s", dbesc($name), dbesc($fieldname), db_field_command($parameters));
	echo $sql.";\n";
	//$ret = q($sql);
}

function db_modify_table_field($name, $fieldname, $parameters) {
	$sql = sprintf("ALTER TABLE `%s` MODIFY `%s` %s", dbesc($name), dbesc($fieldname), db_field_command($parameters, false));
	echo $sql.";\n";
	//$ret = q($sql);
}

function db_drop_index($name, $indexname) {
	$sql = sprintf("DROP INDEX `%s` ON `%s`", dbesc($indexname), dbesc($name));
	echo $sql.";\n";
	//$ret = q($sql);
}

function db_create_index($name, $indexname, $fieldnames) {

	if ($indexname == "PRIMARY")
		return;

	$names = "";
	foreach ($fieldnames AS $fieldname) {
		if ($names != "")
			$names .= ",";

		if (preg_match('|(.+)\((\d+)\)|', $fieldname, $matches))
			$names .= "`".dbesc($matches[1])."`(".intval($matches[2]).")";
		else
			$names .= "`".dbesc($fieldname)."`";
	}

	$sql = sprintf("CREATE INDEX `%s` ON `%s`(%s)", dbesc($indexname), dbesc($name), $names);
	echo $sql."\n";
	//$ret = q($sql);
}

function db_definition() {

	$db = array();

	$db["addon"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"version" => array("type" => "char(255)", "not null" => "1"),
					"installed" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"hidden" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"timestamp" => array("type" => "bigint(20)", "not null" => "1", "default" => "0"),
					"plugin_admin" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["attach"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"hash" => array("type" => "char(64)", "not null" => "1"),
					"filename" => array("type" => "char(255)", "not null" => "1"),
					"filetype" => array("type" => "char(64)", "not null" => "1"),
					"filesize" => array("type" => "int(11)", "not null" => "1"),
					"data" => array("type" => "longblob", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"edited" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"allow_cid" => array("type" => "mediumtext", "not null" => "1"),
					"allow_gid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_cid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_gid" => array("type" => "mediumtext", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["auth_codes"] = array(
			"fields" => array(
					"id" => array("type" => "varchar(40)", "not null" => "1", "primary" => "1"),
					"client_id" => array("type" => "varchar(20)", "not null" => "1"),
					"redirect_uri" => array("type" => "varchar(200)", "not null" => "1"),
					"expires" => array("type" => "int(11)", "not null" => "1"),
					"scope" => array("type" => "varchar(250)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["cache"] = array(
			"fields" => array(
					"k" => array("type" => "char(255)", "not null" => "1", "primary" => "1"),
					"v" => array("type" => "text", "not null" => "1"),
					"updated" => array("type" => "datetime", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("k"),
					"updated" => array("updated"),
					)
			);
	$db["challenge"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"challenge" => array("type" => "char(255)", "not null" => "1"),
					"dfrn-id" => array("type" => "char(255)", "not null" => "1"),
					"expire" => array("type" => "int(11)", "not null" => "1"),
					"type" => array("type" => "char(255)", "not null" => "1"),
					"last_update" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["clients"] = array(
			"fields" => array(
					"client_id" => array("type" => "varchar(20)", "not null" => "1", "primary" => "1"),
					"pw" => array("type" => "varchar(20)", "not null" => "1"),
					"redirect_uri" => array("type" => "varchar(200)", "not null" => "1"),
					"name" => array("type" => "text"),
					"icon" => array("type" => "text"),
					"uid" => array("type" => "int(11)", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("client_id"),
					)
			);
	$db["config"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"cat" => array("type" => "char(255)", "not null" => "1"),
					"k" => array("type" => "char(255)", "not null" => "1"),
					"v" => array("type" => "text", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"access" => array("cat","k"),
					)
			);
	$db["contact"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"self" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"remote_self" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"rel" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"duplex" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"network" => array("type" => "char(255)", "not null" => "1"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"nick" => array("type" => "char(255)", "not null" => "1"),
					"attag" => array("type" => "char(255)", "not null" => "1"),
					"photo" => array("type" => "text", "not null" => "1"),
					"thumb" => array("type" => "text", "not null" => "1"),
					"micro" => array("type" => "text", "not null" => "1"),
					"site-pubkey" => array("type" => "text", "not null" => "1"),
					"issued-id" => array("type" => "char(255)", "not null" => "1"),
					"dfrn-id" => array("type" => "char(255)", "not null" => "1"),
					"url" => array("type" => "char(255)", "not null" => "1"),
					"nurl" => array("type" => "char(255)", "not null" => "1"),
					"addr" => array("type" => "char(255)", "not null" => "1"),
					"alias" => array("type" => "char(255)", "not null" => "1"),
					"pubkey" => array("type" => "text", "not null" => "1"),
					"prvkey" => array("type" => "text", "not null" => "1"),
					"batch" => array("type" => "char(255)", "not null" => "1"),
					"request" => array("type" => "text", "not null" => "1"),
					"notify" => array("type" => "text", "not null" => "1"),
					"poll" => array("type" => "text", "not null" => "1"),
					"confirm" => array("type" => "text", "not null" => "1"),
					"poco" => array("type" => "text", "not null" => "1"),
					"aes_allow" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"ret-aes" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"usehub" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"subhub" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"hub-verify" => array("type" => "char(255)", "not null" => "1"),
					"last-update" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"success_update" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"name-date" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"uri-date" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"avatar-date" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"term-date" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"priority" => array("type" => "tinyint(3)", "not null" => "1"),
					"blocked" => array("type" => "tinyint(1)", "not null" => "1", "default" => "1"),
					"readonly" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"writable" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"forum" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"prv" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"hidden" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"archive" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"pending" => array("type" => "tinyint(1)", "not null" => "1", "default" => "1"),
					"rating" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"reason" => array("type" => "text", "not null" => "1"),
					"closeness" => array("type" => "tinyint(2)", "not null" => "1", "default" => "99"),
					"info" => array("type" => "mediumtext", "not null" => "1"),
					"profile-id" => array("type" => "int(11)", "not null" => "1", "default" => "0"),
					"bdyear" => array("type" => "char(4)", "not null" => "1"),
					"bd" => array("type" => "date", "not null" => "1"),
					"notify_new_posts" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"fetch_further_information" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					)
			);
	$db["conv"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"guid" => array("type" => "char(64)", "not null" => "1"),
					"recips" => array("type" => "mediumtext", "not null" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"creator" => array("type" => "char(255)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"updated" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"subject" => array("type" => "mediumtext", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					)
			);
	$db["deliverq"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"cmd" => array("type" => "char(32)", "not null" => "1"),
					"item" => array("type" => "int(11)", "not null" => "1"),
					"contact" => array("type" => "int(11)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["dsprphotoq"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"msg" => array("type" => "mediumtext", "not null" => "1"),
					"attempt" => array("type" => "tinyint(4)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["event"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"cid" => array("type" => "int(11)", "not null" => "1"),
					"uri" => array("type" => "char(255)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1"),
					"edited" => array("type" => "datetime", "not null" => "1"),
					"start" => array("type" => "datetime", "not null" => "1"),
					"finish" => array("type" => "datetime", "not null" => "1"),
					"summary" => array("type" => "text", "not null" => "1"),
					"desc" => array("type" => "text", "not null" => "1"),
					"location" => array("type" => "text", "not null" => "1"),
					"type" => array("type" => "char(255)", "not null" => "1"),
					"nofinish" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"adjust" => array("type" => "tinyint(1)", "not null" => "1", "default" => "1"),
					"ignore" => array("type" => "tinyint(1) unsigned", "not null" => "1", "default" => "0"),
					"allow_cid" => array("type" => "mediumtext", "not null" => "1"),
					"allow_gid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_cid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_gid" => array("type" => "mediumtext", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					)
			);
	$db["fcontact"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"url" => array("type" => "char(255)", "not null" => "1"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"photo" => array("type" => "char(255)", "not null" => "1"),
					"request" => array("type" => "char(255)", "not null" => "1"),
					"nick" => array("type" => "char(255)", "not null" => "1"),
					"addr" => array("type" => "char(255)", "not null" => "1"),
					"batch" => array("type" => "char(255)", "not null" => "1"),
					"notify" => array("type" => "char(255)", "not null" => "1"),
					"poll" => array("type" => "char(255)", "not null" => "1"),
					"confirm" => array("type" => "char(255)", "not null" => "1"),
					"priority" => array("type" => "tinyint(1)", "not null" => "1"),
					"network" => array("type" => "char(32)", "not null" => "1"),
					"alias" => array("type" => "char(255)", "not null" => "1"),
					"pubkey" => array("type" => "text", "not null" => "1"),
					"updated" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"addr" => array("addr"),
					)
			);
	$db["ffinder"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"cid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"fid" => array("type" => "int(10) unsigned", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["fserver"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"server" => array("type" => "char(255)", "not null" => "1"),
					"posturl" => array("type" => "char(255)", "not null" => "1"),
					"key" => array("type" => "text", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"server" => array("server"),
					)
			);
	$db["fsuggest"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"cid" => array("type" => "int(11)", "not null" => "1"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"url" => array("type" => "char(255)", "not null" => "1"),
					"request" => array("type" => "char(255)", "not null" => "1"),
					"photo" => array("type" => "char(255)", "not null" => "1"),
					"note" => array("type" => "text", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["gcign"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"gcid" => array("type" => "int(11)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					"gcid" => array("gcid"),
					)
			);
	$db["gcontact"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"url" => array("type" => "char(255)", "not null" => "1"),
					"nurl" => array("type" => "char(255)", "not null" => "1"),
					"photo" => array("type" => "char(255)", "not null" => "1"),
					"connect" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"nurl" => array("nurl"),
					)
			);
	$db["glink"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"cid" => array("type" => "int(11)", "not null" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"gcid" => array("type" => "int(11)", "not null" => "1"),
					"zcid" => array("type" => "int(11)", "not null" => "1"),
					"updated" => array("type" => "datetime", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"cid_uid_gcid_zcid" => array("cid","uid","gcid","zcid"),
					"gcid" => array("gcid"),
					"zcid" => array("zcid"),
					)
			);
	$db["group"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"visible" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"deleted" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					)
			);
	$db["group_member"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"gid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"contact-id" => array("type" => "int(10) unsigned", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid_gid_contactid" => array("uid","gid","contact-id"),
					)
			);
	$db["guid"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"guid" => array("type" => "char(64)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"guid" => array("guid"),
					)
			);
	$db["hook"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"hook" => array("type" => "char(255)", "not null" => "1"),
					"file" => array("type" => "char(255)", "not null" => "1"),
					"function" => array("type" => "char(255)", "not null" => "1"),
					"priority" => array("type" => "int(11) unsigned", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"hook_file_function" => array("hook","file","function"),
					)
			);
	$db["intro"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"fid" => array("type" => "int(11)", "not null" => "1", "default" => "0"),
					"contact-id" => array("type" => "int(11)", "not null" => "1"),
					"knowyou" => array("type" => "tinyint(1)", "not null" => "1"),
					"duplex" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"note" => array("type" => "text", "not null" => "1"),
					"hash" => array("type" => "char(255)", "not null" => "1"),
					"datetime" => array("type" => "datetime", "not null" => "1"),
					"blocked" => array("type" => "tinyint(1)", "not null" => "1", "default" => "1"),
					"ignore" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["item"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"guid" => array("type" => "char(64)", "not null" => "1"),
					"uri" => array("type" => "char(255)", "not null" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"contact-id" => array("type" => "int(11)", "not null" => "1"),
					"type" => array("type" => "char(255)", "not null" => "1"),
					"wall" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"gravity" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"parent" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"parent-uri" => array("type" => "char(255)", "not null" => "1"),
					"extid" => array("type" => "char(255)", "not null" => "1"),
					"thr-parent" => array("type" => "char(255)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"edited" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"commented" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"received" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"changed" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"owner-name" => array("type" => "char(255)", "not null" => "1"),
					"owner-link" => array("type" => "char(255)", "not null" => "1"),
					"owner-avatar" => array("type" => "char(255)", "not null" => "1"),
					"author-name" => array("type" => "char(255)", "not null" => "1"),
					"author-link" => array("type" => "char(255)", "not null" => "1"),
					"author-avatar" => array("type" => "char(255)", "not null" => "1"),
					"title" => array("type" => "char(255)", "not null" => "1"),
					"body" => array("type" => "mediumtext", "not null" => "1"),
					"app" => array("type" => "char(255)", "not null" => "1"),
					"verb" => array("type" => "char(255)", "not null" => "1"),
					"object-type" => array("type" => "char(255)", "not null" => "1"),
					"object" => array("type" => "text", "not null" => "1"),
					"target-type" => array("type" => "char(255)", "not null" => "1"),
					"target" => array("type" => "text", "not null" => "1"),
					"postopts" => array("type" => "text", "not null" => "1"),
					"plink" => array("type" => "char(255)", "not null" => "1"),
					"resource-id" => array("type" => "char(255)", "not null" => "1"),
					"event-id" => array("type" => "int(11)", "not null" => "1"),
					"tag" => array("type" => "mediumtext", "not null" => "1"),
					"attach" => array("type" => "mediumtext", "not null" => "1"),
					"inform" => array("type" => "mediumtext", "not null" => "1"),
					"file" => array("type" => "mediumtext", "not null" => "1"),
					"location" => array("type" => "char(255)", "not null" => "1"),
					"coord" => array("type" => "char(255)", "not null" => "1"),
					"allow_cid" => array("type" => "mediumtext", "not null" => "1"),
					"allow_gid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_cid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_gid" => array("type" => "mediumtext", "not null" => "1"),
					"private" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"pubmail" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"moderated" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"visible" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"spam" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"starred" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"bookmark" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"unseen" => array("type" => "tinyint(1)", "not null" => "1", "default" => "1"),
					"deleted" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"origin" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"forum_mode" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"last-child" => array("type" => "tinyint(1) unsigned", "not null" => "1", "default" => "1"),
					"mention" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"network" => array("type" => "char(32)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"guid" => array("guid"),
					"uri" => array("uri"),
					"parent" => array("parent"),
					"parent-uri" => array("parent-uri"),
					"extid" => array("extid"),
					"uid_id" => array("uid","id"),
					"uid_created" => array("uid","created"),
					"uid_unseen" => array("uid","unseen"),
					"uid_network_received" => array("uid","network","received"),
					"uid_received" => array("uid","received"),
					"uid_network_commented" => array("uid","network","commented"),
					"uid_commented" => array("uid","commented"),
					"uid_title" => array("uid","title"),
					"uid_thrparent" => array("uid","thr-parent"),
					"uid_parenturi" => array("uid","parent-uri"),
					"uid_contactid_created" => array("uid","contact-id","created"),
					"wall_body" => array("wall","body(6)"),
					"uid_visible_moderated_created" => array("uid","visible","moderated","created"),
					"uid_uri" => array("uid","uri"),
					"uid_wall_created" => array("uid","wall","created"),
					"resource-id" => array("resource-id"),
					"uid_type" => array("uid","type"),
					"uid_starred" => array("uid","starred"),
					"contactid_allowcid_allowpid_denycid_denygid" => array("contact-id","allow_cid(10)","allow_gid(10)","deny_cid(10)","deny_gid(10)"),
					"uid_wall_parent_created" => array("uid","wall","parent","created"),
					"uid_type_changed" => array("uid","type","changed"),
					"contactid_verb" => array("contact-id","verb"),
					"deleted_changed" => array("deleted","changed"),
					"uid_wall_changed" => array("uid","wall","changed"),
					"uid_eventid" => array("uid","event-id"),
					"uid_authorlink" => array("uid","author-link"),
					"uid_ownerlink" => array("uid","owner-link"),
					)
			);
	$db["item_id"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"iid" => array("type" => "int(11)", "not null" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"sid" => array("type" => "char(255)", "not null" => "1"),
					"service" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					"sid" => array("sid"),
					"service" => array("service"),
					"iid" => array("iid"),
					)
			);
	$db["locks"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"name" => array("type" => "char(128)", "not null" => "1"),
					"locked" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["mail"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"guid" => array("type" => "char(64)", "not null" => "1"),
					"from-name" => array("type" => "char(255)", "not null" => "1"),
					"from-photo" => array("type" => "char(255)", "not null" => "1"),
					"from-url" => array("type" => "char(255)", "not null" => "1"),
					"contact-id" => array("type" => "char(255)", "not null" => "1"),
					"convid" => array("type" => "int(11) unsigned", "not null" => "1"),
					"title" => array("type" => "char(255)", "not null" => "1"),
					"body" => array("type" => "mediumtext", "not null" => "1"),
					"seen" => array("type" => "tinyint(1)", "not null" => "1"),
					"reply" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"replied" => array("type" => "tinyint(1)", "not null" => "1"),
					"unknown" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"uri" => array("type" => "char(255)", "not null" => "1"),
					"parent-uri" => array("type" => "char(255)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					"guid" => array("guid"),
					"convid" => array("convid"),
					"reply" => array("reply"),
					"uri" => array("uri"),
					"parent-uri" => array("parent-uri"),
					)
			);
	$db["mailacct"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"server" => array("type" => "char(255)", "not null" => "1"),
					"port" => array("type" => "int(11)", "not null" => "1"),
					"ssltype" => array("type" => "char(16)", "not null" => "1"),
					"mailbox" => array("type" => "char(255)", "not null" => "1"),
					"user" => array("type" => "char(255)", "not null" => "1"),
					"pass" => array("type" => "text", "not null" => "1"),
					"reply_to" => array("type" => "char(255)", "not null" => "1"),
					"action" => array("type" => "int(11)", "not null" => "1"),
					"movetofolder" => array("type" => "char(255)", "not null" => "1"),
					"pubmail" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"last_check" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["manage"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"mid" => array("type" => "int(11)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid_mid" => array("uid","mid"),
					)
			);
	$db["notify"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"hash" => array("type" => "char(64)", "not null" => "1"),
					"type" => array("type" => "int(11)", "not null" => "1"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"url" => array("type" => "char(255)", "not null" => "1"),
					"photo" => array("type" => "char(255)", "not null" => "1"),
					"date" => array("type" => "datetime", "not null" => "1"),
					"msg" => array("type" => "mediumtext", "not null" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"link" => array("type" => "char(255)", "not null" => "1"),
					"parent" => array("type" => "int(11)", "not null" => "1"),
					"seen" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"verb" => array("type" => "char(255)", "not null" => "1"),
					"otype" => array("type" => "char(16)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					)
			);
	$db["notify-threads"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"notify-id" => array("type" => "int(11)", "not null" => "1"),
					"master-parent-item" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"parent-item" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"receiver-uid" => array("type" => "int(11)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"master-parent-item" => array("master-parent-item"),
					"receiver-uid" => array("receiver-uid"),
					)
			);
	$db["pconfig"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1", "default" => "0"),
					"cat" => array("type" => "char(255)", "not null" => "1"),
					"k" => array("type" => "char(255)", "not null" => "1"),
					"v" => array("type" => "mediumtext", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"access" => array("uid","cat","k"),
					)
			);
	$db["photo"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"contact-id" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"guid" => array("type" => "char(64)", "not null" => "1"),
					"resource-id" => array("type" => "char(255)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1"),
					"edited" => array("type" => "datetime", "not null" => "1"),
					"title" => array("type" => "char(255)", "not null" => "1"),
					"desc" => array("type" => "text", "not null" => "1"),
					"album" => array("type" => "char(255)", "not null" => "1"),
					"filename" => array("type" => "char(255)", "not null" => "1"),
					"type" => array("type" => "char(128)", "not null" => "1", "default" => "image/jpeg"),
					"height" => array("type" => "smallint(6)", "not null" => "1"),
					"width" => array("type" => "smallint(6)", "not null" => "1"),
					"datasize" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"data" => array("type" => "mediumblob", "not null" => "1"),
					"scale" => array("type" => "tinyint(3)", "not null" => "1"),
					"profile" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"allow_cid" => array("type" => "mediumtext", "not null" => "1"),
					"allow_gid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_cid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_gid" => array("type" => "mediumtext", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					"resource-id" => array("resource-id"),
					"guid" => array("guid"),
					)
			);
	$db["poll"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"q0" => array("type" => "mediumtext", "not null" => "1"),
					"q1" => array("type" => "mediumtext", "not null" => "1"),
					"q2" => array("type" => "mediumtext", "not null" => "1"),
					"q3" => array("type" => "mediumtext", "not null" => "1"),
					"q4" => array("type" => "mediumtext", "not null" => "1"),
					"q5" => array("type" => "mediumtext", "not null" => "1"),
					"q6" => array("type" => "mediumtext", "not null" => "1"),
					"q7" => array("type" => "mediumtext", "not null" => "1"),
					"q8" => array("type" => "mediumtext", "not null" => "1"),
					"q9" => array("type" => "mediumtext", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					)
			);
	$db["poll_result"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"poll_id" => array("type" => "int(11)", "not null" => "1"),
					"choice" => array("type" => "int(11)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"poll_id" => array("poll_id"),
					"choice" => array("choice"),
					)
			);
	$db["profile"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"profile-name" => array("type" => "char(255)", "not null" => "1"),
					"is-default" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"hide-friends" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"pdesc" => array("type" => "char(255)", "not null" => "1"),
					"dob" => array("type" => "char(32)", "not null" => "1", "default" => "0000-00-00"),
					"address" => array("type" => "char(255)", "not null" => "1"),
					"locality" => array("type" => "char(255)", "not null" => "1"),
					"region" => array("type" => "char(255)", "not null" => "1"),
					"postal-code" => array("type" => "char(32)", "not null" => "1"),
					"country-name" => array("type" => "char(255)", "not null" => "1"),
					"hometown" => array("type" => "char(255)", "not null" => "1"),
					"gender" => array("type" => "char(32)", "not null" => "1"),
					"marital" => array("type" => "char(255)", "not null" => "1"),
					"with" => array("type" => "text", "not null" => "1"),
					"howlong" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"sexual" => array("type" => "char(255)", "not null" => "1"),
					"politic" => array("type" => "char(255)", "not null" => "1"),
					"religion" => array("type" => "char(255)", "not null" => "1"),
					"pub_keywords" => array("type" => "text", "not null" => "1"),
					"prv_keywords" => array("type" => "text", "not null" => "1"),
					"likes" => array("type" => "text", "not null" => "1"),
					"dislikes" => array("type" => "text", "not null" => "1"),
					"about" => array("type" => "text", "not null" => "1"),
					"summary" => array("type" => "char(255)", "not null" => "1"),
					"music" => array("type" => "text", "not null" => "1"),
					"book" => array("type" => "text", "not null" => "1"),
					"tv" => array("type" => "text", "not null" => "1"),
					"film" => array("type" => "text", "not null" => "1"),
					"interest" => array("type" => "text", "not null" => "1"),
					"romance" => array("type" => "text", "not null" => "1"),
					"work" => array("type" => "text", "not null" => "1"),
					"education" => array("type" => "text", "not null" => "1"),
					"contact" => array("type" => "text", "not null" => "1"),
					"homepage" => array("type" => "char(255)", "not null" => "1"),
					"photo" => array("type" => "char(255)", "not null" => "1"),
					"thumb" => array("type" => "char(255)", "not null" => "1"),
					"publish" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"net-publish" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"hometown" => array("hometown"),
					)
			);
	$db["profile_check"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"cid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"dfrn_id" => array("type" => "char(255)", "not null" => "1"),
					"sec" => array("type" => "char(255)", "not null" => "1"),
					"expire" => array("type" => "int(11)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["push_subscriber"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"callback_url" => array("type" => "char(255)", "not null" => "1"),
					"topic" => array("type" => "char(255)", "not null" => "1"),
					"nickname" => array("type" => "char(255)", "not null" => "1"),
					"push" => array("type" => "int(11)", "not null" => "1"),
					"last_update" => array("type" => "datetime", "not null" => "1"),
					"secret" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["queue"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"cid" => array("type" => "int(11)", "not null" => "1"),
					"network" => array("type" => "char(32)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1"),
					"last" => array("type" => "datetime", "not null" => "1"),
					"content" => array("type" => "mediumtext", "not null" => "1"),
					"batch" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"cid" => array("cid"),
					"created" => array("created"),
					"last" => array("last"),
					"network" => array("network"),
					"batch" => array("batch"),
					)
			);
	$db["register"] = array(
			"fields" => array(
					"id" => array("type" => "int(11) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"hash" => array("type" => "char(255)", "not null" => "1"),
					"created" => array("type" => "datetime", "not null" => "1"),
					"uid" => array("type" => "int(11) unsigned", "not null" => "1"),
					"password" => array("type" => "char(255)", "not null" => "1"),
					"language" => array("type" => "char(16)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["search"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"term" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					"term" => array("term"),
					)
			);
	$db["session"] = array(
			"fields" => array(
					"id" => array("type" => "bigint(20) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"sid" => array("type" => "char(255)", "not null" => "1"),
					"data" => array("type" => "text", "not null" => "1"),
					"expire" => array("type" => "int(10) unsigned", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"sid" => array("sid"),
					"expire" => array("expire"),
					)
			);
	$db["sign"] = array(
			"fields" => array(
					"id" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"iid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"retract_iid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"signed_text" => array("type" => "mediumtext", "not null" => "1"),
					"signature" => array("type" => "text", "not null" => "1"),
					"signer" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"iid" => array("iid"),
					"retract_iid" => array("retract_iid"),
					)
			);
	$db["spam"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					"spam" => array("type" => "int(11)", "not null" => "1", "default" => "0"),
					"ham" => array("type" => "int(11)", "not null" => "1", "default" => "0"),
					"term" => array("type" => "char(255)", "not null" => "1"),
					"date" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"uid" => array("uid"),
					"spam" => array("spam"),
					"ham" => array("ham"),
					"term" => array("term"),
					)
			);
	$db["term"] = array(
			"fields" => array(
					"tid" => array("type" => "int(10) unsigned", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"oid" => array("type" => "int(10) unsigned", "not null" => "1"),
					"otype" => array("type" => "tinyint(3) unsigned", "not null" => "1"),
					"type" => array("type" => "tinyint(3) unsigned", "not null" => "1"),
					"term" => array("type" => "char(255)", "not null" => "1"),
					"url" => array("type" => "char(255)", "not null" => "1"),
					"aid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					),
			"indexes" => array(
					"PRIMARY" => array("tid"),
					"oid_otype_type_term" => array("oid","otype","type","term"),
					"uid_term_tid" => array("uid","term","tid"),
					"type_term" => array("type","term"),
					"uid_otype_type_term_tid" => array("uid","otype","type","term","tid"),
					"otype_type_term_tid" => array("otype","type","term","tid"),
					)
			);
	$db["thread"] = array(
			"fields" => array(
					"iid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0", "primary" => "1"),
					"uid" => array("type" => "int(10) unsigned", "not null" => "1", "default" => "0"),
					"contact-id" => array("type" => "int(11) unsigned", "not null" => "1", "default" => "0"),
					"created" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"edited" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"commented" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"received" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"changed" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"wall" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"private" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"pubmail" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"moderated" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"visible" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"spam" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"starred" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"bookmark" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"unseen" => array("type" => "tinyint(1)", "not null" => "1", "default" => "1"),
					"deleted" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"origin" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"forum_mode" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"mention" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"network" => array("type" => "char(32)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("iid"),
					"created" => array("created"),
					"commented" => array("commented"),
					"uid_network_commented" => array("uid","network","commented"),
					"uid_network_created" => array("uid","network","created"),
					"uid_contactid_commented" => array("uid","contact-id","commented"),
					"uid_contactid_created" => array("uid","contact-id","created"),
					"wall_private_received" => array("wall","private","received"),
					"uid_created" => array("uid","created"),
					"uid_commented" => array("uid","commented"),
					)
			);
	$db["tokens"] = array(
			"fields" => array(
					"id" => array("type" => "varchar(40)", "not null" => "1", "primary" => "1"),
					"secret" => array("type" => "text", "not null" => "1"),
					"client_id" => array("type" => "varchar(20)", "not null" => "1"),
					"expires" => array("type" => "int(11)", "not null" => "1"),
					"scope" => array("type" => "varchar(200)", "not null" => "1"),
					"uid" => array("type" => "int(11)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					)
			);
	$db["unique_contacts"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"url" => array("type" => "char(255)", "not null" => "1"),
					"nick" => array("type" => "char(255)", "not null" => "1"),
					"name" => array("type" => "char(255)", "not null" => "1"),
					"avatar" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"url" => array("url"),
					)
			);
	$db["user"] = array(
			"fields" => array(
					"uid" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"guid" => array("type" => "char(64)", "not null" => "1"),
					"username" => array("type" => "char(255)", "not null" => "1"),
					"password" => array("type" => "char(255)", "not null" => "1"),
					"nickname" => array("type" => "char(255)", "not null" => "1"),
					"email" => array("type" => "char(255)", "not null" => "1"),
					"openid" => array("type" => "char(255)", "not null" => "1"),
					"timezone" => array("type" => "char(128)", "not null" => "1"),
					"language" => array("type" => "char(32)", "not null" => "1", "default" => "en"),
					"register_date" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"login_date" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"default-location" => array("type" => "char(255)", "not null" => "1"),
					"allow_location" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"theme" => array("type" => "char(255)", "not null" => "1"),
					"pubkey" => array("type" => "text", "not null" => "1"),
					"prvkey" => array("type" => "text", "not null" => "1"),
					"spubkey" => array("type" => "text", "not null" => "1"),
					"sprvkey" => array("type" => "text", "not null" => "1"),
					"verified" => array("type" => "tinyint(1) unsigned", "not null" => "1", "default" => "0"),
					"blocked" => array("type" => "tinyint(1) unsigned", "not null" => "1", "default" => "0"),
					"blockwall" => array("type" => "tinyint(1) unsigned", "not null" => "1", "default" => "0"),
					"hidewall" => array("type" => "tinyint(1) unsigned", "not null" => "1", "default" => "0"),
					"blocktags" => array("type" => "tinyint(1) unsigned", "not null" => "1", "default" => "0"),
					"unkmail" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"cntunkmail" => array("type" => "int(11)", "not null" => "1", "default" => "10"),
					"notify-flags" => array("type" => "int(11) unsigned", "not null" => "1", "default" => "65535"),
					"page-flags" => array("type" => "int(11) unsigned", "not null" => "1", "default" => "0"),
					"prvnets" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"pwdreset" => array("type" => "char(255)", "not null" => "1"),
					"maxreq" => array("type" => "int(11)", "not null" => "1", "default" => "10"),
					"expire" => array("type" => "int(11) unsigned", "not null" => "1", "default" => "0"),
					"account_removed" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"account_expired" => array("type" => "tinyint(1)", "not null" => "1", "default" => "0"),
					"account_expires_on" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"expire_notification_sent" => array("type" => "datetime", "not null" => "1", "default" => "0000-00-00 00:00:00"),
					"service_class" => array("type" => "char(32)", "not null" => "1"),
					"def_gid" => array("type" => "int(11)", "not null" => "1", "default" => "0"),
					"allow_cid" => array("type" => "mediumtext", "not null" => "1"),
					"allow_gid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_cid" => array("type" => "mediumtext", "not null" => "1"),
					"deny_gid" => array("type" => "mediumtext", "not null" => "1"),
					"openidserver" => array("type" => "text", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("uid"),
					"nickname" => array("nickname"),
					)
			);
	$db["userd"] = array(
			"fields" => array(
					"id" => array("type" => "int(11)", "not null" => "1", "extra" => "auto_increment", "primary" => "1"),
					"username" => array("type" => "char(255)", "not null" => "1"),
					),
			"indexes" => array(
					"PRIMARY" => array("id"),
					"username" => array("username"),
					)
			);

	return($db);
}
