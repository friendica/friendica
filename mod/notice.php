<?php

/* GNU Social -> friendica items permanent-url compatibility */

use Friendica\App;
use Friendica\Core\System;
use Friendica\Database\DBM;

function notice_init(App $a) {

	$id = $a->argv[1];
	$r = q("SELECT `user`.`nickname` FROM `user` LEFT JOIN `item` ON `item`.`uid` = `user`.`uid` WHERE `item`.`id` = %d", intval($id));
	if (DBM::isResult($r)) {
		$nick = $r[0]['nickname'];
		$url = System::baseUrl() . "/display/$nick/$id";
		goaway($url);
	} else {
		$a->error = 404;
		notice(t('Item not found.') . EOL);
	}

	return;
}
