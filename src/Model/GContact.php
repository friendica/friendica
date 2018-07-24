<?php

/**
 * @file src/Model/GlobalContact.php
 * @brief This file includes the GlobalContact class with directory related functions
 */
namespace Friendica\Model;

use Exception;
use Friendica\Core\Config;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Network\Probe;
use Friendica\Protocol\PortableContact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Network;

require_once 'include/dba.php';

/**
 * @brief This class handles GlobalContact related functions
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
		if (empty($search)) {
			return [];
		}

		// check supported networks
		if (Config::get('system', 'diaspora_enabled')) {
			$diaspora = Protocol::DIASPORA;
		} else {
			$diaspora = Protocol::DFRN;
		}

		if (!Config::get('system', 'ostatus_disabled')) {
			$ostatus = Protocol::OSTATUS;
		} else {
			$ostatus = Protocol::DFRN;
		}

		// check if we search only communities or every contact
		if ($mode === "community") {
			$extra_sql = " AND `community`";
		} else {
			$extra_sql = "";
		}

		$search .= "%";

		$results = DBA::p("SELECT `nurl` FROM `gcontact`
			WHERE NOT `hide` AND `network` IN (?, ?, ?) AND
				((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`)) AND
				(`addr` LIKE ? OR `name` LIKE ? OR `nick` LIKE ?) $extra_sql
				GROUP BY `nurl` ORDER BY `nurl` DESC LIMIT 1000",
			Protocol::DFRN, $ostatus, $diaspora, $search, $search, $search
		);

		$gcontacts = [];
		while ($result = DBA::fetch($results)) {
			$urlparts = parse_url($result["nurl"]);

			// Ignore results that look strange.
			// For historic reasons the gcontact table does contain some garbage.
			if (!empty($urlparts['query']) || !empty($urlparts['fragment'])) {
				continue;
			}

			$gcontacts[] = Contact::getDetailsByURL($result["nurl"], local_user());
		}
		return $gcontacts;
	}

	/**
	 * @brief Link the gcontact entry with user, contact and global contact
	 *
	 * @param integer $gcid Global contact ID
	 * @param integer $uid  User ID
	 * @param integer $cid  Contact ID
	 * @param integer $zcid Global Contact ID
	 * @return void
	 */
	public static function link($gcid, $uid = 0, $cid = 0, $zcid = 0)
	{
		if ($gcid <= 0) {
			return;
		}

		$condition = ['cid' => $cid, 'uid' => $uid, 'gcid' => $gcid, 'zcid' => $zcid];
		DBA::update('glink', ['updated' => DateTimeFormat::utcNow()], $condition, true);
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
	 * @return array $gcontact
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

		if (in_array($urlparts["host"],	["twitter.com", "identi.ca"])) {
			throw new Exception('Contact from a non federated network ignored. ('.$gcontact['url'].')');
		}

		// Don't store the statusnet connector as network
		// We can't simply set this to Protocol::OSTATUS since the connector could have fetched posts from friendica as well
		if ($gcontact['network'] == Protocol::STATUSNET) {
			$gcontact['network'] = "";
		}

		// Assure that there are no parameter fragments in the profile url
		if (in_array($gcontact['network'], [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, ""])) {
			$gcontact['url'] = self::cleanContactUrl($gcontact['url']);
		}

		$alternate = PortableContact::alternateOStatusUrl($gcontact['url']);

		// The global contacts should contain the original picture, not the cached one
		if (($gcontact['generation'] != 1) && stristr(normalise_link($gcontact['photo']), normalise_link(System::baseUrl()."/photo/"))) {
			$gcontact['photo'] = "";
		}

		if (!isset($gcontact['network'])) {
			$condition = ["`uid` = 0 AND `nurl` = ? AND `network` != '' AND `network` != ?",
				normalise_link($gcontact['url']), Protocol::STATUSNET];
			$contact = DBA::selectFirst('contact', ['network'], $condition);
			if (DBA::isResult($contact)) {
				$gcontact['network'] = $contact["network"];
			}

			if (($gcontact['network'] == "") || ($gcontact['network'] == Protocol::OSTATUS)) {
				$condition = ["`uid` = 0 AND `alias` IN (?, ?) AND `network` != '' AND `network` != ?",
					$gcontact['url'], normalise_link($gcontact['url']), Protocol::STATUSNET];
				$contact = DBA::selectFirst('contact', ['network'], $condition);
				if (DBA::isResult($contact)) {
					$gcontact['network'] = $contact["network"];
				}
			}
		}

		$gcontact['server_url'] = '';
		$gcontact['network'] = '';

		$fields = ['network', 'updated', 'server_url', 'url', 'addr'];
		$gcnt = DBA::selectFirst('gcontact', $fields, ['nurl' => normalise_link($gcontact['url'])]);
		if (DBA::isResult($gcnt)) {
			if (!isset($gcontact['network']) && ($gcnt["network"] != Protocol::STATUSNET)) {
				$gcontact['network'] = $gcnt["network"];
			}
			if ($gcontact['updated'] <= NULL_DATE) {
				$gcontact['updated'] = $gcnt["updated"];
			}
			if (!isset($gcontact['server_url']) && (normalise_link($gcnt["server_url"]) != normalise_link($gcnt["url"]))) {
				$gcontact['server_url'] = $gcnt["server_url"];
			}
			if (!isset($gcontact['addr'])) {
				$gcontact['addr'] = $gcnt["addr"];
			}
		}

		if ((!isset($gcontact['network']) || !isset($gcontact['name']) || !isset($gcontact['addr']) || !isset($gcontact['photo']) || !isset($gcontact['server_url']) || $alternate)
			&& PortableContact::reachable($gcontact['url'], $gcontact['server_url'], $gcontact['network'], false)
		) {
			$data = Probe::uri($gcontact['url']);

			if ($data["network"] == Protocol::PHANTOM) {
				throw new Exception('Probing for URL '.$gcontact['url'].' failed');
			}

			$orig_profile = $gcontact['url'];

			$gcontact["server_url"] = $data["baseurl"];

			$gcontact = array_merge($gcontact, $data);

			if ($alternate && ($gcontact['network'] == Protocol::OSTATUS)) {
				// Delete the old entry - if it exists
				if (DBA::exists('gcontact', ['nurl' => normalise_link($orig_profile)])) {
					DBA::delete('gcontact', ['nurl' => normalise_link($orig_profile)]);
				}
			}
		}

		if (!isset($gcontact['name']) || !isset($gcontact['photo'])) {
			throw new Exception('No name and photo for URL '.$gcontact['url']);
		}

		if (!in_array($gcontact['network'], [Protocol::DFRN, Protocol::OSTATUS, Protocol::DIASPORA])) {
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
	 * @param integer $uid id
	 * @param integer $cid id
	 * @return integer
	 */
	public static function countCommonFriends($uid, $cid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			WHERE `glink`.`cid` = %d AND `glink`.`uid` = %d AND
			((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR
			(`gcontact`.`updated` >= `gcontact`.`last_failure`))
			AND `gcontact`.`nurl` IN (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 and id != %d ) ",
			intval($cid),
			intval($uid),
			intval($uid),
			intval($cid)
		);

		// logger("countCommonFriends: $uid $cid {$r[0]['total']}");
		if (DBA::isResult($r)) {
			return $r[0]['total'];
		}
		return 0;
	}

	/**
	 * @param integer $uid  id
	 * @param integer $zcid zcid
	 * @return integer
	 */
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

		if (DBA::isResult($r)) {
			return $r[0]['total'];
		}

		return 0;
	}

	/**
	 * @param integer $uid     user
	 * @param integer $cid     cid
	 * @param integer $start   optional, default 0
	 * @param integer $limit   optional, default 9999
	 * @param boolean $shuffle optional, default false
	 * @return object
	 */
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

		/// @TODO Check all calling-findings of this function if they properly use DBA::isResult()
		return $r;
	}

	/**
	 * @param integer $uid     user
	 * @param integer $zcid    zcid
	 * @param integer $start   optional, default 0
	 * @param integer $limit   optional, default 9999
	 * @param boolean $shuffle optional, default false
	 * @return object
	 */
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

		/// @TODO Check all calling-findings of this function if they properly use DBA::isResult()
		return $r;
	}

	/**
	 * @param integer $uid user
	 * @param integer $cid cid
	 * @return integer
	 */
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

		if (DBA::isResult($r)) {
			return $r[0]['total'];
		}

		return 0;
	}

	/**
	 * @param integer $uid   user
	 * @param integer $cid   cid
	 * @param integer $start optional, default 0
	 * @param integer $limit optional, default 80
	 * @return array
	 */
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

		/// @TODO Check all calling-findings of this function if they properly use DBA::isResult()
		return $r;
	}

	/**
	 * @param object  $uid   user
	 * @param integer $start optional, default 0
	 * @param integer $limit optional, default 80
	 * @return array
	 */
	public static function suggestionQuery($uid, $start = 0, $limit = 80)
	{
		if (!$uid) {
			return [];
		}

		/*
		* Uncommented because the result of the queries are to big to store it in the cache.
		* We need to decide if we want to change the db column type or if we want to delete it.
		*/
		//$list = Cache::get("suggestion_query:".$uid.":".$start.":".$limit);
		//if (!is_null($list)) {
		//	return $list;
		//}

		$network = [Protocol::DFRN];

		if (Config::get('system', 'diaspora_enabled')) {
			$network[] = Protocol::DIASPORA;
		}

		if (!Config::get('system', 'ostatus_disabled')) {
			$network[] = Protocol::OSTATUS;
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
			DBA::escape(NULL_DATE),
			$sql_network,
			intval($start),
			intval($limit)
		);

		if (DBA::isResult($r) && count($r) >= ($limit -1)) {
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
			DBA::escape(NULL_DATE),
			$sql_network,
			intval($start),
			intval($limit)
		);

		$list = [];
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

	/**
	 * @return void
	 */
	public static function updateSuggestions()
	{
		$a = get_app();

		$done = [];

		/// @TODO Check if it is really neccessary to poll the own server
		PortableContact::loadWorker(0, 0, 0, System::baseUrl() . '/poco');

		$done[] = System::baseUrl() . '/poco';

		if (strlen(Config::get('system', 'directory'))) {
			$x = Network::fetchUrl(get_server()."/pubsites");
			if (!empty($x)) {
				$j = json_decode($x);
				if (!empty($j->entries)) {
					foreach ($j->entries as $entry) {
						PortableContact::checkServer($entry->url);

						$url = $entry->url . '/poco';
						if (!in_array($url, $done)) {
							PortableContact::loadWorker(0, 0, 0, $url);
							$done[] = $url;
						}
					}
				}
			}
		}

		// Query your contacts from Friendica and Redmatrix/Hubzilla for their contacts
		$r = q(
			"SELECT DISTINCT(`poco`) AS `poco` FROM `contact` WHERE `network` IN ('%s', '%s')",
			DBA::escape(Protocol::DFRN),
			DBA::escape(Protocol::DIASPORA)
		);

		if (DBA::isResult($r)) {
			foreach ($r as $rr) {
				$base = substr($rr['poco'], 0, strrpos($rr['poco'], '/'));
				if (! in_array($base, $done)) {
					PortableContact::loadWorker(0, 0, 0, $base);
				}
			}
		}
	}

	/**
	 * @brief Removes unwanted parts from a contact url
	 *
	 * @param string $url Contact url
	 *
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
	 * @param array $contact contact array (called by reference)
	 * @return void
	 */
	public static function fixAlternateContactAddress(&$contact)
	{
		if (($contact["network"] == Protocol::OSTATUS) && PortableContact::alternateOStatusUrl($contact["url"])) {
			$data = Probe::uri($contact["url"]);
			if ($contact["network"] == Protocol::OSTATUS) {
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
	 * @param array $contact contact array
	 *
	 * @return bool|int Returns false if not found, integer if contact was found
	 */
	public static function getId($contact)
	{
		$gcontact_id = 0;
		$doprobing = false;
		$last_failure_str = '';
		$last_contact_str = '';

		if (empty($contact["network"])) {
			logger("Empty network for contact url ".$contact["url"]." - Called by: ".System::callstack(), LOGGER_DEBUG);
			return false;
		}

		if (in_array($contact["network"], [Protocol::PHANTOM])) {
			logger("Invalid network for contact url ".$contact["url"]." - Called by: ".System::callstack(), LOGGER_DEBUG);
			return false;
		}

		if ($contact["network"] == Protocol::STATUSNET) {
			$contact["network"] = Protocol::OSTATUS;
		}

		// All new contacts are hidden by default
		if (!isset($contact["hide"])) {
			$contact["hide"] = true;
		}

		// Replace alternate OStatus user format with the primary one
		self::fixAlternateContactAddress($contact);

		// Remove unwanted parts from the contact url (e.g. "?zrl=...")
		if (in_array($contact["network"], [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS])) {
			$contact["url"] = self::cleanContactUrl($contact["url"]);
		}

		DBA::lock('gcontact');
		$fields = ['id', 'last_contact', 'last_failure', 'network'];
		$gcnt = DBA::selectFirst('gcontact', $fields, ['nurl' => normalise_link($contact["url"])]);
		if (DBA::isResult($gcnt)) {
			$gcontact_id = $gcnt["id"];

			// Update every 90 days
			if (in_array($gcnt["network"], [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, ""])) {
				$last_failure_str = $gcnt["last_failure"];
				$last_failure = strtotime($gcnt["last_failure"]);
				$last_contact_str = $gcnt["last_contact"];
				$last_contact = strtotime($gcnt["last_contact"]);
				$doprobing = (((time() - $last_contact) > (90 * 86400)) && ((time() - $last_failure) > (90 * 86400)));
			}
		} else {
			$contact['location'] = defaults($contact, 'location', '');
			$contact['about'] = defaults($contact, 'about', '');
			$contact['generation'] = defaults($contact, 'generation', 0);

			q(
				"INSERT INTO `gcontact` (`name`, `nick`, `addr` , `network`, `url`, `nurl`, `photo`, `created`, `updated`, `location`, `about`, `hide`, `generation`)
				VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, %d)",
				DBA::escape($contact["name"]),
				DBA::escape($contact["nick"]),
				DBA::escape($contact["addr"]),
				DBA::escape($contact["network"]),
				DBA::escape($contact["url"]),
				DBA::escape(normalise_link($contact["url"])),
				DBA::escape($contact["photo"]),
				DBA::escape(DateTimeFormat::utcNow()),
				DBA::escape(DateTimeFormat::utcNow()),
				DBA::escape($contact["location"]),
				DBA::escape($contact["about"]),
				intval($contact["hide"]),
				intval($contact["generation"])
			);

			$condition = ['nurl' => normalise_link($contact["url"])];
			$cnt = DBA::selectFirst('gcontact', ['id', 'network'], $condition, ['order' => ['id']]);
			if (DBA::isResult($cnt)) {
				$gcontact_id = $cnt["id"];
				$doprobing = in_array($cnt["network"], [Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, ""]);
			}
		}
		DBA::unlock();

		if ($doprobing) {
			logger("Last Contact: ". $last_contact_str." - Last Failure: ".$last_failure_str." - Checking: ".$contact["url"], LOGGER_DEBUG);
			Worker::add(PRIORITY_LOW, 'GProbe', $contact["url"]);
		}

		return $gcontact_id;
	}

	/**
	 * @brief Updates the gcontact table from a given array
	 *
	 * @param array $contact contact array
	 *
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

		$public_contact = q(
			"SELECT `name`, `nick`, `photo`, `location`, `about`, `addr`, `generation`, `birthday`, `gender`, `keywords`,
				`contact-type`, `hide`, `nsfw`, `network`, `alias`, `notify`, `server_url`, `connect`, `updated`, `url`
			FROM `gcontact` WHERE `id` = %d LIMIT 1",
			intval($gcontact_id)
		);

		// Get all field names
		$fields = [];
		foreach ($public_contact[0] as $field => $data) {
			$fields[$field] = $data;
		}

		unset($fields["url"]);
		unset($fields["updated"]);
		unset($fields["hide"]);

		// Bugfix: We had an error in the storing of keywords which lead to the "0"
		// This value is still transmitted via poco.
		if (!empty($contact["keywords"]) && ($contact["keywords"] == "0")) {
			unset($contact["keywords"]);
		}

		if (!empty($public_contact[0]["keywords"]) && ($public_contact[0]["keywords"] == "0")) {
			$public_contact[0]["keywords"] = "";
		}

		// assign all unassigned fields from the database entry
		foreach ($fields as $field => $data) {
			if (!isset($contact[$field]) || ($contact[$field] == "")) {
				$contact[$field] = $public_contact[0][$field];
			}
		}

		if (!isset($contact["hide"])) {
			$contact["hide"] = $public_contact[0]["hide"];
		}

		$fields["hide"] = $public_contact[0]["hide"];

		if ($contact["network"] == Protocol::STATUSNET) {
			$contact["network"] = Protocol::OSTATUS;
		}

		// Replace alternate OStatus user format with the primary one
		self::fixAlternateContactAddress($contact);

		if (!isset($contact["updated"])) {
			$contact["updated"] = DateTimeFormat::utcNow();
		}

		if ($contact["network"] == Protocol::TWITTER) {
			$contact["server_url"] = 'http://twitter.com';
		}

		if ($contact["server_url"] == "") {
			$data = Probe::uri($contact["url"]);
			if ($data["network"] != Protocol::PHANTOM) {
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

		if ((($contact["generation"] > 0) && ($contact["generation"] <= $public_contact[0]["generation"])) || ($public_contact[0]["generation"] == 0)) {
			foreach ($fields as $field => $data) {
				if ($contact[$field] != $public_contact[0][$field]) {
					logger("Difference for contact ".$contact["url"]." in field '".$field."'. New value: '".$contact[$field]."', old value '".$public_contact[0][$field]."'", LOGGER_DEBUG);
					$update = true;
				}
			}

			if ($contact["generation"] < $public_contact[0]["generation"]) {
				logger("Difference for contact ".$contact["url"]." in field 'generation'. new value: '".$contact["generation"]."', old value '".$public_contact[0]["generation"]."'", LOGGER_DEBUG);
				$update = true;
			}
		}

		if ($update) {
			logger("Update gcontact for ".$contact["url"], LOGGER_DEBUG);
			$condition = ['`nurl` = ? AND (`generation` = 0 OR `generation` >= ?)',
					normalise_link($contact["url"]), $contact["generation"]];
			$contact["updated"] = DateTimeFormat::utc($contact["updated"]);

			$updated = ['photo' => $contact['photo'], 'name' => $contact['name'],
					'nick' => $contact['nick'], 'addr' => $contact['addr'],
					'network' => $contact['network'], 'birthday' => $contact['birthday'],
					'gender' => $contact['gender'], 'keywords' => $contact['keywords'],
					'hide' => $contact['hide'], 'nsfw' => $contact['nsfw'],
					'contact-type' => $contact['contact-type'], 'alias' => $contact['alias'],
					'notify' => $contact['notify'], 'url' => $contact['url'],
					'location' => $contact['location'], 'about' => $contact['about'],
					'generation' => $contact['generation'], 'updated' => $contact['updated'],
					'server_url' => $contact['server_url'], 'connect' => $contact['connect']];

			DBA::update('gcontact', $updated, $condition, $fields);

			// Now update the contact entry with the user id "0" as well.
			// This is used for the shadow copies of public items.
			/// @todo Check if we really should do this.
			// The quality of the gcontact table is mostly lower than the public contact
			$public_contact = DBA::selectFirst('contact', ['id'], ['nurl' => normalise_link($contact["url"]), 'uid' => 0]);
			if (DBA::isResult($public_contact)) {
				logger("Update public contact ".$public_contact["id"], LOGGER_DEBUG);

				Contact::updateAvatar($contact["photo"], 0, $public_contact["id"]);

				$fields = ['name', 'nick', 'addr',
						'network', 'bd', 'gender',
						'keywords', 'alias', 'contact-type',
						'url', 'location', 'about'];
				$old_contact = DBA::selectFirst('contact', $fields, ['id' => $public_contact["id"]]);

				// Update it with the current values
				$fields = ['name' => $contact['name'], 'nick' => $contact['nick'],
						'addr' => $contact['addr'], 'network' => $contact['network'],
						'bd' => $contact['birthday'], 'gender' => $contact['gender'],
						'keywords' => $contact['keywords'], 'alias' => $contact['alias'],
						'contact-type' => $contact['contact-type'], 'url' => $contact['url'],
						'location' => $contact['location'], 'about' => $contact['about']];

				// Don't update the birthday field if not set or invalid
				if (empty($contact['birthday']) || ($contact['birthday'] < '0001-01-01')) {
					unset($fields['bd']);
				}


				DBA::update('contact', $fields, ['id' => $public_contact["id"]], $old_contact);
			}
		}

		return $gcontact_id;
	}

	/**
	 * @brief Updates the gcontact entry from probe
	 *
	 * @param string $url profile link
	 * @return void
	 */
	public static function updateFromProbe($url)
	{
		$data = Probe::uri($url);

		if (in_array($data["network"], [Protocol::PHANTOM])) {
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
	 * @return void
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

		if (!DBA::isResult($r)) {
			logger('Cannot find user with uid=' . $uid, LOGGER_INFO);
			return false;
		}

		$location = Profile::formatLocation(
			["locality" => $r[0]["locality"], "region" => $r[0]["region"], "country-name" => $r[0]["country-name"]]
		);

		// The "addr" field was added in 3.4.3 so it can be empty for older users
		if ($r[0]["addr"] != "") {
			$addr = $r[0]["nickname"].'@'.str_replace(["http://", "https://"], "", System::baseUrl());
		} else {
			$addr = $r[0]["addr"];
		}

		$gcontact = ["name" => $r[0]["name"], "location" => $location, "about" => $r[0]["about"],
				"gender" => $r[0]["gender"], "keywords" => $r[0]["pub_keywords"],
				"birthday" => $r[0]["dob"], "photo" => $r[0]["photo"],
				"notify" => $r[0]["notify"], "url" => $r[0]["url"],
				"hide" => ($r[0]["hidewall"] || !$r[0]["net-publish"]),
				"nick" => $r[0]["nickname"], "addr" => $addr,
				"connect" => $addr, "server_url" => System::baseUrl(),
				"generation" => 1, "network" => Protocol::DFRN];

		self::update($gcontact);
	}

	/**
	 * @brief Fetches users of given GNU Social server
	 *
	 * If the "Statistics" addon is enabled (See http://gstools.org/ for details) we query user data with this.
	 *
	 * @param string $server Server address
	 * @return void
	 */
	public static function fetchGsUsers($server)
	{
		logger("Fetching users from GNU Social server ".$server, LOGGER_DEBUG);

		$url = $server."/main/statistics";

		$result = Network::curl($url);
		if (!$result["success"]) {
			return false;
		}

		$statistics = json_decode($result["body"]);

		if (!empty($statistics->config)) {
			if ($statistics->config->instance_with_ssl) {
				$server = "https://";
			} else {
				$server = "http://";
			}

			$server .= $statistics->config->instance_address;

			$hostname = $statistics->config->instance_address;
		} elseif (!empty($statistics)) {
			if ($statistics->instance_with_ssl) {
				$server = "https://";
			} else {
				$server = "http://";
			}

			$server .= $statistics->instance_address;

			$hostname = $statistics->instance_address;
		}

		if (!empty($statistics->users)) {
			foreach ($statistics->users as $nick => $user) {
				$profile_url = $server."/".$user->nickname;

				$contact = ["url" => $profile_url,
						"name" => $user->fullname,
						"addr" => $user->nickname."@".$hostname,
						"nick" => $user->nickname,
						"network" => Protocol::OSTATUS,
						"photo" => System::baseUrl()."/images/person-175.jpg"];

				if (isset($user->bio)) {
					$contact["about"] = $user->bio;
				}

				self::getId($contact);
			}
		}
	}

	/**
	 * @brief Asking GNU Social server on a regular base for their user data
	 * @return void
	 */
	public static function discoverGsUsers()
	{
		$requery_days = intval(Config::get("system", "poco_requery_days"));

		$last_update = date("c", time() - (60 * 60 * 24 * $requery_days));

		$r = q(
			"SELECT `nurl`, `url` FROM `gserver` WHERE `last_contact` >= `last_failure` AND `network` = '%s' AND `last_poco_query` < '%s' ORDER BY RAND() LIMIT 5",
			DBA::escape(Protocol::OSTATUS),
			DBA::escape($last_update)
		);

		if (!DBA::isResult($r)) {
			return;
		}

		foreach ($r as $server) {
			self::fetchGsUsers($server["url"]);
			q("UPDATE `gserver` SET `last_poco_query` = '%s' WHERE `nurl` = '%s'", DBA::escape(DateTimeFormat::utcNow()), DBA::escape($server["nurl"]));
		}
	}

	/**
	 * @return string
	 */
	public static function getRandomUrl()
	{
		$r = q(
			"SELECT `url` FROM `gcontact` WHERE `network` = '%s'
					AND `last_contact` >= `last_failure`
					AND `updated` > UTC_TIMESTAMP - INTERVAL 1 MONTH
				ORDER BY rand() LIMIT 1",
			DBA::escape(Protocol::DFRN)
		);

		if (DBA::isResult($r)) {
			return dirname($r[0]['url']);
		}

		return '';
	}
}
