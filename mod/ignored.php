<?php

use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Item;

function ignored_init(App $a)
{
	$ignored = 0;

	if (!local_user()) {
		killme();
	}

	if ($a->argc > 1) {
		$message_id = intval($a->argv[1]);
	}

	if (!$message_id) {
		killme();
	}

	$thread = Item::selectFirstThreadForUser(local_user(), ['uid', 'ignored'], ['iid' => $message_id]);
	if (!DBA::isResult($thread)) {
		killme();
	}

	if (!$thread['ignored']) {
		$ignored = true;
	}

	if ($thread['uid'] != 0) {
		DBA::update('thread', ['ignored' => $ignored], ['iid' => $message_id]);
	} else {
		DBA::update('user-item', ['ignored' => $ignored], ['iid' => $message_id, 'uid' => local_user()], true);
	}

	// See if we've been passed a return path to redirect to
	$return_path = defaults($_REQUEST, 'return', '');
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

	echo json_encode($ignored);
	killme();
}
