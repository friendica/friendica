<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Model;

use DOMDocument;
use DOMXPath;
use Exception;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Search;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Network\HTTPRequest;
use Friendica\Network\Probe;
use Friendica\Protocol\ActivityPub;
use Friendica\Protocol\PortableContact;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

/**
 * This class handles GlobalContact related functions
 */
class GContact
{
	/**
	 * Search global contact table by nick or name
	 *
	 * @param string $search Name or nick
	 * @param string $mode   Search mode (e.g. "community")
	 *
	 * @return array with search results
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function searchByName($search, $mode = '')
	{
		if (empty($search)) {
			return [];
		}

		// check supported networks
		if (DI::config()->get('system', 'diaspora_enabled')) {
			$diaspora = Protocol::DIASPORA;
		} else {
			$diaspora = Protocol::DFRN;
		}

		if (!DI::config()->get('system', 'ostatus_disabled')) {
			$ostatus = Protocol::OSTATUS;
		} else {
			$ostatus = Protocol::DFRN;
		}

		// check if we search only communities or every contact
		if ($mode === 'community') {
			$extra_sql = ' AND `community`';
		} else {
			$extra_sql = '';
		}

		$search .= '%';

		$results = DBA::p("SELECT `nurl` FROM `gcontact`
			WHERE NOT `hide` AND `network` IN (?, ?, ?, ?) AND
				((`last_contact` >= `last_failure`) OR (`updated` >= `last_failure`)) AND
				(`addr` LIKE ? OR `name` LIKE ? OR `nick` LIKE ?) $extra_sql
				GROUP BY `nurl` ORDER BY `nurl` DESC LIMIT 1000",
			Protocol::DFRN, Protocol::ACTIVITYPUB, $ostatus, $diaspora, $search, $search, $search
		);

		$gcontacts = [];
		while ($result = DBA::fetch($results)) {
			$urlparts = parse_url($result['nurl']);

			// Ignore results that look strange.
			// For historic reasons the gcontact table does contain some garbage.
			if (!empty($urlparts['query']) || !empty($urlparts['fragment'])) {
				continue;
			}

			$gcontacts[] = Contact::getDetailsByURL($result['nurl'], local_user());
		}
		return $gcontacts;
	}

	/**
	 * Link the gcontact entry with user, contact and global contact
	 *
	 * @param integer $gcid Global contact ID
	 * @param integer $uid  User ID
	 * @param integer $cid  Contact ID
	 * @param integer $zcid Global Contact ID
	 * @return void
	 * @throws Exception
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
	 * Sanitize the given gcontact data
	 *
	 * Generation:
	 *  0: No definition
	 *  1: Profiles on this server
	 *  2: Contacts of profiles on this server
	 *  3: Contacts of contacts of profiles on this server
	 *  4: ...
	 *
	 * @param array $gcontact array with gcontact data
	 * @return array $gcontact
	 * @throws Exception
	 */
	public static function sanitize($gcontact)
	{
		if (empty($gcontact['url'])) {
			throw new Exception('URL is empty');
		}

		$gcontact['server_url'] = $gcontact['server_url'] ?? '';

		$urlparts = parse_url($gcontact['url']);
		if (empty($urlparts['scheme'])) {
			throw new Exception('This (' . $gcontact['url'] . ") doesn't seem to be an url.");
		}

		if (in_array($urlparts['host'],	['twitter.com', 'identi.ca'])) {
			throw new Exception('Contact from a non federated network ignored. (' . $gcontact['url'] . ')');
		}

		// Don't store the statusnet connector as network
		// We can't simply set this to Protocol::OSTATUS since the connector could have fetched posts from friendica as well
		if ($gcontact['network'] == Protocol::STATUSNET) {
			$gcontact['network'] = '';
		}

		// Assure that there are no parameter fragments in the profile url
		if (empty($gcontact['*network']) || in_array($gcontact['network'], Protocol::FEDERATED)) {
			$gcontact['url'] = self::cleanContactUrl($gcontact['url']);
		}

		// The global contacts should contain the original picture, not the cached one
		if (($gcontact['generation'] != 1) && stristr(Strings::normaliseLink($gcontact['photo']), Strings::normaliseLink(DI::baseUrl() . '/photo/'))) {
			$gcontact['photo'] = '';
		}

		if (empty($gcontact['network'])) {
			$gcontact['network'] = '';

			$condition = ["`uid` = 0 AND `nurl` = ? AND `network` != '' AND `network` != ?",
				Strings::normaliseLink($gcontact['url']), Protocol::STATUSNET];
			$contact = DBA::selectFirst('contact', ['network'], $condition);
			if (DBA::isResult($contact)) {
				$gcontact['network'] = $contact['network'];
			}

			if (($gcontact['network'] == '') || ($gcontact['network'] == Protocol::OSTATUS)) {
				$condition = ["`uid` = 0 AND `alias` IN (?, ?) AND `network` != '' AND `network` != ?",
					$gcontact['url'], Strings::normaliseLink($gcontact['url']), Protocol::STATUSNET];
				$contact = DBA::selectFirst('contact', ['network'], $condition);
				if (DBA::isResult($contact)) {
					$gcontact['network'] = $contact['network'];
				}
			}
		}

		$fields = ['network', 'updated', 'server_url', 'url', 'addr'];
		$gcnt = DBA::selectFirst('gcontact', $fields, ['nurl' => Strings::normaliseLink($gcontact['url'])]);
		if (DBA::isResult($gcnt)) {
			if (!isset($gcontact['network']) && ($gcnt['network'] != Protocol::STATUSNET)) {
				$gcontact['network'] = $gcnt['network'];
			}
			if ($gcontact['updated'] <= DBA::NULL_DATETIME) {
				$gcontact['updated'] = $gcnt['updated'];
			}
			if (!isset($gcontact['server_url']) && (Strings::normaliseLink($gcnt['server_url']) != Strings::normaliseLink($gcnt['url']))) {
				$gcontact['server_url'] = $gcnt['server_url'];
			}
			if (!isset($gcontact['addr'])) {
				$gcontact['addr'] = $gcnt['addr'];
			}
		}

		if ((!isset($gcontact['network']) || !isset($gcontact['name']) || !isset($gcontact['addr']) || !isset($gcontact['photo']) || !isset($gcontact['server_url']))
			&& GServer::reachable($gcontact['url'], $gcontact['server_url'], $gcontact['network'], false)
		) {
			$data = Probe::uri($gcontact['url']);

			if ($data['network'] == Protocol::PHANTOM) {
				throw new Exception('Probing for URL ' . $gcontact['url'] . ' failed');
			}

			$orig_profile = $gcontact['url'];

			$gcontact['server_url'] = $data['baseurl'];

			$gcontact = array_merge($gcontact, $data);
		}

		if (!isset($gcontact['name']) || !isset($gcontact['photo'])) {
			throw new Exception('No name and photo for URL '.$gcontact['url']);
		}

		if (!in_array($gcontact['network'], Protocol::FEDERATED)) {
			throw new Exception('No federated network (' . $gcontact['network'] . ') detected for URL ' . $gcontact['url']);
		}

		if (empty($gcontact['server_url'])) {
			// We check the server url to be sure that it is a real one
			$server_url = self::getBasepath($gcontact['url']);

			// We are now sure that it is a correct URL. So we use it in the future
			if ($server_url != '') {
				$gcontact['server_url'] = $server_url;
			}
		}

		// The server URL doesn't seem to be valid, so we don't store it.
		if (!GServer::check($gcontact['server_url'], $gcontact['network'])) {
			$gcontact['server_url'] = '';
		}

		return $gcontact;
	}

	/**
	 * @param integer $uid id
	 * @param integer $cid id
	 * @return integer
	 * @throws Exception
	 */
	public static function countCommonFriends($uid, $cid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			WHERE `glink`.`cid` = %d AND `glink`.`uid` = %d AND
			((`gcontact`.`last_contact` >= `gcontact`.`last_failure`) OR
			(`gcontact`.`updated` >= `gcontact`.`last_failure`))
			AND `gcontact`.`nurl` IN (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0 and id != %d) ",
			intval($cid),
			intval($uid),
			intval($uid),
			intval($cid)
		);

		if (DBA::isResult($r)) {
			return $r[0]['total'];
		}
		return 0;
	}

	/**
	 * @param integer $uid  id
	 * @param integer $zcid zcid
	 * @return integer
	 * @throws Exception
	 */
	public static function countCommonFriendsZcid($uid, $zcid)
	{
		$r = q(
			"SELECT count(*) as `total`
			FROM `glink` INNER JOIN `gcontact` on `glink`.`gcid` = `gcontact`.`id`
			where `glink`.`zcid` = %d
			and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0) ",
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
	 * @throws Exception
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
	 * @throws Exception
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
			and `gcontact`.`nurl` in (select nurl from contact where uid = %d and self = 0 and blocked = 0 and hidden = 0)
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
	 * @throws Exception
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
	 * @throws Exception
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
	 * @param int     $uid   user
	 * @param integer $start optional, default 0
	 * @param integer $limit optional, default 80
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function suggestionQuery($uid, $start = 0, $limit = 80)
	{
		if (!$uid) {
			return [];
		}

		$network = [Protocol::DFRN, Protocol::ACTIVITYPUB];

		if (DI::config()->get('system', 'diaspora_enabled')) {
			$network[] = Protocol::DIASPORA;
		}

		if (!DI::config()->get('system', 'ostatus_disabled')) {
			$network[] = Protocol::OSTATUS;
		}

		$sql_network = "'" . implode("', '", $network) . "'";

		/// @todo This query is really slow
		// By now we cache the data for five minutes
		$r = q(
			"SELECT count(glink.gcid) as `total`, gcontact.* from gcontact
			INNER JOIN `glink` ON `glink`.`gcid` = `gcontact`.`id`
			where uid = %d and not gcontact.nurl in ( select nurl from contact where uid = %d )
			AND NOT `gcontact`.`name` IN (SELECT `name` FROM `contact` WHERE `uid` = %d)
			AND NOT `gcontact`.`id` IN (SELECT `gcid` FROM `gcign` WHERE `uid` = %d)
			AND `gcontact`.`updated` >= '%s' AND NOT `gcontact`.`hide`
			AND `gcontact`.`last_contact` >= `gcontact`.`last_failure`
			AND `gcontact`.`network` IN (%s)
			GROUP BY `glink`.`gcid` ORDER BY `gcontact`.`updated` DESC,`total` DESC LIMIT %d, %d",
			intval($uid),
			intval($uid),
			intval($uid),
			intval($uid),
			DBA::NULL_DATETIME,
			$sql_network,
			intval($start),
			intval($limit)
		);

		if (DBA::isResult($r) && count($r) >= ($limit -1)) {
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
			DBA::NULL_DATETIME,
			$sql_network,
			intval($start),
			intval($limit)
		);

		$list = [];
		foreach ($r2 as $suggestion) {
			$list[$suggestion['nurl']] = $suggestion;
		}

		foreach ($r as $suggestion) {
			$list[$suggestion['nurl']] = $suggestion;
		}

		while (sizeof($list) > ($limit)) {
			array_pop($list);
		}

		return $list;
	}

	/**
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function updateSuggestions()
	{
		$done = [];

		/// @TODO Check if it is really neccessary to poll the own server
		PortableContact::loadWorker(0, 0, 0, DI::baseUrl() . '/poco');

		$done[] = DI::baseUrl() . '/poco';

		if (strlen(DI::config()->get('system', 'directory'))) {
			$x = DI::httpRequest()->fetchUrl(Search::getGlobalDirectory() . '/pubsites');
			if (!empty($x)) {
				$j = json_decode($x);
				if (!empty($j->entries)) {
					foreach ($j->entries as $entry) {
						GServer::check($entry->url);

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
		$contacts = DBA::p("SELECT DISTINCT(`poco`) AS `poco` FROM `contact` WHERE `network` IN (?, ?)", Protocol::DFRN, Protocol::DIASPORA);
		while ($contact = DBA::fetch($contacts)) {
			$base = substr($contact['poco'], 0, strrpos($contact['poco'], '/'));
			if (!in_array($base, $done)) {
				PortableContact::loadWorker(0, 0, 0, $base);
			}
		}
	}

	/**
	 * Removes unwanted parts from a contact url
	 *
	 * @param string $url Contact url
	 *
	 * @return string Contact url with the wanted parts
	 * @throws Exception
	 */
	public static function cleanContactUrl($url)
	{
		$parts = parse_url($url);

		if (empty($parts['scheme']) || empty($parts['host'])) {
			return $url;
		}

		$new_url = $parts['scheme'] . '://' . $parts['host'];

		if (!empty($parts['port'])) {
			$new_url .= ':' . $parts['port'];
		}

		if (!empty($parts['path'])) {
			$new_url .= $parts['path'];
		}

		if ($new_url != $url) {
			Logger::info('Cleaned contact url', ['url' => $url, 'new_url' => $new_url, 'callstack' => System::callstack()]);
		}

		return $new_url;
	}

	/**
	 * Fetch the gcontact id, add an entry if not existed
	 *
	 * @param array $contact contact array
	 *
	 * @return bool|int Returns false if not found, integer if contact was found
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getId($contact)
	{
		$gcontact_id = 0;

		if (empty($contact['network'])) {
			Logger::notice('Empty network', ['url' => $contact['url'], 'callstack' => System::callstack()]);
			return false;
		}

		if (in_array($contact['network'], [Protocol::PHANTOM])) {
			Logger::notice('Invalid network', ['url' => $contact['url'], 'callstack' => System::callstack()]);
			return false;
		}

		if ($contact['network'] == Protocol::STATUSNET) {
			$contact['network'] = Protocol::OSTATUS;
		}

		// All new contacts are hidden by default
		if (!isset($contact['hide'])) {
			$contact['hide'] = true;
		}

		// Remove unwanted parts from the contact url (e.g. '?zrl=...')
		if (in_array($contact['network'], Protocol::FEDERATED)) {
			$contact['url'] = self::cleanContactUrl($contact['url']);
		}

		DBA::lock('gcontact');
		$fields = ['id', 'last_contact', 'last_failure', 'network'];
		$gcnt = DBA::selectFirst('gcontact', $fields, ['nurl' => Strings::normaliseLink($contact['url'])]);
		if (DBA::isResult($gcnt)) {
			$gcontact_id = $gcnt['id'];
		} else {
			$contact['location'] = $contact['location'] ?? '';
			$contact['about'] = $contact['about'] ?? '';
			$contact['generation'] = $contact['generation'] ?? 0;

			$fields = ['name' => $contact['name'], 'nick' => $contact['nick'] ?? '', 'addr' => $contact['addr'] ?? '', 'network' => $contact['network'],
				'url' => $contact['url'], 'nurl' => Strings::normaliseLink($contact['url']), 'photo' => $contact['photo'],
				'created' => DateTimeFormat::utcNow(), 'updated' => DateTimeFormat::utcNow(), 'location' => $contact['location'],
				'about' => $contact['about'], 'hide' => $contact['hide'], 'generation' => $contact['generation']];

			DBA::insert('gcontact', $fields);

			$condition = ['nurl' => Strings::normaliseLink($contact['url'])];
			$cnt = DBA::selectFirst('gcontact', ['id', 'network'], $condition, ['order' => ['id']]);
			if (DBA::isResult($cnt)) {
				$gcontact_id = $cnt['id'];
			}
		}
		DBA::unlock();

		return $gcontact_id;
	}

	/**
	 * Updates the gcontact table from a given array
	 *
	 * @param array $contact contact array
	 *
	 * @return bool|int Returns false if not found, integer if contact was found
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
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

		$public_contact = DBA::selectFirst('gcontact', [
			'name', 'nick', 'photo', 'location', 'about', 'addr', 'generation', 'birthday', 'keywords',
			'contact-type', 'hide', 'nsfw', 'network', 'alias', 'notify', 'server_url', 'connect', 'updated', 'url'
		], ['id' => $gcontact_id]);

		if (!DBA::isResult($public_contact)) {
			return false;
		}

		// Get all field names
		$fields = [];
		foreach ($public_contact as $field => $data) {
			$fields[$field] = $data;
		}

		unset($fields['url']);
		unset($fields['updated']);
		unset($fields['hide']);

		// Bugfix: We had an error in the storing of keywords which lead to the "0"
		// This value is still transmitted via poco.
		if (isset($contact['keywords']) && ($contact['keywords'] == '0')) {
			unset($contact['keywords']);
		}

		if (isset($public_contact['keywords']) && ($public_contact['keywords'] == '0')) {
			$public_contact['keywords'] = '';
		}

		// assign all unassigned fields from the database entry
		foreach ($fields as $field => $data) {
			if (empty($contact[$field])) {
				$contact[$field] = $public_contact[$field];
			}
		}

		if (!isset($contact['hide'])) {
			$contact['hide'] = $public_contact['hide'];
		}

		$fields['hide'] = $public_contact['hide'];

		if ($contact['network'] == Protocol::STATUSNET) {
			$contact['network'] = Protocol::OSTATUS;
		}

		if (!isset($contact['updated'])) {
			$contact['updated'] = DateTimeFormat::utcNow();
		}

		if ($contact['network'] == Protocol::TWITTER) {
			$contact['server_url'] = 'http://twitter.com';
		}

		if (empty($contact['server_url'])) {
			$data = Probe::uri($contact['url']);
			if ($data['network'] != Protocol::PHANTOM) {
				$contact['server_url'] = $data['baseurl'];
			}
		} else {
			$contact['server_url'] = Strings::normaliseLink($contact['server_url']);
		}

		if (empty($contact['addr']) && !empty($contact['server_url']) && !empty($contact['nick'])) {
			$hostname = str_replace('http://', '', $contact['server_url']);
			$contact['addr'] = $contact['nick'] . '@' . $hostname;
		}

		// Check if any field changed
		$update = false;
		unset($fields['generation']);

		if ((($contact['generation'] > 0) && ($contact['generation'] <= $public_contact['generation'])) || ($public_contact['generation'] == 0)) {
			foreach ($fields as $field => $data) {
				if ($contact[$field] != $public_contact[$field]) {
					Logger::debug('Difference found.', ['contact' => $contact['url'], 'field' => $field, 'new' => $contact[$field], 'old' => $public_contact[$field]]);
					$update = true;
				}
			}

			if ($contact['generation'] < $public_contact['generation']) {
				Logger::debug('Difference found.', ['contact' => $contact['url'], 'field' => 'generation', 'new' => $contact['generation'], 'old' => $public_contact['generation']]);
				$update = true;
			}
		}

		if ($update) {
			Logger::debug('Update gcontact.', ['contact' => $contact['url']]);
			$condition = ["`nurl` = ? AND (`generation` = 0 OR `generation` >= ?)",
					Strings::normaliseLink($contact['url']), $contact['generation']];
			$contact['updated'] = DateTimeFormat::utc($contact['updated']);

			$updated = [
				'photo' => $contact['photo'], 'name' => $contact['name'],
				'nick' => $contact['nick'], 'addr' => $contact['addr'],
				'network' => $contact['network'], 'birthday' => $contact['birthday'],
				'keywords' => $contact['keywords'],
				'hide' => $contact['hide'], 'nsfw' => $contact['nsfw'],
				'contact-type' => $contact['contact-type'], 'alias' => $contact['alias'],
				'notify' => $contact['notify'], 'url' => $contact['url'],
				'location' => $contact['location'], 'about' => $contact['about'],
				'generation' => $contact['generation'], 'updated' => $contact['updated'],
				'server_url' => $contact['server_url'], 'connect' => $contact['connect']
			];

			DBA::update('gcontact', $updated, $condition, $fields);
		}

		return $gcontact_id;
	}

	/**
	 * Set the last date that the contact had posted something
	 *
	 * @param string $data  Probing result
	 * @param bool   $force force updating
	 */
	public static function setLastUpdate(array $data, bool $force = false)
	{
		// Fetch the global contact
		$gcontact = DBA::selectFirst('gcontact', ['created', 'updated', 'last_contact', 'last_failure'],
			['nurl' => Strings::normaliseLink($data['url'])]);
		if (!DBA::isResult($gcontact)) {
			return;
		}

		if (!$force && !GServer::updateNeeded($gcontact['created'], $gcontact['updated'], $gcontact['last_failure'], $gcontact['last_contact'])) {
			Logger::info("Don't update profile", ['url' => $data['url'], 'updated' => $gcontact['updated']]);
			return;
		}

		if (self::updateFromNoScrape($data)) {
			return;
		}

		if (!empty($data['outbox'])) {
			self::updateFromOutbox($data['outbox'], $data);
		} elseif (!empty($data['poll']) && ($data['network'] == Protocol::ACTIVITYPUB)) {
			self::updateFromOutbox($data['poll'], $data);
		} elseif (!empty($data['poll'])) {
			self::updateFromFeed($data);
		}
	}

	/**
	 * Update a global contact via the "noscrape" endpoint
	 *
	 * @param string $data Probing result
	 *
	 * @return bool 'true' if update was successful or the server was unreachable
	 */
	private static function updateFromNoScrape(array $data)
	{
		// Check the 'noscrape' endpoint when it is a Friendica server
		$gserver = DBA::selectFirst('gserver', ['noscrape'], ["`nurl` = ? AND `noscrape` != ''",
		Strings::normaliseLink($data['baseurl'])]);
		if (!DBA::isResult($gserver)) {
			return false;
		}

		$curlResult = DI::httpRequest()->get($gserver['noscrape'] . '/' . $data['nick']);

		if ($curlResult->isSuccess() && !empty($curlResult->getBody())) {
			$noscrape = json_decode($curlResult->getBody(), true);
			if (!empty($noscrape) && !empty($noscrape['updated'])) {
				$noscrape['updated'] = DateTimeFormat::utc($noscrape['updated'], DateTimeFormat::MYSQL);
				$fields = ['last_contact' => DateTimeFormat::utcNow(), 'updated' => $noscrape['updated']];
				DBA::update('gcontact', $fields, ['nurl' => Strings::normaliseLink($data['url'])]);
				return true;
			}
		} elseif ($curlResult->isTimeout()) {
			// On a timeout return the existing value, but mark the contact as failure
			$fields = ['last_failure' => DateTimeFormat::utcNow()];
			DBA::update('gcontact', $fields, ['nurl' => Strings::normaliseLink($data['url'])]);
			return true;
		}
		return false;
	}

	/**
	 * Update a global contact via an ActivityPub Outbox
	 *
	 * @param string $feed
	 * @param array  $data Probing result
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function updateFromOutbox(string $feed, array $data)
	{
		$outbox = ActivityPub::fetchContent($feed);
		if (empty($outbox)) {
			return;
		}

		if (!empty($outbox['orderedItems'])) {
			$items = $outbox['orderedItems'];
		} elseif (!empty($outbox['first']['orderedItems'])) {
			$items = $outbox['first']['orderedItems'];
		} elseif (!empty($outbox['first']['href'])) {
			self::updateFromOutbox($outbox['first']['href'], $data);
			return;
		} elseif (!empty($outbox['first'])) {
			if (is_string($outbox['first'])) {
				self::updateFromOutbox($outbox['first'], $data);
			} else {
				Logger::warning('Unexpected data', ['outbox' => $outbox]);
			}
			return;
		} else {
			$items = [];
		}

		$last_updated = '';
		foreach ($items as $activity) {
			if (!empty($activity['published'])) {
				$published =  DateTimeFormat::utc($activity['published']);
			} elseif (!empty($activity['object']['published'])) {
				$published =  DateTimeFormat::utc($activity['object']['published']);
			} else {
				continue;
			}

			if ($last_updated < $published) {
				$last_updated = $published;
			}
		}

		if (empty($last_updated)) {
			return;
		}

		$fields = ['last_contact' => DateTimeFormat::utcNow(), 'updated' => $last_updated];
		DBA::update('gcontact', $fields, ['nurl' => Strings::normaliseLink($data['url'])]);
	}

	/**
	 * Update a global contact via an XML feed
	 *
	 * @param string $data Probing result
	 */
	private static function updateFromFeed(array $data)
	{
		// Search for the newest entry in the feed
		$curlResult = DI::httpRequest()->get($data['poll']);
		if (!$curlResult->isSuccess()) {
			$fields = ['last_failure' => DateTimeFormat::utcNow()];
			DBA::update('gcontact', $fields, ['nurl' => Strings::normaliseLink($data['url'])]);

			Logger::info("Profile wasn't reachable (no feed)", ['url' => $data['url']]);
			return;
		}

		$doc = new DOMDocument();
		@$doc->loadXML($curlResult->getBody());

		$xpath = new DOMXPath($doc);
		$xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

		$entries = $xpath->query('/atom:feed/atom:entry');

		$last_updated = '';

		foreach ($entries as $entry) {
			$published_item = $xpath->query('atom:published/text()', $entry)->item(0);
			$updated_item   = $xpath->query('atom:updated/text()'  , $entry)->item(0);
			$published      = !empty($published_item->nodeValue) ? DateTimeFormat::utc($published_item->nodeValue) : null;
			$updated        = !empty($updated_item->nodeValue) ? DateTimeFormat::utc($updated_item->nodeValue) : null;

			if (empty($published) || empty($updated)) {
				Logger::notice('Invalid entry for XPath.', ['entry' => $entry, 'url' => $data['url']]);
				continue;
			}

			if ($last_updated < $published) {
				$last_updated = $published;
			}

			if ($last_updated < $updated) {
				$last_updated = $updated;
			}
		}

		if (empty($last_updated)) {
			return;
		}

		$fields = ['last_contact' => DateTimeFormat::utcNow(), 'updated' => $last_updated];
		DBA::update('gcontact', $fields, ['nurl' => Strings::normaliseLink($data['url'])]);
	}
	/**
	 * Updates the gcontact entry from a given public contact id
	 *
	 * @param integer $cid contact id
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromPublicContactID($cid)
	{
		self::updateFromPublicContact(['id' => $cid]);
	}

	/**
	 * Updates the gcontact entry from a given public contact url
	 *
	 * @param string $url contact url
	 * @return integer gcontact id
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromPublicContactURL($url)
	{
		return self::updateFromPublicContact(['nurl' => Strings::normaliseLink($url)]);
	}

	/**
	 * Helper function for updateFromPublicContactID and updateFromPublicContactURL
	 *
	 * @param array $condition contact condition
	 * @return integer gcontact id
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function updateFromPublicContact($condition)
	{
		$fields = ['name', 'nick', 'url', 'nurl', 'location', 'about', 'keywords',
			'bd', 'contact-type', 'network', 'addr', 'notify', 'alias', 'archive', 'term-date',
			'created', 'updated', 'avatar', 'success_update', 'failure_update', 'forum', 'prv',
			'baseurl', 'sensitive', 'unsearchable'];

		$contact = DBA::selectFirst('contact', $fields, array_merge($condition, ['uid' => 0, 'network' => Protocol::FEDERATED]));
		if (!DBA::isResult($contact)) {
			return 0;
		}

		$fields = ['name', 'nick', 'url', 'nurl', 'location', 'about', 'keywords', 'generation',
			'birthday', 'contact-type', 'network', 'addr', 'notify', 'alias', 'archived', 'archive_date',
			'created', 'updated', 'photo', 'last_contact', 'last_failure', 'community', 'connect',
			'server_url', 'nsfw', 'hide', 'id'];

		$old_gcontact = DBA::selectFirst('gcontact', $fields, ['nurl' => $contact['nurl']]);
		$do_insert = !DBA::isResult($old_gcontact);
		if ($do_insert) {
			$old_gcontact = [];
		}

		$gcontact = [];

		// These fields are identical in both contact and gcontact
		$fields = ['name', 'nick', 'url', 'nurl', 'location', 'about', 'keywords',
			'contact-type', 'network', 'addr', 'notify', 'alias', 'created', 'updated'];

		foreach ($fields as $field) {
			$gcontact[$field] = $contact[$field];
		}

		// These fields are having different names but the same content
		$gcontact['server_url'] = $contact['baseurl'] ?? ''; // "baseurl" can be null, "server_url" not
		$gcontact['nsfw'] = $contact['sensitive'];
		$gcontact['hide'] = $contact['unsearchable'];
		$gcontact['archived'] = $contact['archive'];
		$gcontact['archive_date'] = $contact['term-date'];
		$gcontact['birthday'] = $contact['bd'];
		$gcontact['photo'] = $contact['avatar'];
		$gcontact['last_contact'] = $contact['success_update'];
		$gcontact['last_failure'] = $contact['failure_update'];
		$gcontact['community'] = ($contact['forum'] || $contact['prv']);

		foreach (['last_contact', 'last_failure', 'updated'] as $field) {
			if (!empty($old_gcontact[$field]) && ($old_gcontact[$field] >= $gcontact[$field])) {
				unset($gcontact[$field]);
			}
		}

		if (!$gcontact['archived']) {
			$gcontact['archive_date'] = DBA::NULL_DATETIME;
		}

		if (!empty($old_gcontact['created']) && ($old_gcontact['created'] > DBA::NULL_DATETIME)
			&& ($old_gcontact['created'] <= $gcontact['created'])) {
			unset($gcontact['created']);
		}

		if (empty($gcontact['birthday']) && ($gcontact['birthday'] <= DBA::NULL_DATETIME)) {
			unset($gcontact['birthday']);
		}

		if (empty($old_gcontact['generation']) || ($old_gcontact['generation'] > 2)) {
			$gcontact['generation'] = 2; // We fetched the data directly from the other server
		}

		if (!$do_insert) {
			DBA::update('gcontact', $gcontact, ['nurl' => $contact['nurl']], $old_gcontact);
			return $old_gcontact['id'];
		} elseif (!$gcontact['archived']) {
			DBA::insert('gcontact', $gcontact);
			return DBA::lastInsertId();
		}
	}

	/**
	 * Updates the gcontact entry from probe
	 *
	 * @param string  $url   profile link
	 * @param boolean $force Optional forcing of network probing (otherwise we use the cached data)
	 *
	 * @return boolean 'true' when contact had been updated
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateFromProbe($url, $force = false)
	{
		$data = Probe::uri($url, $force);

		if (in_array($data['network'], [Protocol::PHANTOM])) {
			$fields = ['last_failure' => DateTimeFormat::utcNow()];
			DBA::update('gcontact', $fields, ['nurl' => Strings::normaliseLink($url)]);
			Logger::info('Invalid network for contact', ['url' => $data['url'], 'callstack' => System::callstack()]);
			return false;
		}

		$data['server_url'] = $data['baseurl'];

		self::update($data);

		// Set the date of the latest post
		self::setLastUpdate($data, $force);

		return true;
	}

	/**
	 * Update the gcontact entry for a given user id
	 *
	 * @param int $uid User ID
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function updateForUser($uid)
	{
		$profile = Profile::getByUID($uid);
		if (empty($profile)) {
			Logger::error('Cannot find profile', ['uid' => $uid]);
			return false;
		}

		$user = User::getOwnerDataById($uid);
		if (empty($user)) {
			Logger::error('Cannot find user', ['uid' => $uid]);
			return false;
		}

		$userdata = array_merge($profile, $user);

		$location = Profile::formatLocation(
			['locality' => $userdata['locality'], 'region' => $userdata['region'], 'country-name' => $userdata['country-name']]
		);

		$gcontact = ['name' => $userdata['name'], 'location' => $location, 'about' => $userdata['about'],
				'keywords' => $userdata['pub_keywords'],
				'birthday' => $userdata['dob'], 'photo' => $userdata['photo'],
				"notify" => $userdata['notify'], 'url' => $userdata['url'],
				"hide" => !$userdata['net-publish'],
				'nick' => $userdata['nickname'], 'addr' => $userdata['addr'],
				"connect" => $userdata['addr'], "server_url" => DI::baseUrl(),
				"generation" => 1, 'network' => Protocol::DFRN];

		self::update($gcontact);
	}

	/**
	 * Get the basepath for a given contact link
	 *
	 * @param string $url The gcontact link
	 * @param boolean $dont_update Don't update the contact
	 *
	 * @return string basepath
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getBasepath($url, $dont_update = false)
	{
		$gcontact = DBA::selectFirst('gcontact', ['server_url'], ['nurl' => Strings::normaliseLink($url)]);
		if (!empty($gcontact['server_url'])) {
			return $gcontact['server_url'];
		} elseif ($dont_update) {
			return '';
		}

		self::updateFromProbe($url, true);

		// Fetch the result
		$gcontact = DBA::selectFirst('gcontact', ['server_url'], ['nurl' => Strings::normaliseLink($url)]);
		if (empty($gcontact['server_url'])) {
			Logger::info('No baseurl for gcontact', ['url' => $url]);
			return '';
		}

		Logger::info('Found baseurl for gcontact', ['url' => $url, 'baseurl' => $gcontact['server_url']]);
		return $gcontact['server_url'];
	}

	/**
	 * Fetches users of given GNU Social server
	 *
	 * If the "Statistics" addon is enabled (See http://gstools.org/ for details) we query user data with this.
	 *
	 * @param string $server Server address
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchGsUsers($server)
	{
		Logger::info('Fetching users from GNU Social server', ['server' => $server]);

		$url = $server . '/main/statistics';

		$curlResult = DI::httpRequest()->get($url);
		if (!$curlResult->isSuccess()) {
			return false;
		}

		$statistics = json_decode($curlResult->getBody());

		if (!empty($statistics->config->instance_address)) {
			if (!empty($statistics->config->instance_with_ssl)) {
				$server = 'https://';
			} else {
				$server = 'http://';
			}

			$server .= $statistics->config->instance_address;

			$hostname = $statistics->config->instance_address;
		} elseif (!empty($statistics->instance_address)) {
			if (!empty($statistics->instance_with_ssl)) {
				$server = 'https://';
			} else {
				$server = 'http://';
			}

			$server .= $statistics->instance_address;

			$hostname = $statistics->instance_address;
		}

		if (!empty($statistics->users)) {
			foreach ($statistics->users as $nick => $user) {
				$profile_url = $server . '/' . $user->nickname;

				$contact = ['url' => $profile_url,
						'name' => $user->fullname,
						'addr' => $user->nickname . '@' . $hostname,
						'nick' => $user->nickname,
						"network" => Protocol::OSTATUS,
						'photo' => DI::baseUrl() . '/images/person-300.jpg'];

				if (isset($user->bio)) {
					$contact['about'] = $user->bio;
				}

				self::getId($contact);
			}
		}
	}

	/**
	 * Asking GNU Social server on a regular base for their user data
	 *
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function discoverGsUsers()
	{
		$requery_days = intval(DI::config()->get('system', 'poco_requery_days'));

		$last_update = date("c", time() - (60 * 60 * 24 * $requery_days));

		$r = DBA::select('gserver', ['nurl', 'url'], [
			'`network` = ?
			AND `last_contact` >= `last_failure`
			AND `last_poco_query` < ?',
			Protocol::OSTATUS,
			$last_update
		], [
			'limit' => 5,
			'order' => ['RAND()']
		]);

		if (!DBA::isResult($r)) {
			return;
		}

		foreach ($r as $server) {
			self::fetchGsUsers($server['url']);
			DBA::update('gserver', ['last_poco_query' => DateTimeFormat::utcNow()], ['nurl' => $server['nurl']]);
		}
	}

	/**
	 * Returns a random, global contact of the current node
	 *
	 * @return string The profile URL
	 * @throws Exception
	 */
	public static function getRandomUrl()
	{
		$r = DBA::selectFirst('gcontact', ['url'], [
			'`network` = ? 
			AND `last_contact` >= `last_failure`  
			AND `updated` > ?',
			Protocol::DFRN,
			DateTimeFormat::utc('now - 1 month'),
		], ['order' => ['RAND()']]);

		if (DBA::isResult($r)) {
			return $r['url'];
		}

		return '';
	}
}
