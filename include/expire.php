<?php

use \Friendica\Core\Config;

require_once("boot.php");

function expire_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}

	if(is_null($db)) {
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	require_once('include/session.php');
	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/Contact.php');

	Config::load();

	$a->set_baseurl(get_config('system','url'));


	// physically remove anything that has been deleted for more than two months

	$r = q("delete from item where deleted = 1 and changed < UTC_TIMESTAMP() - INTERVAL 60 DAY");

	// make this optional as it could have a performance impact on large sites

	if(intval(get_config('system','optimize_items')))
		q("optimize table item");

	logger('expire: start');

	$r = q("SELECT `uid`,`username`,`expire` FROM `user` WHERE `expire` != 0");
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			logger('Expire: ' . $rr['username'] . ' interval: ' . $rr['expire'], LOGGER_DEBUG);
			item_expire($rr['uid'],$rr['expire']);
		}
	}

	load_hooks();

	call_hooks('expire');

	return;
}

if (array_search(__file__,get_included_files())===0){
  expire_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
