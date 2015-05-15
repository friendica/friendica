<?php

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
	require_once('include/pidfile.php');

	load_config('config');
	load_config('system');

	$maxsysload = intval(get_config('system','maxloadavg'));
	if($maxsysload < 1)
		$maxsysload = 50;
	if(function_exists('sys_getloadavg')) {
		$load = sys_getloadavg();
		if(intval($load[0]) > $maxsysload) {
			logger('system: load ' . $load[0] . ' too high. Cronhooks deferred to next scheduled run.');
			return;
		}
	}

	$lockpath = get_lockpath();
	if ($lockpath != '') {
		$pidfile = new pidfile($lockpath, 'cronhooks');
		if($pidfile->is_already_running()) {
			logger("cronhooks: Already running");
			if ($pidfile->running_time() > 19*60) {
                                $pidfile->kill();
                                logger("cronhooks: killed stale process");
				// Calling a new instance
				proc_run('php','include/cronhooks.php');
                        }
			exit;
		}
	}

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	logger('cronhooks: start');

	$d = datetime_convert();

	call_hooks('cron', $d);

	logger('cronhooks: end');

	return;
}

if (array_search(__file__,get_included_files())===0){
  cronhooks_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
