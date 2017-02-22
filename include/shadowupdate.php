<?php

use \Friendica\Core\Config;

require_once("boot.php");
require_once("include/threads.php");

global $a, $db;

if(is_null($a))
	$a = new App;

if(is_null($db)) {
	@include(".htconfig.php");
	require_once("include/dba.php");
	$db = new dba($db_host, $db_user, $db_pass, $db_data);
	unset($db_host, $db_user, $db_pass, $db_data);
}

Config::load();

update_shadow_copy();
killme();
?>
