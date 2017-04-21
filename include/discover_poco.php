<?php

use \Friendica\Core\Config;

require_once('include/socgraph.php');
require_once('include/datetime.php');

function discover_poco_run(&$argv, &$argc) {

	/*
	This function can be called in these ways:
	- dirsearch <search pattern>: Searches for "search pattern" in the directory. "search pattern" is url encoded.
	- checkcontact: Updates gcontact entries
	- suggestions: Discover other servers for their contacts.
	- server <poco url>: Searches for the poco server list. "poco url" is base64 encoded.
	- update_server: Frequently check the first 250 servers for vitality.
	- update_server_directory: Discover the given server id for their contacts
	- poco_load: Load POCO data from a given POCO address
	- check_profile: Update remote profile data
	*/

	if (($argc > 2) && ($argv[1] == "dirsearch")) {
		$search = urldecode($argv[2]);
		$mode = 1;
	} elseif (($argc == 2) && ($argv[1] == "checkcontact")) {
		$mode = 2;
	} elseif (($argc == 2) && ($argv[1] == "suggestions")) {
		$mode = 3;
	} elseif (($argc == 3) && ($argv[1] == "server")) {
		$mode = 4;
	} elseif (($argc == 2) && ($argv[1] == "update_server")) {
		$mode = 5;
	} elseif (($argc == 3) && ($argv[1] == "update_server_directory")) {
		$mode = 6;
	} elseif (($argc > 5) && ($argv[1] == "poco_load")) {
		$mode = 7;
	} elseif (($argc == 3) && ($argv[1] == "check_profile")) {
		$mode = 8;
	} elseif ($argc == 1) {
		$search = "";
		$mode = 0;
	} else {
		die("Unknown or missing parameter ".$argv[1]."\n");
	}

	logger('start '.$search);

	if ($mode == 8) {
		$profile_url = base64_decode($argv[2]);
		if ($profile_url != "") {
			poco_last_updated($profile_url, true);
		}
	} elseif ($mode == 7) {
		if ($argc == 6) {
			$url = base64_decode($argv[5]);
		} else {
			$url = '';
		}
		poco_load_worker(intval($argv[2]), intval($argv[3]), intval($argv[4]), $url);
	} elseif ($mode == 6) {
		poco_discover_single_server(intval($argv[2]));
	} elseif ($mode == 5) {
		update_server();
	} elseif ($mode == 4) {
		$server_url = base64_decode($argv[2]);
		if ($server_url == "") {
			return;
		}
		$server_url = filter_var($server_url, FILTER_SANITIZE_URL);
		if (substr(normalise_link($server_url), 0, 7) != "http://") {
			return;
		}
		$result = "Checking server ".$server_url." - ";
		$ret = poco_check_server($server_url);
		if ($ret) {
			$result .= "success";
		} else {
			$result .= "failed";
		}
		logger($result, LOGGER_DEBUG);
	} elseif ($mode == 3) {
		update_suggestions();
	} elseif (($mode == 2) AND get_config('system','poco_completion')) {
		discover_users();
	} elseif (($mode == 1) AND ($search != "") and get_config('system','poco_local_search')) {
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

/**
 * @brief Updates the first 250 servers
 *
 */
function update_server() {
	$r = q("SELECT `url`, `created`, `last_failure`, `last_contact` FROM `gserver` ORDER BY rand()");

	if (!dbm::is_result($r)) {
		return;
	}

	$updated = 0;

	foreach ($r AS $server) {
		if (!poco_do_update($server["created"], "", $server["last_failure"], $server["last_contact"])) {
			continue;
		}
		logger('Update server status for server '.$server["url"], LOGGER_DEBUG);

		proc_run(PRIORITY_LOW, "include/discover_poco.php", "server", base64_encode($server["url"]));

		if (++$updated > 250) {
			return;
		}
	}
}

function discover_users() {
	logger("Discover users", LOGGER_DEBUG);

	$starttime = time();

	$users = q("SELECT `url`, `created`, `updated`, `last_failure`, `last_contact`, `server_url`, `network` FROM `gcontact`
			WHERE `last_contact` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
				`last_failure` < UTC_TIMESTAMP - INTERVAL 1 MONTH AND
				`network` IN ('%s', '%s', '%s', '%s', '') ORDER BY rand()",
			dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS), dbesc(NETWORK_FEED));

	if (!$users) {
		return;
	}
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

		$server_url = poco_detect_server($user["url"]);
		$force_update = false;

		if ($user["server_url"] != "") {

			$force_update = (normalise_link($user["server_url"]) != normalise_link($server_url));

			$server_url = $user["server_url"];
		}

		if ((($server_url == "") AND ($user["network"] == NETWORK_FEED)) OR $force_update OR poco_check_server($server_url, $user["network"])) {
			logger('Check profile '.$user["url"]);
			proc_run(PRIORITY_LOW, "include/discover_poco.php", "check_profile", base64_encode($user["url"]));

			if (++$checked > 100) {
				return;
			}
		} else {
			q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
				dbesc(datetime_convert()), dbesc(normalise_link($user["url"])));
		}

		// Quit the loop after 3 minutes
		if (time() > ($starttime + 180)) {
			return;
		}
	}
}

function discover_directory($search) {

	$data = Cache::get("dirsearch:".$search);
	if (!is_null($data)) {
		// Only search for the same item every 24 hours
		if (time() < $data + (60 * 60 * 24)) {
			logger("Already searched for ".$search." in the last 24 hours", LOGGER_DEBUG);
			return;
		}
	}

	$x = fetch_url(get_server()."/lsearch?p=1&n=500&search=".urlencode($search));
	$j = json_decode($x);

	if (count($j->results)) {
		foreach ($j->results as $jj) {
			// Check if the contact already exists
			$exists = q("SELECT `id`, `last_contact`, `last_failure`, `updated` FROM `gcontact` WHERE `nurl` = '%s'", normalise_link($jj->url));
			if (dbm::is_result($exists)) {
				logger("Profile ".$jj->url." already exists (".$search.")", LOGGER_DEBUG);

				if (($exists[0]["last_contact"] < $exists[0]["last_failure"]) AND
					($exists[0]["updated"] < $exists[0]["last_failure"])) {
					continue;
				}
				// Update the contact
				poco_last_updated($jj->url);
				continue;
			}

			$server_url = poco_detect_server($jj->url);
			if ($server_url != '') {
				if (!poco_check_server($server_url)) {
					logger("Friendica server ".$server_url." doesn't answer.", LOGGER_DEBUG);
					continue;
				}
				logger("Friendica server ".$server_url." seems to be okay.", LOGGER_DEBUG);
			}

			$data = probe_url($jj->url);
			if ($data["network"] == NETWORK_DFRN) {
				logger("Profile ".$jj->url." is reachable (".$search.")", LOGGER_DEBUG);
				logger("Add profile ".$jj->url." to local directory (".$search.")", LOGGER_DEBUG);

				if ($jj->tags != "") {
					$data["keywords"] = $jj->tags;
				}

				$data["server_url"] = $data["baseurl"];

				update_gcontact($data);
			} else {
				logger("Profile ".$jj->url." is not responding or no Friendica contact - but network ".$data["network"], LOGGER_DEBUG);
			}
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

	// Currently disabled, since the service isn't available anymore.
	// It is not removed since I hope that there will be a successor.
	return false;

	$a = get_app();

	$url = "http://gstools.org/api/users_search/".urlencode($search);

	$result = z_fetch_url($url);
	if (!$result["success"]) {
		return false;
	}

	$contacts = json_decode($result["body"]);

	if ($contacts->status == 'ERROR') {
		return false;
	}

	/// @TODO AS is considered as a notation for constants (as they usually being written all upper-case)
	/// @TODO find all those and convert to all lower-case which is a keyword then
	foreach ($contacts->data AS $user) {
		$contact = probe_url($user->site_address."/".$user->name);
		if ($contact["network"] != NETWORK_PHANTOM) {
			$contact["about"] = $user->description;
			update_gcontact($contact);
		}
	}
}
