<?php
/**
 * @file mod/starred.php
 */
use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;

function starred_init(App $a) {
	$starred = 0;
	$message_id = null;

	if (!local_user()) {
		killme();
	}
	if ($a->argc > 1) {
		$message_id = intval($a->argv[1]);
	}
	if (!$message_id) {
		killme();
	}

	$item = Item::selectFirstForUser(local_user(), ['starred'], ['uid' => local_user(), 'id' => $message_id]);
	if (!DBA::isResult($item)) {
		killme();
	}

	if (!intval($item['starred'])) {
		$starred = 1;
	}

	Item::update(['starred' => $starred], ['id' => $message_id]);

	// See if we've been passed a return path to redirect to
	$return_path = (x($_REQUEST,'return') ? $_REQUEST['return'] : '');
	if ($return_path) {
		$rand = '_=' . time();
		if (strpos($return_path, '?')) {
			$rand = "&$rand";
		} else {
			$rand = "?$rand";
		}

		goaway(System::baseUrl() . "/" . $return_path . $rand);
	}

	// the json doesn't really matter, it will either be 0 or 1

	echo json_encode($starred);
	killme();
}
