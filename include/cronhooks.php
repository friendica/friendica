<?php

use \Friendica\Core\Config;

require_once("boot.php");

function cronhooks_run(&$argv, &$argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}

	if(is_null($db)) {
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	require_once('include/session.php');
	require_once('include/datetime.php');

	Config::load();

	// Don't check this stuff if the function is called by the poller
	if (App::callstack() != "poller_run") {
		if ($a->maxload_reached())
			return;
		if (App::is_already_running('cronhooks', 'include/cronhooks.php', 1140))
			return;
	}

	load_hooks();

	if (($argc == 2) AND is_array($a->hooks) AND array_key_exists("cron", $a->hooks)) {
                foreach ($a->hooks["cron"] as $hook)
			if ($hook[1] == $argv[1]) {
				logger("Calling cron hook '".$hook[1]."'", LOGGER_DEBUG);
				call_single_hook($a, $name, $hook, $data);
			}
		return;
	}

	$last = get_config('system','last_cronhook');

	$poll_interval = intval(get_config('system','cronhook_interval'));
	if(! $poll_interval)
		$poll_interval = 9;

	if($last) {
		$next = $last + ($poll_interval * 60);
		if($next > time()) {
			logger('cronhook intervall not reached');
			return;
		}
	}

	$a->set_baseurl(get_config('system','url'));

	logger('cronhooks: start');

	$d = datetime_convert();

	if (get_config("system", "worker") AND is_array($a->hooks) AND array_key_exists("cron", $a->hooks)) {
                foreach ($a->hooks["cron"] as $hook) {
			logger("Calling cronhooks for '".$hook[1]."'", LOGGER_DEBUG);
			proc_run(PRIORITY_MEDIUM, "include/cronhooks.php", $hook[1]);
		}
	} else
		call_hooks('cron', $d);

	logger('cronhooks: end');

	set_config('system','last_cronhook', time());

	return;
}

if (array_search(__file__,get_included_files())===0){
	cronhooks_run($_SERVER["argv"],$_SERVER["argc"]);
	killme();
}
