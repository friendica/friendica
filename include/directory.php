<?php
require_once("boot.php");

use \Friendica\Core\Config;

function directory_run(&$argv, &$argc){
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

	Config::load();

	if($argc != 2)
		return;

	load_hooks();


	$a->set_baseurl(get_config('system','url'));

	$dir = get_config('system','directory');

	if(! strlen($dir))
		return;

	$dir .= "/submit";

	$arr = array('url' => $argv[1]);

	call_hooks('globaldir_update', $arr);

	logger('Updating directory: ' . $arr['url'], LOGGER_DEBUG);
	if(strlen($arr['url']))
		fetch_url($dir . '?url=' . bin2hex($arr['url']));

	return;
}

if (array_search(__file__,get_included_files())===0){
  directory_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
