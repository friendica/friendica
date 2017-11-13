<?php
/**
 * @file src/Protocol/PortableContact.php
 */
namespace Friendica\Protocol;

use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\GlobalContact;
use Friendica\Network\Probe;

use dba;
use DOMDocument;
use DomXPath;

require_once 'include/datetime.php';
require_once 'include/network.php';
require_once 'include/html2bbcode.php';
require_once 'include/Contact.php';
require_once 'include/Photo.php';

class PortableContact
{
	/**
	 * @brief Fetch POCO data
	 *
	 * @param integer $cid  Contact ID
	 * @param integer $uid  User ID
	 * @param integer $zcid Global Contact ID
	 * @param integer $url  POCO address that should be polled
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
	public static function loadWorker($cid, $uid = 0, $zcid = 0, $url = null)
	{
		// Call the function "loadWorker" via the worker
		Worker::add(PRIORITY_LOW, "discover_poco", "load", (int)$cid, (int)$uid, (int)$zcid, $url);
	}
	
	/**
	 * @brief Fetch POCO data from the worker
	 *
	 * @param integer $cid  Contact ID
	 * @param integer $uid  User ID
	 * @param integer $zcid Global Contact ID
	 * @param integer $url  POCO address that should be polled
	 *
	 */
	public static function load($cid, $uid, $zcid, $url)
	{
		$a = get_app();

		if ($cid) {
			if ((! $url) || (! $uid)) {
				$r = q(
					"select `poco`, `uid` from `contact` where `id` = %d limit 1",
					intval($cid)
				);
				if (DBM::is_result($r)) {
					$url = $r[0]['poco'];
					$uid = $r[0]['uid'];
				}
			}
			if (! $uid) {
				return;
			}
		}

		if (! $url) {
			return;
		}

		$url = $url . (($uid) ? '/@me/@all?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,contactType,generation' : '?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,contactType,generation') ;

		logger('load: ' . $url, LOGGER_DEBUG);

		$s = fetch_url($url);

		logger('load: returns ' . $s, LOGGER_DATA);

		logger('load: return code: ' . $a->get_curl_code(), LOGGER_DEBUG);

		if (($a->get_curl_code() > 299) || (! $s)) {
			return;
		}

		$j = json_decode($s);

		logger('load: json: ' . print_r($j, true), LOGGER_DATA);

		if (! isset($j->entry)) {
			return;
		}

		$total = 0;
		foreach ($j->entry as $entry) {
			$total ++;
			$profile_url = '';
			$profile_photo = '';
			$connect_url = '';
			$name = '';
			$network = '';
			$updated = NULL_DATE;
			$location = '';
			$about = '';
			$keywords = '';
			$gender = '';
			$contact_type = -1;
			$generation = 0;

			$name = $entry->displayName;

			if (isset($entry->urls)) {
				foreach ($entry->urls as $url) {
					if ($url->type == 'profile') {
						$profile_url = $url->value;
						continue;
					}
					if ($url->type == 'webfinger') {
						$connect_url = str_replace('acct:', '', $url->value);
						continue;
					}
				}
			}
			if (isset($entry->photos)) {
				foreach ($entry->photos as $photo) {
					if ($photo->type == 'profile') {
						$profile_photo = $photo->value;
						continue;
					}
				}
			}

			if (isset($entry->updated)) {
				$updated = date("Y-m-d H:i:s", strtotime($entry->updated));
			}

			if (isset($entry->network)) {
				$network = $entry->network;
			}

			if (isset($entry->currentLocation)) {
				$location = $entry->currentLocation;
			}

			if (isset($entry->aboutMe)) {
				$about = html2bbcode($entry->aboutMe);
			}

			if (isset($entry->gender)) {
				$gender = $entry->gender;
			}

			if (isset($entry->generation) && ($entry->generation > 0)) {
				$generation = ++$entry->generation;
			}

			if (isset($entry->tags)) {
				foreach ($entry->tags as $tag) {
					$keywords = implode(", ", $tag);
				}
			}

			if (isset($entry->contactType) && ($entry->contactType >= 0)) {
				$contact_type = $entry->contactType;
			}

			$gcontact = array("url" => $profile_url,
					"name" => $name,
					"network" => $network,
					"photo" => $profile_photo,
					"about" => $about,
					"location" => $location,
					"gender" => $gender,
					"keywords" => $keywords,
					"connect" => $connect_url,
					"updated" => $updated,
					"contact-type" => $contact_type,
					"generation" => $generation);

			try {
				$gcontact = GlobalContact::sanitize($gcontact);
				$gcid = GlobalContact::update($gcontact);

				GlobalContact::link($gcid, $uid, $cid, $zcid);
			} catch (Exception $e) {
				logger($e->getMessage(), LOGGER_DEBUG);
			}
		}
		logger("load: loaded $total entries", LOGGER_DEBUG);

		q(
			"DELETE FROM `glink` WHERE `cid` = %d AND `uid` = %d AND `zcid` = %d AND `updated` < UTC_TIMESTAMP - INTERVAL 2 DAY",
			intval($cid),
			intval($uid),
			intval($zcid)
		);
	}
	
	public static function reachable($profile, $server = "", $network = "", $force = false)
	{
		if ($server == "") {
			$server = self::detectServer($profile);
		}
	
		if ($server == "") {
			return true;
		}
	
		return self::checkServer($server, $network, $force);
	}

	public static function detectServer($profile)
	{
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
	
		// Mastodon
		if ($server_url == "") {
			$mastodon = preg_replace("=(https?://)(.*)/users/(.*)=ism", "$1$2", $profile);
			if ($mastodon != $profile) {
				$server_url = $mastodon;
				$network = NETWORK_OSTATUS;
			}
		}
	
		// Numeric OStatus variant
		if ($server_url == "") {
			$ostatus = preg_replace("=(https?://)(.*)/user/(.*)=ism", "$1$2", $profile);
			if ($ostatus != $profile) {
				$server_url = $ostatus;
				$network = NETWORK_OSTATUS;
			}
		}
	
		// Wild guess
		if ($server_url == "") {
			$base = preg_replace("=(https?://)(.*?)/(.*)=ism", "$1$2", $profile);
			if ($base != $profile) {
				$server_url = $base;
				$network = NETWORK_PHANTOM;
			}
		}
	
		if ($server_url == "") {
			return "";
		}
	
		$r = q(
			"SELECT `id` FROM `gserver` WHERE `nurl` = '%s' AND `last_contact` > `last_failure`",
			dbesc(normalise_link($server_url))
		);

		if (DBM::is_result($r)) {
			return $server_url;
		}
	
		// Fetch the host-meta to check if this really is a server
		$serverret = z_fetch_url($server_url."/.well-known/host-meta");
		if (!$serverret["success"]) {
			return "";
		}
	
		return $server_url;
	}

	public static function lastUpdated($profile, $force = false)
	{
		$gcontacts = q(
			"SELECT * FROM `gcontact` WHERE `nurl` = '%s'",
			dbesc(normalise_link($profile))
		);
	
		if (!DBM::is_result($gcontacts)) {
			return false;
		}
	
		$contact = array("url" => $profile);
	
		if ($gcontacts[0]["created"] <= NULL_DATE) {
			$contact['created'] = datetime_convert();
		}
	
		if ($force) {
			$server_url = normalise_link(self::detectServer($profile));
		}
	
		if (($server_url == '') && ($gcontacts[0]["server_url"] != "")) {
			$server_url = $gcontacts[0]["server_url"];
		}
	
		if (!$force && (($server_url == '') || ($gcontacts[0]["server_url"] == $gcontacts[0]["nurl"]))) {
			$server_url = normalise_link(self::detectServer($profile));
		}
	
		if (!in_array($gcontacts[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_FEED, NETWORK_OSTATUS, ""))) {
			logger("Profile ".$profile.": Network type ".$gcontacts[0]["network"]." can't be checked", LOGGER_DEBUG);
			return false;
		}
	
		if ($server_url != "") {
			if (!self::checkServer($server_url, $gcontacts[0]["network"], $force)) {
				if ($force) {
					q(
						"UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
						dbesc(datetime_convert()),
						dbesc(normalise_link($profile))
					);
				}
	
				logger("Profile ".$profile.": Server ".$server_url." wasn't reachable.", LOGGER_DEBUG);
				return false;
			}
			$contact['server_url'] = $server_url;
		}
	
		if (in_array($gcontacts[0]["network"], array("", NETWORK_FEED))) {
			$server = q(
				"SELECT `network` FROM `gserver` WHERE `nurl` = '%s' AND `network` != ''",
				dbesc(normalise_link($server_url))
			);
	
			if ($server) {
				$contact['network'] = $server[0]["network"];
			} else {
				return false;
			}
		}
	
		// noscrape is really fast so we don't cache the call.
		if (($server_url != "") && ($gcontacts[0]["nick"] != "")) {
			//  Use noscrape if possible
			$server = q("SELECT `noscrape`, `network` FROM `gserver` WHERE `nurl` = '%s' AND `noscrape` != ''", dbesc(normalise_link($server_url)));
	
			if ($server) {
				$noscraperet = z_fetch_url($server[0]["noscrape"]."/".$gcontacts[0]["nick"]);
	
				if ($noscraperet["success"] && ($noscraperet["body"] != "")) {
	
					$noscrape = json_decode($noscraperet["body"], true);
	
					if (is_array($noscrape)) {
						$contact["network"] = $server[0]["network"];
	
						if (isset($noscrape["fn"])) {
							$contact["name"] = $noscrape["fn"];
						}
						if (isset($noscrape["comm"])) {
							$contact["community"] = $noscrape["comm"];
						}
						if (isset($noscrape["tags"])) {
							$keywords = implode(" ", $noscrape["tags"]);
							if ($keywords != "") {
								$contact["keywords"] = $keywords;
							}
						}
	
						$location = formatted_location($noscrape);
						if ($location) {
							$contact["location"] = $location;
						}
						if (isset($noscrape["dfrn-notify"])) {
							$contact["notify"] = $noscrape["dfrn-notify"];
						}
						// Remove all fields that are not present in the gcontact table
						unset($noscrape["fn"]);
						unset($noscrape["key"]);
						unset($noscrape["homepage"]);
						unset($noscrape["comm"]);
						unset($noscrape["tags"]);
						unset($noscrape["locality"]);
						unset($noscrape["region"]);
						unset($noscrape["country-name"]);
						unset($noscrape["contacts"]);
						unset($noscrape["dfrn-request"]);
						unset($noscrape["dfrn-confirm"]);
						unset($noscrape["dfrn-notify"]);
						unset($noscrape["dfrn-poll"]);
	
						// Set the date of the last contact
						/// @todo By now the function "update_gcontact" doesn't work with this field
						//$contact["last_contact"] = datetime_convert();
	
						$contact = array_merge($contact, $noscrape);
	
						GlobalContact::update($contact);
	
						if (trim($noscrape["updated"]) != "") {
							q(
								"UPDATE `gcontact` SET `last_contact` = '%s' WHERE `nurl` = '%s'",
								dbesc(datetime_convert()),
								dbesc(normalise_link($profile))
							);
	
							logger("Profile ".$profile." was last updated at ".$noscrape["updated"]." (noscrape)", LOGGER_DEBUG);
	
							return $noscrape["updated"];
						}
					}
				}
			}
		}
	
		// If we only can poll the feed, then we only do this once a while
		if (!$force && !self::updateNeeded($gcontacts[0]["created"], $gcontacts[0]["updated"], $gcontacts[0]["last_failure"], $gcontacts[0]["last_contact"])) {
			logger("Profile ".$profile." was last updated at ".$gcontacts[0]["updated"]." (cached)", LOGGER_DEBUG);
	
			GlobalContact::update($contact);
			return $gcontacts[0]["updated"];
		}
	
		$data = Probe::uri($profile);
	
		// Is the profile link the alternate OStatus link notation? (http://domain.tld/user/4711)
		// Then check the other link and delete this one
		if (($data["network"] == NETWORK_OSTATUS) && self::alternateOStatusUrl($profile)
			&& (normalise_link($profile) == normalise_link($data["alias"]))
			&& (normalise_link($profile) != normalise_link($data["url"]))
		) {
			// Delete the old entry
			q("DELETE FROM `gcontact` WHERE `nurl` = '%s'", dbesc(normalise_link($profile)));
			q("DELETE FROM `glink` WHERE `gcid` = %d", intval($gcontacts[0]["id"]));
	
			$gcontact = array_merge($gcontacts[0], $data);
	
			$gcontact["server_url"] = $data["baseurl"];
	
			try {
				$gcontact = GlobalContact::sanitize($gcontact);
				GlobalContact::update($gcontact);
	
				self::lastUpdated($data["url"], $force);
			} catch (Exception $e) {
				logger($e->getMessage(), LOGGER_DEBUG);
			}
	
			logger("Profile ".$profile." was deleted", LOGGER_DEBUG);
			return false;
		}
	
		if (($data["poll"] == "") || (in_array($data["network"], array(NETWORK_FEED, NETWORK_PHANTOM)))) {
			q(
				"UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
				dbesc(datetime_convert()),
				dbesc(normalise_link($profile))
			);
	
			logger("Profile ".$profile." wasn't reachable (profile)", LOGGER_DEBUG);
			return false;
		}
	
		$contact = array_merge($contact, $data);
	
		$contact["server_url"] = $data["baseurl"];
	
		GlobalContact::update($contact);
	
		$feedret = z_fetch_url($data["poll"]);
	
		if (!$feedret["success"]) {
			q(
				"UPDATE `gcontact` SET `last_failure` = '%s' WHERE `nurl` = '%s'",
				dbesc(datetime_convert()),
				dbesc(normalise_link($profile))
			);
	
			logger("Profile ".$profile." wasn't reachable (no feed)", LOGGER_DEBUG);
			return false;
		}
	
		$doc = new DOMDocument();
		@$doc->loadXML($feedret["body"]);
	
		$xpath = new DomXPath($doc);
		$xpath->registerNamespace('atom', "http://www.w3.org/2005/Atom");
	
		$entries = $xpath->query('/atom:feed/atom:entry');
	
		$last_updated = "";
	
		foreach ($entries as $entry) {
			$published = $xpath->query('atom:published/text()', $entry)->item(0)->nodeValue;
			$updated = $xpath->query('atom:updated/text()', $entry)->item(0)->nodeValue;
	
			if ($last_updated < $published)
				$last_updated = $published;
	
			if ($last_updated < $updated)
				$last_updated = $updated;
		}
	
		// Maybe there aren't any entries. Then check if it is a valid feed
		if ($last_updated == "") {
			if ($xpath->query('/atom:feed')->length > 0) {
				$last_updated = NULL_DATE;
			}
		}
		q(
			"UPDATE `gcontact` SET `updated` = '%s', `last_contact` = '%s' WHERE `nurl` = '%s'",
			dbesc(DBM::date($last_updated)),
			dbesc(DBM::date()),
			dbesc(normalise_link($profile))
		);
	
		if (($gcontacts[0]["generation"] == 0)) {
			q(
				"UPDATE `gcontact` SET `generation` = 9 WHERE `nurl` = '%s'",
				dbesc(normalise_link($profile))
			);
		}
	
		logger("Profile ".$profile." was last updated at ".$last_updated, LOGGER_DEBUG);
	
		return($last_updated);
	}

	public static function updateNeeded($created, $updated, $last_failure, $last_contact)
	{
		$now = strtotime(datetime_convert());
	
		if ($updated > $last_contact) {
			$contact_time = strtotime($updated);
		} else {
			$contact_time = strtotime($last_contact);
		}
	
		$failure_time = strtotime($last_failure);
		$created_time = strtotime($created);
	
		// If there is no "created" time then use the current time
		if ($created_time <= 0) {
			$created_time = $now;
		}
	
		// If the last contact was less than 24 hours then don't update
		if (($now - $contact_time) < (60 * 60 * 24)) {
			return false;
		}
	
		// If the last failure was less than 24 hours then don't update
		if (($now - $failure_time) < (60 * 60 * 24)) {
			return false;
		}
	
		// If the last contact was less than a week ago and the last failure is older than a week then don't update
		//if ((($now - $contact_time) < (60 * 60 * 24 * 7)) && ($contact_time > $failure_time))
		//	return false;
	
		// If the last contact time was more than a week ago and the contact was created more than a week ago, then only try once a week
		if ((($now - $contact_time) > (60 * 60 * 24 * 7)) && (($now - $created_time) > (60 * 60 * 24 * 7)) && (($now - $failure_time) < (60 * 60 * 24 * 7))) {
			return false;
		}
	
		// If the last contact time was more than a month ago and the contact was created more than a month ago, then only try once a month
		if ((($now - $contact_time) > (60 * 60 * 24 * 30)) && (($now - $created_time) > (60 * 60 * 24 * 30)) && (($now - $failure_time) < (60 * 60 * 24 * 30))) {
			return false;
		}
	
		return true;
	}

	public static function toBoolean($val)
	{
		if (($val == "true") || ($val == 1)) {
			return true;
		} elseif (($val == "false") || ($val == 0)) {
			return false;
		}
	
		return $val;
	}

	/**
	 * @brief Detect server type (Hubzilla or Friendica) via the poco data
	 *
	 * @param object $data POCO data
	 * @return array Server data
	 */
	public static function detectPocoData($data)
	{
		$server = false;
	
		if (!isset($data->entry)) {
			return false;
		}
	
		if (count($data->entry) == 0) {
			return false;
		}
	
		if (!isset($data->entry[0]->urls)) {
			return false;
		}
	
		if (count($data->entry[0]->urls) == 0) {
			return false;
		}
	
		foreach ($data->entry[0]->urls as $url) {
			if ($url->type == 'zot') {
				$server = array();
				$server["platform"] = 'Hubzilla';
				$server["network"] = NETWORK_DIASPORA;
				return $server;
			}
		}
		return false;
	}

	/**
	 * @brief Detect server type by using the nodeinfo data
	 *
	 * @param string $server_url address of the server
	 * @return array Server data
	 */
	public static function fetchNodeinfo($server_url)
	{
		$serverret = z_fetch_url($server_url."/.well-known/nodeinfo");
		if (!$serverret["success"]) {
			return false;
		}
	
		$nodeinfo = json_decode($serverret['body']);
	
		if (!is_object($nodeinfo)) {
			return false;
		}
	
		if (!is_array($nodeinfo->links)) {
			return false;
		}
	
		$nodeinfo_url = '';
	
		foreach ($nodeinfo->links as $link) {
			if ($link->rel == 'http://nodeinfo.diaspora.software/ns/schema/1.0') {
				$nodeinfo_url = $link->href;
			}
		}
	
		if ($nodeinfo_url == '') {
			return false;
		}
	
		$serverret = z_fetch_url($nodeinfo_url);
		if (!$serverret["success"]) {
			return false;
		}
	
		$nodeinfo = json_decode($serverret['body']);
	
		if (!is_object($nodeinfo)) {
			return false;
		}
	
		$server = array();
	
		$server['register_policy'] = REGISTER_CLOSED;
	
		if (is_bool($nodeinfo->openRegistrations) && $nodeinfo->openRegistrations) {
			$server['register_policy'] = REGISTER_OPEN;
		}
	
		if (is_object($nodeinfo->software)) {
			if (isset($nodeinfo->software->name)) {
				$server['platform'] = $nodeinfo->software->name;
			}
	
			if (isset($nodeinfo->software->version)) {
				$server['version'] = $nodeinfo->software->version;
				// Version numbers on Nodeinfo are presented with additional info, e.g.:
				// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
				$server['version'] = preg_replace("=(.+)-(.{4,})=ism", "$1", $server['version']);
			}
		}
	
		if (is_object($nodeinfo->metadata)) {
			if (isset($nodeinfo->metadata->nodeName)) {
				$server['site_name'] = $nodeinfo->metadata->nodeName;
			}
		}
	
		$diaspora = false;
		$friendica = false;
		$gnusocial = false;
	
		if (is_array($nodeinfo->protocols->inbound)) {
			foreach ($nodeinfo->protocols->inbound as $inbound) {
				if ($inbound == 'diaspora') {
					$diaspora = true;
				}
				if ($inbound == 'friendica') {
					$friendica = true;
				}
				if ($inbound == 'gnusocial') {
					$gnusocial = true;
				}
			}
		}
	
		if ($gnusocial) {
			$server['network'] = NETWORK_OSTATUS;
		}
		if ($diaspora) {
			$server['network'] = NETWORK_DIASPORA;
		}
		if ($friendica) {
			$server['network'] = NETWORK_DFRN;
		}
	
		if (!$server) {
			return false;
		}
	
		return $server;
	}

	/**
	 * @brief Detect server type (Hubzilla or Friendica) via the front page body
	 *
	 * @param string $body Front page of the server
	 * @return array Server data
	 */
	public static function detectServerType($body)
	{
		$server = false;
	
		$doc = new DOMDocument();
		@$doc->loadHTML($body);
		$xpath = new DomXPath($doc);
	
		$list = $xpath->query("//meta[@name]");
	
		foreach ($list as $node) {
			$attr = array();
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attr[$attribute->name] = $attribute->value;
				}
			}
			if ($attr['name'] == 'generator') {
				$version_part = explode(" ", $attr['content']);
				if (count($version_part) == 2) {
					if (in_array($version_part[0], array("Friendika", "Friendica"))) {
						$server = array();
						$server["platform"] = $version_part[0];
						$server["version"] = $version_part[1];
						$server["network"] = NETWORK_DFRN;
					}
				}
			}
		}
	
		if (!$server) {
			$list = $xpath->query("//meta[@property]");
	
			foreach ($list as $node) {
				$attr = array();
				if ($node->attributes->length) {
					foreach ($node->attributes as $attribute) {
						$attr[$attribute->name] = $attribute->value;
					}
				}
				if ($attr['property'] == 'generator' && in_array($attr['content'], array("hubzilla", "BlaBlaNet"))) {
					$server = array();
					$server["platform"] = $attr['content'];
					$server["version"] = "";
					$server["network"] = NETWORK_DIASPORA;
				}
			}
		}
	
		if (!$server) {
			return false;
		}
	
		$server["site_name"] = $xpath->evaluate($element."//head/title/text()", $context)->item(0)->nodeValue;
		return $server;
	}

	public static function checkServer($server_url, $network = "", $force = false)
	{
		// Unify the server address
		$server_url = trim($server_url, "/");
		$server_url = str_replace("/index.php", "", $server_url);
	
		if ($server_url == "") {
			return false;
		}
	
		$servers = q("SELECT * FROM `gserver` WHERE `nurl` = '%s'", dbesc(normalise_link($server_url)));
		if (DBM::is_result($servers)) {
			if ($servers[0]["created"] <= NULL_DATE) {
				q(
					"UPDATE `gserver` SET `created` = '%s' WHERE `nurl` = '%s'",
					dbesc(datetime_convert()),
					dbesc(normalise_link($server_url))
				);
			}
			$poco = $servers[0]["poco"];
			$noscrape = $servers[0]["noscrape"];
	
			if ($network == "") {
				$network = $servers[0]["network"];
			}
	
			$last_contact = $servers[0]["last_contact"];
			$last_failure = $servers[0]["last_failure"];
			$version = $servers[0]["version"];
			$platform = $servers[0]["platform"];
			$site_name = $servers[0]["site_name"];
			$info = $servers[0]["info"];
			$register_policy = $servers[0]["register_policy"];
	
			if (!$force && !self::updateNeeded($servers[0]["created"], "", $last_failure, $last_contact)) {
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
	
			$last_contact = NULL_DATE;
			$last_failure = NULL_DATE;
		}
		logger("Server ".$server_url." is outdated or unknown. Start discovery. Force: ".$force." Created: ".$servers[0]["created"]." Failure: ".$last_failure." Contact: ".$last_contact, LOGGER_DEBUG);
	
		$failure = false;
		$possible_failure = false;
		$orig_last_failure = $last_failure;
		$orig_last_contact = $last_contact;
	
		// Check if the page is accessible via SSL.
		$orig_server_url = $server_url;
		$server_url = str_replace("http://", "https://", $server_url);
	
		// We set the timeout to 20 seconds since this operation should be done in no time if the server was vital
		$serverret = z_fetch_url($server_url."/.well-known/host-meta", false, $redirects, array('timeout' => 20));
	
		// Quit if there is a timeout.
		// But we want to make sure to only quit if we are mostly sure that this server url fits.
		if (DBM::is_result($servers) && ($orig_server_url == $server_url)
			&& ($serverret['errno'] == CURLE_OPERATION_TIMEDOUT)
		) {
			logger("Connection to server ".$server_url." timed out.", LOGGER_DEBUG);
			dba::update('gserver', array('last_failure' => datetime_convert()), array('nurl' => normalise_link($server_url)));
			return false;
		}
	
		// Maybe the page is unencrypted only?
		$xmlobj = @simplexml_load_string($serverret["body"], 'SimpleXMLElement', 0, "http://docs.oasis-open.org/ns/xri/xrd-1.0");
		if (!$serverret["success"] || ($serverret["body"] == "") || (@sizeof($xmlobj) == 0) || !is_object($xmlobj)) {
			$server_url = str_replace("https://", "http://", $server_url);
	
			// We set the timeout to 20 seconds since this operation should be done in no time if the server was vital
			$serverret = z_fetch_url($server_url."/.well-known/host-meta", false, $redirects, array('timeout' => 20));
	
			// Quit if there is a timeout
			if ($serverret['errno'] == CURLE_OPERATION_TIMEDOUT) {
				logger("Connection to server ".$server_url." timed out.", LOGGER_DEBUG);
				dba::update('gserver', array('last_failure' => datetime_convert()), array('nurl' => normalise_link($server_url)));
				return false;
			}
	
			$xmlobj = @simplexml_load_string($serverret["body"], 'SimpleXMLElement', 0, "http://docs.oasis-open.org/ns/xri/xrd-1.0");
		}
	
		if (!$serverret["success"] || ($serverret["body"] == "") || (sizeof($xmlobj) == 0) || !is_object($xmlobj)) {
			// Workaround for bad configured servers (known nginx problem)
			if (!in_array($serverret["debug"]["http_code"], array("403", "404"))) {
				$failure = true;
			}
			$possible_failure = true;
		}
	
		// If the server has no possible failure we reset the cached data
		if (!$possible_failure) {
			$version = "";
			$platform = "";
			$site_name = "";
			$info = "";
			$register_policy = -1;
		}
	
		// Look for poco
		if (!$failure) {
			$serverret = z_fetch_url($server_url."/poco");
			if ($serverret["success"]) {
				$data = json_decode($serverret["body"]);
				if (isset($data->totalResults)) {
					$poco = $server_url."/poco";
					$server = self::detectPocoData($data);
					if ($server) {
						$platform = $server['platform'];
						$network = $server['network'];
						$version = '';
						$site_name = '';
					}
				}
			}
		}
	
		if (!$failure) {
			// Test for Diaspora, Hubzilla, Mastodon or older Friendica servers
			$serverret = z_fetch_url($server_url);
	
			if (!$serverret["success"] || ($serverret["body"] == "")) {
				$failure = true;
			} else {
				$server = self::detectServerType($serverret["body"]);
				if ($server) {
					$platform = $server['platform'];
					$network = $server['network'];
					$version = $server['version'];
					$site_name = $server['site_name'];
				}
	
				$lines = explode("\n", $serverret["header"]);
				if (count($lines)) {
					foreach ($lines as $line) {
						$line = trim($line);
						if (stristr($line, 'X-Diaspora-Version:')) {
							$platform = "Diaspora";
							$version = trim(str_replace("X-Diaspora-Version:", "", $line));
							$version = trim(str_replace("x-diaspora-version:", "", $version));
							$network = NETWORK_DIASPORA;
							$versionparts = explode("-", $version);
							$version = $versionparts[0];
						}
	
						if (stristr($line, 'Server: Mastodon')) {
							$platform = "Mastodon";
							$network = NETWORK_OSTATUS;
						}
					}
				}
			}
		}
	
		if (!$failure && ($poco == "")) {
			// Test for Statusnet
			// Will also return data for Friendica and GNU Social - but it will be overwritten later
			// The "not implemented" is a special treatment for really, really old Friendica versions
			$serverret = z_fetch_url($server_url."/api/statusnet/version.json");
			if ($serverret["success"] && ($serverret["body"] != '{"error":"not implemented"}')
				&& ($serverret["body"] != '') && (strlen($serverret["body"]) < 30)
			) {
				$platform = "StatusNet";
				// Remove junk that some GNU Social servers return
				$version = str_replace(chr(239).chr(187).chr(191), "", $serverret["body"]);
				$version = trim($version, '"');
				$network = NETWORK_OSTATUS;
			}
	
			// Test for GNU Social
			$serverret = z_fetch_url($server_url."/api/gnusocial/version.json");
			if ($serverret["success"] && ($serverret["body"] != '{"error":"not implemented"}')
				&& ($serverret["body"] != '') && (strlen($serverret["body"]) < 30)
			) {
				$platform = "GNU Social";
				// Remove junk that some GNU Social servers return
				$version = str_replace(chr(239).chr(187).chr(191), "", $serverret["body"]);
				$version = trim($version, '"');
				$network = NETWORK_OSTATUS;
			}
	
			// Test for Mastodon
			$orig_version = $version;
			$serverret = z_fetch_url($server_url."/api/v1/instance");
			if ($serverret["success"] && ($serverret["body"] != '')) {
				$data = json_decode($serverret["body"]);
				if (isset($data->version)) {
					$platform = "Mastodon";
					$version = $data->version;
					$site_name = $data->title;
					$info = $data->description;
					$network = NETWORK_OSTATUS;
				}
			}
			if (strstr($orig_version.$version, 'Pleroma')) {
				$platform = 'Pleroma';
				$version = trim(str_replace('Pleroma', '', $version));
			}
		}
	
		if (!$failure) {
			// Test for Hubzilla and Red
			$serverret = z_fetch_url($server_url."/siteinfo.json");
			if ($serverret["success"]) {
				$data = json_decode($serverret["body"]);
				if (isset($data->url)) {
					$platform = $data->platform;
					$version = $data->version;
					$network = NETWORK_DIASPORA;
				}
				if (!empty($data->site_name)) {
					$site_name = $data->site_name;
				}
				switch ($data->register_policy) {
					case "REGISTER_OPEN":
						$register_policy = REGISTER_OPEN;
						break;
					case "REGISTER_APPROVE":
						$register_policy = REGISTER_APPROVE;
						break;
					case "REGISTER_CLOSED":
					default:
						$register_policy = REGISTER_CLOSED;
						break;
				}
			} else {
				// Test for Hubzilla, Redmatrix or Friendica
				$serverret = z_fetch_url($server_url."/api/statusnet/config.json");
				if ($serverret["success"]) {
					$data = json_decode($serverret["body"]);
					if (isset($data->site->server)) {
						if (isset($data->site->platform)) {
							$platform = $data->site->platform->PLATFORM_NAME;
							$version = $data->site->platform->STD_VERSION;
							$network = NETWORK_DIASPORA;
						}
						if (isset($data->site->BlaBlaNet)) {
							$platform = $data->site->BlaBlaNet->PLATFORM_NAME;
							$version = $data->site->BlaBlaNet->STD_VERSION;
							$network = NETWORK_DIASPORA;
						}
						if (isset($data->site->hubzilla)) {
							$platform = $data->site->hubzilla->PLATFORM_NAME;
							$version = $data->site->hubzilla->RED_VERSION;
							$network = NETWORK_DIASPORA;
						}
						if (isset($data->site->redmatrix)) {
							if (isset($data->site->redmatrix->PLATFORM_NAME)) {
								$platform = $data->site->redmatrix->PLATFORM_NAME;
							} elseif (isset($data->site->redmatrix->RED_PLATFORM)) {
								$platform = $data->site->redmatrix->RED_PLATFORM;
							}
	
							$version = $data->site->redmatrix->RED_VERSION;
							$network = NETWORK_DIASPORA;
						}
						if (isset($data->site->friendica)) {
							$platform = $data->site->friendica->FRIENDICA_PLATFORM;
							$version = $data->site->friendica->FRIENDICA_VERSION;
							$network = NETWORK_DFRN;
						}
	
						$site_name = $data->site->name;
	
						$data->site->closed = self::toBoolean($data->site->closed);
						$data->site->private = self::toBoolean($data->site->private);
						$data->site->inviteonly = self::toBoolean($data->site->inviteonly);
	
						if (!$data->site->closed && !$data->site->private and $data->site->inviteonly) {
							$register_policy = REGISTER_APPROVE;
						} elseif (!$data->site->closed && !$data->site->private) {
							$register_policy = REGISTER_OPEN;
						} else {
							$register_policy = REGISTER_CLOSED;
						}
					}
				}
			}
		}
	
		// Query statistics.json. Optional package for Diaspora, Friendica and Redmatrix
		if (!$failure) {
			$serverret = z_fetch_url($server_url."/statistics.json");
			if ($serverret["success"]) {
				$data = json_decode($serverret["body"]);
				if (isset($data->version)) {
					$version = $data->version;
					// Version numbers on statistics.json are presented with additional info, e.g.:
					// 0.6.3.0-p1702cc1c, 0.6.99.0-p1b9ab160 or 3.4.3-2-1191.
					$version = preg_replace("=(.+)-(.{4,})=ism", "$1", $version);
				}
	
				if (!empty($data->name)) {
					$site_name = $data->name;
				}
	
				if (!empty($data->network)) {
					$platform = $data->network;
				}
	
				if ($platform == "Diaspora") {
					$network = NETWORK_DIASPORA;
				}
	
				if ($data->registrations_open) {
					$register_policy = REGISTER_OPEN;
				} else {
					$register_policy = REGISTER_CLOSED;
				}
			}
		}
	
		// Query nodeinfo. Working for (at least) Diaspora and Friendica.
		if (!$failure) {
			$server = self::fetchNodeinfo($server_url);
			if ($server) {
				$register_policy = $server['register_policy'];
	
				if (isset($server['platform'])) {
					$platform = $server['platform'];
				}
	
				if (isset($server['network'])) {
					$network = $server['network'];
				}
	
				if (isset($server['version'])) {
					$version = $server['version'];
				}
	
				if (isset($server['site_name'])) {
					$site_name = $server['site_name'];
				}
			}
		}
	
		// Check for noscrape
		// Friendica servers could be detected as OStatus servers
		if (!$failure && in_array($network, array(NETWORK_DFRN, NETWORK_OSTATUS))) {
			$serverret = z_fetch_url($server_url."/friendica/json");
	
			if (!$serverret["success"]) {
				$serverret = z_fetch_url($server_url."/friendika/json");
			}
	
			if ($serverret["success"]) {
				$data = json_decode($serverret["body"]);
	
				if (isset($data->version)) {
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
	
		if ($possible_failure && !$failure) {
			$failure = true;
		}
	
		if ($failure) {
			$last_contact = $orig_last_contact;
			$last_failure = datetime_convert();
		} else {
			$last_contact = datetime_convert();
			$last_failure = $orig_last_failure;
		}
	
		if (($last_contact <= $last_failure) && !$failure) {
			logger("Server ".$server_url." seems to be alive, but last contact wasn't set - could be a bug", LOGGER_DEBUG);
		} elseif (($last_contact >= $last_failure) && $failure) {
			logger("Server ".$server_url." seems to be dead, but last failure wasn't set - could be a bug", LOGGER_DEBUG);
		}
	
		// Check again if the server exists
		$servers = q("SELECT `nurl` FROM `gserver` WHERE `nurl` = '%s'", dbesc(normalise_link($server_url)));
	
		$version = strip_tags($version);
		$site_name = strip_tags($site_name);
		$info = strip_tags($info);
		$platform = strip_tags($platform);
	
		if ($servers) {
			q(
				"UPDATE `gserver` SET `url` = '%s', `version` = '%s', `site_name` = '%s', `info` = '%s', `register_policy` = %d, `poco` = '%s', `noscrape` = '%s',
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
		} elseif (!$failure) {
			q(
				"INSERT INTO `gserver` (`url`, `nurl`, `version`, `site_name`, `info`, `register_policy`, `poco`, `noscrape`, `network`, `platform`, `created`, `last_contact`, `last_failure`)
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
		}
		logger("End discovery for server " . $server_url, LOGGER_DEBUG);
	
		return !$failure;
	}

	/**
	 * @brief Fetch server list from remote servers and adds them when they are new.
	 *
	 * @param string $poco URL to the POCO endpoint
	 */
	public static function fetchServerlist($poco)
	{
		$serverret = z_fetch_url($poco."/@server");
		if (!$serverret["success"]) {
			return;
		}
		$serverlist = json_decode($serverret['body']);
	
		if (!is_array($serverlist)) {
			return;
		}
	
		foreach ($serverlist as $server) {
			$server_url = str_replace("/index.php", "", $server->url);
	
			$r = q("SELECT `nurl` FROM `gserver` WHERE `nurl` = '%s'", dbesc(normalise_link($server_url)));
			if (!DBM::is_result($r)) {
				logger("Call server check for server ".$server_url, LOGGER_DEBUG);
				Worker::add(PRIORITY_LOW, "discover_poco", "server", $server_url);
			}
		}
	}

	public static function discoverFederation()
	{
		$last = Config::get('poco', 'last_federation_discovery');
	
		if ($last) {
			$next = $last + (24 * 60 * 60);
			if ($next > time()) {
				return;
			}
		}
	
		// Discover Friendica, Hubzilla and Diaspora servers
		$serverdata = fetch_url("http://the-federation.info/pods.json");
	
		if ($serverdata) {
			$servers = json_decode($serverdata);
	
			foreach ($servers->pods as $server) {
				Worker::add(PRIORITY_LOW, "discover_poco", "server", "https://".$server->host);
			}
		}
	
		// Disvover Mastodon servers
		if (!Config::get('system', 'ostatus_disabled')) {
			$serverdata = fetch_url("https://instances.mastodon.xyz/instances.json");
	
			if ($serverdata) {
				$servers = json_decode($serverdata);
	
				foreach ($servers as $server) {
					$url = (is_null($server->https_score) ? 'http' : 'https').'://'.$server->name;
					Worker::add(PRIORITY_LOW, "discover_poco", "server", $url);
				}
			}
		}
	
		// Currently disabled, since the service isn't available anymore.
		// It is not removed since I hope that there will be a successor.
		// Discover GNU Social Servers.
		//if (!Config::get('system','ostatus_disabled')) {
		//	$serverdata = "http://gstools.org/api/get_open_instances/";
	
		//	$result = z_fetch_url($serverdata);
		//	if ($result["success"]) {
		//		$servers = json_decode($result["body"]);
	
		//		foreach($servers->data as $server)
		//			checkServer($server->instance_address);
		//	}
		//}
	
		Config::set('poco', 'last_federation_discovery', time());
	}

	public static function discoverSingleServer($id)
	{
		$r = q("SELECT `poco`, `nurl`, `url`, `network` FROM `gserver` WHERE `id` = %d", intval($id));
		if (!DBM::is_result($r)) {
			return false;
		}
	
		$server = $r[0];
	
		// Discover new servers out there (Works from Friendica version 3.5.2)
		self::fetchServerlist($server["poco"]);
	
		// Fetch all users from the other server
		$url = $server["poco"]."/?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,contactType,generation";
	
		logger("Fetch all users from the server ".$server["url"], LOGGER_DEBUG);
	
		$retdata = z_fetch_url($url);
		if ($retdata["success"]) {
			$data = json_decode($retdata["body"]);
	
			self::discoverServer($data, 2);
	
			if (Config::get('system', 'poco_discovery') > 1) {
				$timeframe = Config::get('system', 'poco_discovery_since');
				if ($timeframe == 0) {
					$timeframe = 30;
				}
	
				$updatedSince = date("Y-m-d H:i:s", time() - $timeframe * 86400);
	
				// Fetch all global contacts from the other server (Not working with Redmatrix and Friendica versions before 3.3)
				$url = $server["poco"]."/@global?updatedSince=".$updatedSince."&fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,contactType,generation";
	
				$success = false;
	
				$retdata = z_fetch_url($url);
				if ($retdata["success"]) {
					logger("Fetch all global contacts from the server ".$server["nurl"], LOGGER_DEBUG);
					$success = self::discoverServer(json_decode($retdata["body"]));
				}
	
				if (!$success && (Config::get('system', 'poco_discovery') > 2)) {
					logger("Fetch contacts from users of the server ".$server["nurl"], LOGGER_DEBUG);
					self::discoverServerUsers($data, $server);
				}
			}
	
			q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", dbesc(datetime_convert()), dbesc($server["nurl"]));
	
			return true;
		} else {
			// If the server hadn't replied correctly, then force a sanity check
			self::checkServer($server["url"], $server["network"], true);
	
			// If we couldn't reach the server, we will try it some time later
			q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", dbesc(datetime_convert()), dbesc($server["nurl"]));
	
			return false;
		}
	}

	public static function discover($complete = false)
	{
		// Update the server list
		self::discoverFederation();
	
		$no_of_queries = 5;
	
		$requery_days = intval(Config::get("system", "poco_requery_days"));
	
		if ($requery_days == 0) {
			$requery_days = 7;
		}
		$last_update = date("c", time() - (60 * 60 * 24 * $requery_days));
	
		$r = q("SELECT `id`, `url`, `network` FROM `gserver` WHERE `last_contact` >= `last_failure` AND `poco` != '' AND `last_poco_query` < '%s' ORDER BY RAND()", dbesc($last_update));
		if (DBM::is_result($r)) {
			foreach ($r as $server) {
				if (!self::checkServer($server["url"], $server["network"])) {
					// The server is not reachable? Okay, then we will try it later
					q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", dbesc(datetime_convert()), dbesc($server["nurl"]));
					continue;
				}
	
				logger('Update directory from server '.$server['url'].' with ID '.$server['id'], LOGGER_DEBUG);
				Worker::add(PRIORITY_LOW, "discover_poco", "update_server_directory", (int)$server['id']);
	
				if (!$complete && (--$no_of_queries == 0)) {
					break;
				}
			}
		}
	}

	public static function discoverServerUsers($data, $server)
	{
		if (!isset($data->entry)) {
			return;
		}
	
		foreach ($data->entry as $entry) {
			$username = "";
			if (isset($entry->urls)) {
				foreach ($entry->urls as $url) {
					if ($url->type == 'profile') {
						$profile_url = $url->value;
						$urlparts = parse_url($profile_url);
						$username = end(explode("/", $urlparts["path"]));
					}
				}
			}
			if ($username != "") {
				logger("Fetch contacts for the user ".$username." from the server ".$server["nurl"], LOGGER_DEBUG);
	
				// Fetch all contacts from a given user from the other server
				$url = $server["poco"]."/".$username."/?fields=displayName,urls,photos,updated,network,aboutMe,currentLocation,tags,gender,contactType,generation";
	
				$retdata = z_fetch_url($url);
				if ($retdata["success"]) {
					self::discoverServer(json_decode($retdata["body"]), 3);
				}
			}
		}
	}

	public static function discoverServer($data, $default_generation = 0)
	{
		if (!isset($data->entry) || !count($data->entry)) {
			return false;
		}
	
		$success = false;
	
		foreach ($data->entry as $entry) {
			$profile_url = '';
			$profile_photo = '';
			$connect_url = '';
			$name = '';
			$network = '';
			$updated = NULL_DATE;
			$location = '';
			$about = '';
			$keywords = '';
			$gender = '';
			$contact_type = -1;
			$generation = $default_generation;
	
			$name = $entry->displayName;
	
			if (isset($entry->urls)) {
				foreach ($entry->urls as $url) {
					if ($url->type == 'profile') {
						$profile_url = $url->value;
						continue;
					}
					if ($url->type == 'webfinger') {
						$connect_url = str_replace('acct:' , '', $url->value);
						continue;
					}
				}
			}
	
			if (isset($entry->photos)) {
				foreach ($entry->photos as $photo) {
					if ($photo->type == 'profile') {
						$profile_photo = $photo->value;
						continue;
					}
				}
			}
	
			if (isset($entry->updated)) {
				$updated = date("Y-m-d H:i:s", strtotime($entry->updated));
			}
	
			if (isset($entry->network)) {
				$network = $entry->network;
			}
	
			if (isset($entry->currentLocation)) {
				$location = $entry->currentLocation;
			}
	
			if (isset($entry->aboutMe)) {
				$about = html2bbcode($entry->aboutMe);
			}
	
			if (isset($entry->gender)) {
				$gender = $entry->gender;
			}
	
			if (isset($entry->generation) && ($entry->generation > 0)) {
				$generation = ++$entry->generation;
			}
	
			if (isset($entry->contactType) && ($entry->contactType >= 0)) {
				$contact_type = $entry->contactType;
			}
	
			if (isset($entry->tags)) {
				foreach ($entry->tags as $tag) {
					$keywords = implode(", ", $tag);
				}
			}
	
			if ($generation > 0) {
				$success = true;
	
				logger("Store profile ".$profile_url, LOGGER_DEBUG);
	
				$gcontact = array("url" => $profile_url,
						"name" => $name,
						"network" => $network,
						"photo" => $profile_photo,
						"about" => $about,
						"location" => $location,
						"gender" => $gender,
						"keywords" => $keywords,
						"connect" => $connect_url,
						"updated" => $updated,
						"contact-type" => $contact_type,
						"generation" => $generation);
	
				try {
					$gcontact = GlobalContact::sanitize($gcontact);
					GlobalContact::update($gcontact);
				} catch (Exception $e) {
					logger($e->getMessage(), LOGGER_DEBUG);
				}
	
				logger("Done for profile ".$profile_url, LOGGER_DEBUG);
			}
		}
		return $success;
	}

	/**
	 * @brief Returns a list of all known servers
	 * @return array List of server urls
	 */
	public static function serverlist()
	{
		$r = q(
			"SELECT `url`, `site_name` AS `displayName`, `network`, `platform`, `version` FROM `gserver`
			WHERE `network` IN ('%s', '%s', '%s') AND `last_contact` > `last_failure`
			ORDER BY `last_contact`
			LIMIT 1000",
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA),
			dbesc(NETWORK_OSTATUS)
		);

		if (!DBM::is_result($r)) {
			return false;
		}
	
		return $r;
	}

	public static function alternateOStatusUrl($url)
	{
		return preg_match("=https?://.+/user/\d+=ism", $url, $matches);
	}
}
