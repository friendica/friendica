<?php

require_once('include/datetime.php');
require_once("include/Scrape.php");
require_once("include/html2bbcode.php");

/*
 To-Do:
 - Move GNU Social URL schemata (http://server.tld/user/number) to http://server.tld/username
 - Fetch profile data from profile page for Redmatrix users
 - Detect if it is a forum
*/

/*
 * poco_load
 *
 * Given a contact-id (minimum), load the PortableContacts friend list for that contact,
 * and add the entries to the gcontact (Global Contact) table, or update existing entries
 * if anything (name or photo) has changed.
 * We use normalised urls for comparison which ignore http vs https and www.domain vs domain
 *
 * Once the global contact is stored add (if necessary) the contact linkage which associates
 * the given uid, cid to the global contact entry. There can be many uid/cid combinations
 * pointing to the same global contact id.
 *
 */




function poco_load($cid,$uid = 0,$zcid = 0,$url = null) {

	$a = get_app();

	if($cid) {
		if((! $url) || (! $uid)) {
			$r = q("select `poco`, `uid` from `contact` where `id` = %d limit 1",
				intval($cid)
			);
			if(count($r)) {
				$url = $r[0]['poco'];
				$uid = $r[0]['uid'];
			}
		}
		if(! $uid)
			return;
	}

	if(! $url)
		return;

	$url = $url . (($uid) ? '/@me/@all?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,generation' : '?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,generation') ;

	logger('poco_load: ' . $url, LOGGER_DEBUG);

	$s = fetch_url($url);

	logger('poco_load: returns ' . $s, LOGGER_DATA);

	logger('poco_load: return code: ' . $a->get_curl_code(), LOGGER_DEBUG);

	if(($a->get_curl_code() > 299) || (! $s))
		return;

	$j = json_decode($s);

	logger('poco_load: json: ' . print_r($j,true),LOGGER_DATA);

	if(! isset($j->entry))
		return;

	$total = 0;
	foreach($j->entry as $entry) {

		$total ++;
		$profile_url = '';
		$profile_photo = '';
		$connect_url = '';
		$name = '';
		$network = '';
		$updated = '0000-00-00 00:00:00';
		$location = '';
		$about = '';
		$keywords = '';
		$gender = '';
		$generation = 0;

		$name = $entry->displayName;

		if(isset($entry->urls)) {
			foreach($entry->urls as $url) {
				if($url->type == 'profile') {
					$profile_url = $url->value;
					continue;
				}
				if($url->type == 'webfinger') {
					$connect_url = str_replace('acct:' , '', $url->value);
					continue;
				}
			}
		}
		if(isset($entry->photos)) {
			foreach($entry->photos as $photo) {
				if($photo->type == 'profile') {
					$profile_photo = $photo->value;
					continue;
				}
			}
		}

		if(isset($entry->updated))
			$updated = date("Y-m-d H:i:s", strtotime($entry->updated));

		if(isset($entry->network))
			$network = $entry->network;

		if(isset($entry->currentLocation))
			$location = $entry->currentLocation;

		if(isset($entry->aboutMe))
			$about = html2bbcode($entry->aboutMe);

		if(isset($entry->gender))
			$gender = $entry->gender;

		if(isset($entry->generation) AND ($entry->generation > 0))
			$generation = ++$entry->generation;

		if(isset($entry->tags))
			foreach($entry->tags as $tag)
				$keywords = implode(", ", $tag);

		// If you query a Friendica server for its profiles, the network has to be Friendica
		// To-Do: It could also be a Redmatrix server
		//if ($uid == 0)
		//	$network = NETWORK_DFRN;

		poco_check($profile_url, $name, $network, $profile_photo, $about, $location, $gender, $keywords, $connect_url, $updated, $generation, $cid, $uid, $zcid);

		// Update the Friendica contacts. Diaspora is doing it via a message. (See include/diaspora.php)
		if (($location != "") OR ($about != "") OR ($keywords != "") OR ($gender != ""))
			q("UPDATE `contact` SET `location` = '%s', `about` = '%s', `keywords` = '%s', `gender` = '%s'
				WHERE `nurl` = '%s' AND NOT `self` AND `network` = '%s'",
				dbesc($location),
				dbesc($about),
				dbesc($keywords),
				dbesc($gender),
				dbesc(normalise_link($profile_url)),
				dbesc(NETWORK_DFRN));
	}
	logger("poco_load: loaded $total entries",LOGGER_DEBUG);

	q("DELETE FROM `glink` WHERE `cid` = %d AND `uid` = %d AND `zcid` = %d AND `updated` < UTC_TIMESTAMP - INTERVAL 2 DAY",
		intval($cid),
		intval($uid),
		intval($zcid)
	);

}

function poco_check($profile_url, $name, $network, $profile_photo, $about, $location, $gender, $keywords, $connect_url, $updated, $generation, $cid = 0, $uid = 0, $zcid = 0) {

	$a = get_app();

	// Generation:
	//  0: No definition
	//  1: Profiles on this server
	//  2: Contacts of profiles on this server
	//  3: Contacts of contacts of profiles on this server
	//  4: ...

	$gcid = "";

	$alternate = poco_alternate_ostatus_url($profile_url);

	if ($profile_url == "")
		return $gcid;

	$urlparts = parse_url($profile_url);
	if (!isset($urlparts["scheme"]))
		return $gcid;

	if (in_array($urlparts["host"], array("www.facebook.com", "facebook.com", "twitter.com",
						"identi.ca", "alpha.app.net")))
		return $gcid;

	$orig_updated = $updated;

	// Don't store the statusnet connector as network
	// We can't simply set this to NETWORK_OSTATUS since the connector could have fetched posts from friendica as well
	if ($network == NETWORK_STATUSNET)
		$network = "";

	// The global contacts should contain the original picture, not the cached one
	if (($generation != 1) AND stristr(normalise_link($profile_photo), normalise_link($a->get_baseurl()."/photo/")))
		$profile_photo = "";

	$r = q("SELECT `network` FROM `contact` WHERE `nurl` = '%s' AND `network` != '' AND `network` != '%s' LIMIT 1",
		dbesc(normalise_link($profile_url)), dbesc(NETWORK_STATUSNET)
	);
	if(count($r))
		$network = $r[0]["network"];

	if (($network == "") OR ($network == NETWORK_OSTATUS)) {
		$r = q("SELECT `network`, `url` FROM `contact` WHERE `alias` IN ('%s', '%s') AND `network` != '' AND `network` != '%s' LIMIT 1",
			dbesc($profile_url), dbesc(normalise_link($profile_url)), dbesc(NETWORK_STATUSNET)
		);
		if(count($r)) {
			$network = $r[0]["network"];
			//$profile_url = $r[0]["url"];
		}
	}

	$x = q("SELECT * FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
		dbesc(normalise_link($profile_url))
	);

	if (count($x)) {
		if (($network == "") AND ($x[0]["network"] != NETWORK_STATUSNET))
			$network = $x[0]["network"];

		if ($updated == "0000-00-00 00:00:00")
			$updated = $x[0]["updated"];

		$created = $x[0]["created"];
		$server_url = $x[0]["server_url"];
		$nick = $x[0]["nick"];
		$addr = $x[0]["addr"];
	} else {
		$created = "0000-00-00 00:00:00";
		$server_url = "";

		$urlparts = parse_url($profile_url);
		$nick = end(explode("/", $urlparts["path"]));
		$addr = "";
	}

	if ((($network == "") OR ($name == "") OR ($addr == "") OR ($profile_photo == "") OR ($server_url == "") OR $alternate)
		AND poco_reachable($profile_url, $server_url, $network, false)) {
		$data = probe_url($profile_url);

		$orig_profile = $profile_url;

		$network = $data["network"];
		$name = $data["name"];
		$nick = $data["nick"];
		$addr = $data["addr"];
		$profile_url = $data["url"];
		$profile_photo = $data["photo"];
		$server_url = $data["baseurl"];

		if ($alternate AND ($network == NETWORK_OSTATUS)) {
			// Delete the old entry - if it exists
			$r = q("SELECT `id` FROM `gcontact` WHERE `nurl` = '%s'", dbesc(normalise_link($orig_profile)));
			if ($r) {
				q("DELETE FROM `gcontact` WHERE `nurl` = '%s'", dbesc(normalise_link($orig_profile)));
				q("DELETE FROM `glink` WHERE `gcid` = %d", intval($r[0]["id"]));
			}

			// possibly create a new entry
			poco_check($profile_url, $name, $network, $profile_photo, $about, $location, $gender, $keywords, $connect_url, $updated, $generation, $cid, $uid, $zcid);
		}
	}

	if ($alternate AND ($network == NETWORK_OSTATUS))
		return $gcid;

	if (count($x) AND ($x[0]["network"] == "") AND ($network != "")) {
		q("UPDATE `gcontact` SET `network` = '%s' WHERE `nurl` = '%s'",
			dbesc($network),
			dbesc(normalise_link($profile_url))
		);
	}

	if (($name == "") OR ($profile_photo == ""))
		return $gcid;

	if (!in_array($network, array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA)))
		return $gcid;

	logger("profile-check generation: ".$generation." Network: ".$network." URL: ".$profile_url." name: ".$name." avatar: ".$profile_photo, LOGGER_DEBUG);

	poco_check_server($server_url, $network);

	if(count($x)) {
		$gcid = $x[0]['id'];

		if (($location == "") AND ($x[0]['location'] != ""))
			$location = $x[0]['location'];

		if (($about == "") AND ($x[0]['about'] != ""))
			$about = $x[0]['about'];

		if (($gender == "") AND ($x[0]['gender'] != ""))
			$gender = $x[0]['gender'];

		if (($keywords == "") AND ($x[0]['keywords'] != ""))
			$keywords = $x[0]['keywords'];

		if (($addr == "") AND ($x[0]['addr'] != ""))
			$addr = $x[0]['addr'];

		if (($generation == 0) AND ($x[0]['generation'] > 0))
			$generation = $x[0]['generation'];

		if($x[0]['name'] != $name || $x[0]['photo'] != $profile_photo || $x[0]['updated'] < $updated) {
			q("UPDATE `gcontact` SET `name` = '%s', `addr` = '%s', `network` = '%s', `photo` = '%s', `connect` = '%s', `url` = '%s', `server_url` = '%s',
				`updated` = '%s', `location` = '%s', `about` = '%s', `keywords` = '%s', `gender` = '%s', `generation` = %d
				WHERE (`generation` >= %d OR `generation` = 0) AND `nurl` = '%s'",
				dbesc($name),
				dbesc($addr),
				dbesc($network),
				dbesc($profile_photo),
				dbesc($connect_url),
				dbesc($profile_url),
				dbesc($server_url),
				dbesc($updated),
				dbesc($location),
				dbesc($about),
				dbesc($keywords),
				dbesc($gender),
				intval($generation),
				intval($generation),
				dbesc(normalise_link($profile_url))
			);
		}
	} else {
		q("INSERT INTO `gcontact` (`name`, `nick`, `addr`, `network`, `url`, `nurl`, `photo`, `connect`, `server_url`, `created`, `updated`, `location`, `about`, `keywords`, `gender`, `generation`)
			VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
			dbesc($name),
			dbesc($nick),
			dbesc($addr),
			dbesc($network),
			dbesc($profile_url),
			dbesc(normalise_link($profile_url)),
			dbesc($profile_photo),
			dbesc($connect_url),
			dbesc($server_url),
			dbesc(datetime_convert()),
			dbesc($updated),
			dbesc($location),
			dbesc($about),
			dbesc($keywords),
			dbesc($gender),
			intval($generation)
		);
		$x = q("SELECT * FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
			dbesc(normalise_link($profile_url))
		);
		if(count($x))
			$gcid = $x[0]['id'];
	}

	if(! $gcid)
		return $gcid;

	$r = q("SELECT * FROM `glink` WHERE `cid` = %d AND `uid` = %d AND `gcid` = %d AND `zcid` = %d LIMIT 1",
		intval($cid),
		intval($uid),
		intval($gcid),
		intval($zcid)
	);
	if(! count($r)) {
		q("INSERT INTO `glink` (`cid`,`uid`,`gcid`,`zcid`, `updated`) VALUES (%d,%d,%d,%d, '%s') ",
			intval($cid),
			intval($uid),
			intval($gcid),
			intval($zcid),
			dbesc(datetime_convert())
		);
	} else {
		q("UPDATE `glink` SET `updated` = '%s' WHERE `cid` = %d AND `uid` = %d AND `gcid` = %d AND `zcid` = %d",
			dbesc(datetime_convert()),
			intval($cid),
			intval($uid),
			intval($gcid),
			intval($zcid)
		);
	}

	// For unknown reasons there are sometimes duplicates
	q("DELETE FROM `gcontact` WHERE `nurl` = '%s' AND `id` != %d AND
		NOT EXISTS (SELECT `gcid` FROM `glink` WHERE `gcid` = `gcontact`.`id`)",
		dbesc(normalise_link($profile_url)),
		intval($gcid)
	);

	return $gcid;
}

function poco_reachable($profile, $server = "", $network = "", $force = false) {

	if ($server == "")
		$server = poco_detect_server($profile);

	if ($server == "")
		return true;

	return poco_check_server($server, $network, $force);
}

function poco_detect_server($profile) {

	// Try to detect the server path based upon some known standard paths
	$server_url = "";

	if ($server_url == "") {
		$friendica = preg_replace("=(https?://)(.*)/profile/(.*)=ism", "$1$2", $profile);
		if ($friendica != $profile) {
			$server_url = $friendica;
			$network = NETWORK_DFRN;
		}
	}

	if ($server_url == "") {
		$diaspora = preg_replace("=(https?://)(.*)/u/(.*)=ism", "$1$2", $profile);
		if ($diaspora != $profile) {
			$server_url = $diaspora;
			$network = NETWORK_DIASPORA;
		}
	}

	if ($server_url == "") {
		$red = preg_replace("=(https?://)(.*)/channel/(.*)=ism", "$1$2", $profile);
		if ($red != $profile) {
			$server_url = $red;
			$network = NETWORK_DIASPORA;
		}
	}

	return $server_url;
}

function poco_alternate_ostatus_url($url) {
	return(preg_match("=https?://.+/user/\d+=ism", $url, $matches));
}

function poco_last_updated($profile, $force = false) {

	$gcontacts = q("SELECT * FROM `gcontact` WHERE `nurl` = '%s'",
			dbesc(normalise_link($profile)));

	if ($gcontacts[0]["created"] == "0000-00-00 00:00:00")
		q("UPDATE `gcontact` SET `created` = '%s' WHERE `nurl` = '%s'",
			dbesc(datetime_convert()), dbesc(normalise_link($profile)));

	if ($gcontacts[0]["server_url"] != "")
		$server_url = $gcontacts[0]["server_url"];
	else
		$server_url = poco_detect_server($profile);

	if ($server_url != "") {
		if (!poco_check_server($server_url, $gcontacts[0]["network"], $force)) {

			if ($force)
				q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
					dbesc(datetime_convert()), dbesc(normalise_link($profile)));

			return false;
		}

		q("UPDATE `gcontact` SET `server_url` = '%s' WHERE `nurl` = '%s'",
			dbesc($server_url), dbesc(normalise_link($profile)));
	}

	if (in_array($gcontacts[0]["network"], array("", NETWORK_FEED))) {
		$server = q("SELECT `network` FROM `gserver` WHERE `nurl` = '%s' AND `network` != ''",
			dbesc(normalise_link($server_url)));

		if ($server)
			q("UPDATE `gcontact` SET `network` = '%s' WHERE `nurl` = '%s'",
				dbesc($server[0]["network"]), dbesc(normalise_link($profile)));
		else
			return;
	}

	// noscrape is really fast so we don't cache the call.
	if (($gcontacts[0]["server_url"] != "") AND ($gcontacts[0]["nick"] != "")) {

		//  Use noscrape if possible
		$server = q("SELECT `noscrape` FROM `gserver` WHERE `nurl` = '%s' AND `noscrape` != ''", dbesc(normalise_link($gcontacts[0]["server_url"])));

		if ($server) {
			$noscraperet = z_fetch_url($server[0]["noscrape"]."/".$gcontacts[0]["nick"]);

			 if ($noscraperet["success"] AND ($noscraperet["body"] != "")) {

				$noscrape = json_decode($noscraperet["body"], true);

				if (($noscrape["fn"] != "") AND ($noscrape["fn"] != $gcontacts[0]["name"]))
					q("UPDATE `gcontact` SET `name` = '%s' WHERE `nurl` = '%s'",
						dbesc($noscrape["fn"]), dbesc(normalise_link($profile)));

				if (($noscrape["photo"] != "") AND ($noscrape["photo"] != $gcontacts[0]["photo"]))
					q("UPDATE `gcontact` SET `photo` = '%s' WHERE `nurl` = '%s'",
						dbesc($noscrape["photo"]), dbesc(normalise_link($profile)));

				if (($noscrape["updated"] != "") AND ($noscrape["updated"] != $gcontacts[0]["updated"]))
					q("UPDATE `gcontact` SET `updated` = '%s' WHERE `nurl` = '%s'",
						dbesc($noscrape["updated"]), dbesc(normalise_link($profile)));

				if (($noscrape["gender"] != "") AND ($noscrape["gender"] != $gcontacts[0]["gender"]))
					q("UPDATE `gcontact` SET `gender` = '%s' WHERE `nurl` = '%s'",
						dbesc($noscrape["gender"]), dbesc(normalise_link($profile)));

				if (($noscrape["pdesc"] != "") AND ($noscrape["pdesc"] != $gcontacts[0]["about"]))
					q("UPDATE `gcontact` SET `about` = '%s' WHERE `nurl` = '%s'",
						dbesc($noscrape["pdesc"]), dbesc(normalise_link($profile)));

				if (($noscrape["about"] != "") AND ($noscrape["about"] != $gcontacts[0]["about"]))
					q("UPDATE `gcontact` SET `about` = '%s' WHERE `nurl` = '%s'",
						dbesc($noscrape["about"]), dbesc(normalise_link($profile)));

				if (isset($noscrape["comm"]) AND ($noscrape["comm"] != $gcontacts[0]["community"]))
					q("UPDATE `gcontact` SET `community` = %d WHERE `nurl` = '%s'",
						intval($noscrape["comm"]), dbesc(normalise_link($profile)));

				if (isset($noscrape["tags"]))
					$keywords = implode(" ", $noscrape["tags"]);
				else
					$keywords = "";

				if (($keywords != "") AND ($keywords != $gcontacts[0]["keywords"]))
					q("UPDATE `gcontact` SET `keywords` = '%s' WHERE `nurl` = '%s'",
						dbesc($keywords), dbesc(normalise_link($profile)));

				$location = $noscrape["locality"];

				if ($noscrape["region"] != "") {
					if ($location != "")
						$location .= ", ";

					$location .= $noscrape["region"];
				}

				if ($noscrape["country-name"] != "") {
					if ($location != "")
						$location .= ", ";

					$location .= $noscrape["country-name"];
				}

				if (($location != "") AND ($location != $gcontacts[0]["location"]))
					q("UPDATE `gcontact` SET `location` = '%s' WHERE `nurl` = '%s'",
						dbesc($location), dbesc(normalise_link($profile)));

				// If we got data from noscrape then mark the contact as reachable
				if (is_array($noscrape) AND count($noscrape))
					q("UPDATE `gcontact` SET `last_contact` = '%s' WHERE `nurl` = '%s'",
						dbesc(datetime_convert()), dbesc(normalise_link($profile)));

				return $noscrape["updated"];
			}
		}
	}

	// If we only can poll the feed, then we only do this once a while
	if (!$force AND !poco_do_update($gcontacts[0]["created"], $gcontacts[0]["updated"], $gcontacts[0]["last_failure"],  $gcontacts[0]["last_contact"]))
		return $gcontacts[0]["updated"];

	$data = probe_url($profile);

	// Is the profile link the alternate OStatus link notation? (http://domain.tld/user/4711)
	// Then check the other link and delete this one
	if (($data["network"] == NETWORK_OSTATUS) AND poco_alternate_ostatus_url($profile) AND
		(normalise_link($profile) == normalise_link($data["alias"])) AND
		(normalise_link($profile) != normalise_link($data["url"]))) {

		// Delete the old entry
		q("DELETE FROM `gcontact` WHERE `nurl` = '%s'", dbesc(normalise_link($profile)));
		q("DELETE FROM `glink` WHERE `gcid` = %d", intval($gcontacts[0]["id"]));

		poco_check($data["url"], $data["name"], $data["network"], $data["photo"], $gcontacts[0]["about"], $gcontacts[0]["location"],
				$gcontacts[0]["gender"], $gcontacts[0]["keywords"], $data["addr"], $gcontacts[0]["updated"], $gcontacts[0]["generation"]);

		poco_last_updated($data["url"], $force);

		return false;
	}

	if (($data["poll"] == "") OR (in_array($data["network"], array(NETWORK_FEED, NETWORK_PHANTOM)))) {
		q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
			dbesc(datetime_convert()), dbesc(normalise_link($profile)));
		return false;
	}

	if (($data["name"] != "") AND ($data["name"] != $gcontacts[0]["name"]))
		q("UPDATE `gcontact` SET `name` = '%s' WHERE `nurl` = '%s'",
			dbesc($data["name"]), dbesc(normalise_link($profile)));

	if (($data["nick"] != "") AND ($data["nick"] != $gcontacts[0]["nick"]))
		q("UPDATE `gcontact` SET `nick` = '%s' WHERE `nurl` = '%s'",
			dbesc($data["nick"]), dbesc(normalise_link($profile)));

	if (($data["addr"] != "") AND ($data["addr"] != $gcontacts[0]["connect"]))
		q("UPDATE `gcontact` SET `connect` = '%s' WHERE `nurl` = '%s'",
			dbesc($data["addr"]), dbesc(normalise_link($profile)));

	if (($data["photo"] != "") AND ($data["photo"] != $gcontacts[0]["photo"]))
		q("UPDATE `gcontact` SET `photo` = '%s' WHERE `nurl` = '%s'",
			dbesc($data["photo"]), dbesc(normalise_link($profile)));

	if (($data["baseurl"] != "") AND ($data["baseurl"] != $gcontacts[0]["server_url"]))
		q("UPDATE `gcontact` SET `server_url` = '%s' WHERE `nurl` = '%s'",
			dbesc($data["baseurl"]), dbesc(normalise_link($profile)));

	$feedret = z_fetch_url($data["poll"]);

	if (!$feedret["success"]) {
		q("UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
			dbesc(datetime_convert()), dbesc(normalise_link($profile)));
		return false;
	}

	$doc = new DOMDocument();
	@$doc->loadXML($feedret["body"]);

	$xpath = new DomXPath($doc);
	$xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");

	$entries = $xpath->query('/atom:feed/atom:entry');

	$last_updated = "";

	foreach ($entries AS $entry) {
		$published = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;
		$updated = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;

		if ($last_updated < $published)
			$last_updated = $published;

		if ($last_updated < $updated)
			$last_updated = $updated;
	}

	// Maybe there aren't any entries. Then check if it is a valid feed
	if ($last_updated == "")
		if ($xpath->query('/atom:feed')->length > 0)
			$last_updated = "0000-00-00 00:00:00";

	q("UPDATE `gcontact` SET `updated` = '%s', `last_contact` = '%s' WHERE `nurl` = '%s'",
		dbesc($last_updated), dbesc(datetime_convert()), dbesc(normalise_link($profile)));

	if (($gcontacts[0]["generation"] == 0))
		q("UPDATE `gcontact` SET `generation` = 9 WHERE `nurl` = '%s'",
			dbesc(normalise_link($profile)));

	return($last_updated);
}

function poco_do_update($created, $updated, $last_failure,  $last_contact) {
	$now = strtotime(datetime_convert());

	if ($updated > $last_contact)
		$contact_time = strtotime($updated);
	else
		$contact_time = strtotime($last_contact);

	$failure_time = strtotime($last_failure);
	$created_time = strtotime($created);

	// If there is no "created" time then use the current time
	if ($created_time <= 0)
		$created_time = $now;

	// If the last contact was less than 24 hours then don't update
	if (($now - $contact_time) < (60 * 60 * 24))
		return false;

	// If the last failure was less than 24 hours then don't update
	if (($now - $failure_time) < (60 * 60 * 24))
		return false;

	// If the last contact was less than a week ago and the last failure is older than a week then don't update
	//if ((($now - $contact_time) < (60 * 60 * 24 * 7)) AND ($contact_time > $failure_time))
	//	return false;

	// If the last contact time was more than a week ago and the contact was created more than a week ago, then only try once a week
	if ((($now - $contact_time) > (60 * 60 * 24 * 7)) AND (($now - $created_time) > (60 * 60 * 24 * 7)) AND (($now - $failure_time) < (60 * 60 * 24 * 7)))
		return false;

	// If the last contact time was more than a month ago and the contact was created more than a month ago, then only try once a month
	if ((($now - $contact_time) > (60 * 60 * 24 * 30)) AND (($now - $created_time) > (60 * 60 * 24 * 30)) AND (($now - $failure_time) < (60 * 60 * 24 * 30)))
		return false;

	return true;
}

function poco_to_boolean($val) {
	if (($val == "true") OR ($val == 1))
		return(true);
	if (($val == "false") OR ($val == 0))
		return(false);

	return ($val);
}

function poco_check_server($server_url, $network = "", $force = false) {

	if ($server_url == "")
		return false;

	$servers = q("SELECT * FROM `gserver` WHERE `nurl` = '%s'", dbesc(normalise_link($server_url)));
	if ($servers) {

		if ($servers[0]["created"] == "0000-00-00 00:00:00")
			q("UPDATE `gserver` SET `created` = '%s' WHERE `nurl` = '%s'",
				dbesc(datetime_convert()), dbesc(normalise_link($server_url)));

		$poco = $servers[0]["poco"];
		$noscrape = $servers[0]["noscrape"];

		if ($network == "")
			$network = $servers[0]["network"];

		$last_contact = $servers[0]["last_contact"];
		$last_failure = $servers[0]["last_failure"];
		$version = $servers[0]["version"];
		$platform = $servers[0]["platform"];
		$site_name = $servers[0]["site_name"];
		$info = $servers[0]["info"];
		$register_policy = $servers[0]["register_policy"];

		if (!$force AND !poco_do_update($servers[0]["created"], "", $last_failure, $last_contact)) {
			logger("Use cached data for server ".$server_url, LOGGER_DEBUG);
			return ($last_contact >= $last_failure);
		}
	} else {
		$poco = "";
		$noscrape = "";
		$version = "";
		$platform = "";
		$site_name = "";
		$info = "";
		$register_policy = -1;

		$last_contact = "0000-00-00 00:00:00";
		$last_failure = "0000-00-00 00:00:00";
	}
	logger("Server ".$server_url." is outdated or unknown. Start discovery. Force: ".$force." Created: ".$servers[0]["created"]." Failure: ".$last_failure." Contact: ".$last_contact, LOGGER_DEBUG);

	$failure = false;
	$orig_last_failure = $last_failure;

	// Check if the page is accessible via SSL.
	$server_url = str_replace("http://", "https://", $server_url);
	$serverret = z_fetch_url($server_url."/.well-known/host-meta");

	// Maybe the page is unencrypted only?
	$xmlobj = @simplexml_load_string($serverret["body"],'SimpleXMLElement',0, "http://docs.oasis-open.org/ns/xri/xrd-1.0");
	if (!$serverret["success"] OR ($serverret["body"] == "") OR (@sizeof($xmlobj) == 0) OR !is_object($xmlobj)) {
		$server_url = str_replace("https://", "http://", $server_url);
		$serverret = z_fetch_url($server_url."/.well-known/host-meta");

		$xmlobj = @simplexml_load_string($serverret["body"],'SimpleXMLElement',0, "http://docs.oasis-open.org/ns/xri/xrd-1.0");
	}

	if (!$serverret["success"] OR ($serverret["body"] == "") OR (sizeof($xmlobj) == 0) OR !is_object($xmlobj)) {
		// Workaround for bad configured servers (known nginx problem)
		if ($serverret["debug"]["http_code"] != "403") {
			$last_failure = datetime_convert();
			$failure = true;
		}
	} elseif ($network == NETWORK_DIASPORA)
		$last_contact = datetime_convert();

	if (!$failure) {
		// Test for Diaspora
		$serverret = z_fetch_url($server_url);

		$lines = explode("\n",$serverret["header"]);
		if(count($lines))
			foreach($lines as $line) {
				$line = trim($line);
				if(stristr($line,'X-Diaspora-Version:')) {
					$platform = "Diaspora";
					$version = trim(str_replace("X-Diaspora-Version:", "", $line));
					$version = trim(str_replace("x-diaspora-version:", "", $version));
					$network = NETWORK_DIASPORA;
				}
			}
	}

	if (!$failure) {
		// Test for Statusnet
		// Will also return data for Friendica and GNU Social - but it will be overwritten later
		// The "not implemented" is a special treatment for really, really old Friendica versions
		$serverret = z_fetch_url($server_url."/api/statusnet/version.json");
		if ($serverret["success"] AND ($serverret["body"] != '{"error":"not implemented"}') AND ($serverret["body"] != '') AND (strlen($serverret["body"]) < 250)) {
			$platform = "StatusNet";
			$version = trim($serverret["body"], '"');
			$network = NETWORK_OSTATUS;
		}

		// Test for GNU Social
		$serverret = z_fetch_url($server_url."/api/gnusocial/version.json");
		if ($serverret["success"] AND ($serverret["body"] != '{"error":"not implemented"}') AND ($serverret["body"] != '') AND (strlen($serverret["body"]) < 250)) {
			$platform = "GNU Social";
			$version = trim($serverret["body"], '"');
			$network = NETWORK_OSTATUS;
		}

		$serverret = z_fetch_url($server_url."/api/statusnet/config.json");
		if ($serverret["success"]) {
			$data = json_decode($serverret["body"]);

			if (isset($data->site->server)) {
				$last_contact = datetime_convert();

				if (isset($data->site->hubzilla)) {
					$platform = $data->site->hubzilla->PLATFORM_NAME;
					$version = $data->site->hubzilla->RED_VERSION;
					$network = NETWORK_DIASPORA;
				}
				if (isset($data->site->redmatrix)) {
					if (isset($data->site->redmatrix->PLATFORM_NAME))
						$platform = $data->site->redmatrix->PLATFORM_NAME;
					elseif (isset($data->site->redmatrix->RED_PLATFORM))
						$platform = $data->site->redmatrix->RED_PLATFORM;

					$version = $data->site->redmatrix->RED_VERSION;
					$network = NETWORK_DIASPORA;
				}
				if (isset($data->site->friendica)) {
					$platform = $data->site->friendica->FRIENDICA_PLATFORM;
					$version = $data->site->friendica->FRIENDICA_VERSION;
					$network = NETWORK_DFRN;
				}

				$site_name = $data->site->name;

				$data->site->closed = poco_to_boolean($data->site->closed);
				$data->site->private = poco_to_boolean($data->site->private);
				$data->site->inviteonly = poco_to_boolean($data->site->inviteonly);

				if (!$data->site->closed AND !$data->site->private and $data->site->inviteonly)
					$register_policy = REGISTER_APPROVE;
				elseif (!$data->site->closed AND !$data->site->private)
					$register_policy = REGISTER_OPEN;
				else
					$register_policy = REGISTER_CLOSED;
			}
		}
	}

	// Query statistics.json. Optional package for Diaspora, Friendica and Redmatrix
	if (!$failure) {
		$serverret = z_fetch_url($server_url."/statistics.json");
		if ($serverret["success"]) {
			$data = json_decode($serverret["body"]);
			if ($version == "")
				$version = $data->version;

			$site_name = $data->name;

			if (isset($data->network) AND ($platform == ""))
				$platform = $data->network;

			if ($platform == "Diaspora")
				$network = NETWORK_DIASPORA;

			if ($data->registrations_open)
				$register_policy = REGISTER_OPEN;
			else
				$register_policy = REGISTER_CLOSED;

			if (isset($data->version))
				$last_contact = datetime_convert();
		}
	}

	// Check for noscrape
	// Friendica servers could be detected as OStatus servers
	if (!$failure AND in_array($network, array(NETWORK_DFRN, NETWORK_OSTATUS))) {
		$serverret = z_fetch_url($server_url."/friendica/json");

		if (!$serverret["success"])
			$serverret = z_fetch_url($server_url."/friendika/json");

		if ($serverret["success"]) {
			$data = json_decode($serverret["body"]);

			if (isset($data->version)) {
				$last_contact = datetime_convert();
				$network = NETWORK_DFRN;

				$noscrape = $data->no_scrape_url;
				$version = $data->version;
				$site_name = $data->site_name;
				$info = $data->info;
				$register_policy_str = $data->register_policy;
				$platform = $data->platform;

				switch ($register_policy_str) {
					case "REGISTER_CLOSED":
						$register_policy = REGISTER_CLOSED;
						break;
					case "REGISTER_APPROVE":
						$register_policy = REGISTER_APPROVE;
						break;
					case "REGISTER_OPEN":
						$register_policy = REGISTER_OPEN;
						break;
				}
			}
		}
	}

	// Look for poco
	if (!$failure) {
		$serverret = z_fetch_url($server_url."/poco");
		if ($serverret["success"]) {
			$data = json_decode($serverret["body"]);
			if (isset($data->totalResults)) {
				$poco = $server_url."/poco";
				$last_contact = datetime_convert();
			}
		}
	}

	// Check again if the server exists
	$servers = q("SELECT `nurl` FROM `gserver` WHERE `nurl` = '%s'", dbesc(normalise_link($server_url)));

	if ($servers)
		 q("UPDATE `gserver` SET `url` = '%s', `version` = '%s', `site_name` = '%s', `info` = '%s', `register_policy` = %d, `poco` = '%s', `noscrape` = '%s',
			`network` = '%s', `platform` = '%s', `last_contact` = '%s', `last_failure` = '%s' WHERE `nurl` = '%s'",
			dbesc($server_url),
			dbesc($version),
			dbesc($site_name),
			dbesc($info),
			intval($register_policy),
			dbesc($poco),
			dbesc($noscrape),
			dbesc($network),
			dbesc($platform),
			dbesc($last_contact),
			dbesc($last_failure),
			dbesc(normalise_link($server_url))
		);
	else
		q("INSERT INTO `gserver` (`url`, `nurl`, `version`, `site_name`, `info`, `register_policy`, `poco`, `noscrape`, `network`, `platform`, `created`, `last_contact`, `last_failure`)
					VALUES ('%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
				dbesc($server_url),
				dbesc(normalise_link($server_url)),
				dbesc($version),
				dbesc($site_name),
				dbesc($info),
				intval($register_policy),
				dbesc($poco),
				dbesc($noscrape),
				dbesc($network),
				dbesc($platform),
				dbesc(datetime_convert()),
				dbesc($last_contact),
				dbesc($last_failure),
				dbesc(datetime_convert())
		);

	logger("End discovery for server ".$server_url, LOGGER_DEBUG);

	return !$failure;
}

function poco_contact_from_body($body, $created, $cid, $uid) {
	preg_replace_callback("/\[share(.*?)\].*?\[\/share\]/ism",
		function ($match) use ($created, $cid, $uid){
			return(sub_poco_from_share($match, $created, $cid, $uid));
		}, $body);
}

function sub_poco_from_share($share, $created, $cid, $uid) {
	$profile = "";
	preg_match("/profile='(.*?)'/ism", $share[1], $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	preg_match('/profile="(.*?)"/ism', $share[1], $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	if ($profile == "")
		return;

	logger("prepare poco_check for profile ".$profile, LOGGER_DEBUG);
	poco_check($profile, "", "", "", "", "", "", "", "", $created, 3, $cid, $uid);
}

function poco_store($item) {

	// Isn't it public?
	if ($item['private'])
		return;

	// Or is it from a network where we don't store the global contacts?
	if (!in_array($item["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, NETWORK_STATUSNET, "")))
		return;

	// Is it a global copy?
	$store_gcontact = ($item["uid"] == 0);

	// Is it a comment on a global copy?
	if (!$store_gcontact AND ($item["uri"] != $item["parent-uri"])) {
		$q = q("SELECT `id` FROM `item` WHERE `uri`='%s' AND `uid` = 0", $item["parent-uri"]);
		$store_gcontact = count($q);
	}

	if (!$store_gcontact)
		return;

	// "3" means: We don't know this contact directly (Maybe a reshared item)
	$generation = 3;
	$network = "";
	$profile_url = $item["author-link"];

	// Is it a user from our server?
	$q = q("SELECT `id` FROM `contact` WHERE `self` AND `nurl` = '%s' LIMIT 1",
		dbesc(normalise_link($item["author-link"])));
	if (count($q)) {
		logger("Our user (generation 1): ".$item["author-link"], LOGGER_DEBUG);
		$generation = 1;
		$network = NETWORK_DFRN;
	} else { // Is it a contact from a user on our server?
		$q = q("SELECT `network`, `url` FROM `contact` WHERE `uid` != 0 AND `network` != ''
			AND (`nurl` = '%s' OR `alias` IN ('%s', '%s')) AND `network` != '%s' LIMIT 1",
			dbesc(normalise_link($item["author-link"])),
			dbesc(normalise_link($item["author-link"])),
			dbesc($item["author-link"]),
			dbesc(NETWORK_STATUSNET));
		if (count($q)) {
			$generation = 2;
			$network = $q[0]["network"];
			$profile_url = $q[0]["url"];
			logger("Known contact (generation 2): ".$profile_url, LOGGER_DEBUG);
		}
	}

	if ($generation == 3)
		logger("Unknown contact (generation 3): ".$item["author-link"], LOGGER_DEBUG);

	poco_check($profile_url, $item["author-name"], $network, $item["author-avatar"], "", "", "", "", "", $item["received"], $generation, $item["contact-id"], $item["uid"]);

	// Maybe its a body with a shared item? Then extract a global contact from it.
	poco_contact_from_body($item["body"], $item["received"], $item["contact-id"], $item["uid"]);
}

function count_common_friends($uid,$cid) {

	$r = q("SELECT count(*) as `total`
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		WHERE `glink`.`cid` = %d AND `glink`.`uid` = %d AND
		((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`))
		AND `gcontact`.`nurl` IN (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 and id != %d ) ",
		intval($cid),
		intval($uid),
		intval($uid),
		intval($cid)
	);

//	logger("count_common_friends: $uid $cid {$r[0]['total']}");
	if(count($r))
		return $r[0]['total'];
	return 0;

}


function common_friends($uid,$cid,$start = 0,$limit=9999,$shuffle = false) {

	if($shuffle)
		$sql_extra = " order by rand() ";
	else
		$sql_extra = " order by `gcontact`.`name` asc ";

	$r = q("SELECT `gcontact`.*, `contact`.`id` AS `cid`
		FROM `glink`
		INNER JOIN `gcontact` ON `glink`.`gcid` = `gcontact`.`id`
		INNER JOIN `contact` ON `gcontact`.`nurl` = `contact`.`nurl`
		WHERE `glink`.`cid` = %d and `glink`.`uid` = %d
			AND `contact`.`uid` = %d AND `contact`.`self` = 0 AND `contact`.`blocked` = 0
			AND `contact`.`hidden` = 0 AND `contact`.`id` != %d
			AND ((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`))
			$sql_extra LIMIT %d, %d",
		intval($cid),
		intval($uid),
		intval($uid),
		intval($cid),
		intval($start),
		intval($limit)
	);

	return $r;

}


function count_common_friends_zcid($uid,$zcid) {

	$r = q("SELECT count(*) as `total`
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`zcid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 ) ",
		intval($zcid),
		intval($uid)
	);

	if(count($r))
		return $r[0]['total'];
	return 0;

}

function common_friends_zcid($uid,$zcid,$start = 0, $limit = 9999,$shuffle = false) {

	if($shuffle)
		$sql_extra = " order by rand() ";
	else
		$sql_extra = " order by `gcontact`.`name` asc ";

	$r = q("SELECT `gcontact`.*
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`zcid` = %d
		and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 ) 
		$sql_extra limit %d, %d",
		intval($zcid),
		intval($uid),
		intval($start),
		intval($limit)
	);

	return $r;

}


function count_all_friends($uid,$cid) {

	$r = q("SELECT count(*) as `total`
		FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		where `glink`.`cid` = %d and `glink`.`uid` = %d AND
		((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`))",
		intval($cid),
		intval($uid)
	);

	if(count($r))
		return $r[0]['total'];
	return 0;

}


function all_friends($uid,$cid,$start = 0, $limit = 80) {

	$r = q("SELECT `gcontact`.*, `contact`.`id` AS `cid`
		FROM `glink`
		INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
		LEFT JOIN `contact` ON `contact`.`nurl` = `gcontact`.`nurl` AND `contact`.`uid` = %d
		WHERE `glink`.`cid` = %d AND `glink`.`uid` = %d AND
		((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`))
		ORDER BY `gcontact`.`name` ASC LIMIT %d, %d ",
		intval($uid),
		intval($cid),
		intval($uid),
		intval($start),
		intval($limit)
	);

	return $r;
}



function suggestion_query($uid, $start = 0, $limit = 80) {

	if(! $uid)
		return array();

	$network = array(NETWORK_DFRN);

	if (get_config('system','diaspora_enabled'))
		$network[] = NETWORK_DIASPORA;

	if (!get_config('system','ostatus_disabled'))
		$network[] = NETWORK_OSTATUS;

	$sql_network = implode("', '", $network);
	//$sql_network = "'".$sql_network."', ''";
	$sql_network = "'".$sql_network."'";

	$r = q("SELECT count(glink.gcid) as `total`, gcontact.* from gcontact
		INNER JOIN glink on glink.gcid = gcontact.id
		where uid = %d and not gcontact.nurl in ( select nurl from contact where uid = %d )
		and not gcontact.name in ( select name from contact where uid = %d )
		and not gcontact.id in ( select gcid from gcign where uid = %d )
		AND `gcontact`.`updated` != '0000-00-00 00:00:00'
		AND `gcontact`.`last_contact` >= `gcontact`.`last_failure`
		AND `gcontact`.`network` IN (%s)
		group by glink.gcid order by gcontact.updated desc,total desc limit %d, %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		intval($uid),
		$sql_network,
		intval($start),
		intval($limit)
	);

	if(count($r) && count($r) >= ($limit -1))
		return $r;

	$r2 = q("SELECT gcontact.* from gcontact
		INNER JOIN glink on glink.gcid = gcontact.id
		where glink.uid = 0 and glink.cid = 0 and glink.zcid = 0 and not gcontact.nurl in ( select nurl from contact where uid = %d )
		and not gcontact.name in ( select name from contact where uid = %d )
		and not gcontact.id in ( select gcid from gcign where uid = %d )
		AND `gcontact`.`updated` != '0000-00-00 00:00:00'
		AND `gcontact`.`network` IN (%s)
		order by rand() limit %d, %d ",
		intval($uid),
		intval($uid),
		intval($uid),
		$sql_network,
		intval($start),
		intval($limit)
	);

	$list = array();
	foreach ($r2 AS $suggestion)
		$list[$suggestion["nurl"]] = $suggestion;

	foreach ($r AS $suggestion)
		$list[$suggestion["nurl"]] = $suggestion;

	return $list;
}

function update_suggestions() {

	$a = get_app();

	$done = array();

	// To-Do: Check if it is really neccessary to poll the own server
	poco_load(0,0,0,$a->get_baseurl() . '/poco');

	$done[] = $a->get_baseurl() . '/poco';

	if(strlen(get_config('system','directory'))) {
		$x = fetch_url(get_server()."/pubsites");
		if($x) {
			$j = json_decode($x);
			if($j->entries) {
				foreach($j->entries as $entry) {

					poco_check_server($entry->url);

					$url = $entry->url . '/poco';
					if(! in_array($url,$done))
						poco_load(0,0,0,$entry->url . '/poco');
				}
			}
		}
	}

	// Query your contacts from Friendica and Redmatrix/Hubzilla for their contacts
	$r = q("SELECT DISTINCT(`poco`) AS `poco` FROM `contact` WHERE `network` IN ('%s', '%s')",
		dbesc(NETWORK_DFRN), dbesc(NETWORK_DIASPORA)
	);

	if(count($r)) {
		foreach($r as $rr) {
			$base = substr($rr['poco'],0,strrpos($rr['poco'],'/'));
			if(! in_array($base,$done))
				poco_load(0,0,0,$base);
		}
	}
}

function poco_discover_federation() {
	$last = get_config('poco','last_federation_discovery');

	if($last) {
		$next = $last + (24 * 60 * 60);
		if($next > time())
			return;
	}

	$serverdata = fetch_url("http://the-federation.info/pods.json");

	if (!$serverdata)
		return;

	$servers = json_decode($serverdata);

	foreach($servers->pods AS $server)
		poco_check_server("https://".$server->host);

	set_config('poco','last_federation_discovery', time());

}

function poco_discover($complete = false) {

	// Update the server list
	poco_discover_federation();

	$no_of_queries = 5;

	$requery_days = intval(get_config("system", "poco_requery_days"));

	if ($requery_days == 0)
		$requery_days = 7;

	$last_update = date("c", time() - (60 * 60 * 24 * $requery_days));

	$r = q("SELECT `poco`, `nurl`, `url`, `network` FROM `gserver` WHERE `last_contact` >= `last_failure` AND `poco` != '' AND `last_poco_query` < '%s' ORDER BY RAND()", dbesc($last_update));
	if ($r)
		foreach ($r AS $server) {

			if (!poco_check_server($server["url"], $server["network"])) {
				// The server is not reachable? Okay, then we will try it later
				q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", dbesc(datetime_convert()), dbesc($server["nurl"]));
				continue;
			}

			// Fetch all users from the other server
			$url = $server["poco"]."/?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,generation";

			logger("Fetch all users from the server ".$server["nurl"], LOGGER_DEBUG);

			$retdata = z_fetch_url($url);
			if ($retdata["success"]) {
				$data = json_decode($retdata["body"]);

				poco_discover_server($data, 2);

				if (get_config('system','poco_discovery') > 1) {

					$timeframe = get_config('system','poco_discovery_since');
					if ($timeframe == 0)
						$timeframe = 30;

					$updatedSince = date("Y-m-d H:i:s", time() - $timeframe * 86400);

					// Fetch all global contacts from the other server (Not working with Redmatrix and Friendica versions before 3.3)
					$url = $server["poco"]."/@global?updatedSince=".$updatedSince."&fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,generation";

					$success = false;

					$retdata = z_fetch_url($url);
					if ($retdata["success"]) {
						logger("Fetch all global contacts from the server ".$server["nurl"], LOGGER_DEBUG);
						$success = poco_discover_server(json_decode($retdata["body"]));
					}

					if (!$success AND (get_config('system','poco_discovery') > 2)) {
						logger("Fetch contacts from users of the server ".$server["nurl"], LOGGER_DEBUG);
						poco_discover_server_users($data, $server);
					}
				}

				q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", dbesc(datetime_convert()), dbesc($server["nurl"]));
				if (!$complete AND (--$no_of_queries == 0))
					break;
			} else {
				// If the server hadn't replied correctly, then force a sanity check
				poco_check_server($server["url"], $server["network"], true);

				// If we couldn't reach the server, we will try it some time later
				q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", dbesc(datetime_convert()), dbesc($server["nurl"]));
			}
		}
}

function poco_discover_server_users($data, $server) {

	if (!isset($data->entry))
		return;

	foreach ($data->entry AS $entry) {
		$username = "";
		if (isset($entry->urls)) {
			foreach($entry->urls as $url)
				if($url->type == 'profile') {
					$profile_url = $url->value;
					$urlparts = parse_url($profile_url);
					$username = end(explode("/", $urlparts["path"]));
				}
		}
		if ($username != "") {
			logger("Fetch contacts for the user ".$username." from the server ".$server["nurl"], LOGGER_DEBUG);

			// Fetch all contacts from a given user from the other server
			$url = $server["poco"]."/".$username."/?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,generation";

			$retdata = z_fetch_url($url);
			if ($retdata["success"])
				poco_discover_server(json_decode($retdata["body"]), 3);
		}
	}
}

function poco_discover_server($data, $default_generation = 0) {

	if (!isset($data->entry) OR !count($data->entry))
		return false;

	$success = false;

	foreach ($data->entry AS $entry) {
		$profile_url = '';
		$profile_photo = '';
		$connect_url = '';
		$name = '';
		$network = '';
		$updated = '0000-00-00 00:00:00';
		$location = '';
		$about = '';
		$keywords = '';
		$gender = '';
		$generation = $default_generation;

		$name = $entry->displayName;

		if(isset($entry->urls)) {
			foreach($entry->urls as $url) {
				if($url->type == 'profile') {
					$profile_url = $url->value;
					continue;
				}
				if($url->type == 'webfinger') {
					$connect_url = str_replace('acct:' , '', $url->value);
					continue;
				}
			}
		}

		if(isset($entry->photos)) {
			foreach($entry->photos as $photo) {
				if($photo->type == 'profile') {
					$profile_photo = $photo->value;
					continue;
				}
			}
		}

		if(isset($entry->updated))
			$updated = date("Y-m-d H:i:s", strtotime($entry->updated));

		if(isset($entry->network))
			$network = $entry->network;

		if(isset($entry->currentLocation))
			$location = $entry->currentLocation;

		if(isset($entry->aboutMe))
			$about = html2bbcode($entry->aboutMe);

		if(isset($entry->gender))
			$gender = $entry->gender;

		if(isset($entry->generation) AND ($entry->generation > 0))
			$generation = ++$entry->generation;

		if(isset($entry->tags))
			foreach($entry->tags as $tag)
				$keywords = implode(", ", $tag);

		if ($generation > 0) {
			$success = true;

			logger("Store profile ".$profile_url, LOGGER_DEBUG);
			poco_check($profile_url, $name, $network, $profile_photo, $about, $location, $gender, $keywords, $connect_url, $updated, $generation, 0, 0, 0);
			logger("Done for profile ".$profile_url, LOGGER_DEBUG);
		}
	}
	return $success;
}
?>
