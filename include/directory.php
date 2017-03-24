<?php
use \Friendica\Core\Config;

function directory_run(&$argv, &$argc){
	if ($argc != 2) {
		return;
	}

	$dir = get_config('system', 'directory');

	if (!strlen($dir)) {
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
