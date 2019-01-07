<?php
/**
 * @file mod/receive.php
 * @brief Diaspora endpoint
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Protocol\Diaspora;

/**
 * @param App $a App
 * @return void
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function receive_post(App $a)
{
	$enabled = intval(Config::get('system', 'diaspora_enabled'));
	if (!$enabled) {
		Logger::log('mod-diaspora: disabled');
		System::httpExit(500);
	}

	if (($a->argc == 2) && ($a->argv[1] === 'public')) {
		$public = true;
		$importer = [];
	} else {
		$public = false;

		if ($a->argc != 3 || $a->argv[1] !== 'users') {
			System::httpExit(500);
		}
		$guid = $a->argv[2];

		$importer = DBA::selectFirst('user', [], ['guid' => $guid, 'account_expired' => false, 'account_removed' => false]);
		if (!DBA::isResult($importer)) {
			System::httpExit(500);
		}
	}

	// It is an application/x-www-form-urlencoded

	Logger::log('mod-diaspora: receiving post', Logger::DEBUG);

	if (empty($_POST['xml'])) {
		$postdata = file_get_contents("php://input");
		if ($postdata == '') {
			System::httpExit(500);
		}

		Logger::log('mod-diaspora: message is in the new format', Logger::DEBUG);
		$msg = Diaspora::decodeRaw($importer, $postdata);
	} else {
		$xml = urldecode($_POST['xml']);

		Logger::log('mod-diaspora: decode message in the old format', Logger::DEBUG);
		$msg = Diaspora::decode($importer, $xml);

		if ($public && !$msg) {
			Logger::log('mod-diaspora: decode message in the new format', Logger::DEBUG);
			$msg = Diaspora::decodeRaw($importer, $xml);
		}
	}

	Logger::log('mod-diaspora: decoded', Logger::DEBUG);

	Logger::log('mod-diaspora: decoded msg: ' . print_r($msg, true), Logger::DATA);

	if (!is_array($msg)) {
		System::httpExit(500);
	}

	Logger::log('mod-diaspora: dispatching', Logger::DEBUG);

	$ret = true;
	if ($public) {
		Diaspora::dispatchPublic($msg);
	} else {
		$ret = Diaspora::dispatch($importer, $msg);
	}

	System::httpExit(($ret) ? 200 : 500);
	// NOTREACHED
}
