<?php
/// @TODO no longer used?
use \Friendica\Core\Config;

function directory_run(&$argv, &$argc){
	$dir = get_config('system', 'directory');

	if (!strlen($dir)) {
		return;
	}

	if ($argc < 2) {
		directory_update_all();
		return;
	}

	$dir .= "/submit";

	$arr = array('url' => $argv[1]);

	call_hooks('globaldir_update', $arr);

	logger('Updating directory: ' . $arr['url'], LOGGER_DEBUG);
	if (strlen($arr['url'])) {
		fetch_url($dir . '?url=' . bin2hex($arr['url']));
	}

	return;
}

function directory_update_all() {
	$r = q("SELECT `url` FROM `contact`
		INNER JOIN `profile` ON `profile`.`uid` = `contact`.`uid`
		INNER JOIN `user` ON `user`.`uid` = `contact`.`uid`
			WHERE `contact`.`self` AND `profile`.`net-publish` AND `profile`.`is-default` AND
				NOT `user`.`account_expired` AND `user`.`verified`");

	if (dbm::is_result($r)) {
		foreach ($r AS $user) {
			proc_run(PRIORITY_LOW, 'include/directory.php', $user['url']);
		}
	}
}
