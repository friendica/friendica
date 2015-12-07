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
    	require_once("include/dba.php");
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

	logger("gprobe start for ".normalise_link($url), LOGGER_DEBUG);

	if(! count($r)) {

		// Is it a DDoS attempt?
		$urlparts = parse_url($url);

		$result = Cache::get("gprobe:".$urlparts["host"]);
		if (!is_null($result)) {
			$result = unserialize($result);
			if (in_array($result["network"], array(NETWORK_FEED, NETWORK_PHANTOM))) {
				logger("DDoS attempt detected for ".$urlparts["host"]." by ".$_SERVER["REMOTE_ADDR"].". server data: ".print_r($_SERVER, true), LOGGER_DEBUG);
				return;
			}
		}

		$arr = probe_url($url);

		if (is_null($result))
			Cache::set("gprobe:".$urlparts["host"],serialize($arr));

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

	logger("gprobe end for ".normalise_link($url), LOGGER_DEBUG);
	return;
}

if (array_search(__file__,get_included_files())===0){
  gprobe_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
