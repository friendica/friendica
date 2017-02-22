<?php
use \Friendica\Core\Config;

if (!file_exists("boot.php") AND (sizeof($_SERVER["argv"]) != 0)) {
	$directory = dirname($_SERVER["argv"][0]);

	if (substr($directory, 0, 1) != "/")
		$directory = $_SERVER["PWD"]."/".$directory;

	$directory = realpath($directory."/..");

	chdir($directory);
}

require_once("boot.php");


function cronjobs_run(&$argv, &$argc){
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
	require_once('include/ostatus.php');
	require_once('include/post_update.php');
	require_once('mod/nodeinfo.php');

	Config::load();

	$a->set_baseurl(get_config('system','url'));

	// No parameter set? So return
	if ($argc <= 1)
		return;

	// Check OStatus conversations
	// Check only conversations with mentions (for a longer time)
	if ($argv[1] == 'ostatus_mentions') {
		ostatus::check_conversations(true);
		return;
	}

	// Check every conversation
	if ($argv[1] == 'ostatus_conversations') {
		ostatus::check_conversations(false);
		return;
	}

	// Call possible post update functions
	// see include/post_update.php for more details
	if ($argv[1] == 'post_update') {
		post_update();
		return;
	}

	// update nodeinfo data
	if ($argv[1] == 'nodeinfo') {
		nodeinfo_cron();
		return;
	}

	return;
}

if (array_search(__file__,get_included_files())===0){
	cronjobs_run($_SERVER["argv"],$_SERVER["argc"]);
	killme();
}
