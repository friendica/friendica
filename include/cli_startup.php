<?php /** @file */

use \Friendica\Core\Config;

require_once('boot.php');

// Everything we need to boot standalone 'background' processes

function cli_startup() {

	global $a, $db;

	if (is_null($a)) {
		$a = new App;
	}

	if (is_null($db)) {
		@include(".htconfig.php");
		require_once("dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
  	};

	require_once('include/session.php');

	Config::load();

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

}
