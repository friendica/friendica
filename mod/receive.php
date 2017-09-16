<?php

/**
 * Diaspora endpoint
 */

use Friendica\App;

require_once('include/salmon.php');
require_once('include/crypto.php');
require_once('include/diaspora.php');

function receive_post(App $a) {
	$enabled = intval(get_config('system', 'diaspora_enabled'));
	if (!$enabled) {
		logger('mod-diaspora: disabled');
		http_status_exit(500);
	}

	$public = false;

	if (($a->argc == 2) && ($a->argv[1] === 'public')) {
		$public = true;
	} else {

		if ($a->argc != 3 || $a->argv[1] !== 'users') {
			http_status_exit(500);
		}
		$guid = $a->argv[2];

		$importer = dba::select('user', array(), array('guid' => $guid, 'account_expired' => false, 'account_removed' => false), array('limit' => 1));
		if (!dbm::is_result($importer)) {
			http_status_exit(500);
		}
	}

	// It is an application/x-www-form-urlencoded

	logger('mod-diaspora: receiving post', LOGGER_DEBUG);

	$xml = urldecode($_POST['xml']);

	if (!$xml) {
		$postdata = file_get_contents("php://input");
	        if ($postdata == '') {
			http_status_exit(500);
		}

		logger('mod-diaspora: message is in the new format', LOGGER_DEBUG);
		$msg = Diaspora::decode_raw($importer, $postdata);
	} else {
		logger('mod-diaspora: message is in the old format', LOGGER_DEBUG);
		$msg = Diaspora::decode($importer, $xml);
	}

	logger('mod-diaspora: decoded', LOGGER_DEBUG);

	logger('mod-diaspora: decoded msg: ' . print_r($msg, true), LOGGER_DATA);

	if (!is_array($msg)) {
		http_status_exit(500);
	}

	logger('mod-diaspora: dispatching', LOGGER_DEBUG);

	$ret = true;
	if ($public) {
		Diaspora::dispatch_public($msg);
	} else {
		$ret = Diaspora::dispatch($importer, $msg);
	}

	http_status_exit(($ret) ? 200 : 500);
	// NOTREACHED
}

