<?php

require_once("boot.php");
require_once('include/Scrape.php');
require_once('include/socgraph.php');

function gprobe_run(&$argv, &$argc){
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

	require_once('include/session.php');
	require_once('include/datetime.php');

	load_config('config');
	load_config('system');

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	if($argc != 2)
		return;

	$url = hex2bin($argv[1]);

	$r = q("select * from gcontact where nurl = '%s' limit 1",
		dbesc(normalise_link($url))
	);

	if(! count($r)) {

		$arr = probe_url($url);
		if(count($arr) && x($arr,'network') && $arr['network'] === NETWORK_DFRN) {
			q("insert into `gcontact` (`name`,`url`,`nurl`,`photo`)
				values ( '%s', '%s', '%s', '%s') ",
				dbesc($arr['name']),
				dbesc($arr['url']),
				dbesc(normalise_link($arr['url'])),
				dbesc($arr['photo'])
			);
		}
		$r = q("select * from gcontact where nurl = '%s' limit 1",
			dbesc(normalise_link($url))
		);
	}
	if(count($r))
		poco_load(0,0,$r[0]['id'], str_replace('/profile/','/poco/',$r[0]['url']));
		
	return;
}

if (array_search(__file__,get_included_files())===0){
  gprobe_run($argv,$argc);
  killme();
}
