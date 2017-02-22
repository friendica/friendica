<?php

use \Friendica\Core\Config;

require_once("boot.php");

function update_gcontact_run(&$argv, &$argc){
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

	require_once('include/Scrape.php');
	require_once("include/socgraph.php");

	Config::load();

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	logger('update_gcontact: start');

	if(($argc > 1) && (intval($argv[1])))
		$contact_id = intval($argv[1]);

	if(!$contact_id) {
		logger('update_gcontact: no contact');
		return;
	}

	// Don't check this stuff if the function is called by the poller
	if (App::callstack() != "poller_run")
		if (App::is_already_running('update_gcontact'.$contact_id, '', 540))
			return;

	$r = q("SELECT * FROM `gcontact` WHERE `id` = %d", intval($contact_id));

	if (!$r)
		return;

	if (!in_array($r[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS)))
		return;

	$data = probe_url($r[0]["url"]);

	if (!in_array($data["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS))) {
		if ($r[0]["server_url"] != "")
			poco_check_server($r[0]["server_url"], $r[0]["network"]);

		q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()), intval($contact_id));
		return;
	}

	if (($data["name"] == "") AND ($r[0]['name'] != ""))
		$data["name"] = $r[0]['name'];

	if (($data["nick"] == "") AND ($r[0]['nick'] != ""))
		$data["nick"] = $r[0]['nick'];

	if (($data["addr"] == "") AND ($r[0]['addr'] != ""))
		$data["addr"] = $r[0]['addr'];

	if (($data["photo"] == "") AND ($r[0]['photo'] != ""))
		$data["photo"] = $r[0]['photo'];


	q("UPDATE `gcontact` SET `name` = '%s', `nick` = '%s', `addr` = '%s', `photo` = '%s'
				WHERE `id` = %d",
				dbesc($data["name"]),
				dbesc($data["nick"]),
				dbesc($data["addr"]),
				dbesc($data["photo"]),
				intval($contact_id)
			);

	q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `addr` = '%s', `photo` = '%s'
				WHERE `uid` = 0 AND `addr` = '' AND `nurl` = '%s'",
				dbesc($data["name"]),
				dbesc($data["nick"]),
				dbesc($data["addr"]),
				dbesc($data["photo"]),
				dbesc(normalise_link($data["url"]))
			);

	q("UPDATE `contact` SET `addr` = '%s'
				WHERE `uid` != 0 AND `addr` = '' AND `nurl` = '%s'",
				dbesc($data["addr"]),
				dbesc(normalise_link($data["url"]))
			);
}

if (array_search(__file__,get_included_files())===0){
	update_gcontact_run($_SERVER["argv"],$_SERVER["argc"]);
	killme();
}
