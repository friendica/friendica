<?php
/**
 * @file mod/receive.php
 * @brief Diaspora endpoint
 */

use Friendica\App;
use Friendica\Content\Text;
use Friendica\Core\Config;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Protocol\Diaspora;

/**
 * @param object $a App
 * @return void
 */
function receive_post(App $a)
{
	$enabled = intval(Config::get('system', 'diaspora_enabled'));
	if (!$enabled) {
		App::logger('mod-diaspora: disabled');
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

	App::logger('mod-diaspora: receiving post', LOGGER_DEBUG);

	if (empty($_POST['xml'])) {
		$postdata = file_get_contents("php://input");
		if ($postdata == '') {
			System::httpExit(500);
		}

		App::logger('mod-diaspora: message is in the new format', LOGGER_DEBUG);
		$msg = Diaspora::decodeRaw($importer, $postdata);
	} else {
		$xml = urldecode($_POST['xml']);

		App::logger('mod-diaspora: decode message in the old format', LOGGER_DEBUG);
		$msg = Diaspora::decode($importer, $xml);

		if ($public && !$msg) {
			App::logger('mod-diaspora: decode message in the new format', LOGGER_DEBUG);
			$msg = Diaspora::decodeRaw($importer, $xml);
		}
	}

	App::logger('mod-diaspora: decoded', LOGGER_DEBUG);

	App::logger('mod-diaspora: decoded msg: ' . print_r($msg, true), LOGGER_DATA);

	if (!is_array($msg)) {
		System::httpExit(500);
	}

	App::logger('mod-diaspora: dispatching', LOGGER_DEBUG);

	$ret = true;
	if ($public) {
		Diaspora::dispatchPublic($msg);
	} else {
		$ret = Diaspora::dispatch($importer, $msg);
	}

	System::httpExit(($ret) ? 200 : 500);
	// NOTREACHED
}
