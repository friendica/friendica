<?php

use \Friendica\Core\Config;

function cronhooks_run(&$argv, &$argc) {
	global $a;

	require_once 'include/datetime.php';

	if (($argc == 2) AND is_array($a->hooks) AND array_key_exists("cron", $a->hooks)) {
		foreach ($a->hooks["cron"] as $hook) {
			if ($hook[1] == $argv[1]) {
				logger("Calling cron hook '" . $hook[1] . "'", LOGGER_DEBUG);
				call_single_hook($a, $name, $hook, $data);
			}
		}
		return;
	}

	$last = get_config('system', 'last_cronhook');

	$poll_interval = intval(get_config('system', 'cronhook_interval'));
	if (! $poll_interval) {
		$poll_interval = 9;
	}

	if ($last) {
		$next = $last + ($poll_interval * 60);
		if ($next > time()) {
			logger('cronhook intervall not reached');
			return;
		}
	}

	$a->set_baseurl(get_config('system', 'url'));

	logger('cronhooks: start');

	$d = datetime_convert();

	if (is_array($a->hooks) AND array_key_exists("cron", $a->hooks)) {
		foreach ($a->hooks["cron"] as $hook) {
			logger("Calling cronhooks for '" . $hook[1] . "'", LOGGER_DEBUG);
			proc_run(PRIORITY_MEDIUM, "include/cronhooks.php", $hook[1]);
		}
	}

	logger('cronhooks: end');

	set_config('system', 'last_cronhook', time());

	return;
}
