<?php

use \Friendica\Core\Config;

function expire_run(&$argv, &$argc){
	global $a;

	require_once('include/datetime.php');
	require_once('include/items.php');
	require_once('include/Contact.php');

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
