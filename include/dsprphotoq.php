<?php
require_once("boot.php");
require_once('include/diaspora.php');

function dsprphotoq_run($argv, $argc){
	global $a, $db;

	if(is_null($a)){
		$a = new App;
	}

	if(is_null($db)){
		@include(".htconfig.php");
		require_once("include/dba.php");
		$db = new dba($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);
	};

	logger("diaspora photo queue: running", LOGGER_DEBUG);

	$r = q("SELECT * FROM dsprphotoq");
	if(!$r)
		return;

	$dphotos = $r;

	logger("diaspora photo queue: processing " . count($dphotos) . " photos");

	foreach($dphotos as $dphoto) {

		$r = q("SELECT * FROM user WHERE uid = %d",
			intval($dphoto['uid'])
		);
		if(!$r) {
			logger("diaspora photo queue: user " . $dphoto['uid'] . " not found");
			return;
		}

		$ret = diaspora_dispatch($r[0],unserialize($dphoto['msg']),$dphoto['attempt']);
		q("DELETE FROM dsprphotoq WHERE id = %d",
		   intval($dphoto['id'])
		);
	}
}


if (array_search(__file__,get_included_files())===0){
  dsprphotoq_run($argv,$argc);
  killme();
}
