<?php

use \Friendica\Core\Config;

function dbupdate_run(&$argv, &$argc) {
	global $a;

	// We are deleting the latest dbupdate entry.
	// This is done to avoid endless loops because the update was interupted.
	Config::delete('database', 'dbupdate_'.DB_UPDATE_VERSION);

	update_db($a);
}
