<?php

/**
 * @file src/Model/GContact.php
 */
namespace Friendica\Model;

use Friendica\App;
use Friendica\Core\System;
use Friendica\Core\Cache;
use Friendica\Core\Config;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use dba;

require_once 'include/datetime.php';
require_once 'include/network.php';
require_once 'include/html2bbcode.php';
require_once 'include/Contact.php';
require_once 'include/Photo.php';

/**
 * @brief This class handles global contact related functions
 */
class GContact
{
	/**
	 * @brief Search global contact table by nick or name
	 *
	 * @param string $search Name or nick
	 * @param string $mode   Search mode (e.g. "community")
	 *
	 * @return array with search results
	 */
	public static function searchByName($search, $mode = '')
	{
		if ($search) {
			// check supported networks
			if (Config::get('system', 'diaspora_enabled')) {
				$diaspora = NETWORK_DIASPORA;
			} else {
				$diaspora = NETWORK_DFRN;
			}

			if (!Config::get('system', 'ostatus_disabled')) {
				$ostatus = NETWORK_OSTATUS;
			} else {
				$ostatus = NETWORK_DFRN;
			}

			// check if we search only communities or every contact
			if ($mode === "community") {
				$extra_sql = " AND `community`";
			} else {
				$extra_sql = "";
			}

			$search .= "%";

			$results = q(
				"SELECT `contact`.`id` AS `cid`, `gcontact`.`url`, `gcontact`.`name`, `gcontact`.`nick`, `gcontact`.`photo`,
							`gcontact`.`network`, `gcontact`.`keywords`, `gcontact`.`addr`, `gcontact`.`community`
				FROM `gcontact`
				LEFT JOIN `contact` ON `contact`.`nurl` = `gcontact`.`nurl`
						AND `contact`.`uid` = %d AND NOT `contact`.`blocked`
						AND NOT `contact`.`pending` AND `contact`.`rel` IN ('%s', '%s')
				WHERE (`contact`.`id` > 0 OR (NOT `gcontact`.`hide` AND `gcontact`.`network` IN ('%s', '%s', '%s') AND
					((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`)))) AND
					(`gcontact`.`addr` LIKE '%s' OR `gcontact`.`name` LIKE '%s' OR `gcontact`.`nick` LIKE '%s') $extra_sql
							GROUP BY `gcontact`.`nurl`
							ORDER BY `gcontact`.`nurl` DESC
							LIMIT 1000",
				intval(local_user()), dbesc(CONTACT_IS_SHARING), dbesc(CONTACT_IS_FRIEND),
				dbesc(NETWORK_DFRN), dbesc($ostatus), dbesc($diaspora),
				dbesc(escape_tags($search)), dbesc(escape_tags($search)), dbesc(escape_tags($search))
			);

			return $results;
		}
	}

	/**
	 * @brief Sanitize the given gcontact data
	 *
	 * @param array $gcontact array with gcontact data
	 * @throw Exception
	 *
	 * Generation:
	 *  0: No definition
	 *  1: Profiles on this server
	 *  2: Contacts of profiles on this server
	 *  3: Contacts of contacts of profiles on this server
	 *  4: ...
	 *
	 */
	public static function sanitize($gcontact)
	{
		if ($gcontact['url'] == "") {
			throw new Exception('URL is empty');
		}
	
		$urlparts = parse_url($gcontact['url']);
		if (!isset($urlparts["scheme"])) {
			throw new Exception("This (".$gcontact['url'].") doesn't seem to be an url.");
		}
	
		if (in_array($urlparts["host"],
			array("www.facebook.com", "facebook.com", "twitter.com", "identi.ca", "alpha.app.net"))
		) {
			throw new Exception('Contact from a non federated network ignored. ('.$gcontact['url'].')');
		}
	
		// Don't store the statusnet connector as network
		// We can't simply set this to NETWORK_OSTATUS since the connector could have fetched posts from friendica as well
		if ($gcontact['network'] == NETWORK_STATUSNET) {
			$gcontact['network'] = "";
		}
	
		// Assure that there are no parameter fragments in the profile url
		if (in_array($gcontact['network'], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""))) {
			$gcontact['url'] = self::cleanContactUrl($gcontact['url']);
		}
	
		$alternate = self::alternateOstatusUrl($gcontact['url']);
	
		// The global contacts should contain the original picture, not the cached one
		if (($gcontact['generation'] != 1) && stristr(normalise_link($gcontact['photo']), normalise_link(System::baseUrl()."/photo/"))) {
			$gcontact['photo'] = "";
		}
	
		if (!isset($gcontact['network'])) {
			$r = q(
				"SELECT `network` FROM `contact` WHERE `uid` = 0 AND `nurl` = '%s' AND `network` != '' AND `network` != '%s' LIMIT 1",
				dbesc(normalise_link($gcontact['url'])),
				dbesc(NETWORK_STATUSNET)
			);
			if (DBM::is_result($r)) {
				$gcontact['network'] = $r[0]["network"];
			}
	
			if (($gcontact['network'] == "") || ($gcontact['network'] == NETWORK_OSTATUS)) {
				$r = q(
					"SELECT `network`, `url` FROM `contact` WHERE `uid` = 0 AND `alias` IN ('%s', '%s') AND `network` != '' AND `network` != '%s' LIMIT 1",
					dbesc($gcontact['url']),
					dbesc(normalise_link($gcontact['url'])),
					dbesc(NETWORK_STATUSNET)
				);
				if (DBM::is_result($r)) {
					$gcontact['network'] = $r[0]["network"];
				}
			}
		}
	
		$gcontact['server_url'] = '';
		$gcontact['network'] = '';
	
		$x = q(
			"SELECT * FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
			dbesc(normalise_link($gcontact['url']))
		);
	
		if (DBM::is_result($x)) {
			if (!isset($gcontact['network']) && ($x[0]["network"] != NETWORK_STATUSNET)) {
				$gcontact['network'] = $x[0]["network"];
			}
			if ($gcontact['updated'] <= NULL_DATE) {
				$gcontact['updated'] = $x[0]["updated"];
			}
			if (!isset($gcontact['server_url']) && (normalise_link($x[0]["server_url"]) != normalise_link($x[0]["url"]))) {
				$gcontact['server_url'] = $x[0]["server_url"];
			}
			if (!isset($gcontact['addr'])) {
				$gcontact['addr'] = $x[0]["addr"];
			}
		}
	
		if ((!isset($gcontact['network']) || !isset($gcontact['name']) || !isset($gcontact['addr']) || !isset($gcontact['photo']) || !isset($gcontact['server_url']) || $alternate)
			&& PortableContact::reachable($gcontact['url'], $gcontact['server_url'], $gcontact['network'], false)
		) {
			$data = Probe::uri($gcontact['url']);
	
			if ($data["network"] == NETWORK_PHANTOM) {
				throw new Exception('Probing for URL '.$gcontact['url'].' failed');
			}
	
			$orig_profile = $gcontact['url'];
	
			$gcontact["server_url"] = $data["baseurl"];
	
			$gcontact = array_merge($gcontact, $data);
	
			if ($alternate && ($gcontact['network'] == NETWORK_OSTATUS)) {
				// Delete the old entry - if it exists
				$r = q("SELECT `id` FROM `gcontact` WHERE `nurl` = '%s'", dbesc(normalise_link($orig_profile)));
				if (DBM::is_result($r)) {
					q("DELETE FROM `gcontact` WHERE `nurl` = '%s'", dbesc(normalise_link($orig_profile)));
					q("DELETE FROM `glink` WHERE `gcid` = %d", intval($r[0]["id"]));
				}
			}
		}
	
		if (!isset($gcontact['name']) || !isset($gcontact['photo'])) {
			throw new Exception('No name and photo for URL '.$gcontact['url']);
		}
	
		if (!in_array($gcontact['network'], array(NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA))) {
			throw new Exception('No federated network ('.$gcontact['network'].') detected for URL '.$gcontact['url']);
		}
	
		if (!isset($gcontact['server_url'])) {
			// We check the server url to be sure that it is a real one
			$server_url = PortableContact::detectServer($gcontact['url']);
	
			// We are now sure that it is a correct URL. So we use it in the future
			if ($server_url != "") {
				$gcontact['server_url'] = $server_url;
			}
		}
	
		// The server URL doesn't seem to be valid, so we don't store it.
		if (!PortableContact::checkServer($gcontact['server_url'], $gcontact['network'])) {
			$gcontact['server_url'] = "";
		}
	
		return $gcontact;
	}

	/**
	 * @brief Link the gcontact entry with user, contact and global contact
	 *
	 * @param integer $gcid Global contact ID
	 * @param integer $uid  User ID
	 * @param integer $cid  Contact ID
	 * @param integer $zcid Global Contact ID
	 *
	 */
	public static function link($gcid, $uid = 0, $cid = 0, $zcid = 0)
	{
		if ($gcid <= 0) {
			return;
		}
	
		$r = q(
			"SELECT * FROM `glink` WHERE `cid` = %d AND `uid` = %d AND `gcid` = %d AND `zcid` = %d LIMIT 1",
			intval($cid),
			intval($uid),
			intval($gcid),
			intval($zcid)
		);
	
		if (!DBM::is_result($r)) {
			q(
				"INSERT INTO `glink` (`cid`, `uid`, `gcid`, `zcid`, `updated`) VALUES (%d, %d, %d, %d, '%s') ",
				intval($cid),
				intval($uid),
				intval($gcid),
				intval($zcid),
				dbesc(datetime_convert())
			);
		} else {
			q(
				"UPDATE `glink` SET `updated` = '%s' WHERE `cid` = %d AND `uid` = %d AND `gcid` = %d AND `zcid` = %d",
				dbesc(datetime_convert()),
				intval($cid),
				intval($uid),
				intval($gcid),
				intval($zcid)
			);
		}
	}

	public static function alternateOstatusUrl($url)
	{
		return preg_match("=https?://.+/user/\d+=ism", $url, $matches);
	}
	
	public static function countCommonFriends($uid, $cid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			WHERE `glink`.`cid` = %d AND `glink`.`uid` = %d AND
			((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`))
			AND `gcontact`.`nurl` IN (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 and id != %d ) ",
			intval($cid),
			intval($uid),
			intval($uid),
			intval($cid)
		);
	
		// logger("countCommonFriends: $uid $cid {$r[0]['total']}");
		if (DBM::is_result($r)) {
			return $r[0]['total'];
		}
		
		return 0;
	}
	
	public static function commonFriends($uid, $cid, $start = 0, $limit = 9999, $shuffle = false)
	{
		if ($shuffle) {
			$sql_extra = " order by rand() ";
		} else {
			$sql_extra = " order by `gcontact`.`name` asc ";
		}
	
		$r = q(
			"SELECT `gcontact`.*, `contact`.`id` AS `cid`
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
	
		/// @TODO Check all calling-findings of this function if they properly use DBM::is_result()
		return $r;
	}
	
	public static function countCommonFriendsZcid($uid, $zcid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			where `glink`.`zcid` = %d
			and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 ) ",
			intval($zcid),
			intval($uid)
		);
	
		if (DBM::is_result($r)) {
			return $r[0]['total'];
		}

		return 0;
	}
	
	public static function commonFriendsZcid($uid, $zcid, $start = 0, $limit = 9999, $shuffle = false)
	{
		if ($shuffle) {
			$sql_extra = " order by rand() ";
		} else {
			$sql_extra = " order by `gcontact`.`name` asc ";
		}
	
		$r = q(
			"SELECT `gcontact`.*
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			where `glink`.`zcid` = %d
			and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 )
			$sql_extra limit %d, %d",
			intval($zcid),
			intval($uid),
			intval($start),
			intval($limit)
		);
	
		/// @TODO Check all calling-findings of this function if they properly use DBM::is_result()
		return $r;
	}
	
	public static function countAllFriends($uid, $cid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			where `glink`.`cid` = %d and `glink`.`uid` = %d AND
			((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR (`gcontact`.`updated` >= `gcontact`.`last_failure`))",
			intval($cid),
			intval($uid)
		);
	
		if (DBM::is_result($r)) {
			return $r[0]['total'];
		}
		
		return 0;
	}
	
	public static function allFriends($uid, $cid, $start = 0, $limit = 80)
	{
		$r = q(
			"SELECT `gcontact`.*, `contact`.`id` AS `cid`
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
	
		/// @TODO Check all calling-findings of this function if they properly use DBM::is_result()
		return $r;
	}
	
	public static function suggestionQuery($uid, $start = 0, $limit = 80)
	{
		if (!$uid) {
			return array();
		}
	
		/*
		 * Uncommented because the result of the queries are to big to store it in the cache.
		 * We need to decide if we want to change the db column type or if we want to delete it.
		 */
		//$list = Cache::get("suggestion_query:".$uid.":".$start.":".$limit);
		//if (!is_null($list)) {
		//	return $list;
		//}
	
		$network = array(NETWORK_DFRN);
	
		if (Config::get('system', 'diaspora_enabled')) {
			$network[] = NETWORK_DIASPORA;
		}
	
		if (!Config::get('system', 'ostatus_disabled')) {
			$network[] = NETWORK_OSTATUS;
		}
	
		$sql_network = implode("', '", $network);
		$sql_network = "'".$sql_network."'";
	
		/// @todo This query is really slow
		// By now we cache the data for five minutes
		$r = q(
			"SELECT count(glink.gcid) as `total`, gcontact.* from gcontact
			INNER JOIN `glink` ON `glink`.`gcid` = `gcontact`.`id`
			where uid = %d and not gcontact.nurl in ( select nurl from contact where uid = %d )
			AND NOT `gcontact`.`name` IN (SELECT `name` FROM `contact` WHERE `uid` = %d)
			AND NOT `gcontact`.`id` IN (SELECT `gcid` FROM `gcign` WHERE `uid` = %d)
			AND `gcontact`.`updated` >= '%s'
			AND `gcontact`.`last_contact` >= `gcontact`.`last_failure`
			AND `gcontact`.`network` IN (%s)
			GROUP BY `glink`.`gcid` ORDER BY `gcontact`.`updated` DESC,`total` DESC LIMIT %d, %d",
			intval($uid),
			intval($uid),
			intval($uid),
			intval($uid),
			dbesc(NULL_DATE),
			$sql_network,
			intval($start),
			intval($limit)
		);
	
		if (DBM::is_result($r) && count($r) >= ($limit -1)) {
			/*
			 * Uncommented because the result of the queries are to big to store it in the cache.
			 * We need to decide if we want to change the db column type or if we want to delete it.
			 */
			//Cache::set("suggestion_query:".$uid.":".$start.":".$limit, $r, CACHE_FIVE_MINUTES);
	
			return $r;
		}
	
		$r2 = q(
			"SELECT gcontact.* FROM gcontact
			INNER JOIN `glink` ON `glink`.`gcid` = `gcontact`.`id`
			WHERE `glink`.`uid` = 0 AND `glink`.`cid` = 0 AND `glink`.`zcid` = 0 AND NOT `gcontact`.`nurl` IN (SELECT `nurl` FROM `contact` WHERE `uid` = %d)
			AND NOT `gcontact`.`name` IN (SELECT `name` FROM `contact` WHERE `uid` = %d)
			AND NOT `gcontact`.`id` IN (SELECT `gcid` FROM `gcign` WHERE `uid` = %d)
			AND `gcontact`.`updated` >= '%s'
			AND `gcontact`.`last_contact` >= `gcontact`.`last_failure`
			AND `gcontact`.`network` IN (%s)
			ORDER BY rand() LIMIT %d, %d",
			intval($uid),
			intval($uid),
			intval($uid),
			dbesc(NULL_DATE),
			$sql_network,
			intval($start),
			intval($limit)
		);
	
		$list = array();
		foreach ($r2 as $suggestion) {
			$list[$suggestion["nurl"]] = $suggestion;
		}
	
		foreach ($r as $suggestion) {
			$list[$suggestion["nurl"]] = $suggestion;
		}
	
		while (sizeof($list) > ($limit)) {
			array_pop($list);
		}
	
		/*
		 * Uncommented because the result of the queries are to big to store it in the cache.
		 * We need to decide if we want to change the db column type or if we want to delete it.
		 */
		//Cache::set("suggestion_query:".$uid.":".$start.":".$limit, $list, CACHE_FIVE_MINUTES);
		return $list;
	}
	
	public static function updateSuggestions()
	{
		$a = get_app();
	
		$done = array();
	
		/// @TODO Check if it is really neccessary to poll the own server
		PortableContact::load(0, 0, 0, System::baseUrl() . '/poco');
	
		$done[] = System::baseUrl() . '/poco';
	
		if (strlen(Config::get('system', 'directory'))) {
			$x = fetch_url(get_server()."/pubsites");
			if ($x) {
				$j = json_decode($x);
				if ($j->entries) {
					foreach ($j->entries as $entry) {
						PortableContact::checkServer($entry->url);
	
						$url = $entry->url . '/poco';
						if (! in_array($url, $done)) {
							PortableContact::load(0, 0, 0, $entry->url . '/poco');
						}
					}
				}
			}
		}
	
		// Query your contacts from Friendica and Redmatrix/Hubzilla for their contacts
		$r = q(
			"SELECT DISTINCT(`poco`) AS `poco` FROM `contact` WHERE `network` IN ('%s', '%s')",
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA)
		);
	
		if (DBM::is_result($r)) {
			foreach ($r as $rr) {
				$base = substr($rr['poco'], 0, strrpos($rr['poco'], '/'));
				if (! in_array($base, $done)) {
					PortableContact::load(0, 0, 0, $base);
				}
			}
		}
	}
	
	/**
	 * @brief Removes unwanted parts from a contact url
	 *
	 * @param string $url Contact url
	 * @return string Contact url with the wanted parts
	 */
	public static function cleanContactUrl($url)
	{
		$parts = parse_url($url);
	
		if (!isset($parts["scheme"]) || !isset($parts["host"])) {
			return $url;
		}
	
		$new_url = $parts["scheme"]."://".$parts["host"];
	
		if (isset($parts["port"])) {
			$new_url .= ":".$parts["port"];
		}
	
		if (isset($parts["path"])) {
			$new_url .= $parts["path"];
		}
	
		if ($new_url != $url) {
			logger("Cleaned contact url ".$url." to ".$new_url." - Called by: ".System::callstack(), LOGGER_DEBUG);
		}
	
		return $new_url;
	}
	
	/**
	 * @brief Replace alternate OStatus user format with the primary one
	 *
	 * @param arr $contact contact array (called by reference)
	 */
	public static function fixAlternateContactAddress(&$contact)
	{
		if (($contact["network"] == NETWORK_OSTATUS) && self::alternateOstatusUrl($contact["url"])) {
			$data = Probe::uri($contact["url"]);
			if ($contact["network"] == NETWORK_OSTATUS) {
				logger("Fix primary url from ".$contact["url"]." to ".$data["url"]." - Called by: ".System::callstack(), LOGGER_DEBUG);
				$contact["url"] = $data["url"];
				$contact["addr"] = $data["addr"];
				$contact["alias"] = $data["alias"];
				$contact["server_url"] = $data["baseurl"];
			}
		}
	}
	
	/**
	 * @brief Fetch the gcontact id, add an entry if not existed
	 *
	 * @param arr $contact contact array
	 * @return bool|int Returns false if not found, integer if contact was found
	 */
	public static function getId($contact)
	{
		$gcontact_id = 0;
		$doprobing = false;
	
		if (in_array($contact["network"], array(NETWORK_PHANTOM))) {
			logger("Invalid network for contact url ".$contact["url"]." - Called by: ".System::callstack(), LOGGER_DEBUG);
			return false;
		}
	
		if ($contact["network"] == NETWORK_STATUSNET) {
			$contact["network"] = NETWORK_OSTATUS;
		}
	
		// All new contacts are hidden by default
		if (!isset($contact["hide"])) {
			$contact["hide"] = true;
		}
	
		// Replace alternate OStatus user format with the primary one
		self::fixAlternateContactAddress($contact);
	
		// Remove unwanted parts from the contact url (e.g. "?zrl=...")
		if (in_array($contact["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS))) {
			$contact["url"] = self::cleanContactUrl($contact["url"]);
		}
	
		dba::lock('gcontact');
		$r = q(
			"SELECT `id`, `last_contact`, `last_failure`, `network` FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
			dbesc(normalise_link($contact["url"]))
		);
	
		if (DBM::is_result($r)) {
			$gcontact_id = $r[0]["id"];
	
			// Update every 90 days
			if (in_array($r[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""))) {
				$last_failure_str = $r[0]["last_failure"];
				$last_failure = strtotime($r[0]["last_failure"]);
				$last_contact_str = $r[0]["last_contact"];
				$last_contact = strtotime($r[0]["last_contact"]);
				$doprobing = (((time() - $last_contact) > (90 * 86400)) && ((time() - $last_failure) > (90 * 86400)));
			}
		} else {
			q(
				"INSERT INTO `gcontact` (`name`, `nick`, `addr` , `network`, `url`, `nurl`, `photo`, `created`, `updated`, `location`, `about`, `hide`, `generation`)
				VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d)",
				dbesc($contact["name"]),
				dbesc($contact["nick"]),
				dbesc($contact["addr"]),
				dbesc($contact["network"]),
				dbesc($contact["url"]),
				dbesc(normalise_link($contact["url"])),
				dbesc($contact["photo"]),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				dbesc($contact["location"]),
				dbesc($contact["about"]),
				intval($contact["hide"]),
				intval($contact["generation"])
			);
	
			$r = q(
				"SELECT `id`, `network` FROM `gcontact` WHERE `nurl` = '%s' ORDER BY `id` LIMIT 2",
				dbesc(normalise_link($contact["url"]))
			);
	
			if (DBM::is_result($r)) {
				$gcontact_id = $r[0]["id"];
	
				$doprobing = in_array($r[0]["network"], array(NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS, ""));
			}
		}
		dba::unlock();
	
		if ($doprobing) {
			logger("Last Contact: ". $last_contact_str." - Last Failure: ".$last_failure_str." - Checking: ".$contact["url"], LOGGER_DEBUG);
			Worker::add(PRIORITY_LOW, 'gprobe', $contact["url"]);
		}
	
		return $gcontact_id;
	}
	
	/**
	 * @brief Updates the gcontact table from a given array
	 *
	 * @param arr $contact contact array
	 * @return bool|int Returns false if not found, integer if contact was found
	 */
	public static function update($contact)
	{
		// Check for invalid "contact-type" value
		if (isset($contact['contact-type']) && (intval($contact['contact-type']) < 0)) {
			$contact['contact-type'] = 0;
		}
	
		/// @todo update contact table as well
	
		$gcontact_id = self::getId($contact);
	
		if (!$gcontact_id) {
			return false;
		}
	
		$r = q(
			"SELECT `name`, `nick`, `photo`, `location`, `about`, `addr`, `generation`, `birthday`, `gender`, `keywords`,
				`contact-type`, `hide`, `nsfw`, `network`, `alias`, `notify`, `server_url`, `connect`, `updated`, `url`
			FROM `gcontact` WHERE `id` = %d LIMIT 1",
			intval($gcontact_id)
		);
	
		// Get all field names
		$fields = array();
		foreach ($r[0] as $field => $data) {
			$fields[$field] = $data;
		}
	
		unset($fields["url"]);
		unset($fields["updated"]);
		unset($fields["hide"]);
	
		// Bugfix: We had an error in the storing of keywords which lead to the "0"
		// This value is still transmitted via poco.
		if ($contact["keywords"] == "0") {
			unset($contact["keywords"]);
		}
	
		if ($r[0]["keywords"] == "0") {
			$r[0]["keywords"] = "";
		}
	
		// assign all unassigned fields from the database entry
		foreach ($fields as $field => $data) {
			if (!isset($contact[$field]) || ($contact[$field] == "")) {
				$contact[$field] = $r[0][$field];
			}
		}
	
		if (!isset($contact["hide"])) {
			$contact["hide"] = $r[0]["hide"];
		}
	
		$fields["hide"] = $r[0]["hide"];
	
		if ($contact["network"] == NETWORK_STATUSNET) {
			$contact["network"] = NETWORK_OSTATUS;
		}
	
		// Replace alternate OStatus user format with the primary one
		self::fixAlternateContactAddress($contact);
	
		if (!isset($contact["updated"])) {
			$contact["updated"] = DBM::date();
		}
	
		if ($contact["network"] == NETWORK_TWITTER) {
			$contact["server_url"] = 'http://twitter.com';
		}
	
		if ($contact["server_url"] == "") {
			$data = Probe::uri($contact["url"]);
			if ($data["network"] != NETWORK_PHANTOM) {
				$contact["server_url"] = $data['baseurl'];
			}
		} else {
			$contact["server_url"] = normalise_link($contact["server_url"]);
		}
	
		if (($contact["addr"] == "") && ($contact["server_url"] != "") && ($contact["nick"] != "")) {
			$hostname = str_replace("http://", "", $contact["server_url"]);
			$contact["addr"] = $contact["nick"]."@".$hostname;
		}
	
		// Check if any field changed
		$update = false;
		unset($fields["generation"]);
	
		if ((($contact["generation"] > 0) && ($contact["generation"] <= $r[0]["generation"])) || ($r[0]["generation"] == 0)) {
			foreach ($fields as $field => $data) {
				if ($contact[$field] != $r[0][$field]) {
					logger("Difference for contact ".$contact["url"]." in field '".$field."'. New value: '".$contact[$field]."', old value '".$r[0][$field]."'", LOGGER_DEBUG);
					$update = true;
				}
			}
	
			if ($contact["generation"] < $r[0]["generation"]) {
				logger("Difference for contact ".$contact["url"]." in field 'generation'. new value: '".$contact["generation"]."', old value '".$r[0]["generation"]."'", LOGGER_DEBUG);
				$update = true;
			}
		}
	
		if ($update) {
			logger("Update gcontact for ".$contact["url"], LOGGER_DEBUG);
			$condition = array('`nurl` = ? AND (`generation` = 0 OR `generation` >= ?)',
					normalise_link($contact["url"]), $contact["generation"]);
			$contact["updated"] = DBM::date($contact["updated"]);
	
			$updated = array('photo' => $contact['photo'], 'name' => $contact['name'],
					'nick' => $contact['nick'], 'addr' => $contact['addr'],
					'network' => $contact['network'], 'birthday' => $contact['birthday'],
					'gender' => $contact['gender'], 'keywords' => $contact['keywords'],
					'hide' => $contact['hide'], 'nsfw' => $contact['nsfw'],
					'contact-type' => $contact['contact-type'], 'alias' => $contact['alias'],
					'notify' => $contact['notify'], 'url' => $contact['url'],
					'location' => $contact['location'], 'about' => $contact['about'],
					'generation' => $contact['generation'], 'updated' => $contact['updated'],
					'server_url' => $contact['server_url'], 'connect' => $contact['connect']);
	
			dba::update('gcontact', $updated, $condition, $fields);
	
			// Now update the contact entry with the user id "0" as well.
			// This is used for the shadow copies of public items.
			$r = q(
				"SELECT `id` FROM `contact` WHERE `nurl` = '%s' AND `uid` = 0 ORDER BY `id` LIMIT 1",
				dbesc(normalise_link($contact["url"]))
			);
	
			if (DBM::is_result($r)) {
				logger("Update public contact ".$r[0]["id"], LOGGER_DEBUG);
	
				update_contact_avatar($contact["photo"], 0, $r[0]["id"]);
	
				$fields = array('name', 'nick', 'addr',
						'network', 'bd', 'gender',
						'keywords', 'alias', 'contact-type',
						'url', 'location', 'about');
				$old_contact = dba::select('contact', $fields, array('id' => $r[0]["id"]), array('limit' => 1));
	
				// Update it with the current values
				$fields = array('name' => $contact['name'], 'nick' => $contact['nick'],
						'addr' => $contact['addr'], 'network' => $contact['network'],
						'bd' => $contact['birthday'], 'gender' => $contact['gender'],
						'keywords' => $contact['keywords'], 'alias' => $contact['alias'],
						'contact-type' => $contact['contact-type'], 'url' => $contact['url'],
						'location' => $contact['location'], 'about' => $contact['about']);
	
				dba::update('contact', $fields, array('id' => $r[0]["id"]), $old_contact);
			}
		}
	
		return $gcontact_id;
	}
	
	/**
	 * @brief Updates the gcontact entry from probe
	 *
	 * @param str $url profile link
	 */
	public static function updateFromProbe($url)
	{
		$data = Probe::uri($url);
	
		if (in_array($data["network"], array(NETWORK_PHANTOM))) {
			logger("Invalid network for contact url ".$data["url"]." - Called by: ".System::callstack(), LOGGER_DEBUG);
			return;
		}
	
		$data["server_url"] = $data["baseurl"];
	
		self::update($data);
	}
	
	/**
	 * @brief Update the gcontact entry for a given user id
	 *
	 * @param int $uid User ID
	 */
	public static function updateForUser($uid)
	{
		$r = q(
			"SELECT `profile`.`locality`, `profile`.`region`, `profile`.`country-name`,
				`profile`.`name`, `profile`.`about`, `profile`.`gender`,
				`profile`.`pub_keywords`, `profile`.`dob`, `profile`.`photo`,
				`profile`.`net-publish`, `user`.`nickname`, `user`.`hidewall`,
				`contact`.`notify`, `contact`.`url`, `contact`.`addr`
			FROM `profile`
				INNER JOIN `user` ON `user`.`uid` = `profile`.`uid`
				INNER JOIN `contact` ON `contact`.`uid` = `profile`.`uid`
			WHERE `profile`.`uid` = %d AND `profile`.`is-default` AND `contact`.`self`",
			intval($uid)
		);
	
		$location = formatted_location(
			array("locality" => $r[0]["locality"], "region" => $r[0]["region"],
			"country-name" => $r[0]["country-name"])
		);
	
		// The "addr" field was added in 3.4.3 so it can be empty for older users
		if ($r[0]["addr"] != "") {
			$addr = $r[0]["nickname"].'@'.str_replace(array("http://", "https://"), "", System::baseUrl());
		} else {
			$addr = $r[0]["addr"];
		}
	
		$gcontact = array("name" => $r[0]["name"], "location" => $location, "about" => $r[0]["about"],
				"gender" => $r[0]["gender"], "keywords" => $r[0]["pub_keywords"],
				"birthday" => $r[0]["dob"], "photo" => $r[0]["photo"],
				"notify" => $r[0]["notify"], "url" => $r[0]["url"],
				"hide" => ($r[0]["hidewall"] || !$r[0]["net-publish"]),
				"nick" => $r[0]["nickname"], "addr" => $addr,
				"connect" => $addr, "server_url" => System::baseUrl(),
				"generation" => 1, "network" => NETWORK_DFRN);
	
		self::update($gcontact);
	}
	
	/**
	 * @brief Fetches users of given GNU Social server
	 *
	 * If the "Statistics" plugin is enabled (See http://gstools.org/ for details) we query user data with this.
	 *
	 * @param str $server Server address
	 */
	public static function gsFetchUsers($server)
	{
		logger("Fetching users from GNU Social server ".$server, LOGGER_DEBUG);
	
		$url = $server."/main/statistics";
	
		$result = z_fetch_url($url);
		if (!$result["success"]) {
			return false;
		}
	
		$statistics = json_decode($result["body"]);
	
		if (is_object($statistics->config)) {
			if ($statistics->config->instance_with_ssl) {
				$server = "https://";
			} else {
				$server = "http://";
			}
	
			$server .= $statistics->config->instance_address;
	
			$hostname = $statistics->config->instance_address;
		} else {
			/// @TODO is_object() above means here no object, still $statistics is being used as object
			if ($statistics->instance_with_ssl) {
				$server = "https://";
			} else {
				$server = "http://";
			}
	
			$server .= $statistics->instance_address;
	
			$hostname = $statistics->instance_address;
		}
	
		if (is_object($statistics->users)) {
			foreach ($statistics->users as $nick => $user) {
				$profile_url = $server."/".$user->nickname;
	
				$contact = array("url" => $profile_url,
						"name" => $user->fullname,
						"addr" => $user->nickname."@".$hostname,
						"nick" => $user->nickname,
						"about" => $user->bio,
						"network" => NETWORK_OSTATUS,
						"photo" => System::baseUrl()."/images/person-175.jpg");
				self::getId($contact);
			}
		}
	}
	
	/**
	 * @brief Asking GNU Social server on a regular base for their user data
	 *
	 */
	public static function gsDiscover()
	{
		$requery_days = intval(Config::get("system", "poco_requery_days"));
	
		$last_update = date("c", time() - (60 * 60 * 24 * $requery_days));
	
		$r = q(
			"SELECT `nurl`, `url` FROM `gserver` WHERE `last_contact` >= `last_failure` AND `network` = '%s' AND `last_poco_query` < '%s' ORDER BY RAND() LIMIT 5",
			dbesc(NETWORK_OSTATUS),
			dbesc($last_update)
		);
	
		if (!DBM::is_result($r)) {
			return;
		}
	
		foreach ($r as $server) {
			self::gsFetchUsers($server["url"]);
			q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", dbesc(datetime_convert()), dbesc($server["nurl"]));
		}
	}
}
