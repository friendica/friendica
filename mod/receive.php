<?php

/**
 * Diaspora endpoint
 */


require_once('include/salmon.php');
require_once('include/crypto.php');
require_once('include/diaspora.php');


function receive_post(&$a) {


	$enabled = intval(get_config('system','diaspora_enabled'));
	if(! $enabled) {
		logger('mod-diaspora: disabled');
		http_status_exit(500);
	}

	$public = false;

	if(($a->argc == 2) && ($a->argv[1] === 'public')) {
		$public = true;
	}
	else {

		if($a->argc != 3 || $a->argv[1] !== 'users')
			http_status_exit(500);

		$guid = $a->argv[2];

		$r = q("SELECT * FROM `user` WHERE `guid` = '%s' AND `account_expired` = 0 AND `account_removed` = 0 LIMIT 1",
			dbesc($guid)
		);
		if(! count($r))
			http_status_exit(500);

		$importer = $r[0];
	}

	// It is an application/x-www-form-urlencoded

	logger('mod-diaspora: receiving post', LOGGER_DEBUG);

	$xml = urldecode($_POST['xml']);

	logger('mod-diaspora: new salmon ' . $xml, LOGGER_DATA);

	if(! $xml)
		http_status_exit(500);

	logger('mod-diaspora: message is okay', LOGGER_DEBUG);

	$msg = diaspora_decode($importer,$xml);

	logger('mod-diaspora: decoded', LOGGER_DEBUG);

	logger('mod-diaspora: decoded msg: ' . print_r($msg,true), LOGGER_DATA);

	if(! is_array($msg))
		http_status_exit(500);

	logger('mod-diaspora: dispatching', LOGGER_DEBUG);

	$ret = 0;
	if($public)
		diaspora_dispatch_public($msg);
	else
		$ret = diaspora_dispatch($importer,$msg);

	http_status_exit(($ret) ? $ret : 200);
	// NOTREACHED
}

