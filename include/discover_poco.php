<?php

use \Friendica\Core\Config;

require_once("boot.php");
require_once("include/socgraph.php");

function discover_poco_run(&$argv, &$argc){
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
	if (App::callstack() != "poller_run")
		if ($a->maxload_reached())
			return;

	if(($argc > 2) && ($argv[1] == "dirsearch")) {
		$search = urldecode($argv[2]);
		$mode = 1;
	} elseif(($argc == 2) && ($argv[1] == "checkcontact")) {
		$mode = 2;
	} elseif(($argc == 2) && ($argv[1] == "suggestions")) {
		$mode = 3;
	} elseif ($argc == 1) {
		$search = "";
		$mode = 0;
	} else
		die("Unknown or missing parameter ".$argv[1]."\n");

	// Don't check this stuff if the function is called by the poller
	if (App::callstack() != "poller_run")
		if (App::is_already_running('discover_poco'.$mode.urlencode($search), 'include/discover_poco.php', 1140))
			return;

	$a->set_baseurl(get_config('system','url'));

	load_hooks();

	logger('start '.$search);

	if ($mode==3)
		update_suggestions();
	elseif (($mode == 2) AND get_config('system','poco_completion'))
		discover_users();
	elseif (($mode == 1) AND ($search != "") and get_config('system','poco_local_search')) {
		discover_directory($search);
		gs_search_user($search);
	} elseif (($mode == 0) AND ($search == "") and (get_config('system','poco_discovery') > 0)) {
		// Query Friendica and Hubzilla servers for their users
		poco_discover();

		// Query GNU Social servers for their users ("statistics" addon has to be enabled on the GS server)
		if (!get_config('system','ostatus_disabled'))
			gs_discover();
	}

	logger('end '.$search);

	return;
}

function discover_users() {
	logger("Discover users", LOGGER_DEBUG);

	$users = q("SELECT `url`, `created`, `updated`, `last_failure`, `last_contact`, `server_url` FROM `gcontact`
			WHERE `last_contact` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
				`last_failure` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
				`network` IN ('%s', '%s', '%s', '%s', '') ORDER BY rand()",
			dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS), dbesc(NETWORK_FEED));

	if (!$users)
		return;

	$checked = 0;

	foreach ($users AS $user) {

		$urlparts = parse_url($user["url"]);
		if (!isset($urlparts["scheme"])) {
			q("UPDATE `gcontact` SET `network` = '%s' WHERE `nurl` = '%s'",
				dbesc(NETWORK_PHANTOM), dbesc(normalise_link($user["url"])));
			continue;
		 }

		if (in_array($urlparts["host"], array("www.facebook.com", "facebook.com", "twitter.com",
							"identi.ca", "alpha.app.net"))) {
			$networks = array("www.facebook.com" => NETWORK_FACEBOOK,
					"facebook.com" => NETWORK_FACEBOOK,
					"twitter.com" => NETWORK_TWITTER,
					"identi.ca" => NETWORK_PUMPIO,
					"alpha.app.net" => NETWORK_APPNET);

			q("UPDATE `gcontact` SET `network` = '%s' WHERE `nurl` = '%s'",
				dbesc($networks[$urlparts["host"]]), dbesc(normalise_link($user["url"])));
			continue;
		}

		if ($user["server_url"] != "")
			$server_url = $user["server_url"];
		else
			$server_url = poco_detect_server($user["url"]);

		if (($server_url == "") OR poco_check_server($server_url, $gcontacts[0]["network"])) {
			logger('Check user '.$user["url"]);
			poco_last_updated($user["url"], true);

			if (++$checked > 100)
				return;
		} else
			q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
				dbesc(datetime_convert()), dbesc(normalise_link($user["url"])));
	}
}

function discover_directory($search) {

	$data = Cache::get("dirsearch:".$search);
	if (!is_null($data)){
		// Only search for the same item every 24 hours
		if (time() < $data + (60 * 60 * 24)) {
			logger("Already searched for ".$search." in the last 24 hours", LOGGER_DEBUG);
			return;
		}
	}

	$x = fetch_url(get_server()."/lsearch?p=1&n=500&search=".urlencode($search));
	$j = json_decode($x);

	if(count($j->results))
		foreach($j->results as $jj) {
			// Check if the contact already exists
			$exists = q("SELECT `id`, `last_contact`, `last_failure`, `updated` FROM `gcontact` WHERE `nurl` = '%s'", normalise_link($jj->url));
			if ($exists) {
				logger("Profile ".$jj->url." already exists (".$search.")", LOGGER_DEBUG);

				if (($exists[0]["last_contact"] < $exists[0]["last_failure"]) AND
					($exists[0]["updated"] < $exists[0]["last_failure"]))
					continue;

				// Update the contact
				poco_last_updated($jj->url);
				continue;
			}

			// Harcoded paths aren't so good. But in this case it is okay.
			// First: We only will get Friendica contacts (which always are using this url schema)
			// Second: There will be no further problems if we are doing a mistake
			$server_url = preg_replace("=(https?://)(.*)/profile/(.*)=ism", "$1$2", $jj->url);
			if ($server_url != $jj->url)
				if (!poco_check_server($server_url)) {
					logger("Friendica server ".$server_url." doesn't answer.", LOGGER_DEBUG);
					continue;
				}
					logger("Friendica server ".$server_url." seems to be okay.", LOGGER_DEBUG);

			logger("Check if profile ".$jj->url." is reachable (".$search.")", LOGGER_DEBUG);
			$data = probe_url($jj->url);
			if ($data["network"] == NETWORK_DFRN) {
				logger("Add profile ".$jj->url." to local directory (".$search.")", LOGGER_DEBUG);
				poco_check($data["url"], $data["name"], $data["network"], $data["photo"], "", "", "", $jj->tags, $data["addr"], "", 0);
			}
		}
	Cache::set("dirsearch:".$search, time(), CACHE_DAY);
}

/**
 * @brief Search for GNU Social user with gstools.org
 *
 * @param str $search User name
 */
function gs_search_user($search) {

	$a = get_app();

	$url = "http://gstools.org/api/users_search/".urlencode($search);

	$result = z_fetch_url($url);
	if (!$result["success"])
		return false;

	$contacts = json_decode($result["body"]);

	if ($contacts->status == 'ERROR')
		return false;

	foreach($contacts->data AS $user) {
		$contact = probe_url($user->site_address."/".$user->name);
		if ($contact["network"] != NETWORK_PHANTOM) {
			$contact["about"] = $user->description;
			update_gcontact($contact);
		}
	}
}


if (array_search(__file__,get_included_files())===0){
  discover_poco_run($_SERVER["argv"],$_SERVER["argc"]);
  killme();
}
