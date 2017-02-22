<?php

use \Friendica\Core\Config;

require_once("boot.php");

function dbupdate_run(&$argv, &$argc) {
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

	Config::load();

	// We are deleting the latest dbupdate entry.
	// This is done to avoid endless loops because the update was interupted.
	Config::delete('database','dbupdate_'.DB_UPDATE_VERSION);

	update_db($a);
}

if (array_search(__file__,get_included_files())===0){
  dbupdate_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
