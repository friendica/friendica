<?php

/**
 * Zot endpoint
 */


require_once('include/salmon.php');
require_once('include/crypto.php');
// not yet ready for prime time
//require_once('include/zot.php');

function post_post(App $a) {

	$bulk_delivery = false;

	if ($a->argc == 1) {
		$bulk_delivery = true;
	}
	else {
		$nickname = $a->argv[2];
		$r = q("SELECT * FROM `user` WHERE `nickname` = '%s'
				AND `account_expired` = 0 AND `account_removed` = 0 LIMIT 1",
			dbesc($nickname)
		);
		if (! dbm::is_result($r)) {
			http_status_exit(500);
		}

		$importer = $r[0];
	}

	$xml = file_get_contents('php://input');

	logger('mod-post: new zot: ' . $xml, LOGGER_DATA);

	if(! $xml)
		http_status_exit(500);

	$msg = zot_decode($importer,$xml);

	logger('mod-post: decoded msg: ' . print_r($msg,true), LOGGER_DATA);

	if(! is_array($msg))
		http_status_exit(500);

	$ret = 0;
	$ret = zot_incoming($bulk_delivery, $importer,$msg);
	http_status_exit(($ret) ? $ret : 200);
	// NOTREACHED
}

