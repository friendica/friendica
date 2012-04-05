<?php
require_once("boot.php");

function directory_run($argv, $argc){
	global $a, $db;

	if(is_null($a)) {
		$a = new App;
	}
  
	if(is_null($db)) {
		@include(".htconfig.php");
		require_once("dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
				unset($db_host, $db_user, $db_pass, $db_data);
	};

	load_config('config');
	load_config('system');


	if($argc != 2)
		return;

	load_config('system');

	$a->set_baseurl(get_config('system','url'));

	$dir = get_config('system','directory_submit_url');

	if(! strlen($dir))
		return;

	fetch_url($dir . '?url=' . bin2hex($argv[1]));

	return;
}

if (array_search(__file__,get_included_files())===0){
  directory_run($argv,$argc);
  killme();
}
